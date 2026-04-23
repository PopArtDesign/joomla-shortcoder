<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder;

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
     * @throws \RuntimeException If a directory is not readable.
     */
    public function loadShortcodes(): array
    {
        $shortcodes = [];

        foreach ($this->paths as $dir) {
            $dir = \realpath($dir);

            if (false === $dir || !\is_dir($dir) || !\is_readable($dir)) {
                throw new \RuntimeException(
                    \sprintf('Shortcodes directory "%s" not exists or is not readable.', $dir)
                );
            }

            foreach (\glob($dir . '/*.php', \GLOB_NOSORT | \GLOB_ERR) as $filePath) {
                $basename = \basename($filePath, '.php');
                if ($basename === 'shortcodes') {
                    continue;
                }

                $realPath = \realpath($filePath);
                if ($realPath === false) {
                    continue;
                }

                if (!\preg_match('/^[a-zA-Z0-9_\-]+$/', $basename)) {
                    continue;
                }
                if (\strpos($realPath, $dir . \DIRECTORY_SEPARATOR) !== 0) {
                    continue;
                }

                // If a shortcode with the same name exists in multiple paths,
                // the last one found will overwrite the previous one.
                $shortcodes[$basename] = $realPath;
            }

            // Load callable shortcodes from shortcodes.php
            $callableShortcodesFile = $dir . '/shortcodes.php';
            if (\is_file($callableShortcodesFile) && \is_readable($callableShortcodesFile)) {
                $callableShortcodes = require $callableShortcodesFile;

                if (\is_array($callableShortcodes)) {
                    foreach ($callableShortcodes as $tag => $handler) {
                        if (\is_string($tag) && \preg_match('/^[a-zA-Z0-9_\-]+$/', $tag) && \is_callable($handler)) {
                            // Callable shortcodes from file will overwrite file-based shortcodes with same name
                            $shortcodes[$tag] = $handler;
                        }
                    }
                }
            }
        }

        return $shortcodes;
    }
}
