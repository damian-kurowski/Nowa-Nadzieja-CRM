<?php

namespace App\Repository;

use App\Entity\PodpisDokumentu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PodpisDokumentu>
 *
 * @method PodpisDokumentu|null find($id, $lockMode = null, $lockVersion = null)
 * @method PodpisDokumentu|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 *
 * @methfinal od PodpisDokumentu[]    findAll()
 *
 * @method PodpisDokumentu[] findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class PodpisDokumentuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PodpisDokumentu::class);
    }

    public function save(PodpisDokumentu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PodpisDokumentu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
