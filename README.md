## Features

*   **Simple Syntax**: Use curly braces for shortcodes: `{tag}` or `{tag attr="value"}content{/tag}`.
*   **Template-Based**: Each file-based shortcode corresponds to a simple PHP file. No database, no complex UI.
*   **Callable-Based**: Define shortcodes directly using PHP functions for more complex logic.
*   **Developer-Friendly**: Easily create new shortcodes by adding a PHP file or defining a callable.
*   **Content-Aware**: Shortcode templates and callables have access to the current Joomla article (`$item`).
*   **Lightweight**: Minimal processing overhead.

## Installation

1.  Download the latest release package (a `.zip` file).
2.  In your Joomla administrator panel, go to `System` -> `Install` -> `Extensions`.
3.  Upload the downloaded `.zip` file.
4.  Enable the "Content - Shortcoder" plugin by going to `System` -> `Manage` -> `Plugins`.

## Usage

### File-based Shortcodes

1.  Create a `shortcodes` directory in the root of your Joomla installation (if it doesn't already exist).
2.  Inside the `shortcodes` directory, create a new PHP file for each shortcode you want to add. The filename (without `.php`) becomes the shortcode tag. For example, `my_shortcode.php` will be available as `{my_shortcode}`.
3.  Write the PHP and HTML for your shortcode output in that file.

### Callable Shortcodes

For more advanced shortcodes or when you prefer to keep logic within PHP code rather than separate template files, you can define callable shortcodes.

1.  Create a file named `shortcodes.php` directly inside your `shortcodes` directory (e.g., `JPATH_ROOT/shortcodes/shortcodes.php`).
2.  This `shortcodes.php` file must return an associative array where keys are the shortcode tags and values are PHP callables (functions, closures, static methods, etc.).
3.  Callable shortcodes defined in `shortcodes.php` will take precedence over file-based shortcodes with the same tag name.

**Example `shortcodes.php` content:**

```php
<?php

\defined('_JEXEC') or die;

return [
    'my_callable' => function (array $params, string $content, object $item): string {
        $name = $params['name'] ?? 'Guest';
        return 'Hello from callable, ' . $name . '! Content: ' . $content . '. Article ID: ' . ($item->id ?? 'N/A');
    },
    'current_year' => fn() => (string) date('Y'),
];
```

You can then use it in your Joomla articles like this:

```
{my_callable name="Alice"}This is some inner text.{/my_callable}
{current_year}
```

### Example File-based Shortcode

If you create a file at `/shortcodes/hello.php` with the following content:

```php
<?php

\defined('_JEXEC') or die;
?>

<strong>Hello, <?php echo $params['name'] ?? 'World'; ?>!</strong>
<p><?php echo $content; ?></p>
```

You can then use it in your Joomla articles like this:

```
{hello name="John Doe"}This is the content inside the shortcode.{/hello}
```

This will be rendered as:

```html
<strong>Hello, John Doe!</strong>
<p>This is the content inside the shortcode.</p>
```

### Available Variables

Within your shortcode template files **or** callable shortcodes, you have access to:

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
