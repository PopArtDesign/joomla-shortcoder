<?php

namespace JoomlaShortcoder\Plugin\Content\Shortcoder\Extension;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;

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
    private Container $container;

    private array $allowedContexts = [
        'com_content.article',
        'com_content.category',
        'com_content.featured',
    ];

    /**
     * Shortcoder constructor.
     */
    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;

        // Joomla 4.x BC
        if (\version_compare(\JVERSION, '5', '<')) {
            $dispatcher = $container->get(DispatcherInterface::class);
            parent::__construct($dispatcher, $config);
            return;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare' => 'processShortcodes',
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
    public function processShortcodes(EventInterface $event): void
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
                // The ShortcodeProcessor is lazily retrieved from the DI container
                // only if a content property contains a potential shortcode (indicated by '{').
                // This optimizes performance by avoiding unnecessary shortcode loading
                // and object creation when no shortcodes are present on the page.
                $processor ??= $this->container->get(ShortcodeProcessor::class);

                $item->$prop = $processor->processShortcodes($item->$prop, $item);
            }
        }
    }
}
