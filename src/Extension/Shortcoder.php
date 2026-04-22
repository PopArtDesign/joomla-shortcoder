<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Extension;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeProcessingException;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;

\defined('_JEXEC') or die;

/**
 * The main Joomla plugin class for the Shortcoder extension.
 *
 * This class subscribes to the `onContentPrepare` event and uses the
 * ShortcodeProcessor to replace shortcodes in Joomla content.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class Shortcoder extends CMSPlugin
{
    private ShortcodeProcessor $processor;

    /**
     * Shortcoder constructor.
     *
     * @param ShortcodeProcessor $processor The shortcode processor instance.
     */
    public function __construct(ShortcodeProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Event method that is fired before content is prepared for display.
     * This method processes shortcodes within various text properties of the content item.
     *
     * @param string $context The context of the content being passed to the plugin.
     * @param object $item    The content item object.
     * @param object $params  The content item's parameters.
     * @param int    $page    The 'page' number (unused here).
     *
     * @return void
     */
    public function onContentPrepare($context, &$item, &$params, $page = 0): void
    {
        if (!in_array($context, ['com_content.article', 'com_content.category'], true)) {
            return;
        }

        $textProperties = ['text', 'introtext', 'fulltext', 'description'];
        foreach ($textProperties as $prop) {
            if (isset($item->$prop) && is_string($item->$prop) && $item->$prop !== '') {
                try {
                    $item->$prop = $this->processor->processShortcodes($item->$prop, $item);
                } catch (ShortcodeProcessingException $e) {
                    if (\defined('JDEBUG') && JDEBUG) {
                        throw $e;
                    }

                    Log::error(
                        'Shortcoder failed to process content for property "' . $prop . '": ' . $e->getMessage() . "\n" . $e->getPrevious(),
                        ['extension' => 'joomla-shortcoder']
                    );
                }
            }
        }
    }
}
