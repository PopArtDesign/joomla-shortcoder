<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Event;

use Joomla\Event\Event;

\defined('_JEXEC') or die;

/**
 * Event for collecting shortcode paths.
 *
 * This event is dispatched to allow other plugins to add their custom shortcode paths.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class ShortcoderPathsEvent extends Event
{
    /**
     * @var string[]
     */
    protected array $paths = [];

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
}
