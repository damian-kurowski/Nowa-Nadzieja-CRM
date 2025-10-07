<?php

namespace App\Repository;

use App\Entity\ImportPlatnosci;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportPlatnosci>
 */
class ImportPlatnosciRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportPlatnosci::class);
    }

    public function findRecentImports(int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.dataImportu', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countImportsByPeriod(string $period = 'week'): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)');

        switch ($period) {
            case 'today':
                $qb->where('DATE(i.dataImportu) = CURRENT_DATE()');
                break;
            case 'week':
                $qb->where('i.dataImportu >= :date')
                   ->setParameter('date', new \DateTime('-1 week'));
                break;
            case 'month':
                $qb->where('i.dataImportu >= :date')
                   ->setParameter('date', new \DateTime('-1 month'));
                break;
            case 'year':
                $qb->where('i.dataImportu >= :date')
                   ->setParameter('date', new \DateTime('-1 year'));
                break;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getTotalMatchedByPeriod(string $period = 'week'): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('SUM(i.liczbaDopasowanych)');

        switch ($period) {
            case 'today':
                $qb->where('DATE(i.dataImportu) = CURRENT_DATE()');
                break;
            case 'week':
                $qb->where('i.dataImportu >= :date')
                   ->setParameter('date', new \DateTime('-1 week'));
                break;
            case 'month':
                $qb->where('i.dataImportu >= :date')
                   ->setParameter('date', new \DateTime('-1 month'));
                break;
            case 'year':
                $qb->where('i.dataImportu >= :date')
                   ->setParameter('date', new \DateTime('-1 year'));
                break;
        }

        return (int) ($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    public function getTotalErrorsByPeriod(string $period = 'week'): int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('SUM(i.liczbaBlednych)');

        switch ($period) {
            case 'today':
                $qb->where('DATE(i.dataImportu) = CURRENT_DATE()');
                break;
            case 'week':
                $qb->where('i.dataImportu >= :date')
                   ->setParameter('date', new \DateTime('-1 week'));
                break;
            case 'month':
                $qb->where('i.dataImportu >= :date')
                   ->setParameter('date', new \DateTime('-1 month'));
                break;
            case 'year':
                $qb->where('i.dataImportu >= :date')
                   ->setParameter('date', new \DateTime('-1 year'));
                break;
        }

        return (int) ($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    public function getStatsPeriodComparison(): array
    {
        // Pobierz statystyki z poprzedniego okresu do porównania
        $currentWeek = $this->countImportsByPeriod('week');
        $previousWeek = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.dataImportu >= :from AND i.dataImportu < :to')
            ->setParameter('from', new \DateTime('-2 weeks'))
            ->setParameter('to', new \DateTime('-1 week'))
            ->getQuery()
            ->getSingleScalarResult();

        $weekChange = $previousWeek > 0 ? (($currentWeek - $previousWeek) / $previousWeek) * 100 : 0;

        return [
            'imports_change' => round($weekChange, 1),
            'imports_trend' => $weekChange >= 0 ? 'up' : 'down'
        ];
    }
}