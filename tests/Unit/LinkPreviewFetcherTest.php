<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Knowledge\Application\LinkPreviewFetcher;
use App\Shared\Application\PlainTextSanitizer;
use App\Shared\Application\SafeUrlGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LinkPreviewFetcherTest extends TestCase
{
    public function testParsePreviewExtractsAndSanitizesMetadata(): void
    {
        $fetcher = new LinkPreviewFetcher(null, new SafeUrlGuard(), new PlainTextSanitizer());
        $html = <<<'HTML'
<!doctype html>
<html>
<head>
    <title>Fallback title</title>
    <meta property="og:title" content="  Guia <b>segura</b>  ">
    <meta name="description" content="Descripcion   larga con    espacios">
    <meta property="og:image" content="/cover.jpg">
</head>
<body></body>
</html>
HTML;

        $preview = $fetcher->parsePreview('https://example.com/posts/demo', $html);

        self::assertSame('Guia segura', $preview->title);
        self::assertSame('Descripcion larga con espacios', $preview->description);
        self::assertSame('https://example.com/cover.jpg', $preview->imageUrl);
        self::assertSame('generic', $preview->platform);
    }

    public function testParsePreviewDetectsSocialPlatforms(): void
    {
        $fetcher = new LinkPreviewFetcher(null, new SafeUrlGuard(), new PlainTextSanitizer());

        $instagramPreview = $fetcher->parsePreview('https://www.instagram.com/p/demo', '<html><head><title>Instagram</title></head></html>');
        $xPreview = $fetcher->parsePreview('https://x.com/demo/status/1', '<html><head><title>X</title></head></html>');
        $youtubePreview = $fetcher->parsePreview('https://www.youtube.com/watch?v=dQw4w9WgXcQ', '<html><head><title>YouTube</title></head></html>');

        self::assertSame('instagram', $instagramPreview->platform);
        self::assertSame('x', $xPreview->platform);
        self::assertSame('youtube', $youtubePreview->platform);
        self::assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ?feature=oembed', $youtubePreview->embedUrl);
    }

    public function testFetchUsesYouTubeOEmbedWhenAvailable(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode([
                'title' => 'Video de prueba con un titulo bastante largo para truncar',
                'thumbnail_url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            ], \JSON_THROW_ON_ERROR), [
                'response_headers' => ['content-type: application/json'],
            ]),
        ]);

        $fetcher = new LinkPreviewFetcher($client, new SafeUrlGuard(), new PlainTextSanitizer());
        $preview = $fetcher->fetch('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        self::assertSame('Video de prueba con un titulo bastante largo para truncar', $preview->title);
        self::assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $preview->imageUrl);
        self::assertSame('youtube', $preview->platform);
        self::assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ?feature=oembed', $preview->embedUrl);
    }
}
