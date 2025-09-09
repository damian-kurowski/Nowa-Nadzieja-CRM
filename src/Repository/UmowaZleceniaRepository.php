<?php

namespace App\Repository;

use App\Entity\UmowaZlecenia;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UmowaZlecenia>
 */
class UmowaZleceniaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UmowaZlecenia::class);
    }

    public function save(UmowaZlecenia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UmowaZlecenia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all contracts with filtering and sorting
     */
    public function findAllWithFilters(array $filters = []): Query
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.tworca', 't')
            ->leftJoin('u.zleceniobiorca', 'z')
            ->leftJoin('u.sekretarzPartii', 's')
            ->addSelect('t', 'z', 's');

        // Filter by status
        if (!empty($filters['status'])) {
            $qb->andWhere('u.status = :status')
               ->setParameter('status', $filters['status']);
        }

        // Filter by zakres
        if (!empty($filters['zakres'])) {
            $qb->andWhere('u.zakresUmowy = :zakres')
               ->setParameter('zakres', $filters['zakres']);
        }

        // Filter by creator
        if (!empty($filters['tworca_id'])) {
            $qb->andWhere('u.tworca = :tworca')
               ->setParameter('tworca', $filters['tworca_id']);
        }

        // Filter by zleceniobiorca
        if (!empty($filters['zleceniobiorca_id'])) {
            $qb->andWhere('u.zleceniobiorca = :zleceniobiorca')
               ->setParameter('zleceniobiorca', $filters['zleceniobiorca_id']);
        }

        // Filter by creation date range
        if (!empty($filters['data_od'])) {
            $qb->andWhere('u.dataUtworzenia >= :data_od')
               ->setParameter('data_od', $filters['data_od']);
        }

        if (!empty($filters['data_do'])) {
            $qb->andWhere('u.dataUtworzenia <= :data_do')
               ->setParameter('data_do', $filters['data_do']);
        }

        // Filter by contract period
        if (!empty($filters['okres_od'])) {
            $qb->andWhere('u.dataOd >= :okres_od')
               ->setParameter('okres_od', $filters['okres_od']);
        }

        if (!empty($filters['okres_do'])) {
            $qb->andWhere('(u.dataDo IS NULL OR u.dataDo <= :okres_do)')
               ->setParameter('okres_do', $filters['okres_do']);
        }

        // Filter by wynagrodzenie range
        if (!empty($filters['wynagrodzenie_od'])) {
            $qb->andWhere('u.wynagrodzenie >= :wynagrodzenie_od')
               ->setParameter('wynagrodzenie_od', $filters['wynagrodzenie_od']);
        }

        if (!empty($filters['wynagrodzenie_do'])) {
            $qb->andWhere('u.wynagrodzenie <= :wynagrodzenie_do')
               ->setParameter('wynagrodzenie_do', $filters['wynagrodzenie_do']);
        }

        // Filter by contract number
        if (!empty($filters['numer_umowy'])) {
            $qb->andWhere('u.numerUmowy LIKE :numer_umowy')
               ->setParameter('numer_umowy', '%' . $filters['numer_umowy'] . '%');
        }

        // Sorting
        $sortField = $filters['sortuj_po'] ?? 'dataUtworzenia';
        $sortDirection = $filters['kierunek'] ?? 'desc';

        $allowedSortFields = [
            'dataUtworzenia', 'numerUmowy', 'wynagrodzenie', 
            'dataOd', 'dataDo', 'status', 'zakresUmowy'
        ];

        if (in_array($sortField, $allowedSortFields)) {
            $qb->orderBy('u.' . $sortField, strtoupper($sortDirection));
        } else {
            $qb->orderBy('u.dataUtworzenia', 'DESC');
        }

        // Secondary sorting by ID for consistency
        $qb->addOrderBy('u.id', 'DESC');

        return $qb->getQuery();
    }

    /**
     * Find contracts for specific user (for non-party treasurers)
     */
    public function findForUser(User $user, array $filters = []): Query
    {
        $filters['tworca_id'] = $user->getId();
        return $this->findAllWithFilters($filters);
    }

    /**
     * Find contracts by zleceniobiorca
     */
    public function findByZleceniobiorca(User $zleceniobiorca, array $filters = []): Query
    {
        $filters['zleceniobiorca_id'] = $zleceniobiorca->getId();
        return $this->findAllWithFilters($filters);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('u');
        
        if ($user !== null) {
            $qb->where('u.tworca = :user')
               ->setParameter('user', $user);
        }

        // Count by status
        $statusStats = [];
        foreach (UmowaZlecenia::getStatusChoices() as $label => $status) {
            $count = (clone $qb)
                ->select('COUNT(u.id)')
                ->andWhere('u.status = :status')
                ->setParameter('status', $status)
                ->getQuery()
                ->getSingleScalarResult();

            $sum = (clone $qb)
                ->select('COALESCE(SUM(u.wynagrodzenie), 0)')
                ->andWhere('u.status = :status')
                ->setParameter('status', $status)
                ->getQuery()
                ->getSingleScalarResult();

            $statusStats[$status] = [
                'count' => $count,
                'suma' => number_format((float)$sum, 2, '.', '')
            ];
        }

        // Total statistics
        $totalCount = (clone $qb)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalSum = (clone $qb)
            ->select('COALESCE(SUM(u.wynagrodzenie), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        $statusStats['total'] = [
            'count' => $totalCount,
            'suma' => number_format((float)$totalSum, 2, '.', '')
        ];

        return $statusStats;
    }

    /**
     * Find contracts expiring soon (within 30 days)
     */
    public function findExpiringSoon(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.zleceniobiorca', 'z')
            ->addSelect('z')
            ->where('u.status = :status')
            ->andWhere('u.dataDo IS NOT NULL')
            ->andWhere('u.dataDo <= :date_limit')
            ->andWhere('u.dataDo >= :today')
            ->setParameter('status', UmowaZlecenia::STATUS_PODPISANA)
            ->setParameter('date_limit', new \DateTime('+30 days'))
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('u.dataDo', 'ASC');

        if ($user !== null) {
            $qb->andWhere('u.tworca = :user')
               ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find recently signed contracts (last 7 days)
     */
    public function findRecentlySigned(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.zleceniobiorca', 'z')
            ->addSelect('z')
            ->where('u.status = :status')
            ->andWhere('u.dataPodpisania >= :date_limit')
            ->setParameter('status', UmowaZlecenia::STATUS_PODPISANA)
            ->setParameter('date_limit', new \DateTime('-7 days'))
            ->orderBy('u.dataPodpisania', 'DESC');

        if ($user !== null) {
            $qb->andWhere('u.tworca = :user')
               ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find contracts requiring attention (projects older than 7 days)
     */
    public function findRequiringAttention(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.zleceniobiorca', 'z')
            ->addSelect('z')
            ->where('u.status = :status')
            ->andWhere('u.dataUtworzenia <= :date_limit')
            ->setParameter('status', UmowaZlecenia::STATUS_PROJEKT)
            ->setParameter('date_limit', new \DateTime('-7 days'))
            ->orderBy('u.dataUtworzenia', 'ASC');

        if ($user !== null) {
            $qb->andWhere('u.tworca = :user')
               ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get monthly statistics for charts
     */
    public function getMonthlyStatistics(int $year, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select([
                'MONTH(u.dataUtworzenia) as month',
                'COUNT(u.id) as count',
                'COALESCE(SUM(u.wynagrodzenie), 0) as suma'
            ])
            ->where('YEAR(u.dataUtworzenia) = :year')
            ->setParameter('year', $year)
            ->groupBy('MONTH(u.dataUtworzenia)')
            ->orderBy('month', 'ASC');

        if ($user !== null) {
            $qb->andWhere('u.tworca = :user')
               ->setParameter('user', $user);
        }

        $results = $qb->getQuery()->getResult();
        
        // Fill missing months with zeros
        $monthlyStats = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyStats[$i] = ['count' => 0, 'suma' => '0.00'];
        }
        
        foreach ($results as $result) {
            $monthlyStats[$result['month']] = [
                'count' => $result['count'],
                'suma' => number_format((float)$result['suma'], 2, '.', '')
            ];
        }
        
        return $monthlyStats;
    }

    /**
     * Find active contracts (podpisana status with current date within contract period)
     */
    public function findActive(?User $user = null): array
    {
        $today = new \DateTime('today');
        
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.zleceniobiorca', 'z')
            ->addSelect('z')
            ->where('u.status = :status')
            ->andWhere('u.dataOd <= :today')
            ->andWhere('(u.dataDo IS NULL OR u.dataDo >= :today)')
            ->setParameter('status', UmowaZlecenia::STATUS_PODPISANA)
            ->setParameter('today', $today)
            ->orderBy('u.dataOd', 'DESC');

        if ($user !== null) {
            $qb->andWhere('u.tworca = :user')
               ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }
}