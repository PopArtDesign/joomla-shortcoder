<?php

namespace Joomla\Plugin\Content\Shortcoder\Extension;

use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

class Shortcoder extends CMSPlugin
{
    private static array $shortcodeFiles = [];
    private static string $regexPattern = '';
    private bool $initialized = false;

    public function onContentPrepare($context, &$item, &$params, $page = 0): void
    {
        if (!in_array($context, ['com_content.article', 'com_content.category'], true)) {
            return;
        }

        $this->ensureInitialized();

        $textProperties = ['text', 'introtext', 'fulltext', 'description'];
        foreach ($textProperties as $prop) {
            if (isset($item->$prop) && is_string($item->$prop) && $item->$prop !== '') {
                $item->$prop = $this->processShortcodes($item->$prop, $item);
            }
        }
    }

    private function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $dir = JPATH_ROOT . '/shortcodes';
        if (!is_dir($dir) || !is_readable($dir)) {
            return;
        }

        foreach (glob($dir . '/*.php', GLOB_NOSORT | GLOB_ERR) as $filePath) {
            $realPath = realpath($filePath);
            if ($realPath === false) {
                continue;
            }

            $basename = basename($filePath, '.php');
            if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $basename)) {
                continue;
            }
            if (strpos($realPath, realpath($dir) . DIRECTORY_SEPARATOR) !== 0) {
                continue;
            }

            self::$shortcodeFiles[$basename] = $realPath;
        }

        $this->buildRegexPattern();
    }

    private function buildRegexPattern(): void
    {
        if (empty(self::$shortcodeFiles)) {
            self::$regexPattern = '';
            return;
        }

        $tags = implode('|', array_map(fn ($t) => preg_quote($t, '~'), array_keys(self::$shortcodeFiles)));
        self::$regexPattern = '~\{(' . $tags . ')' .
                              '((?:\s+[a-zA-Z0-9_\-]+=(?:"[^"]*"|\'[^\']*\'|[^"\'\s]+))*)\}' .
                              '(?:(.*?)\{/\1\})?~s';
    }

    private function processShortcodes(string $text, object $item): string
    {
        if (self::$regexPattern === '') {
            return $text;
        }

        $count = 0;
        $maxIterations = 10;

        do {
            $text = preg_replace_callback(self::$regexPattern, function (array $matches) use ($item): string {
                $tag        = $matches[1];
                $attrString = $matches[2];
                $content    = $matches[3] ?? '';

                $content = $this->processShortcodes($content, $item);
                $params  = $this->parseAttributes(trim($attrString));

                return $this->executeShortcode($tag, $params, $content, $item);
            }, $text, -1, $count);

            $maxIterations--;
        } while ($count > 0 && $maxIterations > 0);

        return $text;
    }

    private function parseAttributes(string $attrString): array
    {
        $params = [];
        if ($attrString === '') {
            return $params;
        }

        preg_match_all('/([a-zA-Z0-9_\-]+)\s*=\s*(?:(["\'])(.*?)\2|([^\s"\'<>]+))/', $attrString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $params[$match[1]] = $match[3] ?? $match[4];
        }
        return $params;
    }

    private function executeShortcode(string $tag, array $params, string $content, object $item): string
    {
        $filePath = self::$shortcodeFiles[$tag];

        ob_start();
        try {
            extract(['params' => $params, 'item' => $item, 'content' => $content], EXTR_SKIP);
            include $filePath;
        } catch (\Throwable $e) {
            if (JDEBUG) {
                echo '<!-- Shortcode {' . htmlspecialchars($tag) . '} error: ' . htmlspecialchars($e->getMessage()) . ' -->';
            }
        }
        return ob_get_clean();
    }
}
