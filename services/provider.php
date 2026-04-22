<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\Content\Shortcoder\Extension\Shortcoder;
use Joomla\Plugin\Content\Shortcoder\ShortcodeProcessor;

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
            ShortcodeProcessor::class,
            function (Container $container) {
                $dir = \JPATH_ROOT . '/shortcodes';
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

                return new ShortcodeProcessor($shortcodeFiles);
            }
        );
    }
};
