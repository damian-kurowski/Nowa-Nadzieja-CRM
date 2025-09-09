<?php

namespace App\Repository;

use App\Entity\WystepMedialny;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WystepMedialny>
 */
class WystepMedialnyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WystepMedialny::class);
    }

    public function countByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        $result = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.dataIGodzina BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByType(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.nazwaMediaRedakcji, COUNT(w.id) as count')
            ->groupBy('w.nazwaMediaRedakcji')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMedium(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.nazwaMediaRedakcji as medium, COUNT(w.id) as count')
            ->groupBy('w.nazwaMediaRedakcji')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, WystepMedialny>
     */
    public function getRecentAppearances(int $limit = 5): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.dataIGodzina', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, WystepMedialny>
     */
    public function findUpcomingAppearances(?\DateTimeInterface $from = null): array
    {
        $qb = $this->createQueryBuilder('w')
            ->andWhere('w.dataIGodzina > :now')
            ->setParameter('now', $from ?? new \DateTime())
            ->orderBy('w.dataIGodzina', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countAll(): int
    {
        $result = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }

    public function countKonferencje(): int
    {
        // Assuming KonferencjaPrasowa is a separate entity or a field in WystepMedialny
        // This is a placeholder implementation
        return 0;
    }

    /**
     * Find all media appearances for a specific user as a speaker
     * 
     * @param User $user
     * @return array<int, WystepMedialny>
     */
    public function findForSpeaker(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('w')
            ->innerJoin('w.mowcy', 'm')
            ->andWhere('m.id = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('w.dataIGodzina', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count media appearances for a specific user as a speaker
     * 
     * @param User $user
     * @return int
     */
    public function countForSpeaker(\App\Entity\User $user): int
    {
        $result = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->innerJoin('w.mowcy', 'm')
            ->andWhere('m.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }
}
