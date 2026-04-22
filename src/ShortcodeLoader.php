<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder;

/**
 * Responsible for discovering and validating shortcode template files.
 */
class ShortcodeLoader
{
    /**
     * Loads shortcode files from the given directory.
     *
     * @param string $dir The directory to scan for shortcode files.
     *
     * @return array An associative array of shortcode tags and their file paths.
     *
     * @throws \RuntimeException If the directory is not readable.
     */
    public function loadShortcodes(string $dir): array
    {
        if (!is_dir($dir) || !is_readable($dir)) {
            throw new \RuntimeException(
                \sprintf('Shortcodes directory "%s" not exists or is not readable.', $dir)
            );
        }

        $shortcodeFiles = [];

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

            $shortcodeFiles[$basename] = $realPath;
        }

        return $shortcodeFiles;
    }
}
