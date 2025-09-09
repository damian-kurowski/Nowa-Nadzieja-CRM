<?php

namespace App\Repository;

use App\Entity\Region;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Region>
 */
class RegionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Region::class);
    }

    /**
     * Finds all regions ordered by name.
     *
     * @return Region[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.nazwa', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds region by województwo.
     */
    public function findByWojewodztwo(string $wojewodztwo): ?Region
    {
        return $this->createQueryBuilder('r')
            ->where('r.wojewodztwo = :wojewodztwo')
            ->setParameter('wojewodztwo', $wojewodztwo)
            ->getQuery()
            ->getOneOrNullResult();
    }
}