<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Event\ShortcoderPathsEvent;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Extension\Shortcoder;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeLoader;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                return new Shortcoder(
                    $container->get(ShortcodeProcessor::class)
                );
            }
        );

        $container->set(
            ShortcodeLoader::class,
            function (Container $container) {
                /** @var DispatcherInterface $dispatcher */
                $dispatcher = $container->get(DispatcherInterface::class);

                // Dispatch event to allow other plugins to add their paths
                $event = new ShortcoderPathsEvent('onShortcoderRegisterPaths');
                $dispatcher->dispatch('onShortcoderRegisterPaths', $event);

                // Add JPATH_ROOT/shortcodes last to give it the highest priority
                if (\is_dir(\JPATH_ROOT . '/shortcodes')) {
                    $event->addPath(\JPATH_ROOT . '/shortcodes');
                }

                return new ShortcodeLoader($event->getPaths());
            }
        );

        $container->set(
            ShortcodeProcessor::class,
            function (Container $container) {
                $loader = $container->get(ShortcodeLoader::class);

                return new ShortcodeProcessor($loader->loadShortcodes());
            }
        );
    }
};
