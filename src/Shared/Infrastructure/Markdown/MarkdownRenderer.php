<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Markdown;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class MarkdownRenderer
{
    private CommonMarkConverter $converter;
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $environment = new \League\CommonMark\Environment\Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new DisallowedRawHtmlExtension());

        $this->converter = new CommonMarkConverter([], $environment);
        $this->sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig())
                ->allowSafeElements()
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                ->allowElement('img', ['src', 'alt', 'title'])
                ->allowElement('iframe', ['src', 'title', 'loading', 'allow', 'allowfullscreen', 'referrerpolicy', 'width', 'height', 'frameborder'])
                ->allowElement('div', ['class'])
        );
    }

    public function toSanitizedHtml(string $markdown): string
    {
        $html = (string) $this->converter->convert(trim($markdown));
        $html = $this->renderEmbeds($html);
        $html = $this->decorateLinks($html);

        return $this->sanitizer->sanitize($html);
    }

    private function renderEmbeds(string $html): string
    {
        return preg_replace_callback(
            '/<p>\[\[youtube:(https:\/\/www\.youtube\.com\/embed\/[A-Za-z0-9_-]{11}(?:\?feature=oembed)?)\]\]<\/p>/',
            static fn (array $matches): string => sprintf(
                '<div class="video-embed"><iframe width="560" height="315" src="%s" title="YouTube video player" frameborder="0" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>',
                htmlspecialchars($matches[1], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
            ),
            $html,
        ) ?? $html;
    }

    private function decorateLinks(string $html): string
    {
        if (!str_contains($html, '<a ')) {
            return $html;
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach ($dom->getElementsByTagName('a') as $link) {
            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'noopener noreferrer');
        }

        $result = $dom->saveHTML();

        return $result !== false ? $result : $html;
    }
}
