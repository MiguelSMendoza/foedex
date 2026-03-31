<?php

declare(strict_types=1);

namespace App\Knowledge\Infrastructure\Persistence\Doctrine;

use App\Knowledge\Domain\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Page>
 */
final class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function findActiveBySlug(string $slug): ?Page
    {
        return $this->createQueryBuilder('page')
            ->andWhere('page.currentSlug = :slug')
            ->andWhere('page.isArchived = false')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Page>
     */
    public function findLatestUpdated(int $limit = 10): array
    {
        return $this->createQueryBuilder('page')
            ->andWhere('page.isArchived = false')
            ->orderBy('page.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Page>
     */
    public function findLatestUpdatedSlice(int $limit, int $offset = 0): array
    {
        return $this->createQueryBuilder('page')
            ->andWhere('page.isArchived = false')
            ->orderBy('page.updatedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Page>
     */
    public function search(string $query): array
    {
        $needle = '%'.mb_strtolower(trim($query)).'%';

        return $this->createQueryBuilder('page')
            ->leftJoin('page.categories', 'category')
            ->addSelect('category')
            ->andWhere('page.isArchived = false')
            ->andWhere('LOWER(page.currentTitle) LIKE :needle OR LOWER(page.currentExcerpt) LIKE :needle OR LOWER(page.currentMarkdown) LIKE :needle')
            ->setParameter('needle', $needle)
            ->orderBy('page.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Page>
     */
    public function searchSlice(string $query, int $limit, int $offset = 0): array
    {
        $needle = '%'.mb_strtolower(trim($query)).'%';

        return $this->createQueryBuilder('page')
            ->leftJoin('page.categories', 'category')
            ->addSelect('category')
            ->andWhere('page.isArchived = false')
            ->andWhere('LOWER(page.currentTitle) LIKE :needle OR LOWER(page.currentExcerpt) LIKE :needle OR LOWER(page.currentMarkdown) LIKE :needle')
            ->setParameter('needle', $needle)
            ->orderBy('page.updatedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function slugExists(string $slug, ?int $ignorePageId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('page')
            ->select('COUNT(page.id)')
            ->andWhere('page.currentSlug = :slug')
            ->setParameter('slug', $slug);

        if ($ignorePageId !== null) {
            $queryBuilder
                ->andWhere('page.id != :ignorePageId')
                ->setParameter('ignorePageId', $ignorePageId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
}
