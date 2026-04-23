<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Test\Unit;

use PHPUnit\Framework\TestCase;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;

class ShortcodeLoremIpsumTest extends TestCase
{
    private static ShortcodeProcessor $processor;

    public static function setUpBeforeClass(): void
    {
        $shortcodes = require 'shortcodes/shortcodes.php';
        self::$processor = new ShortcodeProcessor($shortcodes);
    }

    private function processShortcodes(string $text): string
    {
        return self::$processor->processShortcodes($text, new \stdClass());
    }

    public function testDefaultLoremIpsumShortcode(): void
    {
        $text = '{loremipsum}';
        $result = $this->processShortcodes($text);

        // Expect 1 paragraph with 84 words
        $this->assertStringStartsWith('<p>', $result);
        $this->assertStringEndsWith('</p>', $result);
        $this->assertEquals(1, substr_count($result, '<p>'));
        $this->assertEquals(1, substr_count($result, '</p>'));

        $content = strip_tags($result);
        $wordCount = str_word_count($content);
        $this->assertEquals(84, $wordCount);
    }

    public function testLoremIpsumWithWordsAttribute(): void
    {
        $text = '{loremipsum words="5"}';
        $result = $this->processShortcodes($text);

        $this->assertEquals(1, substr_count($result, '<p>'));
        $content = strip_tags($result);
        $wordCount = str_word_count($content);
        $this->assertEquals(5, $wordCount);
    }

    public function testLoremIpsumWithWordsRangeAttribute(): void
    {
        $text = '{loremipsum words="5,10"}';
        $result = $this->processShortcodes($text);

        $this->assertEquals(1, substr_count($result, '<p>'));
        $content = strip_tags($result);
        $wordCount = str_word_count($content);
        $this->assertGreaterThanOrEqual(5, $wordCount);
        $this->assertLessThanOrEqual(10, $wordCount);
    }

    public function testLoremIpsumWithParagraphsAttribute(): void
    {
        $text = '{loremipsum paragraphs="3"}';
        $result = $this->processShortcodes($text);

        $this->assertEquals(3, substr_count($result, '<p>'));
        $this->assertEquals(3, substr_count($result, '</p>'));
    }

    public function testLoremIpsumWithParagraphsRangeAttribute(): void
    {
        $text = '{loremipsum paragraphs="2,4"}';
        $result = $this->processShortcodes($text);

        $paragraphCount = substr_count($result, '<p>');
        $this->assertGreaterThanOrEqual(2, $paragraphCount);
        $this->assertLessThanOrEqual(4, $paragraphCount);
    }

    public function testLoremIpsumWithBothWordsAndParagraphsAttributes(): void
    {
        $text = '{loremipsum paragraphs="2" words="10"}';
        $result = $this->processShortcodes($text);

        $this->assertEquals(2, substr_count($result, '<p>'));

        $paragraphs = explode("\n", $result);
        foreach ($paragraphs as $paragraph) {
            $content = strip_tags($paragraph);
            $wordCount = str_word_count($content);
            $this->assertEquals(10, $wordCount);
        }
    }

    public function testLoremIpsumWithBothWordsAndParagraphsRangeAttributes(): void
    {
        $text = '{loremipsum paragraphs="2,4" words="5,10"}';
        $result = $this->processShortcodes($text);

        $paragraphCount = substr_count($result, '<p>');
        $this->assertGreaterThanOrEqual(2, $paragraphCount);
        $this->assertLessThanOrEqual(4, $paragraphCount);

        $paragraphs = explode("\n", $result);
        foreach ($paragraphs as $paragraph) {
            $content = strip_tags($paragraph);
            $wordCount = str_word_count($content);
            $this->assertGreaterThanOrEqual(5, $wordCount);
            $this->assertLessThanOrEqual(10, $wordCount);
        }
    }

    public function testLoremIpsumWithInvalidWordsRange(): void
    {
        $text = '{loremipsum words="10,5"}';
        $result = $this->processShortcodes($text);

        $content = strip_tags($result);
        $wordCount = str_word_count($content);
        $this->assertEquals(10, $wordCount);
    }

    public function testLoremIpsumWithInvalidParagraphsRange(): void
    {
        $text = '{loremipsum paragraphs="4,2"}';
        $result = $this->processShortcodes($text);

        $paragraphCount = substr_count($result, '<p>');
        $this->assertEquals(4, $paragraphCount);
    }

    public function testLoremIpsumWithZeroParagraphs(): void
    {
        $text = '{loremipsum paragraphs="0"}';
        $result = $this->processShortcodes($text);

        $this->assertEmpty($result);
    }
}
