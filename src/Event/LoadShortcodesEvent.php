<?php

namespace JoomlaShortcoder\Plugin\Content\Shortcoder\Event;

use JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeLoadingException;
use JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;
use Joomla\Event\Event;

\defined('_JEXEC') or die;

/**
 * Event for collecting shortcodes.
 *
 * This event is dispatched to allow other plugins to add their custom shortcodes.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class LoadShortcodesEvent extends Event
{
    /**
     * @var string[]
     */
    protected array $paths = [];

    /**
     * @var callable[]
     */
    protected array $shortcodes = [];

    /**
     * Get the paths.
     *
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Add a path.
     *
     * @param string $path Path to add
     *
     * @return  void
     */
    public function addPath(string $path): void
    {
        if (!in_array($path, $this->paths, true)) {
            $this->paths[] = $path;
        }
    }

    /**
     * Adds a callable shortcode.
     *
     * @param string   $tag      The shortcode tag name.
     * @param callable $callable The callable to execute for this shortcode.
     *
     * @return void
     *
     * @throws ShortcodeLoadingException If the tag name is invalid.
     */
    public function addShortcode(string $tag, callable $callable): void
    {
        if (!ShortcodeProcessor::isValidTagName($tag)) {
            throw new ShortcodeLoadingException(\sprintf('Shortcode tag "%s" is not valid.', $tag));
        }

        $this->shortcodes[$tag] = $callable;
    }

    /**
     * Returns the array of manually registered shortcode callables.
     *
     * @return callable[]
     */
    public function getShortcodes(): array
    {
        return $this->shortcodes;
    }
}
