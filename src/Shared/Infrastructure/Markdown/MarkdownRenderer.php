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
                ->allowElement('img', ['src', 'alt', 'title'])
        );
    }

    public function toSanitizedHtml(string $markdown): string
    {
        $html = (string) $this->converter->convert(trim($markdown));

        return $this->sanitizer->sanitize($html);
    }
}
