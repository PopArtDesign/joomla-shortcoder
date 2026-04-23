<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Shortcodes;

\defined('_JEXEC') or die;

if (!\function_exists(__NAMESPACE__ . '\loremIpsum')) {
    /**
     * Generates a Lorem Ipsum paragraph with a specified word count.
     *
     * @param int $wordCount The exact number of words for the paragraph.
     *
     * @return string A Lorem Ipsum paragraph.
     */
    function loremIpsum(int $wordCount = 1): string
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
LOREMIPSUM
            );
        }

        $currentParagraphWords = array_slice($words, 0, $wordCount);
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
}

return [
    'loremipsum' => function (array $attributes): string {
        $wordsAttr = $attributes['words'] ?? '100';

        $minWordCount = 1;
        $maxWordCount = null;

        if (\is_string($wordsAttr) && \strpos($wordsAttr, ',') !== false) {
            [$min, $max] = explode(',', $wordsAttr);
            $minWordCount = (int) $min;
            $maxWordCount = (int) $max;

            if ($maxWordCount < $minWordCount) {
                $maxWordCount = $minWordCount;
            }
        } else {
            $minWordCount = (int) $wordsAttr;
            $maxWordCount = $minWordCount;
        }

        $chosenWordCount = $minWordCount;
        if ($maxWordCount !== null && $maxWordCount > $minWordCount) {
            $chosenWordCount = rand($minWordCount, $maxWordCount);
        }

        return loremIpsum($chosenWordCount);
    },
    'repeat' => function ($attributes, $content) {
        $repeatAttr = $attributes[0] ?? '1';

        $minRepeats = 1;
        $maxRepeats = null;

        if (\is_string($repeatAttr) && \strpos($repeatAttr, ',') !== false) {
            [$min, $max] = explode(',', $repeatAttr);
            $minRepeats = (int) $min;
            $maxRepeats = (int) $max;

            if ($maxRepeats < $minRepeats) {
                $maxRepeats = $minRepeats;
            }
        } else {
            $minRepeats = (int) $repeatAttr;
            $maxRepeats = $minRepeats;
        }

        $numberOfRepeats = $minRepeats;
        if ($maxRepeats !== null && $maxRepeats > $minRepeats) {
            $numberOfRepeats = rand($minRepeats, $maxRepeats);
        }

        if ($numberOfRepeats <= 0) {
            return '';
        }

        return \str_repeat($content, $numberOfRepeats);
    },
];
