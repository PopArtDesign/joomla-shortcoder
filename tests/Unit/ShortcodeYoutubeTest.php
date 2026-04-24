<?php

namespace PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\Test\Unit;

use PopArtDesign\JoomlaShortcoder\Plugin\Content\Shortcoder\ShortcodeProcessor;
use PHPUnit\Framework\TestCase;

class ShortcodeYoutubeTest extends TestCase
{
    private $processor;

    protected function setUp(): void
    {
        $shortcodes = [
            'youtube' => \dirname(__DIR__, 2) . '/shortcodes/youtube.php',
        ];
        $this->processor = new ShortcodeProcessor($shortcodes);
    }

    public function testNoVideoId()
    {
        $content = $this->processor->processShortcodes('{youtube}', new \stdClass());
        $this->assertEquals('', $content);
    }

    public function testBasicUsage()
    {
        $content = $this->processor->processShortcodes('{youtube abc-123}', new \stdClass());
        $expected = '
<div class="youtube-container">
    <iframe
        src="https://www.youtube.com/embed/abc-123?start=0"
        width="560"
        height="315"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        title="YouTube video player"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }

    public function testCustomDimensions()
    {
        $content = $this->processor->processShortcodes('{youtube abc-123 width="800" height="600"}', new \stdClass());
        $expected = '
<div class="youtube-container">
    <iframe
        src="https://www.youtube.com/embed/abc-123?start=0"
        width="800"
        height="600"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        title="YouTube video player"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }

    public function testStartTimeInSeconds()
    {
        $content = $this->processor->processShortcodes('{youtube abc-123 start="90"}', new \stdClass());
        $expected = '
<div class="youtube-container">
    <iframe
        src="https://www.youtube.com/embed/abc-123?start=90"
        width="560"
        height="315"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        title="YouTube video player"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }

    public function testStartTimeInMmSs()
    {
        $content = $this->processor->processShortcodes('{youtube abc-123 start="1:30"}', new \stdClass());
        $expected = '
<div class="youtube-container">
    <iframe
        src="https://www.youtube.com/embed/abc-123?start=90"
        width="560"
        height="315"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        title="YouTube video player"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }

    public function testAllAttributes()
    {
        $content = $this->processor->processShortcodes('{youtube abc-123 width="1024" height="768" start="0:42" class="my-class" title="My Video" allow="autoplay"}', new \stdClass());
        $expected = '
<div class="my-class">
    <iframe
        src="https://www.youtube.com/embed/abc-123?start=42"
        width="1024"
        height="768"
        allow="autoplay"
        title="My Video"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }

    public function testYoutubeUrlWatchV()
    {
        $content = $this->processor->processShortcodes('{youtube https://www.youtube.com/watch?v=siiEuhfdhf}', new \stdClass());
        $expected = '
<div class="youtube-container">
    <iframe
        src="https://www.youtube.com/embed/siiEuhfdhf?start=0"
        width="560"
        height="315"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        title="YouTube video player"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }

    public function testYoutubeUrlYoutuBe()
    {
        $content = $this->processor->processShortcodes('{youtube https://youtu.be/siiEuhfdhf}', new \stdClass());
        $expected = '
<div class="youtube-container">
    <iframe
        src="https://www.youtube.com/embed/siiEuhfdhf?start=0"
        width="560"
        height="315"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        title="YouTube video player"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }

    public function testYoutubeUrlEmbed()
    {
        $content = $this->processor->processShortcodes('{youtube https://www.youtube.com/embed/siiEuhfdhf}', new \stdClass());
        $expected = '
<div class="youtube-container">
    <iframe
        src="https://www.youtube.com/embed/siiEuhfdhf?start=0"
        width="560"
        height="315"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        title="YouTube video player"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }

    public function testYoutubeUrlWithOtherParams()
    {
        $content = $this->processor->processShortcodes('{youtube https://www.youtube.com/watch?v=siiEuhfdhf&t=10s}', new \stdClass());
        $expected = '
<div class="youtube-container">
    <iframe
        src="https://www.youtube.com/embed/siiEuhfdhf?start=0"
        width="560"
        height="315"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        title="YouTube video player"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }

    public function testYoutubeUrlWithoutScheme()
    {
        $content = $this->processor->processShortcodes('{youtube www.youtube.com/watch?v=siiEuhfdhf}', new \stdClass());
        $expected = '
<div class="youtube-container">
    <iframe
        src="https://www.youtube.com/embed/siiEuhfdhf?start=0"
        width="560"
        height="315"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        title="YouTube video player"
        referrerpolicy="strict-origin-when-cross-origin"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>';
        $this->assertEquals(trim($expected), trim($content));
    }
}
