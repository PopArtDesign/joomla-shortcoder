# Joomla Shortcoder Plugin

## Project Overview

This project contains a Joomla 4+ content plugin that provides a lightweight shortcode engine. The plugin scans Joomla content (articles, categories) for shortcodes in the format `{tag attr="val"}content{/tag}` and replaces them with the output of corresponding PHP template files.

## Architecture

*   **`shortcoder.xml`**: The Joomla extension manifest file. It defines the plugin's metadata, version, and file structure.
*   **`services/provider.php`**: A Joomla DI (Dependency Injection) service provider. It discovers shortcode template files from the `JPATH_ROOT/shortcodes/` directory and registers the core `ShortcodeProcessor` and the `Shortcoder` plugin services.
*   **`src/Extension/Shortcoder.php`**: The main plugin class (`CMSPlugin`). It subscribes to the `onContentPrepare` event for content articles and categories, and uses the `ShortcodeProcessor` to replace shortcodes in the text.
*   **`src/ShortcodeProcessor.php`**: The core engine. It builds a regular expression from the discovered shortcode tags, parses shortcodes (including attributes and nested content), and renders the corresponding PHP template to generate the final output.

## Development

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

### Key Conventions

*   **Shortcode Templates**: All shortcode templates are standard PHP files and must be located in the `JPATH_ROOT/shortcodes/` directory of the Joomla installation (Note: this directory is outside the plugin's own folder).
*   **Shortcode Naming**: The filename of the template (without the `.php` extension) serves as the shortcode tag. Tag names are restricted to alphanumeric characters, underscores, and hyphens (`/^[a-zA-Z0-9_\-]+$/`).
*   **Template Variables**: Within a shortcode template, the following variables are available:
    *   `$params`: An associative array of the attributes passed to the shortcode.
    *   `$content`: A string containing the content nested between the opening and closing shortcode tags (e.g., `...content...` in `{tag}content{/tag}`).
    *   `$item`: The Joomla content item object (e.g., the article object) being processed.
*   **Installation**: The plugin uses `method="upgrade"` in its manifest, allowing it to be updated in-place via Joomla's extension installer.
