<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Identity\Domain\User;
use App\Knowledge\Domain\Page;
use App\Shared\UI\Api\ApiViewFactory;
use PHPUnit\Framework\TestCase;

final class ApiViewFactoryTest extends TestCase
{
    public function testPageExcerptPreservesRenderedHtmlLinks(): void
    {
        $user = (new User())
            ->setEmail('test@example.com')
            ->setDisplayName('Tester')
            ->setPassword('hash');

        $page = (new Page())
            ->setCurrentTitle('Demo')
            ->setCurrentSlug('demo')
            ->setCurrentMarkdown('[enlace](https://example.com)')
            ->setCurrentHtml('<p><a href="https://example.com">enlace</a></p><p>Segundo bloque</p>')
            ->setCreatedBy($user)
            ->setLastEditedBy($user);

        $view = (new ApiViewFactory())->page($page);

        self::assertStringContainsString('<a href="https://example.com">enlace</a>', $view['excerptHtml']);
        self::assertStringNotContainsString('Segundo bloque', $view['excerptHtml']);
    }
}
