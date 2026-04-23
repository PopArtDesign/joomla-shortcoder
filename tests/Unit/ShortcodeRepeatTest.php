<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Test\Unit;

use PHPUnit\Framework\TestCase;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;

class ShortcodeRepeatTest extends TestCase
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

    public function testDefaultRepeatShortcode(): void
    {
        $text = '{repeat}test{/repeat}';
        $result = $this->processShortcodes($text);
        $this->assertEquals('test', $result);
    }

    public function testRepeatShortcodeWithNumber(): void
    {
        $text = '{repeat 3}test{/repeat}';
        $result = $this->processShortcodes($text);
        $this->assertEquals('testtesttest', $result);
    }

    public function testRepeatShortcodeWithRange(): void
    {
        $text = '{repeat 2,4}test{/repeat}';
        $result = $this->processShortcodes($text);
        $count = substr_count($result, 'test');
        $this->assertGreaterThanOrEqual(2, $count);
        $this->assertLessThanOrEqual(4, $count);
    }

    public function testRepeatShortcodeWithInvalidRange(): void
    {
        $text = '{repeat 4,2}test{/repeat}';
        $result = $this->processShortcodes($text);
        $this->assertEquals('testtesttesttest', $result);
    }

    public function testRepeatShortcodeWithZero(): void
    {
        $text = '{repeat 0}test{/repeat}';
        $result = $this->processShortcodes($text);
        $this->assertEmpty($result);
    }

    public function testRepeatWithNestedLoremIpsum(): void
    {
        $text = '{repeat 3}<p>{loremipsum words="5"}</p>{/repeat}';
        $result = $this->processShortcodes($text);

        // Check for 3 paragraphs
        $this->assertEquals(3, substr_count($result, '<p>'));
        $this->assertEquals(3, substr_count($result, '</p>'));

        // Check that each paragraph has 5 words
        $paragraphs = explode('</p>', trim($result, "\n"));
        array_pop($paragraphs); // remove last empty element

        foreach ($paragraphs as $paragraph) {
            $content = strip_tags($paragraph);
            $wordCount = str_word_count($content);
            $this->assertEquals(5, $wordCount);
        }
    }

    public function testRepeatWithRangeAndNestedLoremIpsum(): void
    {
        $text = '{repeat 2,5}<p>{loremipsum words="10"}</p>{/repeat}';
        $result = $this->processShortcodes($text);

        // Check for 2-5 paragraphs
        $count = substr_count($result, '<p>');
        $this->assertGreaterThanOrEqual(2, $count);
        $this->assertLessThanOrEqual(5, $count);
        $this->assertEquals($count, substr_count($result, '</p>'));

        // Check that each paragraph has 10 words
        $paragraphs = explode('</p>', trim($result, "\n"));
        array_pop($paragraphs); // remove last empty element

        foreach ($paragraphs as $paragraph) {
            $content = strip_tags($paragraph);
            $wordCount = str_word_count($content);
            $this->assertEquals(10, $wordCount);
        }
    }
}
