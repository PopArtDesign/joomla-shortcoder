<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder;

use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeProcessingException;
use Throwable;

\defined('_JEXEC') or die;

/**
 * Processes text to find and replace shortcodes with their rendered output.
 *
 * This class scans input text for defined shortcode tags, parses their attributes,
 * and builds an abstract syntax tree (AST) to represent the nested structure of shortcodes.
 * The tree is then rendered recursively, executing the corresponding shortcode handler
 * (either a file or a callable) to generate the final content.
 * This approach correctly handles both nested and adjacent shortcodes.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class ShortcodeProcessor
{
    /**
     * An associative array where keys are shortcode tags and values are their handlers (file paths or callables).
     *
     * @var array
     */
    private array $shortcodes = [];

    /**
     * Stores the number of parameters each shortcode handler expects,
     * used for dynamically calling handlers with the correct arguments.
     *
     * @var array
     */
    private array $handlerParams = [];

    /**
     * ShortcodeProcessor constructor.
     *
     * Initializes the processor with available shortcode handlers and
     * pre-processes callable handlers to determine their parameter count
     * for dynamic argument passing.
     *
     * @param array $shortcodes An associative array where keys are shortcode tags
     *                          and values are their handlers (file paths or callables).
     */
    public function __construct(array $shortcodes)
    {
        $this->shortcodes = $shortcodes;
        foreach ($this->shortcodes as $tag => $handler) {
            if (\is_callable($handler)) {
                $ref = new \ReflectionFunction($handler);
                $this->handlerParams[$tag] = $ref->getNumberOfParameters();
            } else {
                // For file-based handlers, we assume they can potentially receive all arguments (attributes, content, item).
                // This assumption is crucial for the `buildTree` method to correctly
                // differentiate between enclosing and implicitly self-closing shortcodes.
                $this->handlerParams[$tag] = 3;
            }
        }
    }

    /**
     * Processes the given text, replacing shortcodes with their rendered output.
     *
     * This is the main entry point for shortcode processing. It first builds an
     * abstract syntax tree (AST) from the input text and then renders it.
     *
     * @param string $text     The input text potentially containing shortcodes.
     * @param object $item     The Joomla content item object (e.g., article, category) being processed.
     * @param int    $maxDepth The maximum depth for processing nested shortcodes.
     *                         A depth of 0 or less will prevent any shortcodes from being processed.
     *
     * @return string The processed text with shortcodes replaced.
     */
    public function processShortcodes(string $text, object $item, int $maxDepth = 10): string
    {
        if (empty($this->shortcodes) || $maxDepth <= 0) {
            return $text;
        }

        $tree = $this->buildTree($text);
        return $this->renderTree($tree, $item, $maxDepth);
    }

    /**
     * Builds an Abstract Syntax Tree (AST) from the input text, representing
     * the hierarchical structure of shortcodes and interspersed text.
     *
     * The tree is built using a stack-based approach to correctly handle nesting.
     * Each node in the tree can be either a 'text' node or a 'shortcode' node.
     *
     * @param string $text The input text to parse.
     *
     * @return array The root level children of the AST.
     *               Each element is a node representing text or a shortcode.
     */
    private function buildTree(string $text): array
    {
        $tags = \implode('|', \array_map(fn ($t) => \preg_quote($t, '~'), \array_keys($this->shortcodes)));
        // Regex to find all shortcode tags:
        // Group 1: Optional '/' for closing tags (e.g., {/tag})
        // Group 2: The tag name (e.g., 'tag')
        // Group 3: Attributes string (e.g., ' attr="value" /')
        // The optional '/' at the end of the full match is for self-closing tags like {tag /}
        $regex = '~\{(/)?(' . $tags . ')(.*?)\/?\}~s';

        $matches = [];
        \preg_match_all($regex, $text, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);

        // If no shortcodes are found, return the entire text as a single text node
        if (empty($matches)) {
            return [['type' => 'text', 'content' => $text]];
        }

        $root = ['children' => []];
        $stack = [&$root]; // Stack for managing nested shortcodes. $root is always at the bottom.
        $lastOffset = 0;   // Keeps track of the last processed position in the text.

        foreach ($matches as $match) {
            $fullMatch = $match[0][0]; // Full shortcode string matched (e.g., "{tag attr='val'}")
            $offset = $match[0][1];    // Offset of the match in the original text.
            $parent = &$stack[\count($stack) - 1]; // Current parent node from the stack.

            // Add any text content found before the current shortcode tag as a text node
            if ($offset > $lastOffset) {
                $parent['children'][] = ['type' => 'text', 'content' => \substr($text, $lastOffset, $offset - $lastOffset)];
            }

            $lastOffset = $offset + \strlen($fullMatch);

            $isClosing = !empty($match[1][0]); // Check if it's a closing tag (e.g., {/tag})
            $tag = $match[2][0];               // The shortcode tag name

            if ($isClosing) {
                // If it's a closing tag and matches the tag on top of the stack, pop the stack
                if (!empty($stack) && ($parent['tag'] ?? null) === $tag) {
                    \array_pop($stack);
                } else {
                    // Unmatched closing tag, treat as plain text
                    $parent['children'][] = ['type' => 'text', 'content' => $fullMatch];
                }
            } else {
                $attrString = trim($match[3][0]); // Raw attribute string (e.g., ' attr="value" /')
                // Check for explicit self-closing marker (e.g., {tag /})
                $isExplicitlySelfClosing = !empty($attrString) && \substr($attrString, -1) === '/';
                if ($isExplicitlySelfClosing) {
                    $attrString = trim(\substr($attrString, 0, -1)); // Remove the self-closing slash from attributes
                }

                // Determine if the shortcode handler expects content (i.e., is an enclosing shortcode)
                $isEnclosing = $this->handlerParams[$tag] > 1;

                $node = [
                    'type' => 'shortcode',
                    'tag' => $tag,
                    'attributes' => $this->parseAttributes($attrString),
                    'children' => [], // Children will contain content if it's an enclosing shortcode
                ];

                // Add the new shortcode node as a child of the current parent
                $parent['children'][] = &$node;

                // If it's an enclosing shortcode and not explicitly self-closing, push it onto the stack
                // so subsequent nodes become its children.
                if ($isEnclosing && !$isExplicitlySelfClosing) {
                    $stack[] = &$node;
                }
                // Unset the reference to avoid issues with array_pop if the node is popped later
                unset($node);
            }
        }

        // Add any remaining text after the last shortcode
        if ($lastOffset < \strlen($text)) {
            $root['children'][] = ['type' => 'text', 'content' => \substr($text, $lastOffset)];
        }

        return $root['children'];
    }

    /**
     * Renders the Abstract Syntax Tree (AST) into a final output string.
     *
     * This method traverses the AST recursively, rendering child nodes first
     * and then executing the shortcode handler for the parent node.
     *
     * @param array  $tree     The AST (or a sub-tree/list of children) to render.
     * @param object $item     The Joomla content item object to pass to shortcode handlers.
     * @param int    $maxDepth The current maximum processing depth. Decremented for each nested level.
     *
     * @return string The rendered output string.
     */
    private function renderTree(array $tree, object $item, int $maxDepth): string
    {
        // If maxDepth is reached or exceeded, reconstruct the original shortcode text
        // without processing it, effectively disabling shortcodes at this depth.
        if ($maxDepth <= 0) {
            $output = '';
            foreach ($tree as $node) {
                if ($node['type'] === 'text') {
                    $output .= $node['content'];
                } elseif ($node['type'] === 'shortcode') {
                    $attrParts = [];
                    foreach ($node['attributes'] as $k => $v) {
                        if (\is_int($k)) {
                            $attrParts[] = '"' . $v . '"'; // Positional attribute
                        } else {
                            $attrParts[] = $k . '="' . $v . '"'; // Named attribute
                        }
                    }
                    $attrString = !empty($attrParts) ? ' ' . \implode(' ', $attrParts) : '';

                    $output .= '{' . $node['tag'] . $attrString;
                    if (!empty($node['children'])) {
                        // Reconstruct enclosing shortcode
                        $output .= '}' . $this->renderTree($node['children'], $item, 0) . '{/' . $node['tag'] . '}';
                    } else {
                        // Reconstruct self-closing or implicitly self-closing shortcode
                        // If handler expects content, it implies it's an enclosing shortcode, so add closing tag
                        if ($this->handlerParams[$node['tag']] > 1) {
                            $output .= '}{/' . $node['tag'] . '}';
                        } else {
                            $output .= '}'; // Simple self-closing {tag}
                        }
                    }
                }
            }
            return $output;
        }

        // Normal rendering when maxDepth is not reached
        $output = '';
        foreach ($tree as $node) {
            if ($node['type'] === 'text') {
                $output .= $node['content'];
            } elseif ($node['type'] === 'shortcode') {
                // Recursively render children to get the shortcode's content
                $content = $this->renderTree($node['children'], $item, $maxDepth - 1);
                // Execute the shortcode handler and append its output
                $output .= $this->executeShortcode($node['tag'], $node['attributes'], $content, $item);
            }
        }

        return $output;
    }

    /**
     * Parses a string of shortcode attributes into an associative array.
     *
     * Supports double-quoted, single-quoted, and unquoted attribute values.
     * Positional attributes are stored with numeric keys and also in a special `'_'` key.
     *
     * @param string $attrString The raw attribute string from the shortcode (e.g., 'name="value" id=1').
     *
     * @return array An associative array of attribute names and their values.
     */
    private function parseAttributes(string $attrString): array
    {
        $attributes = [];
        if ($attrString === '') {
            return $attributes;
        }

        // Regex to match attributes:
        // - Optionally captures attribute name (e.g., 'name=')
        // - Captures value: double-quoted, single-quoted, or unquoted (until space or end)
        $pattern = '/(?:([a-zA-Z0-9_\-]+)\s*=\s*)?((?:"[^"]*")|(?:\'[^\']*\')|(?:[^\s"\'<>]+))/';
        \preg_match_all($pattern, $attrString, $matches, \PREG_SET_ORDER);

        $positional = [];
        foreach ($matches as $match) {
            $value = $match[2];
            // Trim quotes from the value
            if (\strpos($value, '"') === 0 || \strpos($value, "'") === 0) {
                $value = \substr($value, 1, -1);
            }

            if (!empty($match[1])) { // Named attribute
                $attributes[$match[1]] = $value;
            } else { // Positional attribute
                $attributes[\count($positional)] = $value;
                $positional[] = $value;
            }
        }

        // Store all positional attributes in a special '_' key
        if (!empty($positional)) {
            $attributes['_'] = $positional;
        }
        return $attributes;
    }

    /**
     * Executes the shortcode handler (either a callable or a file) to generate its output.
     *
     * Dynamically calls callable handlers based on the number of parameters they accept.
     * File-based handlers are included and their output captured.
     *
     * @param string $tag        The shortcode tag name.
     * @param array  $attributes An associative array of attributes passed to the shortcode.
     * @param string $content    The processed content nested between the shortcode tags (empty for self-closing).
     * @param object $item       The Joomla content item object.
     *
     * @return string The generated output of the shortcode.
     *
     * @throws ShortcodeProcessingException If the shortcode handler throws an exception.
     */
    private function executeShortcode(string $tag, array $attributes, string $content, object $item): string
    {
        $handler = $this->shortcodes[$tag];
        try {
            if (\is_callable($handler)) {
                $numParams = $this->handlerParams[$tag];
                // Call the handler with the exact number of arguments it expects.
                // PHP is lenient with extra arguments (they are ignored), but calling with
                // fewer required arguments would cause an error. This explicit check
                // ensures type safety and clarity, and matches the parser's logic.
                if ($numParams === 0) {
                    return (string)$handler();
                }
                if ($numParams === 1) {
                    return (string)$handler($attributes);
                }
                if ($numParams === 2) {
                    return (string)$handler($attributes, $content);
                }
                // Default to passing all arguments if handler expects more than 2
                return (string) $handler($attributes, $content, $item);
            }

            // For file-based handlers, capture output
            \ob_start();
            require $handler;
            return \ob_get_clean();
        } catch (Throwable $e) {
            throw new ShortcodeProcessingException(sprintf('Shortcode "%s" failed to execute.', $tag), $e->getCode(), $e);
        }
    }
}
