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
        $loader = new ShortcodeLoader();
        $result = $loader->loadShortcodes($this->fixturesDir);

        $this->assertArrayHasKey('simple', $result);
        $this->assertArrayHasKey('nested', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('attributes', $result);
    }

    public function testLoadShortcodesThrowsExceptionForInvalidDirectory(): void
    {
        $this->expectException(\RuntimeException::class);

        $loader = new ShortcodeLoader();
        $loader->loadShortcodes($this->fixturesDir . '/non_existent');
    }
}
