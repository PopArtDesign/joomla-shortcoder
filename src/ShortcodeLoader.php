<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder;

\defined('_JEXEC') or die;

/**
 * Discovers and validates shortcode template files.
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
        $shortcodeFiles = [];

        foreach ($this->paths as $dir) {
            if (!\is_dir($dir) || !\is_readable($dir)) {
                throw new \RuntimeException(
                    \sprintf('Shortcodes directory "%s" not exists or is not readable.', $dir)
                );
            }

            foreach (\glob($dir . '/*.php', \GLOB_NOSORT | \GLOB_ERR) as $filePath) {
                $realPath = \realpath($filePath);
                if ($realPath === false) {
                    continue;
                }

                $basename = \basename($filePath, '.php');
                if (!\preg_match('/^[a-zA-Z0-9_\-]+$/', $basename)) {
                    continue;
                }
                if (\strpos($realPath, \realpath($dir) . \DIRECTORY_SEPARATOR) !== 0) {
                    continue;
                }

                // If a shortcode with the same name exists in multiple paths,
                // the last one found will overwrite the previous one.
                $shortcodeFiles[$basename] = $realPath;
            }
        }

        return $shortcodeFiles;
    }
}
