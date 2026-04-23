<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder;

use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeProcessingException;
use Throwable;

\defined('_JEXEC') or die;

class ShortcodeProcessor
{
    private array $shortcodes = [];
    private array $handlerParams = [];

    public function __construct(array $shortcodes)
    {
        $this->shortcodes = $shortcodes;
        foreach ($this->shortcodes as $tag => $handler) {
            if (\is_callable($handler)) {
                $ref = new \ReflectionFunction($handler);
                $this->handlerParams[$tag] = $ref->getNumberOfParameters();
            } else {
                $this->handlerParams[$tag] = -1; // -1 indicates unknown arity for file-based shortcodes
            }
        }
    }

    public function processShortcodes(string $text, object $item, int $maxDepth = 10): string
    {
        if (empty($this->shortcodes) || $maxDepth <= 0) {
            return $text;
        }

        $tree = $this->buildTree($text);
        return $this->renderTree($tree, $item, $maxDepth);
    }

    private function buildTree(string $text): array
    {
        $tags = \implode('|', \array_map(fn ($t) => \preg_quote($t, '~'), \array_keys($this->shortcodes)));
        $regex = '~\{(/)?(' . $tags . ')(.*?)\/?\}~s';

        $matches = [];
        \preg_match_all($regex, $text, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);

        if (empty($matches)) {
            return [['type' => 'text', 'content' => $text]];
        }

        $root = ['children' => []];
        $stack = [&$root];
        $lastOffset = 0;

        foreach ($matches as $match) {
            $fullMatch = $match[0][0];
            $offset = $match[0][1];
            $parent = &$stack[\count($stack) - 1];

            if ($offset > $lastOffset) {
                $parent['children'][] = ['type' => 'text', 'content' => \substr($text, $lastOffset, $offset - $lastOffset)];
            }

            $lastOffset = $offset + \strlen($fullMatch);
            $isClosing = !empty($match[1][0]);
            $tag = $match[2][0];

            if ($isClosing) {
                if (!empty($stack) && ($parent['tag'] ?? null) === $tag) {
                    \array_pop($stack);
                } else {
                    $parent['children'][] = ['type' => 'text', 'content' => $fullMatch];
                }
            } else {
                $attrString = trim($match[3][0]);
                $isExplicitlySelfClosing = !empty($attrString) && \substr($attrString, -1) === '/';
                if ($isExplicitlySelfClosing) {
                    $attrString = trim(\substr($attrString, 0, -1));
                }

                $arity = $this->handlerParams[$tag];
                $isEnclosing = $arity > 1 || $arity === -1; // Enclosing if it takes content, or if we don't know (file-based)

                $node = [
                    'type' => 'shortcode',
                    'tag' => $tag,
                    'attributes' => $this->parseAttributes($attrString),
                    'children' => [],
                    'is_enclosing' => $isEnclosing && !$isExplicitlySelfClosing,
                    'offset' => $offset,
                ];

                $parent['children'][] = &$node;

                if ($node['is_enclosing']) {
                    $stack[] = &$node;
                }
                unset($node);
            }
        }

        if ($lastOffset < \strlen($text)) {
            $root['children'][] = ['type' => 'text', 'content' => \substr($text, $lastOffset)];
        }

        if (\count($stack) > 1) {
            $this->fixUnclosedTags($stack);
        }

        return $root['children'];
    }

    private function fixUnclosedTags(array &$stack): void
    {
        while (\count($stack) > 1) {
            $unclosedNodeRef = \array_pop($stack);
            $parentRef = &$stack[\count($stack) - 1];

            $unclosedNodeIndex = -1;
            foreach ($parentRef['children'] as $i => &$child) {
                if (isset($child['offset']) && $child['offset'] === $unclosedNodeRef['offset']) {
                    $unclosedNodeIndex = $i;
                    break;
                }
            }
            
            if ($unclosedNodeIndex !== -1) {
                $childrenToPromote = $parentRef['children'][$unclosedNodeIndex]['children'];

                $parentRef['children'][$unclosedNodeIndex]['is_enclosing'] = false;
                $parentRef['children'][$unclosedNodeIndex]['children'] = [];

                if (!empty($childrenToPromote)) {
                    \array_splice($parentRef['children'], $unclosedNodeIndex + 1, 0, $childrenToPromote);
                }
            }
        }
    }
    
    private function renderTree(array $tree, object $item, int $maxDepth): string
    {
        if ($maxDepth <= 0) {
            $output = '';
            foreach ($tree as $node) {
                if ($node['type'] === 'text') {
                    $output .= $node['content'];
                } elseif ($node['type'] === 'shortcode') {
                    $attrParts = [];
                    foreach ($node['attributes'] as $k => $v) {
                        if (\is_int($k)) {
                            $attrParts[] = '"' . $v . '"';
                        } else {
                            $attrParts[] = $k . '="' . $v . '"';
                        }
                    }
                    $attrString = !empty($attrParts) ? ' ' . \implode(' ', $attrParts) : '';
                    $output .= '{' . $node['tag'] . $attrString;

                    if ($node['is_enclosing']) {
                        $output .= '}' . $this->renderTree($node['children'], $item, 0) . '{/' . $node['tag'] . '}';
                    } else {
                        $output .= '}';
                    }
                }
            }
            return $output;
        }

        $output = '';
        foreach ($tree as $node) {
            if ($node['type'] === 'text') {
                $output .= $node['content'];
            } elseif ($node['type'] === 'shortcode') {
                $content = $this->renderTree($node['children'], $item, $maxDepth - 1);
                $output .= $this->executeShortcode($node['tag'], $node['attributes'], $content, $item);
            }
        }
        
        return $output;
    }

    private function parseAttributes(string $attrString): array
    {
        $attributes = [];
        if ($attrString === '') return $attributes;
        $pattern = '/(?:([a-zA-Z0-9_\-]+)\s*=\s*)?((?:"[^"]*")|(?:\'[^\']*\')|(?:[^\s"\'<>]+))/';
        \preg_match_all($pattern, $attrString, $matches, \PREG_SET_ORDER);
        $positional = [];
        foreach ($matches as $match) {
            $value = $match[2];
            if (\strpos($value, '"') === 0 || \strpos($value, "'") === 0) {
                $value = \substr($value, 1, -1);
            }
            if (!empty($match[1])) {
                $attributes[$match[1]] = $value;
            } else {
                $attributes[\count($positional)] = $value;
                $positional[] = $value;
            }
        }
        if (!empty($positional)) $attributes['_'] = $positional;
        return $attributes;
    }

    private function executeShortcode(string $tag, array $attributes, string $content, object $item): string
    {
        $handler = $this->shortcodes[$tag];
        try {
            if (\is_callable($handler)) {
                $numParams = $this->handlerParams[$tag] > -1 ? $this->handlerParams[$tag] : 2; // Assume 2 for file-based
                if ($numParams === 0) return (string)$handler();
                if ($numParams === 1) return (string)$handler($attributes);
                if ($numParams === 2) return (string)$handler($attributes, $content);
                return (string) $handler($attributes, $content, $item);
            }
            \ob_start();
            require $handler;
            return \ob_get_clean();
        } catch (Throwable $e) {
            throw new ShortcodeProcessingException(sprintf('Shortcode "%s" failed to execute.', $tag), $e->getCode(), $e);
        }
    }
}
