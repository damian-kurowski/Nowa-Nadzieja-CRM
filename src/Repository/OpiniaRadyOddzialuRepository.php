<?php

namespace App\Repository;

use App\Entity\OpiniaRadyOddzialu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OpiniaRadyOddzialu>
 */
class OpiniaRadyOddzialuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpiniaRadyOddzialu::class);
    }

}
