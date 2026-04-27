## Real-world Examples

Here are some real-world examples of how you might use Shortcoder for common website elements.

### Button

You can create a shortcode to easily generate a styled button with a link.

**Example `shortcodes/button.php`:**

```php
<?php

\defined('_JEXEC') or die;

$url    = $attributes[0] ?? '#'; // Positional attribute for URL
$target = $attributes['target'] ?? '_self';
$class  = $attributes['class'] ?? 'btn btn-primary';
$text   = $content ?: 'Click Here'; // Use content if provided, otherwise default text
?>

<a href="<?php echo htmlspecialchars($url); ?>" target="<?php echo htmlspecialchars($target); ?>" class="<?php echo htmlspecialchars($class); ?>">
    <?php echo htmlspecialchars($text); ?>
</a>
```

You can then use it in your Joomla articles like this:

```
{button "https://example.com/learn-more" class="btn-success" target="_blank"}Learn More{/button}
```

This will be rendered as:

```html
<a href="https://example.com/learn-more" target="_blank" class="btn-success">
    Learn More
</a>
```

### Details (Show/Hide)

A "details" shortcode is a great way to create collapsible content sections for things like spoilers or FAQs.

**Example `shortcodes/details.php`:**

```php
<?php

\defined('_JEXEC') or die;

$summary = $attributes['summary'] ?? 'Click to see more';
?>

<details>
    <summary><?php echo htmlspecialchars($summary); ?></summary>
    <div>
        <?php echo $content; ?>
    </div>
</details>
```

You can then use it in your Joomla articles like this:

```
{details summary="Read the full story"}This is the hidden content that will be revealed when the user clicks on the summary text.{/details}
```

This will be rendered as:

```html
<details>
    <summary>Read the full story</summary>
    <div>
        This is the hidden content that will be revealed when the user clicks on the summary text.
    </div>
</details>
```

### Alert Box

An alert box shortcode demonstrates using content and named attributes for styling.

**Example `shortcodes/alert.php`:**

```php
<?php

\defined('_JEXEC') or die;

$type  = $attributes['type'] ?? 'info'; // success, warning, danger, info
$class = $attributes['class'] ?? '';
?>

<div class="alert alert-<?php echo htmlspecialchars($type); ?> <?php echo htmlspecialchars($class); ?>" role="alert">
    <?php echo $content; ?>
</div>
```

You can then use it in your Joomla articles like this:

```
{alert type="warning"}This is a warning message!{/alert}
```

This will be rendered as:

```html
<div class="alert alert-warning" role="alert">
    This is a warning message!
</div>
```
