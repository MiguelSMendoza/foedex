<?php

declare(strict_types=1);

namespace App\Shared\UI\Api;

use App\Identity\Domain\User;
use App\Knowledge\Domain\MediaAsset;
use App\Knowledge\Domain\Page;
use App\Knowledge\Domain\PageRevision;
use App\Taxonomy\Domain\Category;

final class ApiViewFactory
{
    /**
     * @return array{id:int,email:string,displayName:string,bio:?string,createdAt:string}
     */
    public function user(User $user): array
    {
        return [
            'id' => (int) $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'bio' => $user->getBio(),
            'createdAt' => $user->getCreatedAt()->format(\DATE_ATOM),
        ];
    }

    /**
     * @return array{id:int,name:string,slug:string,description:?string,pageCount:int}
     */
    public function category(Category $category): array
    {
        $activePageCount = count(array_filter(
            $category->getPages()->toArray(),
            static fn (mixed $page): bool => $page instanceof Page && !$page->isArchived(),
        ));

        return [
            'id' => (int) $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'description' => $category->getDescription(),
            'pageCount' => $activePageCount,
        ];
    }

    /**
     * @return array{
     *   id:int,
     *   slug:string,
     *   title:string,
     *   excerpt:?string,
     *   excerptHtml:string,
     *   html:string,
     *   media:list<array{id:int,kind:string,originalFilename:string,url:string,thumbnailUrl:?string,mimeType:string,size:int,width:?int,height:?int}>,
     *   createdAt:string,
     *   updatedAt:string,
     *   createdBy:array{id:int,email:string,displayName:string,bio:?string,createdAt:string},
     *   lastEditedBy:array{id:int,email:string,displayName:string,bio:?string,createdAt:string},
     *   categories:list<array{id:int,name:string,slug:string,description:?string,pageCount:int}>
     * }
     */
    public function page(Page $page): array
    {
        $excerpt = $page->getCurrentExcerpt();

        return [
            'id' => (int) $page->getId(),
            'slug' => $page->getCurrentSlug(),
            'title' => $page->getCurrentTitle(),
            'excerpt' => $excerpt,
            'excerptHtml' => $excerpt !== null && $excerpt !== ''
                ? sprintf('<p>%s</p>', nl2br(htmlspecialchars($excerpt, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')))
                : $this->summarizeHtml($page->getCurrentHtml()),
            'html' => $page->getCurrentHtml(),
            'media' => array_map($this->media(...), $page->getMediaAssets()->toArray()),
            'createdAt' => $page->getCreatedAt()->format(\DATE_ATOM),
            'updatedAt' => $page->getUpdatedAt()->format(\DATE_ATOM),
            'createdBy' => $this->user($page->getCreatedBy()),
            'lastEditedBy' => $this->user($page->getLastEditedBy()),
            'categories' => array_map($this->category(...), $page->getCategories()->toArray()),
        ];
    }

    /**
     * @return array{id:int,kind:string,originalFilename:string,url:string,thumbnailUrl:?string,mimeType:string,size:int,width:?int,height:?int}
     */
    public function media(MediaAsset $mediaAsset): array
    {
        return [
            'id' => (int) $mediaAsset->getId(),
            'kind' => $mediaAsset->getKind(),
            'originalFilename' => $mediaAsset->getOriginalFilename(),
            'url' => $mediaAsset->getPublicPath(),
            'thumbnailUrl' => $mediaAsset->getThumbnailPath(),
            'mimeType' => $mediaAsset->getMimeType(),
            'size' => $mediaAsset->getSize(),
            'width' => $mediaAsset->getWidth(),
            'height' => $mediaAsset->getHeight(),
        ];
    }

    /**
     * @return array{
     *   id:int,
     *   revisionNumber:int,
     *   title:string,
     *   excerpt:?string,
     *   html:string,
     *   createdAt:string,
     *   changeSummary:?string,
     *   author:array{id:int,email:string,displayName:string,bio:?string,createdAt:string}
     * }
     */
    public function revision(PageRevision $revision): array
    {
        return [
            'id' => (int) $revision->getId(),
            'revisionNumber' => $revision->getRevisionNumber(),
            'title' => $revision->getTitleSnapshot(),
            'excerpt' => $revision->getExcerptSnapshot(),
            'html' => $revision->getHtmlSnapshot(),
            'createdAt' => $revision->getCreatedAt()->format(\DATE_ATOM),
            'changeSummary' => $revision->getChangeSummary(),
            'author' => $this->user($revision->getAuthor()),
        ];
    }

    private function summarizeHtml(string $html): string
    {
        $trimmedHtml = trim($html);

        if ($trimmedHtml === '') {
            return '';
        }

        if (preg_match('/<(p|ul|ol|pre|blockquote|table|h1|h2|h3|h4|h5|h6)\b[^>]*>.*?<\/\1>/si', $trimmedHtml, $matches) === 1) {
            return $matches[0];
        }

        return $trimmedHtml;
    }
}
