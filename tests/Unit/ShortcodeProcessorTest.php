<?php

namespace Joomla\Plugin\Content\Shortcoder\Test\Unit;

use Joomla\Plugin\Content\Shortcoder\ShortcodeProcessor;
use PHPUnit\Framework\TestCase;

class ShortcodeProcessorTest extends TestCase
{
    private array $shortcodeFiles = [];

    protected function setUp(): void
    {
        $basePath = __DIR__ . '/../shortcodes/';
        $this->shortcodeFiles = [
            'simple'              => $basePath . 'simple.php',
            'attributes'          => $basePath . 'attributes.php',
            'content'             => $basePath . 'content.php',
            'nested'              => $basePath . 'nested.php',
            'complex'             => $basePath . 'complex.php',
            'multiple_attributes' => $basePath . 'multiple_attributes.php',
            'item_aware'          => $basePath . 'item_aware.php',
        ];
    }

    public function testUnknownShortcode(): void
    {
        $processor = new ShortcodeProcessor([]);

        $text = 'This is a {unknown} test.';

        $this->assertSame(
            $text,
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testSimpleShortcode(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = 'This is a {simple} test.';

        $this->assertSame(
            'This is a Simple Shortcode test.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeWithAttributesDoubleQuotes(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = 'Test {attributes name="Double Quoted"}!';

        $this->assertSame(
            'Test Hello, Double Quoted!',
            $processor->processShortcodes($text, new \stdClass()),
            'Double quotes for attribute values failed.'
        );
    }

    public function testShortcodeWithAttributesSingleQuotes(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = "Test {attributes name='Single Quoted'}!";

        $this->assertSame(
            'Test Hello, Single Quoted!',
            $processor->processShortcodes($text, new \stdClass()),
            'Single quotes for attribute values failed.'
        );
    }

    public function testShortcodeWithAttributesWithoutQuotes(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = 'Test {attributes name=Unquoted}!';

        $this->assertSame(
            'Test Hello, Unquoted!',
            $processor->processShortcodes($text, new \stdClass()),
            'Without quotes for attribute values failed.'
        );
    }

    public function testShortcodeWithEmptyAttributeValue(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $textDouble = 'Test {attributes name=""}!';
        $textSingle = 'Test {attributes name=\'\'}!';

        $this->assertSame(
            'Test Hello, !',
            $processor->processShortcodes($textDouble, new \stdClass()),
            'Empty double-quoted attribute value failed.'
        );

        $this->assertSame(
            'Test Hello, !',
            $processor->processShortcodes($textSingle, new \stdClass()),
            'Empty single-quoted attribute value failed.'
        );
    }

    public function testShortcodeWithMultipleAttributes(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = 'Test {multiple_attributes firstname="John" lastname=\'Doe\' age=30}';

        $this->assertSame(
            'Test FirstName: John, LastName: Doe, Age: 30',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeUsesItemObject(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $mockItem = new \stdClass();
        $mockItem->title = 'My Test Article';

        $text = 'This is my article: {item_aware}.';

        $this->assertSame(
            'This is my article: Article Title: My Test Article.',
            $processor->processShortcodes($text, $mockItem)
        );
    }

    public function testShortcodeWithContent(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = 'This is a {content}wrapped content{/content} test.';

        $this->assertSame(
            'This is a The content is: wrapped content test.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeWithMultilineContent(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $multilineContent = "This is line 1.\nThis is line 2.\nThis is line 3.";
        $text = 'A shortcode with multiline content: {content}' . $multilineContent . '{/content}';

        $this->assertSame(
            'A shortcode with multiline content: The content is: ' . $multilineContent,
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithEmptyContent(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = 'This is a {content}{/content} test.';

        $this->assertSame(
            'This is a The content is:  test.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeTagsAreCaseSensitive(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = 'This is a {SIMPLE} test.';

        $this->assertSame(
            $text,
            $processor->processShortcodes($text, new \stdClass()),
            'Shortcode tags should be case-sensitive but were processed.'
        );
    }

    public function testNestedShortcodes(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = 'Level1 {nested}Level2 {simple} here{/nested}';

        $this->assertSame(
            'Level1 Nested start:(Level2 Simple Shortcode here)Nested end',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testDeeplyNestedShortcodes(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = '{nested}1{nested}2{nested}3{nested}4{nested}5{/nested}4{/nested}3{/nested}2{/nested}1{/nested}';

        $this->assertSame(
            'Nested start:(1Nested start:(2Nested start:(3Nested start:(4Nested start:(5)Nested end4)Nested end3)Nested end2)Nested end1)Nested end',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithAttributesAndContent(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = 'Start {complex attr="Value A"}Inner Content{/complex} End';

        $this->assertSame(
            "Start Complex shortcode with attr 'Value A' and content 'Inner Content' End",
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeMaxDepthIsRespected(): void
    {
        $processor = new ShortcodeProcessor($this->shortcodeFiles);

        $text = '{nested}1{nested}2{nested}3{/nested}2{/nested}1{/nested}';

        // With maxDepth=0, nothing is processed
        $this->assertSame(
            $text,
            $processor->processShortcodes($text, new \stdClass(), 0)
        );

        // With maxDepth=1, only the first level is processed
        $this->assertSame(
            'Nested start:(1{nested}2{nested}3{/nested}2{/nested}1)Nested end',
            $processor->processShortcodes($text, new \stdClass(), 1)
        );

        // With maxDepth=2, the third level should not be processed
        $this->assertSame(
            'Nested start:(1Nested start:(2{nested}3{/nested}2)Nested end1)Nested end',
            $processor->processShortcodes($text, new \stdClass(), 2)
        );
    }
}
