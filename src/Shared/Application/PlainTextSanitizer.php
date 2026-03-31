<?php

declare(strict_types=1);

namespace App\Shared\Application;

final class PlainTextSanitizer
{
    public function sanitizeInline(string $value): string
    {
        $clean = strip_tags(html_entity_decode($value, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? '';

        return trim($clean);
    }

    public function sanitizeBlock(string $value): string
    {
        $clean = strip_tags(html_entity_decode($value, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
        $clean = preg_replace("/\r\n?/", "\n", $clean) ?? '';
        $clean = preg_replace('/[^\S\n]+/u', ' ', $clean) ?? '';
        $clean = preg_replace("/[ \t]+\n/", "\n", $clean) ?? '';
        $clean = preg_replace("/\n{3,}/", "\n\n", $clean) ?? '';

        return trim($clean);
    }
}
