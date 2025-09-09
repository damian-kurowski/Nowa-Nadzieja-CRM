<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 *
 * @method ActivityLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityLog|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method ActivityLog[]    findAll()
 * @method ActivityLog[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function save(ActivityLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find recent activities for a specific user.
     *
     * @return ActivityLog[]
     */
    public function findRecentForUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent activities for user's district/region.
     *
     * @return ActivityLog[]
     */
    public function findRecentForUserScope(User $user, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.user', 'u')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit);

        // Filter based on user's scope
        if ($user->getOkreg()) {
            // District level - show activities from same district
            $qb->andWhere('u.okreg = :okreg')
               ->setParameter('okreg', $user->getOkreg());
        } elseif ($user->getOddzial()) {
            // Branch level - show activities from same branch
            $qb->andWhere('u.oddzial = :oddzial')
               ->setParameter('oddzial', $user->getOddzial());
        }
        // For admin/party level users, show all activities (no additional filter)

        return $qb->getQuery()->getResult();
    }

    /**
     * Clean old activity logs (older than specified days).
     */
    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->modify('-'.$daysToKeep.' days');

        return $this->createQueryBuilder('a')
            ->delete()
            ->andWhere('a.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Get activity statistics for dashboard.
     *
     * @return array<string, mixed>
     */
    public function getActivityStats(User $user, ?\DateTimeInterface $since = null): array
    {
        $since = $since ?? new \DateTime('-30 days');

        $qb = $this->createQueryBuilder('a')
            ->select('a.action, COUNT(a.id) as count')
            ->andWhere('a.createdAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('a.action');

        // Filter by user scope if not admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            if ($user->getOkreg()) {
                $qb->innerJoin('a.user', 'u')
                   ->andWhere('u.okreg = :okreg')
                   ->setParameter('okreg', $user->getOkreg());
            } elseif ($user->getOddzial()) {
                $qb->innerJoin('a.user', 'u')
                   ->andWhere('u.oddzial = :oddzial')
                   ->setParameter('oddzial', $user->getOddzial());
            }
        }

        $results = $qb->getQuery()->getResult();

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['action']] = $result['count'];
        }

        return $stats;
    }
}
