# Joomla Shortcoder

## Project Overview

This project contains a Joomla 4+ content plugin that provides a lightweight shortcode engine. The plugin scans Joomla content (articles, categories) for shortcodes in the format `{tag attr="val"}content{/tag}` and replaces them with the output of corresponding PHP template files.

## Architecture

*   **`shortcoder.xml`**: The Joomla extension manifest file. It defines the plugin's metadata, version, and file structure.
*   **`services/provider.php`**: A Joomla DI (Dependency Injection) service provider. It discovers shortcode template files from the `JPATH_ROOT/shortcodes/` directory and registers the core `ShortcodeProcessor` and the `Shortcoder` plugin services.
*   **`src/Extension/Shortcoder.php`**: The main plugin class (`CMSPlugin`). It subscribes to the `onContentPrepare` event for content articles and categories, and uses the `ShortcodeProcessor` to replace shortcodes in the text.
*   **`src/ShortcodeProcessor.php`**: The core engine. It builds a regular expression from the discovered shortcode tags, parses shortcodes (including attributes and nested content), and renders the corresponding PHP template or executes a callable to generate the final output. **Note:** The regex-based parser does not support nesting a shortcode within another shortcode of the same name.

## Development

### Key Conventions

*   **Shortcode Templates**: Shortcodes can be defined as standard PHP files or as PHP callables.
    *   **File-based Shortcodes**: PHP files must be located in the `JPATH_ROOT/shortcodes/` directory of the Joomla installation (Note: this directory is outside the plugin's own folder).
    *   **Callable Shortcodes**: A special file named `shortcodes.php` can exist in the same `JPATH_ROOT/shortcodes/` directory. This file must return an associative array where keys are shortcode tags and values are PHP callables (functions, closures, static methods, etc.). Callable shortcodes defined in `shortcodes.php` will take precedence over file-based shortcodes with the same name.
*   **Shortcode Naming**: The filename of the template (without the `.php` extension) serves as the shortcode tag for file-based shortcodes. For callable shortcodes, the array key serves as the shortcode tag. Tag names are restricted to alphanumeric characters, underscores, and hyphens (`/^[a-zA-Z0-9_\-]+$/`).
*   **Template Variables**: Within a shortcode template file OR a callable shortcode, the following variables are available:
    *   `$attributes`: An associative array of the attributes passed to the shortcode. This array now supports both named attributes (accessed by their key, e.g., `$attributes['name']`) and positional attributes (accessed by numeric keys, e.g., `$attributes[0]`). All positional attributes are also available as an array under the special `'_'` key (e.g., `$attributes['_']`).
    *   `$content`: A string containing the content nested between the opening and closing shortcode tags (e.g., `...content...` in `{tag}content{/tag}`).
    *   `$item`: The Joomla content item object (e.g., the article object) being processed.
*   **Installation**: The plugin uses `method="upgrade"` in its manifest, allowing it to be updated in-place via Joomla's extension installer.

### Extending with Custom Shortcodes

Other plugins can add their own shortcode directories or directly register callable shortcodes by subscribing to the `onShortcoderLoadShortcodes` event.

**Example of a subscriber plugin:**

```php
<?php

use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use JoomlaShortcoder\Plugin\Content\Shortcoder\Event\LoadShortcodesEvent;

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
            return 'This is my custom shortcode output: ' . $content;
        });
    }
}
```

### Dependencies

Project dependencies are managed with Composer. To install development dependencies (like PHPUnit), run:

```bash
composer install
```

### Testing

The project uses PHPUnit for unit tests. Tests are located in the `tests/Unit` directory.

To run the tests, execute the following command from the project root:

```bash
./vendor/bin/phpunit
```

### Code Style

The project uses `php-cs-fixer` to enforce a consistent code style. The code style can be checked using the following command:

```bash
composer cs
```

The code style can be fixed using the following command:

```bash
composer cs-fix
```
