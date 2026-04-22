<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Test\Unit;

use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeLoader;
use PHPUnit\Framework\TestCase;

class ShortcodeLoaderTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = dirname(__DIR__) . '/shortcodes';
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
}
