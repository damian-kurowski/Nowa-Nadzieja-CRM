<?php

namespace App\Repository;

use App\Entity\LoginAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginAttempt>
 */
class LoginAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginAttempt::class);
    }

    /**
     * Count failed login attempts for IP in last X minutes.
     */
    public function countRecentFailedAttempts(string $ipAddress, int $minutes = 15): int
    {
        $since = new \DateTime("-{$minutes} minutes");

        $result = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.ipAddress = :ip')
            ->andWhere('l.status = :status')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('status', 'failed')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }

    /**
     * Check if IP is currently blocked.
     */
    public function isIpBlocked(string $ipAddress): bool
    {
        $since = new \DateTime('-1 hour'); // Check blocks in last hour

        $result = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.ipAddress = :ip')
            ->andWhere('l.status = :status')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('status', 'blocked')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Get last successful login for IP.
     */
    public function getLastSuccessfulLogin(string $ipAddress): ?LoginAttempt
    {
        return $this->createQueryBuilder('l')
            ->where('l.ipAddress = :ip')
            ->andWhere('l.status = :status')
            ->setParameter('ip', $ipAddress)
            ->setParameter('status', 'success')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count all attempts (not just failed) from IP in last X minutes.
     */
    public function countRecentAttempts(string $ipAddress, int $minutes = 15): int
    {
        $since = new \DateTime("-{$minutes} minutes");

        $result = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.ipAddress = :ip')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }

    /**
     * Clean old login attempts (older than X days).
     */
    public function cleanOldAttempts(int $days = 30): int
    {
        $cutoffDate = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute();
    }
}
