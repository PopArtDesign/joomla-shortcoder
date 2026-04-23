<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Test\Unit;

use PHPUnit\Framework\TestCase;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeProcessingException;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;

class ShortcodeProcessorTest extends TestCase
{
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
        $shortcodes = [
            'simple' => function (): string {
                return 'Simple Shortcode';
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'This is a {simple} test.';

        $this->assertSame(
            'This is a Simple Shortcode test.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeReturningNonString(): void
    {
        $shortcodes = [
            'non_string' => function (): int {
                return 123;
            }
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'The number is {non_string}.';

        $this->assertSame(
            'The number is 123.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeWithAttributesDoubleQuotes(): void
    {
        $shortcodes = [
            'attributes' => function (array $attributes): string {
                return 'Hello, ' . ($attributes['name'] ?? '');
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'Test {attributes name="Double Quoted"}!';

        $this->assertSame(
            'Test Hello, Double Quoted!',
            $processor->processShortcodes($text, new \stdClass()),
            'Double quotes for attribute values failed.'
        );
    }

    public function testShortcodeWithAttributesSingleQuotes(): void
    {
        $shortcodes = [
            'attributes' => function (array $attributes): string {
                return 'Hello, ' . ($attributes['name'] ?? '');
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = "Test {attributes name='Single Quoted'}!";

        $this->assertSame(
            'Test Hello, Single Quoted!',
            $processor->processShortcodes($text, new \stdClass()),
            'Single quotes for attribute values failed.'
        );
    }

    public function testShortcodeWithAttributesWithoutQuotes(): void
    {
        $shortcodes = [
            'attributes' => function (array $attributes): string {
                return 'Hello, ' . ($attributes['name'] ?? '');
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'Test {attributes name=Unquoted}!';

        $this->assertSame(
            'Test Hello, Unquoted!',
            $processor->processShortcodes($text, new \stdClass()),
            'Without quotes for attribute values failed.'
        );
    }

    public function testShortcodeWithEmptyAttributeValue(): void
    {
        $shortcodes = [
            'attributes' => function (array $attributes): string {
                return 'Hello, ' . ($attributes['name'] ?? '');
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

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
        $shortcodes = [
            'multiple_attributes' => function (array $attributes): string {
                return "FirstName: {$attributes['firstname']}, LastName: {$attributes['lastname']}, Age: {$attributes['age']}";
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'Test {multiple_attributes firstname="John" lastname=\'Doe\' age=30}';

        $this->assertSame(
            'Test FirstName: John, LastName: Doe, Age: 30',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeUsesItemObject(): void
    {
        $shortcodes = [
            'item_aware' => function (array $attributes, string $content, object $item): string {
                return 'Article Title: ' . $item->title;
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

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
        $shortcodes = [
            'content' => function (array $attributes, string $content): string {
                return 'The content is: ' . $content;
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'This is a {content}wrapped content{/content} test.';

        $this->assertSame(
            'This is a The content is: wrapped content test.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeWithMultilineContent(): void
    {
        $shortcodes = [
            'content' => function (array $attributes, string $content): string {
                return 'The content is: ' . $content;
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $multilineContent = "This is line 1.\nThis is line 2.\nThis is line 3.";
        $text = 'A shortcode with multiline content: {content}' . $multilineContent . '{/content}';

        $this->assertSame(
            'A shortcode with multiline content: The content is: ' . $multilineContent,
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithEmptyContent(): void
    {
        $shortcodes = [
            'content' => function (array $attributes, string $content): string {
                return 'The content is: ' . $content;
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'This is a {content}{/content} test.';

        $this->assertSame(
            'This is a The content is:  test.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeTagsAreCaseSensitive(): void
    {
        $shortcodes = [
            'simple' => function (): string {
                return 'This should not be rendered.';
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'This is a {SIMPLE} test.';

        $this->assertSame(
            $text,
            $processor->processShortcodes($text, new \stdClass()),
            'Shortcode tags should be case-sensitive but were processed.'
        );
    }

    public function testNestedShortcodes(): void
    {
        $shortcodes = [
            'nested' => function (array $attributes, string $content): string {
                return 'Nested start:(' . $content . ')Nested end';
            },
            'simple' => function (): string {
                return 'Simple Shortcode';
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'Level1 {nested}Level2 {simple} here{/nested}';

        $this->assertSame(
            'Level1 Nested start:(Level2 Simple Shortcode here)Nested end',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testDeeplyNestedShortcodes(): void
    {
        $shortcodes = [
            'nested' => function (array $attributes, string $content): string {
                return 'Nested start:(' . $content . ')Nested end';
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = '{nested}1{nested}2{nested}3{nested}4{nested}5{/nested}4{/nested}3{/nested}2{/nested}1{/nested}';

        $this->assertSame(
            'Nested start:(1Nested start:(2Nested start:(3Nested start:(4Nested start:(5)Nested end4)Nested end3)Nested end2)Nested end1)Nested end',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithAttributesAndContent(): void
    {
        $shortcodes = [
            'complex' => function (array $attributes, string $content): string {
                return "Complex shortcode with attr '{$attributes['attr']}' and content '{$content}'";
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'Start {complex attr="Value A"}Inner Content{/complex} End';

        $this->assertSame(
            "Start Complex shortcode with attr 'Value A' and content 'Inner Content' End",
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeMaxDepthIsRespected(): void
    {
        $shortcodes = [
            'nested' => function (array $attributes, string $content): string {
                return 'Nested start:(' . $content . ')Nested end';
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

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

    public function testMultipleShortcodesInText(): void
    {
        $shortcodes = [
            'foo' => function (): string {
                return 'FOO';
            },
            'bar' => function (): string {
                return 'BAR';
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'This is {foo}. This is {bar}. This is {foo} again.';

        $this->assertSame(
            'This is FOO. This is BAR. This is FOO again.',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testFileBasedShortcode(): void
    {
        $shortcodes = [
            'file_complex' => dirname(__DIR__) . '/fixtures/shortcodes/complex.php',
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $mockItem = new \stdClass();
        $mockItem->title = 'My Item';

        $text = 'Start {file_complex attr="Value"}Inner{/file_complex} End';

        $this->assertSame(
            'Start Attr: Value, Content: Inner, Item: My Item End',
            $processor->processShortcodes($text, $mockItem)
        );
    }

    /**
     * @test
     * @covers \PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor::executeShortcode
     */
    public function testShortcodeExecutionThrowsExceptionAndIsHandledGracefully(): void
    {
        $faultyShortcodeTag = 'faulty_shortcode';
        $errorMessage       = 'Something went wrong inside the shortcode!';

        // Define a callable that deliberately throws an exception
        $faultyHandler = function () use ($errorMessage) {
            throw new \RuntimeException($errorMessage);
        };

        $shortcodes = [
            $faultyShortcodeTag => $faultyHandler,
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = 'Content with a {faulty_shortcode} that will fail.';
        $item = new \stdClass(); // Mock item

        $this->expectException(ShortcodeProcessingException::class);
        $this->expectExceptionMessageMatches(
            sprintf('/Shortcode "%s" failed to execute\./', $faultyShortcodeTag)
        );

        try {
            $processor->processShortcodes($text, $item);
        } catch (ShortcodeProcessingException $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
            $this->assertSame($errorMessage, $e->getPrevious()->getMessage());
            throw $e; // Re-throw to satisfy expectException
        }
    }

    public function testShortcodeWithSinglePositionalAttribute(): void
    {
        $shortcodes = [
            'positional' => function (array $attributes): string {
                return 'Value: ' . ($attributes[0] ?? '');
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = "Test {positional 'value1'}!";

        $this->assertSame(
            'Test Value: value1!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithMultiplePositionalAttributes(): void
    {
        $shortcodes = [
            'positional' => function (array $attributes): string {
                return "Value1: {$attributes[0]}, Value2: {$attributes[1]}";
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = "Test {positional 'value1' 'value2'}!";

        $this->assertSame(
            'Test Value1: value1, Value2: value2!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithMixedNamedAndPositionalAttributes(): void
    {
        $shortcodes = [
            'mixed' => function (array $attributes): string {
                return "Name: {$attributes['name']}, Value1: {$attributes[0]}, Value2: {$attributes[1]}";
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = "Test {mixed name='test' 'value1' 'value2'}!";

        $this->assertSame(
            'Test Name: test, Value1: value1, Value2: value2!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithMixedPositionalAndNamedAttributes(): void
    {
        $shortcodes = [
            'mixed' => function (array $attributes): string {
                return "Value1: {$attributes[0]}, Name: {$attributes['name']}, Value2: {$attributes[1]}";
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = "Test {mixed 'value1' name='test' 'value2'}!";

        $this->assertSame(
            'Test Value1: value1, Name: test, Value2: value2!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithPositionalAttributesInSpecialUnderscoreVar(): void
    {
        $shortcodes = [
            'mixed' => function (array $attributes): string {
                return "Name: {$attributes['name']}, Positional: " . implode(', ', $attributes['_']);
            },
        ];
        $processor = new ShortcodeProcessor($shortcodes);

        $text = "Test {mixed 'value1' name='test' 'value2'}!";

        $this->assertSame(
            'Test Name: test, Positional: value1, value2!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }
}
