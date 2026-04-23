<?php

\defined('_JEXEC') or die;

$videoId = $attributes[0] ?? '';
if (!$videoId) {
    return '';
}

$width   = $attributes['width'] ?? '560';
$height  = $attributes['height'] ?? '315';
$start   = $attributes['start'] ?? '0';
$allow   = $attributes['allow'] ?? 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
$title   = $attributes['title'] ?? 'YouTube video player';
$class   = $attributes['class'] ?? 'youtube-container';

$parts = explode(':', $start);
if (count($parts) == 2) {
    $start = (int) $parts[0] * 60 + (int) $parts[1];
}

$src = sprintf('https://www.youtube.com/embed/%s?start=%d', htmlspecialchars($videoId), (int) $start);
?>

<div class="<?php echo htmlspecialchars($class); ?>">
    <iframe
        src="<?php echo $src; ?>"
        width="<?php echo htmlspecialchars($width); ?>"
        height="<?php echo htmlspecialchars($height); ?>"
        allow="<?php echo htmlspecialchars($allow); ?>"
        title="<?php echo htmlspecialchars($title); ?>"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>
