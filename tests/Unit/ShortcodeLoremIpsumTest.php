<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Test\Unit;

use PHPUnit\Framework\TestCase;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;

class ShortcodeLoremIpsumTest extends TestCase
{
    private static ShortcodeProcessor $processor;

    public static function setUpBeforeClass(): void
    {
        $shortcodes = require __DIR__.'/../../shortcodes/shortcodes.php';

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

        $this->assertStringNotContainsString('<p>', $result);
        $this->assertEquals(84, str_word_count($result));
    }

    public function testLoremIpsumWithWordsAttribute(): void
    {
        $text = '{loremipsum words="5"}';
        $result = $this->processShortcodes($text);

        $this->assertStringNotContainsString('<p>', $result);
        $this->assertEquals(5, str_word_count($result));
    }

    public function testLoremIpsumWithWordsRangeAttribute(): void
    {
        $text = '{loremipsum words="5,10"}';
        $result = $this->processShortcodes($text);

        $this->assertStringNotContainsString('<p>', $result);
        $wordCount = str_word_count($result);
        $this->assertGreaterThanOrEqual(5, $wordCount);
        $this->assertLessThanOrEqual(10, $wordCount);
    }

    public function testLoremIpsumWithInvalidWordsRange(): void
    {
        $text = '{loremipsum words="10,5"}';
        $result = $this->processShortcodes($text);

        $this->assertStringNotContainsString('<p>', $result);
        $wordCount = str_word_count($result);
        $this->assertEquals(10, $wordCount);
    }
}
