# Custom Shortcode Provider Plugin

The Joomla Shortcoder plugin provides a flexible way to extend its functionality by allowing other plugins to register their own shortcodes. This can be done by creating a plugin that subscribes to the `onShortcoderLoadShortcodes` event.

This guide will walk you through the process of creating a custom plugin that provides its own shortcodes.

## 1. Create a Joomla Plugin

First, you need to create a standard Joomla plugin. This involves creating the necessary file structure and the main plugin file. For more information on creating Joomla plugins, please refer to the [official Joomla documentation](https://docs.joomla.org/J4.x:Creating_a_Plugin_for_Joomla).

## 2. Subscribe to the Event

In your plugin's main file, you need to implement the `Joomla\Event\SubscriberInterface` and subscribe to the `onShortcoderLoadShortcodes` event. This event is triggered when the Shortcoder plugin is loading shortcodes.

Here is an example of a plugin that subscribes to the event:

```php
<?php

use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use JoomlaShortcoder\Plugin\Content\Shortcoder\Event\LoadShortcodesEvent;

\defined('_JEXEC') or die;

class MyShortcodeProviderPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onShortcoderLoadShortcodes' => 'addShortcodes',
        ];
    }

    public function addShortcodes(LoadShortcodesEvent $event): void
    {
        // ...
    }
}
```

## 3. Add Your Shortcodes

Inside the `addShortcodes` method, you can register your custom shortcodes. You have two options for registering shortcodes:

### a) Add a Directory of Shortcode Files

You can provide a path to a directory containing shortcode files. The Shortcoder plugin will then load all the `.php` files from that directory as shortcodes. The filename (without the `.php` extension) will be used as the shortcode tag.

```php
public function addShortcodes(LoadShortcodesEvent $event): void
{
    // Add a directory containing shortcode files
    $event->addPath('/path/to/my/shortcodes');
}
```

### b) Register a Callable Shortcode

You can also register a shortcode directly by providing a tag and a PHP callable (like a closure or a static method).

```php
public function addShortcodes(LoadShortcodesEvent $event): void
{
    // Directly register a callable shortcode
    $event->addShortcode('my_custom_shortcode', function ($attributes, $content, $item) {
        return 'This is my custom shortcode output: ' . $content;
    });
}
```

The callable will receive the following parameters:

*   `$attributes`: An associative array of attributes passed to the shortcode.
*   `$content`: The content enclosed within the shortcode tags.
*   `$item`: The Joomla content item (e.g., article) being processed.

## Complete Example

Here is a complete example of a plugin that registers both a directory of shortcodes and a callable shortcode:

```php
<?php

use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use JoomlaShortcoder\Plugin\Content\Shortcoder\Event\LoadShortcodesEvent;

\defined('_JEXEC') or die;

class MyShortcodeProviderPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onShortcoderLoadShortcodes' => 'addShortcodes',
        ];
    }

    public function addShortcodes(LoadShortcodesEvent $event): void
    {
        // Add a directory containing shortcode files
        $event->addPath('/path/to/my/shortcodes');

        // Directly register a callable shortcode
        $event->addShortcode('my_custom_shortcode', function ($attributes, $content, $item) {
            $name = $attributes['name'] ?? 'World';
            return 'Hello, ' . $name . '! ' . $content;
        });
    }
}
```
