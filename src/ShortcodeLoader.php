<?php

namespace JoomlaShortcoder\Plugin\Content\Shortcoder;

use JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeLoadingException;

\defined('_JEXEC') or die;

/**
 * Discovers, validates, and loads shortcode handlers from specified directories.
 *
 * This class is responsible for scanning the configured paths for shortcode definitions,
 * including both file-based templates and callable shortcodes defined in `shortcodes.php`.
 * It validates the shortcode tags and handlers, preparing them for use by the
 * `ShortcodeProcessor`.
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
     * @var callable[]
     */
    private array $shortcodes;

    /**
     * ShortcodeLoader constructor.
     *
     * @param array $paths    The directories to scan for shortcode files.
     * @param array $shortcodes Pre-registered callable shortcodes.
     */
    public function __construct(array $paths, array $shortcodes = [])
    {
        $this->paths = $paths;
        $this->shortcodes = $shortcodes;
    }

    /**
     * Loads shortcode files from the configured directories.
     *
     * @return array An associative array of shortcode tags and their file paths or callables.
     *
     * @throws ShortcodeLoadingException If a directory is not readable.
     */
    public function loadShortcodes(): array
    {
        $shortcodes = $this->shortcodes;

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

    /**
     * Loads file-based shortcodes from a given directory.
     *
     * It iterates through PHP files in the specified path, treating each filename
     * (without the .php extension) as a shortcode tag. It skips 'shortcodes.php'
     * and files with invalid tag names.
     *
     * @param string $path The directory path to scan for shortcode files.
     *
     * @return array An associative array where keys are valid shortcode tags and values are their file paths.
     */
    private function loadFileBasedShortcodes(string $path): array
    {
        $shortcodes = [];

        foreach (\glob($path . '/*.php', \GLOB_NOSORT | \GLOB_ERR) as $filePath) {
            $basename = \basename($filePath, '.php');
            if ($basename === 'shortcodes' || !ShortcodeProcessor::isValidTagName($basename)) {
                continue;
            }

            $shortcodes[$basename] = $filePath;
        }

        return $shortcodes;
    }

    /**
     * Loads callable shortcodes defined in a `shortcodes.php` file within the specified directory.
     *
     * The `shortcodes.php` file is expected to return an associative array where keys
     * are shortcode tags and values are PHP callables. Validates tag names and callable types.
     *
     * @param string $path The directory path where `shortcodes.php` might be located.
     *
     * @return array An associative array of valid shortcode tags and their callable handlers.
     *
     * @throws ShortcodeLoadingException If the `shortcodes.php` file exists but does not return an array.
     */
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
            if (!\is_string($tag) || !ShortcodeProcessor::isValidTagName($tag)) {
                continue;
            }

            if (\is_callable($handler)) {
                $shortcodes[$tag] = $handler;
            }
        }

        return $shortcodes;
    }
}
