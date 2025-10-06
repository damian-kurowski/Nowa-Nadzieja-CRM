<?php

namespace App\Repository;

use App\Entity\PrzekazOdpowiedz;
use App\Entity\PrzekazMedialny;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrzekazOdpowiedz>
 */
class PrzekazOdpowiedzRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrzekazOdpowiedz::class);
    }

    /**
     * Znajdź odpowiedzi dla przekazu
     */
    public function findByPrzekazMedialny(PrzekazMedialny $przekaz): array
    {
        return $this->createQueryBuilder('po')
            ->andWhere('po.przekaz = :przekaz')
            ->setParameter('przekaz', $przekaz)
            ->orderBy('po.dataDodania', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź niezweryfikowane odpowiedzi
     */
    public function findUnverified(): array
    {
        return $this->createQueryBuilder('po')
            ->andWhere('po.zweryfikowany = :zweryfikowany')
            ->setParameter('zweryfikowany', false)
            ->orderBy('po.dataDodania', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź odpowiedzi użytkownika
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('po')
            ->andWhere('po.odbiorca = :user')
            ->setParameter('user', $user)
            ->orderBy('po.dataDodania', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statystyki według typu platformy
     */
    public function getStatsByType(PrzekazMedialny $przekaz): array
    {
        return $this->createQueryBuilder('po')
            ->select('po.typ, COUNT(po.id) as liczba')
            ->andWhere('po.przekaz = :przekaz')
            ->setParameter('przekaz', $przekaz)
            ->groupBy('po.typ')
            ->orderBy('liczba', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
