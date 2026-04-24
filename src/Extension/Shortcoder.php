<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Extension;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
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
class Shortcoder extends CMSPlugin implements SubscriberInterface
{
    private ShortcodeProcessor $processor;

    private array $allowedContexts = [
        'com_content.article',
        'com_content.category',
        'com_content.featured',
    ];

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
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare' => 'onContentPrepare',
        ];
    }

    /**
     * Event method that is fired before content is prepared for display.
     * This method processes shortcodes within various text properties of the content item.
     *
     * @param ContentPrepareEvent $event The content prepare event.
     *
     * @return void
     */
    public function onContentPrepare(EventInterface $event): void
    {
        if ($event instanceof ContentPrepareEvent) {
            $context = $event->getContext();
            $item = $event->getItem();
        } else {
            // Joomla 4.x
            $context = $event->getArgument(0);
            $item = $event->getArgument(1);
        }

        if (!isset($item) || !\in_array($context, $this->allowedContexts, true)) {
            return;
        }

        $textProperties = ['text', 'introtext', 'fulltext', 'description'];
        foreach ($textProperties as $prop) {
            if (isset($item->$prop) && \is_string($item->$prop) && \strpos($item->$prop, '{') !== false) {
                $item->$prop = $this->processor->processShortcodes($item->$prop, $item);
            }
        }
    }
}
