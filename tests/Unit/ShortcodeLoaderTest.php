<?php

namespace JoomlaShortcoder\Plugin\Content\Shortcoder\Test\Unit;

use JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeLoader;
use PHPUnit\Framework\TestCase;

class ShortcodeLoaderTest extends TestCase
{
    private string $fixturesDir;
    private string $fixturesWithCallablesDir;
    private string $fixturesWithInvalidCallablesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = dirname(__DIR__) . '/fixtures/shortcodes';
        $this->fixturesWithCallablesDir = dirname(__DIR__) . '/fixtures/shortcodes_with_callables';
        $this->fixturesWithInvalidCallablesDir = dirname(__DIR__) . '/fixtures/shortcodes_with_invalid_callables';
    }

    public function testLoadShortcodesReturnsFiles(): void
    {
        $loader = new ShortcodeLoader([$this->fixturesDir]);
        $result = $loader->loadShortcodes();

        $this->assertArrayHasKey('simple', $result);
        $this->assertArrayHasKey('nested', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('attributes', $result);
    }

    public function testLoadShortcodesThrowsExceptionForInvalidDirectory(): void
    {
        $this->expectException(\RuntimeException::class);

        $loader = new ShortcodeLoader([$this->fixturesDir . '/non_existent']);
        $loader->loadShortcodes();
    }

    public function testLoadsCallableAndFileShortcodes(): void
    {
        $loader = new ShortcodeLoader([$this->fixturesWithCallablesDir]);
        $result = $loader->loadShortcodes();

        // Assert file-based shortcode is loaded
        $this->assertArrayHasKey('file_tag', $result);
        $this->assertIsString($result['file_tag']);
        $this->assertStringEndsWith('file_tag.php', $result['file_tag']);

        // Assert callable shortcode is loaded
        $this->assertArrayHasKey('callable_tag', $result);
        $this->assertIsCallable($result['callable_tag']);

        // Test a callable with parameters
        $this->assertArrayHasKey('another_callable', $result);
        $this->assertIsCallable($result['another_callable']);
    }

    public function testCallableShortcodeOverwritesFileShortcode(): void
    {
        $loader = new ShortcodeLoader([$this->fixturesWithCallablesDir]);
        $result = $loader->loadShortcodes();

        $this->assertArrayHasKey('overwrite_me', $result);
        $this->assertIsCallable($result['overwrite_me']);
        // Ensure it's not the file path
        $this->assertIsNotString($result['overwrite_me']);
    }

    public function testIgnoresInvalidCallableDefinitions(): void
    {
        $loader = new ShortcodeLoader([$this->fixturesWithInvalidCallablesDir]);
        $result = $loader->loadShortcodes();

        // Invalid tag name should not be loaded
        $this->assertArrayNotHasKey('invalid-tag!', $result);

        // Non-callable handler should not be loaded
        $this->assertArrayNotHasKey('valid-tag', $result);
        $this->assertArrayNotHasKey('another_invalid', $result);
    }
}
