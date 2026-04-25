<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Tests\Unit\Extension;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Exception\ShortcodeProcessingException;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Extension\Shortcoder;
use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;
use PHPUnit\Framework\TestCase;
use stdClass;

class ShortcoderTest extends TestCase
{
    private $processor;
    private Shortcoder $plugin;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(ShortcodeProcessor::class);
        $dispatcher = $this->createMock(DispatcherInterface::class);

        $container = $this->createMock(Container::class);
        $container->method('get')
            ->will($this->returnValueMap([
                [ShortcodeProcessor::class, $this->processor],
                [DispatcherInterface::class, $dispatcher],
            ]));

        $this->plugin = new Shortcoder([], $container);
    }


    public function testGetSubscribedEvents()
    {
        $this->assertEquals(['onContentPrepare' => 'onContentPrepare'], Shortcoder::getSubscribedEvents());
    }

    /**
     * @dataProvider eventProvider
     */
    public function testOnContentPrepare($event, $item, $expectedCalls)
    {
        $originalText = $item->text;

        $this->processor->expects($this->exactly($expectedCalls))
            ->method('processShortcodes')
            ->willReturnCallback(fn ($text) => $text . '_processed');

        $this->plugin->onContentPrepare($event);

        if ($expectedCalls > 0) {
            $this->assertEquals($originalText . '_processed', $item->text);
        } else {
            $this->assertEquals($originalText, $item->text);
        }
    }

    public function eventProvider(): array
    {
        // Joomla 4 Events
        $itemJ4 = new stdClass();
        $itemJ4->text = 'some {shortcode}';
        $eventJ4Allowed = new Event('onContentPrepare', ['com_content.article', &$itemJ4]);

        $itemJ4NoOp = new stdClass();
        $itemJ4NoOp->text = 'some shortcode';
        $eventJ4NoOp = new Event('onContentPrepare', ['com_content.article', &$itemJ4NoOp]);

        $itemJ4Disallowed = new stdClass();
        $itemJ4Disallowed->text = 'some {shortcode}';
        $eventJ4Disallowed = new Event('onContentPrepare', ['com_other.component', &$itemJ4Disallowed]);

        // Joomla 5 Events
        $itemJ5 = new stdClass();
        $itemJ5->text = 'some {shortcode}';
        $eventJ5Allowed = new ContentPrepareEvent('com_content.article', $itemJ5);

        $itemJ5NoOp = new stdClass();
        $itemJ5NoOp->text = 'some shortcode';
        $eventJ5NoOp = new ContentPrepareEvent('com_content.article', $itemJ5NoOp);

        $itemJ5Disallowed = new stdClass();
        $itemJ5Disallowed->text = 'some {shortcode}';
        $eventJ5Disallowed = new ContentPrepareEvent('com_other.component', $itemJ5Disallowed);

        return [
            'Joomla 4 / Allowed context' => [$eventJ4Allowed, $itemJ4, 1],
            'Joomla 4 / No shortcode present' => [$eventJ4NoOp, $itemJ4NoOp, 0],
            'Joomla 4 / Disallowed context' => [$eventJ4Disallowed, $itemJ4Disallowed, 0],
            'Joomla 5 / Allowed context' => [$eventJ5Allowed, $itemJ5, 1],
            'Joomla 5 / No shortcode present' => [$eventJ5NoOp, $itemJ5NoOp, 0],
            'Joomla 5 / Disallowed context' => [$eventJ5Disallowed, $itemJ5Disallowed, 0],
        ];
    }

    /**
     * @runInSeparateProcess
     */
    public function testExceptionHandlingWithDebugOn()
    {
        $this->expectException(ShortcodeProcessingException::class);

        define('JDEBUG', true);

        $item = new stdClass();
        $item->text = 'some {shortcode}';
        $event = new Event('onContentPrepare', ['com_content.article', &$item]);

        $this->processor->expects($this->once())
            ->method('processShortcodes')
            ->willThrowException(new ShortcodeProcessingException('Test error'));

        $this->plugin->onContentPrepare($event);
    }
}
