<?php

declare(strict_types=1);

namespace App\Shared\Application;

use Symfony\Component\String\Slugger\AsciiSlugger;

final class SlugGenerator
{
    private const DEFAULT_MAX_LENGTH = 160;
    private const CODE_ALPHABET = 'abcdefghijklmnopqrstuvwxyz0123456789';

    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger('es');
    }

    public function slugify(string $value, int $maxLength = self::DEFAULT_MAX_LENGTH): string
    {
        $slug = (string) $this->slugger->slug(mb_strtolower(trim($value)));
        $slug = trim(mb_substr($slug, 0, $maxLength), '-');

        return trim($slug, '-') ?: 'sin-titulo';
    }

    public function generateCode(int $length = 12): string
    {
        $alphabetLength = mb_strlen(self::CODE_ALPHABET);
        $code = '';

        for ($index = 0; $index < $length; ++$index) {
            $code .= self::CODE_ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $code;
    }
}
