<?php

\defined('_JEXEC') or die;

$paragraphs = isset($params['paragraphs']) ? (int) $params['paragraphs'] : null;
$minWords = isset($params['minwords']) ? (int) $params['minwords'] : null;
$maxWords = isset($params['maxwords']) ? (int) $params['maxwords'] : null;
$exactWords = (int) ($params['words'] ?? 100);

$lorem = explode(' ', <<<LOREMIPSUM
Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy
nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut
wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis
nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor
in hendrerit in vulputate velit esse molestie consequat, vel illum dolore
eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim
qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.
LOREMIPSUM);

for ($i = 0; $i < $paragraphs; $i++):
    if ($minWords !== null || $maxWords !== null) {
        $effectiveMin = max(1, $minWords ?? 1);
        $effectiveMax = $maxWords ?? $exactWords;

        // Ensure min <= max
        if ($effectiveMin > $effectiveMax) {
            $effectiveMax = $effectiveMin;
        }

        $currentWordCount = rand($effectiveMin, $effectiveMax);
    } else {
        $currentWordCount = $exactWords;
    }
    ?>
<p>
    <?php
    // Slice the words array to the desired length
    $currentParagraphWords = array_slice($lorem, 0, $currentWordCount);
    $text = implode(' ', $currentParagraphWords);

    // Ensure it ends with a dot
    if (substr($text, -1) !== '.') {
        // If it ends with a comma, remove it before adding the dot
        if (substr($text, -1) === ',') {
            $text = substr($text, 0, -1);
        }
        $text .= '.';
    }

    echo htmlspecialchars($text);
    ?>
</p>
<?php endfor; ?>
