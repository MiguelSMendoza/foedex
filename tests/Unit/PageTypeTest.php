<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Knowledge\UI\Web\Form\PageData;
use App\Knowledge\UI\Web\Form\PageType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

final class PageTypeTest extends KernelTestCase
{
    public function testOptionalSlugDoesNotBecomeNull(): void
    {
        self::bootKernel();

        /** @var FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get(FormFactoryInterface::class);
        $form = $formFactory->create(PageType::class, new PageData());

        $form->submit([
            'title' => 'Pagina de prueba',
            'slug' => null,
            'excerpt' => '',
            'markdown' => str_repeat('contenido ', 3),
            'changeSummary' => '',
            'categories' => [],
            'newCategories' => '',
        ]);

        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertInstanceOf(PageData::class, $data);
        self::assertSame('', $data->slug);
    }
}
