<?php

namespace App\Repository;

use App\Entity\Platnosc;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Platnosc>
 */
class PlatnoscRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Platnosc::class);
    }


    public function getTotalPaidByUser(User $user): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.kwota)')
            ->andWhere('p.darczyca = :user')
            ->andWhere('p.statusPlatnosci = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'potwierdzona')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function findByUserOrderedByDate(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.darczyca = :user')
            ->setParameter('user', $user)
            ->orderBy('p.dataKsiegowania', 'DESC')
            ->addOrderBy('p.dataRejestracji', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFirstPaymentByUser(User $user): ?Platnosc
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.darczyca = :user')
            ->andWhere('p.statusPlatnosci = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'potwierdzona')
            ->orderBy('p.dataKsiegowania', 'ASC')
            ->addOrderBy('p.dataRejestracji', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecentPayments(int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.darczyca', 'u')
            ->orderBy('p.dataKsiegowania', 'DESC')
            ->addOrderBy('p.dataRejestracji', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPaymentsByPeriod(string $period = 'week'): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.statusPlatnosci = :status')
            ->setParameter('status', 'potwierdzona');

        switch ($period) {
            case 'today':
                $qb->andWhere('DATE(p.dataKsiegowania) = CURRENT_DATE() OR (p.dataKsiegowania IS NULL AND DATE(p.dataRejestracji) = CURRENT_DATE())');
                break;
            case 'week':
                $qb->andWhere('(p.dataKsiegowania >= :date OR (p.dataKsiegowania IS NULL AND p.dataRejestracji >= :date))')
                   ->setParameter('date', new \DateTime('-1 week'));
                break;
            case 'month':
                $qb->andWhere('(p.dataKsiegowania >= :date OR (p.dataKsiegowania IS NULL AND p.dataRejestracji >= :date))')
                   ->setParameter('date', new \DateTime('-1 month'));
                break;
            case 'year':
                $qb->andWhere('(p.dataKsiegowania >= :date OR (p.dataKsiegowania IS NULL AND p.dataRejestracji >= :date))')
                   ->setParameter('date', new \DateTime('-1 year'));
                break;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getPaymentStatistics(): array
    {
        // Statystyki płatności z poprzedniego tygodnia dla porównania
        $currentWeek = $this->countPaymentsByPeriod('week');
        $previousWeek = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.statusPlatnosci = :status')
            ->andWhere('(p.dataKsiegowania >= :from AND p.dataKsiegowania < :to) OR (p.dataKsiegowania IS NULL AND p.dataRejestracji >= :from AND p.dataRejestracji < :to)')
            ->setParameter('status', 'potwierdzona')
            ->setParameter('from', new \DateTime('-2 weeks'))
            ->setParameter('to', new \DateTime('-1 week'))
            ->getQuery()
            ->getSingleScalarResult();

        $paymentChange = $previousWeek > 0 ? (($currentWeek - $previousWeek) / $previousWeek) * 100 : ($currentWeek > 0 ? 100 : 0);

        // Statystyki dopasowań - wzrost w stosunku do importów
        $totalMatched = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.peselZTytulu IS NOT NULL')
            ->andWhere('(p.dataKsiegowania >= :date OR (p.dataKsiegowania IS NULL AND p.dataRejestracji >= :date))')
            ->setParameter('date', new \DateTime('-1 week'))
            ->getQuery()
            ->getSingleScalarResult();

        $previousMatched = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.peselZTytulu IS NOT NULL')
            ->andWhere('(p.dataKsiegowania >= :from AND p.dataKsiegowania < :to) OR (p.dataKsiegowania IS NULL AND p.dataRejestracji >= :from AND p.dataRejestracji < :to)')
            ->setParameter('from', new \DateTime('-2 weeks'))
            ->setParameter('to', new \DateTime('-1 week'))
            ->getQuery()
            ->getSingleScalarResult();

        $matchedChange = $previousMatched > 0 ? (($totalMatched - $previousMatched) / $previousMatched) * 100 : ($totalMatched > 0 ? 100 : 0);

        return [
            'payments_change' => round($paymentChange, 1),
            'payments_trend' => $paymentChange >= 0 ? 'up' : 'down',
            'matched_change' => round($matchedChange, 1),
            'matched_trend' => $matchedChange >= 0 ? 'up' : 'down',
            'total_matched' => (int) $totalMatched
        ];
    }

    public function getTotalDonationsAmount(): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.kwota)')
            ->andWhere('p.statusPlatnosci = :status')
            ->setParameter('status', 'potwierdzona')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getLastPaymentByUser(User $user): ?Platnosc
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.darczyca = :user')
            ->andWhere('p.statusPlatnosci = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'potwierdzona')
            ->orderBy('p.dataKsiegowania', 'DESC')
            ->addOrderBy('p.dataRejestracji', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getDonorType(User $user): string
    {
        // Sprawdź liczbę płatności w ciągu ostatnich 12 miesięcy
        $recentPaymentsCount = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.darczyca = :user')
            ->andWhere('p.statusPlatnosci = :status')
            ->andWhere('p.dataKsiegowania >= :date OR (p.dataKsiegowania IS NULL AND p.dataRejestracji >= :date)')
            ->setParameter('user', $user)
            ->setParameter('status', 'potwierdzona')
            ->setParameter('date', new \DateTime('-1 year'))
            ->getQuery()
            ->getSingleScalarResult();

        // Sprawdź czy to darczyńca członkowski (czy jest członkiem)
        if ($user->getTypUzytkownika() === 'czlonek') {
            return 'Członkowski';
        }

        // Klasyfikuj na podstawie regularności wpłat
        if ($recentPaymentsCount >= 6) {
            return 'Stały';
        } elseif ($recentPaymentsCount >= 2) {
            return 'Okazjonalny';
        } else {
            return 'Jednorazowy';
        }
    }

    /**
     * Batch-load payment totals for multiple users to avoid N+1 queries
     * @param User[] $users
     * @return array<int, float> Array with user ID as key and total amount as value
     */
    public function getBatchTotalPaidByUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $userIds = array_map(fn(User $user) => $user->getId(), $users);

        $results = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.darczyca) as userId, SUM(p.kwota) as total')
            ->andWhere('p.darczyca IN (:userIds)')
            ->andWhere('p.statusPlatnosci = :status')
            ->setParameter('userIds', $userIds)
            ->setParameter('status', 'potwierdzona')
            ->groupBy('p.darczyca')
            ->getQuery()
            ->getResult();

        $totals = [];
        foreach ($results as $result) {
            $totals[(int)$result['userId']] = (float)($result['total'] ?? 0);
        }

        // Ensure all users have an entry, even if they have no payments
        foreach ($users as $user) {
            if (!isset($totals[$user->getId()])) {
                $totals[$user->getId()] = 0.0;
            }
        }

        return $totals;
    }

    /**
     * Batch-load donor types for multiple users to avoid N+1 queries
     * @param User[] $users
     * @return array<int, string> Array with user ID as key and donor type as value
     */
    public function getBatchDonorTypes(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $userIds = array_map(fn(User $user) => $user->getId(), $users);

        $results = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.darczyca) as userId, COUNT(p.id) as paymentCount')
            ->andWhere('p.darczyca IN (:userIds)')
            ->andWhere('p.statusPlatnosci = :status')
            ->andWhere('p.dataKsiegowania >= :date OR (p.dataKsiegowania IS NULL AND p.dataRejestracji >= :date)')
            ->setParameter('userIds', $userIds)
            ->setParameter('status', 'potwierdzona')
            ->setParameter('date', new \DateTime('-1 year'))
            ->groupBy('p.darczyca')
            ->getQuery()
            ->getResult();

        $paymentCounts = [];
        foreach ($results as $result) {
            $paymentCounts[(int)$result['userId']] = (int)$result['paymentCount'];
        }

        $donorTypes = [];
        foreach ($users as $user) {
            $paymentCount = $paymentCounts[$user->getId()] ?? 0;
            
            if ($user->getTypUzytkownika() === 'czlonek') {
                $donorTypes[$user->getId()] = 'Członkowski';
            } elseif ($paymentCount >= 6) {
                $donorTypes[$user->getId()] = 'Stały';
            } elseif ($paymentCount >= 2) {
                $donorTypes[$user->getId()] = 'Okazjonalny';
            } else {
                $donorTypes[$user->getId()] = 'Jednorazowy';
            }
        }

        return $donorTypes;
    }
}
