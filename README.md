# Joomla Shortcoder

[![CI](https://github.com/PopArtDesign/joomla-shortcoder/actions/workflows/ci.yml/badge.svg)](https://github.com/PopArtDesign/joomla-shortcoder/actions/workflows/ci.yml)

A lightweight, template-based shortcode engine for [Joomla](https://www.joomla.org/) 4 and later.

This plugin scans Joomla content (articles, categories, etc.) for shortcodes and replaces them with the output of corresponding PHP template files or callables. It's designed to be simple, fast, and easy for developers to extend.

## Features

*   **Simple Syntax**: Use curly braces for shortcodes:
    - self-closing: `{tag}` or `{tag attr="value"}`.
    - with content between opening and closing tags: `{tag attr="value"}content{/tag}`.
*   **Template-Based**: Each file-based shortcode corresponds to a simple PHP file. No database, no complex UI.
*   **Callable-Based**: Define shortcodes directly using PHP functions for more complex logic.
*   **Developer-Friendly**: Easily create new shortcodes by adding a PHP file or defining a callable.
*   **Content-Aware**: Shortcode templates and callables have access to the current Joomla article (`$item`).
*   **Lightweight**: Minimal processing overhead.

## Installation

1.  Download the [latest release package](https://github.com/PopArtDesign/joomla-shortcoder/releases/latest) (a `.zip` file).
2.  In your Joomla administrator panel, go to `System` -> `Install` -> `Extensions`.
3.  Upload the downloaded `.zip` file.
4.  Enable the "Content - Shortcoder" plugin by going to `System` -> `Manage` -> `Plugins`.

## Usage

### File-based Shortcodes

1.  Create a `shortcodes` directory in the root of your Joomla installation (if it doesn't already exist).
2.  Inside the `shortcodes` directory, create a new PHP file for each shortcode you want to add. The filename (without `.php`) becomes the shortcode tag. For example, `my_shortcode.php` will be available as `{my_shortcode}`.
3.  Write the PHP and HTML for your shortcode output in that file.

**Example `shortcodes/hello.php`:**

```php
<?php

\defined('_JEXEC') or die;

$name = $attributes['name'] ?? 'World';
?>

<strong>Hello, <?php echo $name; ?>!</strong>
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

### Callable Shortcodes

For more advanced shortcodes or when you prefer to keep logic within PHP code rather than separate template files, you can define callable shortcodes.

1.  Create a file named `shortcodes.php` directly inside your `shortcodes` directory (e.g., `JPATH_ROOT/shortcodes/shortcodes.php`).
2.  This `shortcodes.php` file must return an associative array where keys are the shortcode tags and values are PHP callables (functions, closures, static methods, etc.).
3.  Callable shortcodes defined in `shortcodes.php` will take precedence over file-based shortcodes with the same tag name.

**Example `shortcodes/shortcodes.php`:**

```php
<?php

\defined('_JEXEC') or die;

return [
    'hello' => fn (array $attributes) => sprintf('Hello, %s!', $attributes['name'] ?? 'World'),
    'current_year' => fn() => (string) date('Y'),
];
```

You can then use it in your Joomla articles like this:

```
{hello name="Alice"}

{current_year}
```

### Available Variables

Within your shortcode template files **or** callable shortcodes, you have access to:

*   `$attributes`: An associative array of the attributes passed to the shortcode.
*   `$content`: The string of content nested between the opening and closing shortcode tags.
*   `$item`: The Joomla content item object (e.g., article, category) being processed.

### Shortcode Attributes

Shortcode attributes can be defined in several ways:

*   **Named Attributes (key="value")**: These are the standard `key="value"` pairs.
    *   **Double Quotes**: Attributes enclosed in double quotes `{tag attr="value"}` allow for values containing spaces and special characters. This is generally the recommended approach.
    *   **Single Quotes**: Attributes enclosed in single quotes `{tag attr='value'}` also allow for values containing spaces and special characters.
    *   **Unquoted**: Attributes without quotes `{tag attr=value}` are are allowed, but the value must not contain spaces or special characters. It will be parsed until the next space or the closing bracket.

*   **Positional Attributes**: These are values passed directly without a key, like `{tag 'value1' 'value2'}`.
    *   They are accessible in the `$attributes` array using numeric keys (0, 1, 2, ...).
    *   Additionally, all positional attributes are collected into a special array accessible via the `'_'` key in the `$attributes` array (e.g., `$attributes['_'][0]`). This is useful for iterating over all positional arguments.

**Examples**:

```
{example unquoted=value single='single quoted' double="double quoted value with spaces" 'positional value 1' positional_value_2}
```

In your shortcode, `$attributes` would look something like this:

```php
[
    'unquoted' => 'value',
    'single' => 'single quoted',
    'double' => 'double quoted value with spaces',
    0 => 'positional value 1',
    1 => 'positional_value_2',
    '_' => ['positional value 1', 'positional_value_2'],
]
```

## Limitations

*   **Nested Shortcodes**: Nesting a shortcode within another shortcode of the **same name** is not supported due to limitations in the regular expression-based parser. For example, the following structure will not work as expected:

    ```
    {my_shortcode}
        {my_shortcode}...{/my_shortcode}
    {/my_shortcode}
    ```

    However, nesting shortcodes with **different names** is fully supported:

    ```
    {one}
        {two}...{/two}
    {one}
    ```

*   **Mixed Shortcode Syntax Usage**: While shortcodes support both self-closing (`{tag}`) and paired (`{tag}content{/tag}`) syntax, it is recommended to avoid mixing these forms for the **same shortcode tag** within a single content string. Using `{tag}` and then later `{tag}content{/tag}` for the same `tag` in the same article might lead to unpredictable parsing behavior due to the regex-based nature of the parser.

*   **Joomla Page Cache**: When Joomla's page cache is enabled, the output of shortcodes is cached as part of the page's HTML. This can cause dynamic or user-specific content (e.g., a welcome message with the user's name) to become stale and be shown to all visitors. If your shortcodes must be dynamic, consider disabling Joomla's page cache on the relevant pages.

## Resources

*   [Real-world Examples](docs/examples.md)
*   [Implementing a Custom Shortcode Provider Plugin](docs/custom-shortcode-plugin.md)
*   [Additional Shortcode Pack](https://github.com/PopArtDesign/joomla-shortcodes)

## License

This project is licensed under the [MIT License](LICENSE).
