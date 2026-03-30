<?php

declare(strict_types=1);

namespace App\Discovery\UI\Web\Controller;

use App\Taxonomy\Domain\Category;
use App\Taxonomy\Infrastructure\Persistence\Doctrine\CategoryRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'app_category_index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAllAlphabetical(),
        ]);
    }

    #[Route('/categories/{slug}', name: 'app_category_show')]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }
}
