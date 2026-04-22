<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder;

\defined('_JEXEC') or die;

/**
 * Processes text to find and replace shortcodes with their rendered output.
 *
 * This class scans input text for defined shortcode tags, parses their attributes,
 * and executes the corresponding shortcode handler (either a file or a callable)
 * to generate the final content. It supports nested shortcodes up to a defined depth.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class ShortcodeProcessor
{
    private array $shortcodes = [];
    private string $regexPattern = '';

    /**
     * ShortcodeProcessor constructor.
     *
     * @param array $shortcodes An associative array where keys are shortcode tags and values are their handlers (file paths or callables).
     */
    public function __construct(array $shortcodes)
    {
        $this->shortcodes = $shortcodes;
    }

    /**
     * Processes the given text, replacing shortcodes with their rendered output.
     *
     * @param string $text The input text potentially containing shortcodes.
     * @param object $item The Joomla content item object (e.g., article, category) being processed.
     * @param int    $maxDepth The maximum depth for processing nested shortcodes.
     *
     * @return string The processed text with shortcodes replaced.
     */
    public function processShortcodes(string $text, object $item, int $maxDepth = 10): string
    {
        $this->buildRegexPattern();

        if ($this->regexPattern === '' || $maxDepth <= 0) {
            return $text;
        }

        return \preg_replace_callback($this->regexPattern, function (array $matches) use ($item, $maxDepth): string {
            $tag        = $matches[1];
            $attrString = $matches[2];
            $content    = $matches[3] ?? '';

            $content = $this->processShortcodes($content, $item, $maxDepth - 1);
            $params  = $this->parseAttributes(trim($attrString));

            return $this->executeShortcode($tag, $params, $content, $item);
        }, $text, -1);
    }

    /**
     * Builds the regular expression pattern used to find shortcodes in the text.
     * The pattern is built once and cached.
     *
     * @return void
     */
    private function buildRegexPattern(): void
    {
        if ($this->regexPattern !== '') {
            return;
        }

        if (empty($this->shortcodes)) {
            $this->regexPattern = '';
            return;
        }

        $tags = \implode('|', \array_map(fn ($t) => \preg_quote($t, '~'), \array_keys($this->shortcodes)));

        $this->regexPattern = '~\{(' . $tags . ')' .
                              '((?:\s+[a-zA-Z0-9_\-]+=(?:"[^"]*"|\'[^\']*\'|[^"\'\s]+))*)\}' .
                              '(?:(.*)\{/\1\})?~s';
    }

    /**
     * Parses a string of shortcode attributes into an associative array.
     * Supports double-quoted, single-quoted, and unquoted attribute values.
     *
     * @param string $attrString The raw attribute string from the shortcode.
     *
     * @return array An associative array of attribute names and their values.
     */
    private function parseAttributes(string $attrString): array
    {
        $params = [];
        if ($attrString === '') {
            return $params;
        }

        \preg_match_all('/([a-zA-Z0-9_\-]+)\s*=\s*(?:(?:"([^"]*)")|(?:\'([^\']*)\')|([^\s"\'<>]+))/', $attrString, $matches, \PREG_SET_ORDER);
        foreach ($matches as $match) {
            $value = '';
            // Group 2 for double-quoted, Group 3 for single-quoted, Group 4 for unquoted
            if (isset($match[2]) && $match[2] !== '') {
                $value = $match[2];
            } elseif (isset($match[3]) && $match[3] !== '') {
                $value = $match[3];
            } elseif (isset($match[4]) && $match[4] !== '') {
                $value = $match[4];
            }
            $params[$match[1]] = $value;
        }

        return $params;
    }

    /**
     * Executes the shortcode handler (either a callable or a file) to generate its output.
     *
     * @param string $tag The shortcode tag.
     * @param array  $params An associative array of attributes passed to the shortcode.
     * @param string $content The content nested between the shortcode tags.
     * @param object $item The Joomla content item object.
     *
     * @return string The generated output of the shortcode.
     */
    private function executeShortcode(string $tag, array $params, string $content, object $item): string
    {
        $handler = $this->shortcodes[$tag];

        if (\is_callable($handler)) {
            return (string) $handler($params, $content, $item);
        }

        \ob_start();
        require $handler;
        return \ob_get_clean();
    }
}
