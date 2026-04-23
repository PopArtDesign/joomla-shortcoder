<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Shortcodes;

\defined('_JEXEC') or die;

/**
 * Generates a Lorem Ipsum paragraph with a specified word count range.
 *
 * @param int $minWordCount Minimum number of words for the paragraph.
 * @param int|null $maxWordCount Maximum number of words for the paragraph. If null or less than $minWordCount, $minWordCount is used as exact word count.
 * @return string An HTML-escaped Lorem Ipsum paragraph.
 */
function loremIpsum(int $minWordCount = 1, ?int $maxWordCount = null): string
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
    $chosenWordCount = $minWordCount;
    if ($maxWordCount !== null && $maxWordCount > $minWordCount) {
        $chosenWordCount = rand($minWordCount, $maxWordCount);
    }

    $currentParagraphWords = array_slice($words, 0, $chosenWordCount);
    $text = implode(' ', $currentParagraphWords);

    // Ensure it ends with a dot
    if (substr($text, -1) !== '.') {
        if (substr($text, -1) === ',') {
            $text = substr($text, 0, -1);
        }
        $text .= '.';
    }

    return htmlspecialchars($text);
}

return [
    'loremipsum' => function (array $attributes): string {
        $paragraphsAttr = $attributes['paragraphs'] ?? '1'; // Default to 1 paragraph
        $wordsAttr      = $attributes['words'] ?? '100'; // Default to 100 words

        // --- Parse words attribute ---
        $minWordCount = 1;
        $maxWordCount = null;

        if (\is_string($wordsAttr) && \strpos($wordsAttr, ',') !== false) {
            list($min, $max) = explode(',', $wordsAttr);
            $minWordCount = (int) $min;
            $maxWordCount = (int) $max;

            if ($maxWordCount < $minWordCount) {
                $maxWordCount = $minWordCount; // Ensure valid range
            }
        } else {
            $minWordCount = (int) $wordsAttr;
            $maxWordCount = $minWordCount; // Exact count if only one number
        }

        // --- Parse paragraphs attribute ---
        $minParagraphs = 1;
        $maxParagraphs = null;

        if (\is_string($paragraphsAttr) && \strpos($paragraphsAttr, ',') !== false) {
            list($min, $max) = explode(',', $paragraphsAttr);
            $minParagraphs = (int) $min;
            $maxParagraphs = (int) $max;

            if ($maxParagraphs < $minParagraphs) {
                $maxParagraphs = $minParagraphs; // Ensure valid range
            }
        } else {
            $minParagraphs = (int) $paragraphsAttr;
            $maxParagraphs = $minParagraphs; // Exact count if only one number
        }

        // Determine the actual number of paragraphs to generate
        $numberOfParagraphs = $minParagraphs;
        if ($maxParagraphs !== null && $maxParagraphs > $minParagraphs) {
            $numberOfParagraphs = rand($minParagraphs, $maxParagraphs);
        }

        // --- Generate output ---
        $output = [];

        if ($numberOfParagraphs > 0) {
            for ($i = 0; $i < $numberOfParagraphs; $i++) {
                $output[] = sprintf(
                    '<p>%s</p>',
                    loremIpsum($minWordCount, $maxWordCount)
                );
            }
            return implode("\n", $output);
        }

        // If numberOfParagraphs is 0 or less, return empty string.
        return '';
    },
];
