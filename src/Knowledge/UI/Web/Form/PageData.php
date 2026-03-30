<?php

declare(strict_types=1);

namespace App\Knowledge\UI\Web\Form;

use App\Taxonomy\Domain\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class PageData
{
    public string $title = '';
    public string $slug = '';
    public ?string $excerpt = null;
    public string $markdown = '';
    public ?string $changeSummary = null;

    /**
     * @var Collection<int, Category>
     */
    public Collection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }
}
