<?php

namespace App\Repository;

use App\Entity\OpiniaCzlonka;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OpiniaCzlonka>
 */
class OpiniaCzlonkaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpiniaCzlonka::class);
    }

    /**
     * Znajduje wszystkie opinie o danym członku.
     *
     * @return array<int, OpiniaCzlonka>
     */
    public function findOpinieByCzlonek(User $czlonek): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.czlonek = :czlonek')
            ->setParameter('czlonek', $czlonek)
            ->orderBy('o.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajduje opinie napisane przez danego autora.
     *
     * @return array<int, OpiniaCzlonka>
     */
    public function findOpinieByAutor(User $autor): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.autor = :autor')
            ->setParameter('autor', $autor)
            ->orderBy('o.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sprawdza czy autor już dodał opinię o danym członku.
     */
    public function hasOpiniaByAutorAndCzlonek(User $autor, User $czlonek): bool
    {
        $count = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.autor = :autor')
            ->andWhere('o.czlonek = :czlonek')
            ->setParameter('autor', $autor)
            ->setParameter('czlonek', $czlonek)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Zlicza opinie o danym członku.
     */
    public function countOpinieByCzlonek(User $czlonek): int
    {
        $result = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.czlonek = :czlonek')
            ->setParameter('czlonek', $czlonek)
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }
}
