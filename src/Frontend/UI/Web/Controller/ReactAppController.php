<?php

declare(strict_types=1);

namespace App\Frontend\UI\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ReactAppController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    #[Route('/pages/{slug}', name: 'app_page_show', methods: ['GET'])]
    #[Route('/categories', name: 'app_category_index', methods: ['GET'])]
    #[Route('/categories/{slug}', name: 'app_category_show', methods: ['GET'])]
    #[Route('/login', name: 'app_login', methods: ['GET'])]
    #[Route('/register', name: 'app_register', methods: ['GET'])]
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    #[Route('/editor/new', name: 'app_page_new', methods: ['GET'])]
    #[Route('/editor/{slug}/edit', name: 'app_page_edit', methods: ['GET'])]
    public function __invoke(): Response
    {
        $appJs = $this->projectDir.'/public/app/app.js';
        $appCss = $this->projectDir.'/public/app/app.css';

        return $this->render('frontend/app.html.twig', [
            'app_js_version' => is_file($appJs) ? (string) filemtime($appJs) : 'dev',
            'app_css_version' => is_file($appCss) ? (string) filemtime($appCss) : 'dev',
        ]);
    }
}
