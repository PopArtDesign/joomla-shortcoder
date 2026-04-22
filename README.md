# Joomla Shortcoder Plugin

A lightweight, template-based shortcode engine for Joomla 4 and later.

This plugin scans Joomla content (articles, categories, etc.) for shortcodes and replaces them with the output of corresponding PHP template files. It's designed to be simple, fast, and easy for developers to extend.

## Features

*   **Simple Syntax**: Use curly braces for shortcodes: `{tag}` or `{tag attr="value"}content{/tag}`.
*   **Template-Based**: Each shortcode corresponds to a simple PHP file. No database, no complex UI.
*   **Developer-Friendly**: Easily create new shortcodes by adding a PHP file.
*   **Content-Aware**: Shortcode templates have access to the current Joomla article (`$item`).
*   **Lightweight**: Minimal processing overhead.

## Installation

1.  Download the latest release package (a `.zip` file).
2.  In your Joomla administrator panel, go to `System` -> `Install` -> `Extensions`.
3.  Upload the downloaded `.zip` file.
4.  Enable the "Content - Shortcoder" plugin by going to `System` -> `Manage` -> `Plugins`.

## Usage

1.  Create a `shortcodes` directory in the root of your Joomla installation (if it doesn't already exist).
2.  Inside the `shortcodes` directory, create a new PHP file for each shortcode you want to add. The filename (without `.php`) becomes the shortcode tag. For example, `my_shortcode.php` will be available as `{my_shortcode}`.
3.  Write the PHP and HTML for your shortcode output in that file.

### Example Shortcode

If you create a file at `/shortcodes/hello.php` with the following content:

```php
<?php
// /shortcodes/hello.php

$name = $params['name'] ?? 'World';
?>

<strong>Hello, <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>!</strong>
<p><?php echo $content; ?></p>
```

You can then use it in your Joomla articles like this:

```
{hello name="Oleg"}This is the content inside the shortcode.{/hello}
```

This will be rendered as:

```html
<strong>Hello, Oleg!</strong>
<p>This is the content inside the shortcode.</p>
```

### Available Variables in Templates

Within your shortcode template files, you have access to:

*   `$params`: An associative array of the attributes passed to the shortcode.
*   `$content`: The string of content nested between the opening and closing shortcode tags.
*   `$item`: The Joomla content item object (e.g., article, category) being processed.

## For Developers

### Running Tests

This project uses PHPUnit for unit tests. To run the tests, first install the development dependencies with Composer, then run PHPUnit.

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
