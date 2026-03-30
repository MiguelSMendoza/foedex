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

    /**
     * @return list<Page>
     */
    public function findLatestUpdated(int $limit = 10): array
    {
        return $this->createQueryBuilder('page')
            ->orderBy('page.updatedAt', 'DESC')
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
            ->andWhere('LOWER(page.currentTitle) LIKE :needle OR LOWER(page.currentExcerpt) LIKE :needle OR LOWER(page.currentMarkdown) LIKE :needle')
            ->setParameter('needle', $needle)
            ->orderBy('page.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
