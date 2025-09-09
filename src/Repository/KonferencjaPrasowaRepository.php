<?php

namespace App\Repository;

use App\Entity\KonferencjaPrasowa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KonferencjaPrasowa>
 */
class KonferencjaPrasowaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KonferencjaPrasowa::class);
    }

    /**
     * Znajdź konferencje prasowe dla konkretnego użytkownika (jako mówcy).
     *
     * @param \App\Entity\User $user
     * @return KonferencjaPrasowa[]
     */
    public function findForSpeaker(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('k')
            ->leftJoin('k.mowcy', 'm')
            ->where('m.id = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('k.dataIGodzina', 'DESC')
            ->getQuery()
            ->getResult();
    }

}
