<?php

declare(strict_types=1);

namespace App\Knowledge\Application;

use App\Shared\Application\PlainTextSanitizer;
use App\Shared\Application\SafeUrlGuard;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LinkPreviewFetcher
{
    private HttpClientInterface $httpClient;

    public function __construct(
        ?HttpClientInterface $httpClient,
        private readonly SafeUrlGuard $safeUrlGuard,
        private readonly PlainTextSanitizer $plainTextSanitizer,
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create([
            'timeout' => 8,
            'max_redirects' => 5,
        ]);
    }

    public function fetch(string $url): LinkPreview
    {
        $this->safeUrlGuard->assertPublicHttpUrl($url);
        $platform = $this->detectPlatform($url);

        if ($platform === 'youtube') {
            $youtubePreview = $this->fetchYouTubePreview($url);

            if ($youtubePreview !== null) {
                return $youtubePreview;
            }
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml',
                'User-Agent' => 'FoedexBot/1.0',
            ],
        ]);

        $contentType = $response->getHeaders(false)['content-type'][0] ?? '';

        if (!str_contains(mb_strtolower($contentType), 'html')) {
            throw new \InvalidArgumentException('La URL no devuelve un documento HTML compatible.');
        }

        $html = mb_substr($response->getContent(), 0, 500000);

        return $this->parsePreview($url, $html);
    }

    public function parsePreview(string $url, string $html): LinkPreview
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);

        $title = $this->extractMeta($xpath, [
            "//meta[@property='og:title']/@content",
            "//meta[@name='twitter:title']/@content",
            '//title',
        ]) ?? $url;

        $description = $this->extractMeta($xpath, [
            "//meta[@property='og:description']/@content",
            "//meta[@name='description']/@content",
            "//meta[@name='twitter:description']/@content",
        ]);

        $image = $this->extractMeta($xpath, [
            "//meta[@property='og:image']/@content",
            "//meta[@name='twitter:image']/@content",
        ]);

        $resolvedImage = $image !== null ? $this->resolveUrl($url, $image) : null;

        if ($resolvedImage !== null) {
            try {
                $this->safeUrlGuard->assertPublicHttpUrl($resolvedImage);
            } catch (\InvalidArgumentException) {
                $resolvedImage = null;
            }
        }

        return new LinkPreview(
            url: $url,
            title: $this->plainTextSanitizer->sanitizeInline($title),
            description: $description !== null ? $this->plainTextSanitizer->sanitizeBlock($description) : null,
            imageUrl: $resolvedImage,
            platform: $platform = $this->detectPlatform($url),
            embedUrl: $platform === 'youtube' ? $this->buildYouTubeEmbedUrl($url) : null,
        );
    }

    /**
     * @param list<string> $queries
     */
    private function extractMeta(\DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $query) {
            $result = $xpath->query($query);

            if ($result instanceof \DOMNodeList && $result->length > 0) {
                $value = trim((string) $result->item(0)?->nodeValue);

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function resolveUrl(string $baseUrl, string $candidate): string
    {
        if (str_starts_with($candidate, 'http://') || str_starts_with($candidate, 'https://')) {
            return $candidate;
        }

        $base = parse_url($baseUrl);

        if (str_starts_with($candidate, '//')) {
            return sprintf('%s:%s', $base['scheme'] ?? 'https', $candidate);
        }

        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';

        if ($host === '') {
            return $candidate;
        }

        if (str_starts_with($candidate, '/')) {
            return sprintf('%s://%s%s', $scheme, $host, $candidate);
        }

        $path = $base['path'] ?? '/';
        $directory = rtrim(str_contains($path, '/') ? dirname($path) : '', '/');

        return sprintf('%s://%s%s/%s', $scheme, $host, $directory !== '' ? '/'.$directory : '', ltrim($candidate, '/'));
    }

    private function detectPlatform(string $url): string
    {
        $host = mb_strtolower((string) (parse_url($url, \PHP_URL_HOST) ?? ''));

        if ($this->hostMatches($host, ['instagram.com'])) {
            return 'instagram';
        }

        if ($this->hostMatches($host, ['x.com', 'twitter.com'])) {
            return 'x';
        }

        if ($this->hostMatches($host, ['youtube.com', 'youtu.be'])) {
            return 'youtube';
        }

        return 'generic';
    }

    /**
     * @param list<string> $candidates
     */
    private function hostMatches(string $host, array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            if ($host === $candidate || str_ends_with($host, '.'.$candidate)) {
                return true;
            }
        }

        return false;
    }

    private function buildYouTubeEmbedUrl(string $url): ?string
    {
        $parts = parse_url($url);
        $host = mb_strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $videoId = null;

        if ($host === 'youtu.be' || str_ends_with($host, '.youtu.be')) {
            $videoId = $path !== '' ? explode('/', $path)[0] : null;
        } elseif ($this->hostMatches($host, ['youtube.com'])) {
            parse_str((string) ($parts['query'] ?? ''), $query);

            if (($query['v'] ?? null) !== null) {
                $videoId = (string) $query['v'];
            } elseif (str_starts_with($path, 'shorts/')) {
                $videoId = explode('/', mb_substr($path, 7))[0] ?? null;
            } elseif (str_starts_with($path, 'embed/')) {
                $videoId = explode('/', mb_substr($path, 6))[0] ?? null;
            }
        }

        if ($videoId === null || preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) !== 1) {
            return null;
        }

        return sprintf('https://www.youtube.com/embed/%s?feature=oembed', $videoId);
    }

    private function fetchYouTubePreview(string $url): ?LinkPreview
    {
        try {
            $response = $this->httpClient->request('GET', 'https://www.youtube.com/oembed', [
                'query' => [
                    'url' => $url,
                    'format' => 'json',
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'FoedexBot/1.0',
                ],
            ]);

            $payload = $response->toArray(false);
            $title = $this->plainTextSanitizer->sanitizeInline((string) ($payload['title'] ?? ''));
            $thumbnailUrl = isset($payload['thumbnail_url']) ? trim((string) $payload['thumbnail_url']) : null;

            if ($thumbnailUrl !== null && $thumbnailUrl !== '') {
                try {
                    $this->safeUrlGuard->assertPublicHttpUrl($thumbnailUrl);
                } catch (\InvalidArgumentException) {
                    $thumbnailUrl = null;
                }
            } else {
                $thumbnailUrl = null;
            }

            if ($title === '') {
                return null;
            }

            return new LinkPreview(
                url: $url,
                title: $title,
                description: null,
                imageUrl: $thumbnailUrl,
                platform: 'youtube',
                embedUrl: $this->buildYouTubeEmbedUrl($url),
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
