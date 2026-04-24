<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Event;

use Joomla\Event\Event;

/**
 * Event for collecting shortcode paths.
 *
 * This event is dispatched to allow other plugins to add their custom shortcode paths.
 *
 * @since  1.0.0
 */
class ShortcoderPathsEvent extends Event
{
    /**
     * @var    string[]
     * @since  1.0.0
     */
    protected array $paths = [];



    /**
     * Get the paths.
     *
     * @return  string[]
     *
     * @since  1.0.0
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Add a path.
     *
     * @param  string  $path  Path to add
     *
     * @return  void
     *
     * @since  1.0.0
     */
    public function addPath(string $path): void
    {
        if (!in_array($path, $this->paths, true)) {
            $this->paths[] = $path;
        }
    }
}
