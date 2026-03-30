<?php

declare(strict_types=1);

namespace App\Taxonomy\Infrastructure\Persistence\Doctrine;

use App\Taxonomy\Domain\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
final class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return list<Category>
     */
    public function findAllAlphabetical(): array
    {
        return $this->createQueryBuilder('category')
            ->orderBy('category.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
