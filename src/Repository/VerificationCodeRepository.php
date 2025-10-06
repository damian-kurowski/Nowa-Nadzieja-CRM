<?php

namespace App\Repository;

use App\Entity\VerificationCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerificationCode>
 */
class VerificationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerificationCode::class);
    }

    /**
     * Find a valid (not used, not expired) verification code
     */
    public function findValidCode(string $code): ?VerificationCode
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.code = :code')
            ->andWhere('v.used = :used')
            ->andWhere('v.expiresAt > :now')
            ->setParameter('code', $code)
            ->setParameter('used', false)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Delete expired codes (cleanup)
     */
    public function deleteExpiredCodes(): int
    {
        return $this->createQueryBuilder('v')
            ->delete()
            ->where('v.expiresAt < :now')
            ->orWhere('v.used = :used AND v.usedAt < :oneWeekAgo')
            ->setParameter('now', new \DateTime())
            ->setParameter('used', true)
            ->setParameter('oneWeekAgo', new \DateTime('-1 week'))
            ->getQuery()
            ->execute();
    }

    /**
     * Mark old unused codes for a user as invalid
     */
    public function invalidateUserCodes(int $userId): int
    {
        return $this->createQueryBuilder('v')
            ->update()
            ->set('v.used', ':used')
            ->where('v.user = :userId')
            ->andWhere('v.used = :notUsed')
            ->setParameter('used', true)
            ->setParameter('userId', $userId)
            ->setParameter('notUsed', false)
            ->getQuery()
            ->execute();
    }
}
