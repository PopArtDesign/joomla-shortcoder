<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Extension\Shortcoder;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeLoader;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;

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
                return new ShortcodeLoader();
            }
        );

        $container->set(
            ShortcodeProcessor::class,
            function (Container $container) {
                $loader = $container->get(ShortcodeLoader::class);
                $shortcodeFiles = $loader->loadShortcodes(\JPATH_ROOT . '/shortcodes');

                return new ShortcodeProcessor($shortcodeFiles);
            }
        );
    }
};
