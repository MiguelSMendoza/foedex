<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Application\SlugGenerator;
use PHPUnit\Framework\TestCase;

final class SlugGeneratorTest extends TestCase
{
    public function testSlugifyNormalizesSpanishText(): void
    {
        $slugger = new SlugGenerator();

        self::assertSame('arquitectura-symfony-y-mysql', $slugger->slugify('Arquitectura Symfony y MySQL'));
        self::assertSame('categoria-con-n', $slugger->slugify('Categoría con ñ'));
    }

    public function testGenerateCodeReturnsTwelveAlphanumericCharacters(): void
    {
        $slugger = new SlugGenerator();
        $code = $slugger->generateCode();

        self::assertMatchesRegularExpression('/^[a-z0-9]{12}$/', $code);
    }
}
