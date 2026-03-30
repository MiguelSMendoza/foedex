<?php

declare(strict_types=1);

namespace App\Knowledge\Domain;

use App\Identity\Domain\User;
use App\Taxonomy\Domain\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class PageRevision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'revisions')]
    #[ORM\JoinColumn(nullable: false)]
    private Page $page;

    #[ORM\Column]
    private int $revisionNumber = 1;

    #[ORM\Column(length: 160)]
    private string $titleSnapshot = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $excerptSnapshot = null;

    #[ORM\Column(type: 'text')]
    private string $markdownSnapshot = '';

    #[ORM\Column(type: 'text')]
    private string $htmlSnapshot = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $changeSummary = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $author;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $restoredFromRevision = null;

    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'page_revision_category')]
    private Collection $categories;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function setPage(Page $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getRevisionNumber(): int
    {
        return $this->revisionNumber;
    }

    public function setRevisionNumber(int $revisionNumber): self
    {
        $this->revisionNumber = $revisionNumber;

        return $this;
    }

    public function getTitleSnapshot(): string
    {
        return $this->titleSnapshot;
    }

    public function setTitleSnapshot(string $titleSnapshot): self
    {
        $this->titleSnapshot = $titleSnapshot;

        return $this;
    }

    public function getExcerptSnapshot(): ?string
    {
        return $this->excerptSnapshot;
    }

    public function setExcerptSnapshot(?string $excerptSnapshot): self
    {
        $this->excerptSnapshot = $excerptSnapshot;

        return $this;
    }

    public function getMarkdownSnapshot(): string
    {
        return $this->markdownSnapshot;
    }

    public function setMarkdownSnapshot(string $markdownSnapshot): self
    {
        $this->markdownSnapshot = $markdownSnapshot;

        return $this;
    }

    public function getHtmlSnapshot(): string
    {
        return $this->htmlSnapshot;
    }

    public function setHtmlSnapshot(string $htmlSnapshot): self
    {
        $this->htmlSnapshot = $htmlSnapshot;

        return $this;
    }

    public function getChangeSummary(): ?string
    {
        return $this->changeSummary;
    }

    public function setChangeSummary(?string $changeSummary): self
    {
        $this->changeSummary = $changeSummary !== null ? trim($changeSummary) : null;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRestoredFromRevision(): ?self
    {
        return $this->restoredFromRevision;
    }

    public function setRestoredFromRevision(?self $restoredFromRevision): self
    {
        $this->restoredFromRevision = $restoredFromRevision;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @param iterable<Category> $categories
     */
    public function replaceCategories(iterable $categories): self
    {
        $this->categories->clear();

        foreach ($categories as $category) {
            if (!$this->categories->contains($category)) {
                $this->categories->add($category);
            }
        }

        return $this;
    }
}
