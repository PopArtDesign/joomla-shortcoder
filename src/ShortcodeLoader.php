<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder;

use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeLoadingException;

\defined('_JEXEC') or die;

/**
 * Discovers and validates shortcode template files.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class ShortcodeLoader
{
    /**
     * @var array
     */
    private array $paths;

    /**
     * @param array $paths The directories to scan for shortcode files.
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Loads shortcode files from the configured directories.
     *
     * @return array An associative array of shortcode tags and their file paths.
     *
     * @throws ShortcodeLoadingException If a directory is not readable.
     */
    public function loadShortcodes(): array
    {
        $shortcodes = [];

        foreach ($this->paths as $path) {
            $path = \realpath($path);

            if (false === $path || !\is_dir($path) || !\is_readable($path)) {
                throw new ShortcodeLoadingException(
                    \sprintf('Shortcodes directory "%s" not exists or is not readable.', $path)
                );
            }

            $shortcodes = \array_merge(
                $shortcodes,
                $this->loadFileBasedShortcodes($path),
                $this->loadCallableShortcodes($path),
            );
        }

        return $shortcodes;
    }

    private function loadFileBasedShortcodes(string $path): array
    {
        $shortcodes = [];

        foreach (\glob($path . '/*.php', \GLOB_NOSORT | \GLOB_ERR) as $filePath) {
            $basename = \basename($filePath, '.php');
            if ($basename === 'shortcodes' || !$this->isValidTagName($basename)) {
                continue;
            }

            $shortcodes[$basename] = $filePath;
        }

        return $shortcodes;
    }

    private function loadCallableShortcodes(string $path): array
    {
        $shortcodes = [];

        $shortcodesFile = $path . '/shortcodes.php';
        if (!\file_exists($shortcodesFile)) {
            return $shortcodes;
        }

        $callableShortcodes = require $shortcodesFile;
        if (!\is_array($callableShortcodes)) {
            throw new ShortcodeLoadingException(
                \sprintf('Shortcodes file "%s" must return an array.', $shortcodesFile)
            );
        }

        foreach ($callableShortcodes as $tag => $handler) {
            if (!\is_string($tag) || !$this->isValidTagName($tag)) {
                continue;
            }

            if (\is_callable($handler)) {
                $shortcodes[$tag] = $handler;
            }
        }

        return $shortcodes;
    }

    private function isValidTagName(string $tag): bool
    {
        return \preg_match('/^[a-zA-Z0-9_\-]+$/', $tag) === 1;
    }
}
