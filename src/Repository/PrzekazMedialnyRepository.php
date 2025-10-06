<?php

namespace App\Repository;

use App\Entity\PrzekazMedialny;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrzekazMedialny>
 */
class PrzekazMedialnyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrzekazMedialny::class);
    }

    /**
     * Znajdź ostatnie przekazy
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.dataUtworzenia', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź przekazy według statusu
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statystyki przekazów
     */
    public function getStatystyki(): array
    {
        $qb = $this->createQueryBuilder('p');

        return [
            'wszystkie' => $qb->select('COUNT(p.id)')->getQuery()->getSingleScalarResult(),
            'wysłane' => $this->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.status = :status')
                ->setParameter('status', 'sent')
                ->getQuery()
                ->getSingleScalarResult(),
            'robocze' => $this->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.status = :status')
                ->setParameter('status', 'draft')
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }
}
