<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder;

\defined('_JEXEC') or die;

class ShortcodeProcessor
{
    private array $shortcodeFiles = [];
    private string $regexPattern = '';

    public function __construct(array $shortcodes)
    {
        $this->shortcodeFiles = $shortcodes;
    }

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

    private function buildRegexPattern(): void
    {
        if ($this->regexPattern !== '') {
            return;
        }

        if (empty($this->shortcodeFiles)) {
            $this->regexPattern = '';
            return;
        }

        $tags = \implode('|', \array_map(fn ($t) => \preg_quote($t, '~'), \array_keys($this->shortcodeFiles)));

        $this->regexPattern = '~\{(' . $tags . ')' .
                              '((?:\s+[a-zA-Z0-9_\-]+=(?:"[^"]*"|\'[^\']*\'|[^"\'\s]+))*)\}' .
                              '(?:(.*)\{/\1\})?~s';
    }

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

    private function executeShortcode(string $tag, array $params, string $content, object $item): string
    {
        \ob_start();

        require $this->shortcodeFiles[$tag];

        return \ob_get_clean();
    }
}
