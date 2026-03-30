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
use App\Knowledge\UI\Web\Form\PageType;
use App\Shared\Infrastructure\Markdown\DiffRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PageController extends AbstractController
{
    #[Route('/pages', name: 'app_page_index')]
    public function index(PageRepository $pageRepository): Response
    {
        return $this->render('page/index.html.twig', [
            'pages' => $pageRepository->findLatestUpdated(100),
        ]);
    }

    #[Route('/pages/new', name: 'app_page_new')]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, PageManager $pageManager): Response
    {
        $data = new PageData();
        $form = $this->createForm(PageType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $page = $pageManager->create($data, $user, (string) $form->get('newCategories')->getData());

            $this->addFlash('success', 'Página creada.');

            return $this->redirectToRoute('app_page_show', ['slug' => $page->getCurrentSlug()]);
        }

        return $this->render('page/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
            'preview_html' => null,
        ]);
    }

    #[Route('/pages/{slug}/edit', name: 'app_page_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(
        #[MapEntity(mapping: ['slug' => 'currentSlug'])] Page $page,
        Request $request,
        PageManager $pageManager,
    ): Response
    {
        $data = new PageData();
        $data->title = $page->getCurrentTitle();
        $data->slug = $page->getCurrentSlug();
        $data->excerpt = $page->getCurrentExcerpt();
        $data->markdown = $page->getCurrentMarkdown();

        foreach ($page->getCategories() as $category) {
            $data->categories->add($category);
        }

        $form = $this->createForm(PageType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $pageManager->update($page, $data, $user, (string) $form->get('newCategories')->getData());

            $this->addFlash('success', 'Página actualizada.');

            return $this->redirectToRoute('app_page_show', ['slug' => $page->getCurrentSlug()]);
        }

        return $this->render('page/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
            'page' => $page,
            'preview_html' => $page->getCurrentHtml(),
        ]);
    }

    #[Route('/pages/{slug}', name: 'app_page_show')]
    public function show(string $slug, EntityManagerInterface $entityManager, DiffRenderer $diffRenderer): Response
    {
        $page = $entityManager->getRepository(Page::class)->findOneBy(['currentSlug' => $slug]);

        if (!$page instanceof Page) {
            $redirect = $entityManager->getRepository(PageSlugRedirect::class)->findOneBy(['oldSlug' => $slug]);

            if ($redirect instanceof PageSlugRedirect) {
                return $this->redirectToRoute('app_page_show', ['slug' => $redirect->getPage()->getCurrentSlug()], 301);
            }

            throw $this->createNotFoundException();
        }

        $revisions = $page->getRevisions()->toArray();
        $latestDiff = null;

        if (\count($revisions) >= 2 && $revisions[0] instanceof PageRevision && $revisions[1] instanceof PageRevision) {
            $latestDiff = $diffRenderer->render($revisions[1]->getMarkdownSnapshot(), $revisions[0]->getMarkdownSnapshot());
        }

        return $this->render('page/show.html.twig', [
            'page' => $page,
            'revisions' => $revisions,
            'latest_diff' => $latestDiff,
        ]);
    }

    #[Route('/pages/{slug}/history', name: 'app_page_history')]
    public function history(#[MapEntity(mapping: ['slug' => 'currentSlug'])] Page $page): Response
    {
        return $this->render('page/history.html.twig', [
            'page' => $page,
            'revisions' => $page->getRevisions(),
        ]);
    }

    #[Route('/pages/{slug}/revisions/{revision<\d+>}', name: 'app_page_revision')]
    public function revision(
        #[MapEntity(mapping: ['slug' => 'currentSlug'])] Page $page,
        int $revision,
        DiffRenderer $diffRenderer,
    ): Response
    {
        /** @var PageRevision|null $currentRevision */
        $currentRevision = null;
        /** @var PageRevision|null $previousRevision */
        $previousRevision = null;

        foreach ($page->getRevisions() as $candidate) {
            if ($candidate->getRevisionNumber() === $revision) {
                $currentRevision = $candidate;
            }

            if ($candidate->getRevisionNumber() === $revision - 1) {
                $previousRevision = $candidate;
            }
        }

        if (!$currentRevision instanceof PageRevision) {
            throw $this->createNotFoundException();
        }

        return $this->render('page/revision.html.twig', [
            'page' => $page,
            'revision' => $currentRevision,
            'diff' => $previousRevision instanceof PageRevision
                ? $diffRenderer->render($previousRevision->getMarkdownSnapshot(), $currentRevision->getMarkdownSnapshot())
                : null,
        ]);
    }

    #[Route('/pages/{slug}/revisions/{revision<\d+>}/restore', name: 'app_page_restore', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function restore(
        #[MapEntity(mapping: ['slug' => 'currentSlug'])] Page $page,
        int $revision,
        Request $request,
        PageManager $pageManager,
    ): Response
    {
        if (!$this->isCsrfTokenValid('restore_revision_'.$page->getId().'_'.$revision, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        foreach ($page->getRevisions() as $candidate) {
            if ($candidate->getRevisionNumber() === $revision) {
                $pageManager->restore($page, $candidate, $user);
                $this->addFlash('success', sprintf('La revisión #%d se ha restaurado.', $revision));

                return $this->redirectToRoute('app_page_show', ['slug' => $page->getCurrentSlug()]);
            }
        }

        throw $this->createNotFoundException();
    }
}
