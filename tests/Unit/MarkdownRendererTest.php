<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Infrastructure\Markdown\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class MarkdownRendererTest extends TestCase
{
    public function testRenderedLinksAlwaysOpenInNewTab(): void
    {
        $renderer = new MarkdownRenderer();

        $html = $renderer->toSanitizedHtml('[enlace](https://example.com)');

        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testYoutubeTokenRendersAsIframe(): void
    {
        $renderer = new MarkdownRenderer();

        $html = $renderer->toSanitizedHtml('[[youtube:https://www.youtube.com/embed/dQw4w9WgXcQ?feature=oembed]]');

        self::assertStringContainsString('<iframe', $html);
        self::assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ?feature&#61;oembed', $html);
        self::assertStringContainsString('class="video-embed"', $html);
    }
}
