<?php

namespace JoomlaShortcoder\Plugin\Content\Shortcoder\Test\Unit;

use PHPUnit\Framework\TestCase;
use JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeProcessingException;
use JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;

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
        $processor = new ShortcodeProcessor([
            'simple' => fn () => 'Simple Shortcode',
        ]);

        $text = 'This is a {simple} test.';

        $this->assertSame(
            'This is a Simple Shortcode test.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeReturningNonString(): void
    {
        $processor = new ShortcodeProcessor([
            'non_string' => fn () => 123,
        ]);

        $text = 'The number is {non_string}.';

        $this->assertSame(
            'The number is 123.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeWithAttributesDoubleQuotes(): void
    {
        $processor = new ShortcodeProcessor([
            'hello' => fn (array $attributes) => sprintf(
                'Hello, %s!',
                ($attributes['name'] ?? null) ?: 'World'
            ),
        ]);

        $text = 'Test {hello name="Double Quoted"} Test';

        $this->assertSame(
            'Test Hello, Double Quoted! Test',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeWithAttributesSingleQuotes(): void
    {
        $processor = new ShortcodeProcessor([
            'hello' => fn (array $attributes) => sprintf(
                'Hello, %s!',
                ($attributes['name'] ?? null) ?: 'World'
            ),
        ]);

        $text = "Test {hello name='Single Quoted'} Test";

        $this->assertSame(
            'Test Hello, Single Quoted! Test',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeWithAttributesWithoutQuotes(): void
    {
        $processor = new ShortcodeProcessor([
            'hello' => fn (array $attributes) => sprintf(
                'Hello, %s!',
                ($attributes['name'] ?? null) ?: 'World'
            ),
        ]);

        $text = 'Test {hello name=Unquoted} Test';

        $this->assertSame(
            'Test Hello, Unquoted! Test',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeWithEmptyAttributeValue(): void
    {
        $processor = new ShortcodeProcessor([
            'hello' => fn (array $attributes) => sprintf(
                'Hello, %s!',
                ($attributes['name'] ?? null) ?: 'World'
            ),
        ]);

        $textDouble = 'Test {hello name=""} Test';
        $textSingle = "Test {hello name=''} Test";

        $this->assertSame(
            'Test Hello, World! Test',
            $processor->processShortcodes($textDouble, new \stdClass()),
        );

        $this->assertSame(
            'Test Hello, World! Test',
            $processor->processShortcodes($textSingle, new \stdClass()),
        );
    }

    public function testShortcodeWithEmptyUnquotedAttribute(): void
    {
        $processor = new ShortcodeProcessor([
            'hello' => function (array $attributes) {
                if (!array_key_exists('name', $attributes)) {
                    return 'name attribute not found';
                }
                if ($attributes['name'] === '') {
                    return 'name attribute is an empty string';
                }
                return 'name attribute is: ' . $attributes['name'];
            },
        ]);

        $text = 'Test {hello name=} Test';

        $this->assertSame(
            'Test name attribute is an empty string Test',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithMultipleAttributes(): void
    {
        $processor = new ShortcodeProcessor([
            'person' => fn (array $attributes) => sprintf(
                'FirstName: %s, LastName: %s, Age: %d',
                $attributes['firstname'],
                $attributes['lastname'],
                $attributes['age'],
            ),
        ]);

        $text = 'Test {person firstname="John" lastname=\'Doe\' age=30}';

        $this->assertSame(
            'Test FirstName: John, LastName: Doe, Age: 30',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeUsesItemObject(): void
    {
        $processor = new ShortcodeProcessor([
            'item_aware' => fn (array $attributes, string $content, object $item) =>
                'Article Title: ' . $item->title,
        ]);

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
        $processor = new ShortcodeProcessor([
            'uppercase' => fn (array $attributes, string $content) =>
                strtoupper($content),
        ]);

        $text = 'This is a {uppercase}wrapped content{/uppercase} test.';

        $this->assertSame(
            'This is a WRAPPED CONTENT test.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeWithMultilineContent(): void
    {
        $processor = new ShortcodeProcessor([
            'content' => fn (array $attributes, string $content) =>
                'The content is: ' . $content,
        ]);

        $multilineContent = "This is line 1.\nThis is line 2.\nThis is line 3.";
        $text = 'A shortcode with multiline content: {content}' . $multilineContent . '{/content}';

        $this->assertSame(
            'A shortcode with multiline content: The content is: ' . $multilineContent,
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithEmptyContent(): void
    {
        $processor = new ShortcodeProcessor([
            'content' => fn (array $attributes, string $content) =>
                'The content is: ' . $content,
        ]);

        $text = 'This is a {content}{/content} test.';

        $this->assertSame(
            'This is a The content is:  test.',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testShortcodeTagsAreCaseSensitive(): void
    {
        $processor = new ShortcodeProcessor([
            'simple' => fn () => 'This should not be rendered.',
        ]);

        $text = 'This is a {SIMPLE} test.';

        $this->assertSame(
            $text,
            $processor->processShortcodes($text, new \stdClass()),
            'Shortcode tags should be case-sensitive but were processed.'
        );
    }

    public function testNestedShortcodes(): void
    {
        $processor = new ShortcodeProcessor([
            'nested' => fn (array $attributes, string $content) =>
                'Nested start:(' . $content . ')Nested end',
            'simple' => fn () => 'Simple Shortcode',
        ]);

        $text = 'Level1 {nested}Level2 {simple} here{/nested}';

        $this->assertSame(
            'Level1 Nested start:(Level2 Simple Shortcode here)Nested end',
            $processor->processShortcodes($text, new \stdClass()),
        );
    }

    public function testSameNameNestedShortcodesAreNoLongerProcessed(): void
    {
        $processor = new ShortcodeProcessor([
            'repeat' => fn (array $attributes, string $content) =>
                str_repeat($content ?? '', (int) ($attributes[0] ?? 1)),
        ]);

        $text = '{repeat 2}outer {repeat 3}inner{/repeat} outer{/repeat}';

        // The inner shortcode will be treated as a self-closing shortcode, as its closing tag
        // is outside the content of the parent shortcode.
        $expected = 'outer innerouter inner outer{/repeat}';

        $this->assertSame(
            $expected,
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testAdjacentShortcodesWithSameName(): void
    {
        $processor = new ShortcodeProcessor([
            'repeat' => fn (array $attributes, string $content) =>
                str_repeat($content ?? '', (int) ($attributes[0] ?? 1)),
        ]);

        $text = '{repeat 2}Hello{/repeat} {repeat 3}World!{/repeat}';

        $this->assertSame(
            'HelloHello World!World!World!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithAttributesAndContent(): void
    {
        $processor = new ShortcodeProcessor([
            'alert' => fn (array $attributes, string $content) => sprintf(
                '<div class="alert alert-%s">%s</div>',
                $attributes['type'] ?? 'info',
                $content ?? '',
            ),
        ]);

        $text = 'Start {alert type="warning"}Warning!{/alert} End';

        $this->assertSame(
            'Start <div class="alert alert-warning">Warning!</div> End',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeMaxDepthIsRespected(): void
    {
        $processor = new ShortcodeProcessor([
            'nested_a' => fn (array $attributes, string $content) => "A($content)A",
            'nested_b' => fn (array $attributes, string $content) => "B($content)B",
            'nested_c' => fn (array $attributes, string $content) => "C($content)C",
        ]);

        $text = '{nested_a}{nested_b}{nested_c}level3{/nested_c}{/nested_b}{/nested_a}';

        // With maxDepth=0, nothing is processed
        $this->assertSame(
            $text,
            $processor->processShortcodes($text, new \stdClass(), 0)
        );

        // With maxDepth=1, only the first level is processed
        $this->assertSame(
            'A({nested_b}{nested_c}level3{/nested_c}{/nested_b})A',
            $processor->processShortcodes($text, new \stdClass(), 1)
        );

        // With maxDepth=2, the third level should not be processed
        $this->assertSame(
            'A(B({nested_c}level3{/nested_c})B)A',
            $processor->processShortcodes($text, new \stdClass(), 2)
        );

        // With maxDepth=3, all levels are processed
        $this->assertSame(
            'A(B(C(level3)C)B)A',
            $processor->processShortcodes($text, new \stdClass(), 3)
        );
    }

    public function testMultipleShortcodesInText(): void
    {
        $processor = new ShortcodeProcessor([
            'foo' => fn () => 'FOO',
            'bar' => fn () => 'BAR',
        ]);

        $text = 'This is {foo}. This is {bar}. This is {foo} again.';

        $this->assertSame(
            'This is FOO. This is BAR. This is FOO again.',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testFileBasedShortcode(): void
    {
        $processor = new ShortcodeProcessor([
            'file_complex' => dirname(__DIR__) . '/fixtures/shortcodes/complex.php',
        ]);

        $mockItem = new \stdClass();
        $mockItem->title = 'My Item';

        $text = 'Start {file_complex attr="Value"}Inner{/file_complex} End';

        $this->assertSame(
            'Start Attr: Value, Content: Inner, Item: My Item End',
            $processor->processShortcodes($text, $mockItem)
        );
    }

    /**
     * @covers \JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor::executeShortcode
     */
    public function testShortcodeExecutionThrowsExceptionAndIsHandledGracefully(): void
    {
        $processor = new ShortcodeProcessor([
            'faulty' => fn () =>
                throw new \RuntimeException('Something went wrong inside the shortcode!'),
        ]);

        $text = 'Content with a {faulty_shortcode} that will fail.';

        $this->expectException(ShortcodeProcessingException::class);
        $this->expectExceptionMessage('Shortcode "faulty" failed to execute.');

        try {
            $processor->processShortcodes($text, new \stdClass());
        } catch (ShortcodeProcessingException $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
            $this->assertSame(
                'Something went wrong inside the shortcode!',
                $e->getPrevious()->getMessage(),
            );
            throw $e;
        }
    }

    public function testShortcodeWithSinglePositionalAttribute(): void
    {
        $processor = new ShortcodeProcessor([
            'email' => fn (array $attributes) => sprintf(
                '<a href="mailto:%1$s">%1$s</a>',
                $attributes[0] ?? '',
            ),
        ]);

        $text = 'My email: {email test@localhost}!';

        $this->assertSame(
            'My email: <a href="mailto:test@localhost">test@localhost</a>!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithMultiplePositionalAttributes(): void
    {
        $processor = new ShortcodeProcessor([
            'sum' => fn (array $attributes) =>
                ((int) $attributes[0] ?? 0) + ((int) $attributes[1] ?? 0) + ((int) $attributes[2] ?? 0),
        ]);

        $text = 'Sum 1 + 2 + 3 is {sum 1 \'2\' "3"}!';

        $this->assertSame(
            'Sum 1 + 2 + 3 is 6!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithMixedNamedAndPositionalAttributes(): void
    {
        $processor = new ShortcodeProcessor([
            'mixed' => fn (array $attributes) =>
                "Name: {$attributes['name']}, Value1: {$attributes[0]}, Value2: {$attributes[1]}",
        ]);

        $text = "Test {mixed name='test' 'value1' 'value2'}!";

        $this->assertSame(
            'Test Name: test, Value1: value1, Value2: value2!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithMixedPositionalAndNamedAttributes(): void
    {
        $processor = new ShortcodeProcessor([
            'mixed' => fn (array $attributes) =>
                "Value1: {$attributes[0]}, Name: {$attributes['name']}, Value2: {$attributes[1]}",
        ]);

        $text = "Test {mixed 'value1' name='test' 'value2'}!";

        $this->assertSame(
            'Test Value1: value1, Name: test, Value2: value2!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithPositionalAttributesInSpecialUnderscoreVar(): void
    {
        $processor = new ShortcodeProcessor([
            'sum' => fn (array $attributes) => array_sum($attributes['_']),
        ]);

        $text = "Test {sum 1 2 3 4 5}!";

        $this->assertSame(
            'Test 15!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }

    public function testShortcodeWithNoPositionalAttributesStillHasEmptyUnderscoreVar(): void
    {
        $processor = new ShortcodeProcessor([
            'sum' => fn (array $attributes) => array_sum($attributes['_']),
        ]);

        $text = 'Test {sum name="named_only"}!';

        $this->assertSame(
            'Test 0!',
            $processor->processShortcodes($text, new \stdClass())
        );

        $text = 'Test {sum}!'; // No attributes at all

        $this->assertSame(
            'Test 0!',
            $processor->processShortcodes($text, new \stdClass())
        );
    }
}
