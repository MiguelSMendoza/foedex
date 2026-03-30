<?php

declare(strict_types=1);

namespace App\Knowledge\Domain;

use App\Identity\Domain\User;
use App\Knowledge\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Taxonomy\Domain\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['currentSlug'], message: 'Ya existe una página con ese slug.')]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160, unique: true)]
    private string $currentSlug = '';

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 160)]
    private string $currentTitle = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $currentExcerpt = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $currentMarkdown = '';

    #[ORM\Column(type: 'text')]
    private string $currentHtml = '';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $lastEditedBy;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column]
    private bool $isArchived = false;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'pages')]
    #[ORM\JoinTable(name: 'page_category')]
    private Collection $categories;

    #[ORM\OneToMany(mappedBy: 'page', targetEntity: PageRevision::class, orphanRemoval: true)]
    #[ORM\OrderBy(['revisionNumber' => 'DESC'])]
    private Collection $revisions;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->categories = new ArrayCollection();
        $this->revisions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrentSlug(): string
    {
        return $this->currentSlug;
    }

    public function setCurrentSlug(string $currentSlug): self
    {
        $this->currentSlug = trim($currentSlug);

        return $this;
    }

    public function getCurrentTitle(): string
    {
        return $this->currentTitle;
    }

    public function setCurrentTitle(string $currentTitle): self
    {
        $this->currentTitle = trim($currentTitle);

        return $this;
    }

    public function getCurrentExcerpt(): ?string
    {
        return $this->currentExcerpt;
    }

    public function setCurrentExcerpt(?string $currentExcerpt): self
    {
        $this->currentExcerpt = $currentExcerpt !== null ? trim($currentExcerpt) : null;

        return $this;
    }

    public function getCurrentMarkdown(): string
    {
        return $this->currentMarkdown;
    }

    public function setCurrentMarkdown(string $currentMarkdown): self
    {
        $this->currentMarkdown = trim($currentMarkdown);

        return $this;
    }

    public function getCurrentHtml(): string
    {
        return $this->currentHtml;
    }

    public function setCurrentHtml(string $currentHtml): self
    {
        $this->currentHtml = $currentHtml;

        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getLastEditedBy(): User
    {
        return $this->lastEditedBy;
    }

    public function setLastEditedBy(User $lastEditedBy): self
    {
        $this->lastEditedBy = $lastEditedBy;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): self
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function clearCategories(): self
    {
        $this->categories->clear();

        return $this;
    }

    /**
     * @return Collection<int, PageRevision>
     */
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function addRevision(PageRevision $revision): self
    {
        if (!$this->revisions->contains($revision)) {
            $this->revisions->add($revision);
            $revision->setPage($this);
        }

        return $this;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
