<?php

declare(strict_types=1);

namespace App\Discovery\UI\Web\Controller;

use App\Knowledge\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Taxonomy\Infrastructure\Persistence\Doctrine\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function __invoke(Request $request, PageRepository $pageRepository, CategoryRepository $categoryRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $pages = $query !== '' ? $pageRepository->search($query) : $pageRepository->findLatestUpdated(20);

        return $this->render('page/home.html.twig', [
            'pages' => $pages,
            'categories' => $categoryRepository->findAllAlphabetical(),
            'query' => $query,
        ]);
    }
}
