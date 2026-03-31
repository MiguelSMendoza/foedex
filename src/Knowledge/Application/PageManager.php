<?php

declare(strict_types=1);

namespace App\Knowledge\Application;

use App\Identity\Domain\User;
use App\Knowledge\Domain\Page;
use App\Knowledge\Domain\PageRevision;
use App\Knowledge\Domain\PageSlugRedirect;
use App\Knowledge\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Knowledge\UI\Web\Form\PageData;
use App\Shared\Application\SlugGenerator;
use App\Shared\Infrastructure\Markdown\MarkdownRenderer;
use App\Taxonomy\Domain\Category;
use App\Taxonomy\Infrastructure\Persistence\Doctrine\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

final class PageManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MarkdownRenderer $markdownRenderer,
        private readonly SlugGenerator $slugGenerator,
        private readonly CategoryRepository $categoryRepository,
        private readonly PageRepository $pageRepository,
    ) {
    }

    /**
     * @return list<Category>
     */
    public function resolveCategories(PageData $data, User $actor, string $newCategories): array
    {
        $resolved = [];

        foreach ($data->categories as $category) {
            $resolved[$category->getSlug()] = $category;
        }

        foreach (array_filter(array_map('trim', explode(',', $newCategories))) as $name) {
            $slug = $this->slugGenerator->slugify($name);
            $category = $this->categoryRepository->findOneBy(['slug' => $slug]);

            if (!$category instanceof Category) {
                $category = (new Category())
                    ->setName($name)
                    ->setSlug($slug)
                    ->setCreatedBy($actor);

                $this->entityManager->persist($category);
            }

            $resolved[$slug] = $category;
        }

        return array_values($resolved);
    }

    public function create(PageData $data, User $actor, string $newCategories): Page
    {
        $slug = trim($data->slug) !== ''
            ? $this->resolveUniqueSlug($data->slug)
            : $this->generateUniqueAutomaticSlug();
        $html = $this->markdownRenderer->toSanitizedHtml($data->markdown);

        $page = (new Page())
            ->setCurrentTitle($data->title)
            ->setCurrentSlug($slug)
            ->setCurrentExcerpt($data->excerpt)
            ->setCurrentMarkdown($data->markdown)
            ->setCurrentHtml($html)
            ->setCreatedBy($actor)
            ->setLastEditedBy($actor);

        foreach ($this->resolveCategories($data, $actor, $newCategories) as $category) {
            $page->addCategory($category);
        }

        $revision = $this->buildRevision($page, $actor, 1, $data->changeSummary);
        $page->addRevision($revision);

        $this->entityManager->persist($page);
        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        return $page;
    }

    public function update(Page $page, PageData $data, User $actor, string $newCategories): Page
    {
        $oldSlug = $page->getCurrentSlug();
        $newSlug = trim($data->slug) !== ''
            ? $this->resolveUniqueSlug($data->slug, $page->getId())
            : $oldSlug;

        $page
            ->setCurrentTitle($data->title)
            ->setCurrentSlug($newSlug)
            ->setCurrentExcerpt($data->excerpt)
            ->setCurrentMarkdown($data->markdown)
            ->setCurrentHtml($this->markdownRenderer->toSanitizedHtml($data->markdown))
            ->setLastEditedBy($actor)
            ->clearCategories();

        foreach ($this->resolveCategories($data, $actor, $newCategories) as $category) {
            $page->addCategory($category);
        }

        if ($oldSlug !== $newSlug) {
            $redirect = (new PageSlugRedirect())
                ->setPage($page)
                ->setOldSlug($oldSlug)
                ->setCreatedBy($actor);

            $this->entityManager->persist($redirect);
        }

        $revisionNumber = $page->getRevisions()->count() + 1;
        $revision = $this->buildRevision($page, $actor, $revisionNumber, $data->changeSummary);

        $page->addRevision($revision);
        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        return $page;
    }

    public function restore(Page $page, PageRevision $sourceRevision, User $actor): Page
    {
        $page
            ->setCurrentTitle($sourceRevision->getTitleSnapshot())
            ->setCurrentExcerpt($sourceRevision->getExcerptSnapshot())
            ->setCurrentMarkdown($sourceRevision->getMarkdownSnapshot())
            ->setCurrentHtml($sourceRevision->getHtmlSnapshot())
            ->setLastEditedBy($actor)
            ->clearCategories();

        foreach ($sourceRevision->getCategories() as $category) {
            $page->addCategory($category);
        }

        $revision = $this->buildRevision(
            $page,
            $actor,
            $page->getRevisions()->count() + 1,
            sprintf('Restaurada desde revisión #%d', $sourceRevision->getRevisionNumber()),
        )->setRestoredFromRevision($sourceRevision);

        $page->addRevision($revision);
        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        return $page;
    }

    private function buildRevision(Page $page, User $actor, int $revisionNumber, ?string $summary): PageRevision
    {
        return (new PageRevision())
            ->setPage($page)
            ->setRevisionNumber($revisionNumber)
            ->setTitleSnapshot($page->getCurrentTitle())
            ->setExcerptSnapshot($page->getCurrentExcerpt())
            ->setMarkdownSnapshot($page->getCurrentMarkdown())
            ->setHtmlSnapshot($page->getCurrentHtml())
            ->setChangeSummary($summary)
            ->setAuthor($actor)
            ->replaceCategories($page->getCategories());
    }

    private function resolveUniqueSlug(string $value, ?int $ignorePageId = null): string
    {
        $baseSlug = $this->slugGenerator->slugify($value);
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->pageRepository->slugExists($slug, $ignorePageId)) {
            $slug = sprintf('%s-%d', $baseSlug, $suffix);
            ++$suffix;
        }

        return $slug;
    }

    private function generateUniqueAutomaticSlug(): string
    {
        do {
            $slug = $this->slugGenerator->generateCode();
        } while ($this->pageRepository->slugExists($slug));

        return $slug;
    }
}
