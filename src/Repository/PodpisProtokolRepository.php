<?php

namespace App\Repository;

use App\Entity\PodpisProtokolu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PodpisProtokolu>
 */
class PodpisProtokolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PodpisProtokolu::class);
    }
}
