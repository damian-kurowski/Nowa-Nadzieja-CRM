<?php

namespace App\Repository;

use App\Entity\PrzekazOdbiorca;
use App\Entity\PrzekazMedialny;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrzekazOdbiorca>
 */
class PrzekazOdbiorcaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrzekazOdbiorca::class);
    }

    /**
     * Znajdź odbiorców przekazu według statusu
     */
    public function findByPrzekazAndStatus(PrzekazMedialny $przekaz, string $status): array
    {
        return $this->createQueryBuilder('po')
            ->andWhere('po.przekaz = :przekaz')
            ->andWhere('po.status = :status')
            ->setParameter('przekaz', $przekaz)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź nieprzeczytane przekazy
     */
    public function findUnread(PrzekazMedialny $przekaz): array
    {
        return $this->createQueryBuilder('po')
            ->andWhere('po.przekaz = :przekaz')
            ->andWhere('po.czyPrzeczytany = :przeczytany')
            ->setParameter('przekaz', $przekaz)
            ->setParameter('przeczytany', false)
            ->getQuery()
            ->getResult();
    }
}
