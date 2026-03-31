<?php

declare(strict_types=1);

namespace App\Knowledge\UI\Web\Controller;

use App\Identity\Domain\User;
use App\Knowledge\Application\PageManager;
use App\Knowledge\Domain\Page;
use App\Knowledge\Domain\PageRevision;
use App\Knowledge\Domain\PageSlugRedirect;
use App\Knowledge\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Knowledge\UI\Web\Form\PageData;
use App\Shared\UI\Api\ApiViewFactory;
use App\Taxonomy\Infrastructure\Persistence\Doctrine\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PageController extends AbstractController
{
    #[Route('/api/pages', name: 'api_page_index', methods: ['GET'])]
    public function index(Request $request, PageRepository $pageRepository, ApiViewFactory $apiViewFactory): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $pages = $query !== '' ? $pageRepository->search($query) : $pageRepository->findLatestUpdated(100);

        return new JsonResponse([
            'pages' => array_map($apiViewFactory->page(...), $pages),
        ]);
    }

    #[Route('/api/pages', name: 'api_page_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        PageManager $pageManager,
        ApiViewFactory $apiViewFactory,
        CategoryRepository $categoryRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $payload = $request->toArray();
        $data = $this->hydratePageData($payload, $categoryRepository);
        $errors = $this->validatePayload($data);

        if ($errors !== []) {
            return new JsonResponse(['errors' => $errors], 422);
        }

        $page = $pageManager->create($data, $user, (string) ($payload['newCategories'] ?? ''));

        return new JsonResponse([
            'message' => 'Página creada.',
            'page' => $apiViewFactory->page($page),
        ], 201);
    }

    #[Route('/api/pages/{slug}', name: 'api_page_show', methods: ['GET'])]
    public function show(string $slug, EntityManagerInterface $entityManager, ApiViewFactory $apiViewFactory): JsonResponse
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = $entityManager->getRepository(Page::class);
        $page = $pageRepository->findActiveBySlug($slug);

        if (!$page instanceof Page) {
            $redirect = $entityManager->getRepository(PageSlugRedirect::class)->findOneBy(['oldSlug' => $slug]);

            if ($redirect instanceof PageSlugRedirect) {
                return new JsonResponse([
                    'redirectTo' => sprintf('/pages/%s', $redirect->getPage()->getCurrentSlug()),
                ], 409);
            }

            return new JsonResponse(['message' => 'Página no encontrada.'], 404);
        }

        return new JsonResponse([
            'page' => $apiViewFactory->page($page),
            'history' => array_map(
                $apiViewFactory->revision(...),
                array_slice($page->getRevisions()->toArray(), 0, 10),
            ),
        ]);
    }

    #[Route('/api/pages/{slug}/editor', name: 'api_page_editor', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function editor(string $slug, EntityManagerInterface $entityManager, ApiViewFactory $apiViewFactory): JsonResponse
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = $entityManager->getRepository(Page::class);
        $page = $pageRepository->findActiveBySlug($slug);

        if (!$page instanceof Page) {
            return new JsonResponse(['message' => 'Página no encontrada.'], 404);
        }

        return new JsonResponse([
            'page' => $apiViewFactory->page($page),
            'editor' => [
                'title' => $page->getCurrentTitle(),
                'slug' => $page->getCurrentSlug(),
                'excerpt' => $page->getCurrentExcerpt(),
                'markdown' => $page->getCurrentMarkdown(),
                'categories' => array_map(
                    static fn ($category) => $category->getSlug(),
                    $page->getCategories()->toArray(),
                ),
            ],
        ]);
    }

    #[Route('/api/pages/{slug}', name: 'api_page_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(
        string $slug,
        Request $request,
        EntityManagerInterface $entityManager,
        PageManager $pageManager,
        ApiViewFactory $apiViewFactory,
    ): JsonResponse {
        /** @var PageRepository $pageRepository */
        $pageRepository = $entityManager->getRepository(Page::class);
        $page = $pageRepository->findActiveBySlug($slug);

        if (!$page instanceof Page) {
            return new JsonResponse(['message' => 'Página no encontrada.'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        $payload = $request->toArray();
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $entityManager->getRepository(\App\Taxonomy\Domain\Category::class);
        $data = $this->hydratePageData($payload, $categoryRepository);
        $errors = $this->validatePayload($data);

        if ($errors !== []) {
            return new JsonResponse(['errors' => $errors], 422);
        }

        $pageManager->update($page, $data, $user, (string) ($payload['newCategories'] ?? ''));

        return new JsonResponse([
            'message' => 'Página actualizada.',
            'page' => $apiViewFactory->page($page),
        ]);
    }

    #[Route('/api/pages/{slug}/history', name: 'api_page_history', methods: ['GET'])]
    public function history(string $slug, EntityManagerInterface $entityManager, ApiViewFactory $apiViewFactory): JsonResponse
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = $entityManager->getRepository(Page::class);
        $page = $pageRepository->findActiveBySlug($slug);

        if (!$page instanceof Page) {
            return new JsonResponse(['message' => 'Página no encontrada.'], 404);
        }

        return new JsonResponse([
            'page' => $apiViewFactory->page($page),
            'history' => array_map($apiViewFactory->revision(...), $page->getRevisions()->toArray()),
        ]);
    }

    #[Route('/api/pages/{slug}/revisions/{revision<\d+>}/restore', name: 'api_page_restore', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function restore(
        string $slug,
        int $revision,
        EntityManagerInterface $entityManager,
        PageManager $pageManager,
        ApiViewFactory $apiViewFactory,
    ): JsonResponse {
        /** @var PageRepository $pageRepository */
        $pageRepository = $entityManager->getRepository(Page::class);
        $page = $pageRepository->findActiveBySlug($slug);

        if (!$page instanceof Page) {
            return new JsonResponse(['message' => 'Página no encontrada.'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        foreach ($page->getRevisions() as $candidate) {
            if ($candidate instanceof PageRevision && $candidate->getRevisionNumber() === $revision) {
                $pageManager->restore($page, $candidate, $user);

                return new JsonResponse([
                    'message' => sprintf('La revisión #%d se ha restaurado.', $revision),
                    'page' => $apiViewFactory->page($page),
                ]);
            }
        }

        return new JsonResponse(['message' => 'Revisión no encontrada.'], 404);
    }

    #[Route('/api/pages/{slug}', name: 'api_page_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        string $slug,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var PageRepository $pageRepository */
        $pageRepository = $entityManager->getRepository(Page::class);
        $page = $pageRepository->findActiveBySlug($slug);

        if (!$page instanceof Page) {
            return new JsonResponse(['message' => 'Página no encontrada.'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($page->getCreatedBy()->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Solo puedes borrar páginas creadas por ti.'], 403);
        }

        $page->archive();
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Página archivada.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydratePageData(array $payload, CategoryRepository $categoryRepository): PageData
    {
        $data = new PageData();
        $data->title = trim((string) ($payload['title'] ?? ''));
        $data->slug = trim((string) ($payload['slug'] ?? ''));
        $data->excerpt = array_key_exists('excerpt', $payload) ? (string) $payload['excerpt'] : null;
        $data->markdown = (string) ($payload['markdown'] ?? '');
        $data->changeSummary = array_key_exists('changeSummary', $payload) ? (string) $payload['changeSummary'] : null;

        foreach (($payload['categories'] ?? []) as $slug) {
            $category = $categoryRepository->findOneBy(['slug' => (string) $slug]);

            if ($category !== null) {
                $data->categories->add($category);
            }
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function validatePayload(PageData $data): array
    {
        $errors = [];

        if (mb_strlen(trim($data->title)) < 3) {
            $errors[] = 'El título debe tener al menos 3 caracteres.';
        }

        if (mb_strlen(trim($data->title)) > 60) {
            $errors[] = 'El título no puede superar los 60 caracteres.';
        }

        if (mb_strlen(trim($data->markdown)) < 20) {
            $errors[] = 'El contenido Markdown debe tener al menos 20 caracteres.';
        }

        return $errors;
    }
}
