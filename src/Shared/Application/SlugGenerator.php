<?php

declare(strict_types=1);

namespace App\Shared\Application;

use Symfony\Component\String\Slugger\AsciiSlugger;

final class SlugGenerator
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger('es');
    }

    public function slugify(string $value): string
    {
        $slug = (string) $this->slugger->slug(mb_strtolower(trim($value)));

        return trim($slug, '-') ?: 'sin-titulo';
    }
}
