<?php

namespace JoomlaShortcoder\Plugin\Content\Shortcoder\Test\Unit\Event;

use JoomlaShortcoder\Plugin\Content\Shortcoder\Event\LoadShortcodesEvent;
use JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeLoadingException;
use PHPUnit\Framework\TestCase;

class LoadShortcodesEventTest extends TestCase
{
    public function testAddShortcodeAddsValidShortcode(): void
    {
        $event = new LoadShortcodesEvent('onShortcoderLoadShortcodes');
        $callable = static fn () => 'test';
        $event->addShortcode('foo', $callable);

        $this->assertArrayHasKey('foo', $event->getShortcodes());
        $this->assertSame($callable, $event->getShortcodes()['foo']);
    }

    public function testAddShortcodeThrowsExceptionForInvalidTagName(): void
    {
        $this->expectException(ShortcodeLoadingException::class);
        $this->expectExceptionMessage('Shortcode tag "invalid-tag!" is not valid.');

        $event = new LoadShortcodesEvent('onShortcoderLoadShortcodes');
        $callable = static fn () => 'test';
        $event->addShortcode('invalid-tag!', $callable);
    }

    public function testGetShortcodesReturnsEmptyArrayByDefault(): void
    {
        $event = new LoadShortcodesEvent('onShortcoderLoadShortcodes');
        $this->assertIsArray($event->getShortcodes());
        $this->assertEmpty($event->getShortcodes());
    }

    public function testGetShortcodesReturnsAllAddedShortcodes(): void
    {
        $event = new LoadShortcodesEvent('onShortcoderLoadShortcodes');
        $callable1 = static fn () => 'test1';
        $callable2 = static fn () => 'test2';

        $event->addShortcode('foo', $callable1);
        $event->addShortcode('bar', $callable2);

        $shortcodes = $event->getShortcodes();

        $this->assertCount(2, $shortcodes);
        $this->assertArrayHasKey('foo', $shortcodes);
        $this->assertSame($callable1, $shortcodes['foo']);
        $this->assertArrayHasKey('bar', $shortcodes);
        $this->assertSame($callable2, $shortcodes['bar']);
    }
}
