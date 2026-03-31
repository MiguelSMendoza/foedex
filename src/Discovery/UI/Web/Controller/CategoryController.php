<?php

declare(strict_types=1);

namespace App\Discovery\UI\Web\Controller;

use App\Knowledge\Domain\Page;
use App\Taxonomy\Domain\Category;
use App\Shared\UI\Api\ApiViewFactory;
use App\Taxonomy\Infrastructure\Persistence\Doctrine\CategoryRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController
{
    #[Route('/api/categories', name: 'api_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository, ApiViewFactory $apiViewFactory): JsonResponse
    {
        return new JsonResponse([
            'categories' => array_map($apiViewFactory->category(...), $categoryRepository->findAllAlphabetical()),
        ]);
    }

    #[Route('/api/categories/{slug}', name: 'api_category_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Category $category,
        ApiViewFactory $apiViewFactory,
    ): JsonResponse
    {
        return new JsonResponse([
            'category' => $apiViewFactory->category($category),
            'pages' => array_map(
                $apiViewFactory->page(...),
                array_values(array_filter(
                    $category->getPages()->toArray(),
                    static fn (mixed $page): bool => $page instanceof Page && !$page->isArchived(),
                )),
            ),
        ]);
    }
}
