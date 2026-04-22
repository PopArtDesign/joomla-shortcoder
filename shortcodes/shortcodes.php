<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Shortcodes;

\defined('_JEXEC') or die;

function loremIpsum(?int $minWords, ?int $maxWords, ?int $exactWords): string
{
    static $words = null;

    if ($words === null) {
        $words = explode(' ', <<<LOREMIPSUM
Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy
nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut
wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis
nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor
in hendrerit in vulputate velit esse molestie consequat, vel illum dolore
eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim
qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.
LOREMIPSUM);
    }

    // Determine the number of words for this paragraph
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

    $currentParagraphWords = array_slice($words, 0, $currentWordCount);
    $text = implode(' ', $currentParagraphWords);

    // Ensure it ends with a dot
    if (substr($text, -1) !== '.') {
        if (substr($text, -1) === ',') {
            $text = substr($text, 0, -1);
        }
        $text .= '.';
    }

    return $text;
}

return [
    'loremipsum' => function (array $params, string $content, object $item): string {
        $paragraphs = (int) ($params['paragraphs'] ?? 0);
        $minWords   = isset($params['minwords']) ? (int) $params['minwords'] : null;
        $maxWords   = isset($params['maxwords']) ? (int) $params['maxwords'] : null;
        $exactWords = (int) ($params['words'] ?? 100);

        if ($paragraphs > 0) {
            $output = [];
            for ($i = 0; $i < $paragraphs; $i++) {
                $output[] = sprintf(
                    '<p>%s</p>',
                    loremIpsum($minWords, $maxWords, $exactWords)
                );
            }

            return implode('', $output);
        }

        return loremIpsum($minWords, $maxWords, $exactWords);
    },
];
