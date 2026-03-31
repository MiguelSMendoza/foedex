<?php

declare(strict_types=1);

namespace App\Discovery\UI\Web\Controller;

use App\Knowledge\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Shared\UI\Api\ApiViewFactory;
use App\Taxonomy\Infrastructure\Persistence\Doctrine\CategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController
{
    #[Route('/api/home', name: 'api_home', methods: ['GET'])]
    public function __invoke(
        Request $request,
        PageRepository $pageRepository,
        CategoryRepository $categoryRepository,
        ApiViewFactory $apiViewFactory,
    ): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $limit = max(1, min(20, (int) $request->query->get('limit', 6)));
        $offset = max(0, (int) $request->query->get('offset', 0));
        $pages = $query !== ''
            ? $pageRepository->searchSlice($query, $limit + 1, $offset)
            : $pageRepository->findLatestUpdatedSlice($limit + 1, $offset);
        $hasMore = \count($pages) > $limit;

        if ($hasMore) {
            array_pop($pages);
        }

        return new JsonResponse([
            'query' => $query,
            'pages' => array_map($apiViewFactory->page(...), $pages),
            'offset' => $offset,
            'nextOffset' => $offset + \count($pages),
            'hasMore' => $hasMore,
            'categories' => array_map($apiViewFactory->category(...), $categoryRepository->findAllAlphabetical()),
        ]);
    }
}
