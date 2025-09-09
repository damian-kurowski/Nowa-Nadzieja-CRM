<?php

namespace App\Repository;

use App\Entity\Faktura;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Faktura>
 *
 * @method Faktura|null find($id, $lockMode = null, $lockVersion = null)
 * @method Faktura|null findOneBy(array $criteria, array $orderBy = null)
 * @method Faktura[]    findAll()
 * @method Faktura[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FakturaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faktura::class);
    }

    public function save(Faktura $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Faktura $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Znajdź faktury dla okręgu - dla skarbnika okręgu i zarządu okręgu
     */
    public function findForOkreg($okreg, array $filters = []): Query
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.skarbnik', 's')
            ->addSelect('s')
            ->andWhere('f.okreg = :okreg')
            ->setParameter('okreg', $okreg)
            ->orderBy('f.dataUtworzenia', 'DESC');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery();
    }

    /**
     * Znajdź faktury dla skarbnika okręgu - tylko swoje
     */
    public function findForSkarbnikOkregu(User $skarbnik, array $filters = []): Query
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.skarbnik = :skarbnik')
            ->setParameter('skarbnik', $skarbnik)
            ->orderBy('f.dataUtworzenia', 'DESC');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery();
    }

    /**
     * Znajdź wszystkie faktury dla skarbnika partii
     */
    public function findForSkarbnikPartii(array $filters = []): Query
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.skarbnik', 's')
            ->leftJoin('f.okreg', 'o')
            ->addSelect('s', 'o')
            ->orderBy('f.dataUtworzenia', 'DESC');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery();
    }

    /**
     * Zastosuj filtry do QueryBuilder
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['status'])) {
            $qb->andWhere('f.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['kategoria'])) {
            $qb->andWhere('f.kategoria = :kategoria')
               ->setParameter('kategoria', $filters['kategoria']);
        }

        if (!empty($filters['pilnosc'])) {
            $qb->andWhere('f.pilnosc = :pilnosc')
               ->setParameter('pilnosc', $filters['pilnosc']);
        }

        if (!empty($filters['data_od'])) {
            $qb->andWhere('f.dataPlatnosci >= :dataOd')
               ->setParameter('dataOd', new \DateTime($filters['data_od']));
        }

        if (!empty($filters['data_do'])) {
            $qb->andWhere('f.dataPlatnosci <= :dataDo')
               ->setParameter('dataDo', new \DateTime($filters['data_do']));
        }

        if (!empty($filters['kwota_od'])) {
            $qb->andWhere('f.kwota >= :kwotaOd')
               ->setParameter('kwotaOd', $filters['kwota_od']);
        }

        if (!empty($filters['kwota_do'])) {
            $qb->andWhere('f.kwota <= :kwotaDo')
               ->setParameter('kwotaDo', $filters['kwota_do']);
        }

        if (!empty($filters['numer_faktury'])) {
            $qb->andWhere('f.numerFaktury LIKE :numerFaktury')
               ->setParameter('numerFaktury', '%' . $filters['numer_faktury'] . '%');
        }

        if (!empty($filters['okreg_id'])) {
            $qb->andWhere('f.okreg = :okreg')
               ->setParameter('okreg', $filters['okreg_id']);
        }

        // Sortowanie
        if (!empty($filters['sortuj_po'])) {
            $direction = !empty($filters['kierunek']) && $filters['kierunek'] === 'asc' ? 'ASC' : 'DESC';
            
            switch ($filters['sortuj_po']) {
                case 'data_platnosci':
                    $qb->orderBy('f.dataPlatnosci', $direction);
                    break;
                case 'kwota':
                    $qb->orderBy('f.kwota', $direction);
                    break;
                case 'status':
                    $qb->orderBy('f.status', $direction);
                    break;
                case 'pilnosc':
                    $qb->orderBy('f.pilnosc', $direction);
                    break;
                default:
                    $qb->orderBy('f.dataUtworzenia', $direction);
            }
        }
    }

    /**
     * Pobierz statystyki faktur dla dashboardu
     */
    public function getStatisticsForUser(User $user): array
    {
        $userRoles = $user->getRoles();
        
        // Skarbnik partii - statystyki wszystkich faktur
        if (in_array('ROLE_SKARBNIK_PARTII', $userRoles)) {
            return $this->getStatisticsForSkarbnikPartii();
        }
        
        // Skarbnik okręgu - statystyki faktur z okręgu
        if (in_array('ROLE_SKARBNIK_OKREGU', $userRoles)) {
            return $this->getStatisticsForOkreg($user->getOkreg());
        }
        
        // Zarząd okręgu - statystyki faktur z okręgu
        if (in_array('ROLE_PREZES_OKREGU', $userRoles) || 
            in_array('ROLE_WICEPREZES_OKREGU', $userRoles) || 
            in_array('ROLE_SEKRETARZ_OKREGU', $userRoles)) {
            return $this->getStatisticsForOkreg($user->getOkreg());
        }
        
        // Dla innych użytkowników - puste statystyki
        return $this->formatStatistics([]);
    }

    /**
     * Statystyki dla okręgu
     */
    private function getStatisticsForOkreg($okreg): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f.status', 'COUNT(f.id) as count', 'SUM(f.kwota) as suma')
            ->andWhere('f.okreg = :okreg')
            ->setParameter('okreg', $okreg)
            ->groupBy('f.status');

        $results = $qb->getQuery()->getResult();
        
        return $this->formatStatistics($results);
    }

    /**
     * Statystyki dla skarbnika partii
     */
    private function getStatisticsForSkarbnikPartii(): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f.status', 'COUNT(f.id) as count', 'SUM(f.kwota) as suma')
            ->groupBy('f.status');

        $results = $qb->getQuery()->getResult();
        
        return $this->formatStatistics($results);
    }

    /**
     * Formatuj statystyki
     */
    private function formatStatistics(array $results): array
    {
        $stats = [
            'wprowadzone' => ['count' => 0, 'suma' => '0.00'],
            'zaakceptowane' => ['count' => 0, 'suma' => '0.00'],
            'odrzucone' => ['count' => 0, 'suma' => '0.00'],
            'zrealizowane' => ['count' => 0, 'suma' => '0.00'],
            'total' => ['count' => 0, 'suma' => '0.00'],
        ];

        $totalCount = 0;
        $totalSuma = '0.00';

        foreach ($results as $result) {
            $status = $result['status'];
            $stats[$status] = [
                'count' => $result['count'],
                'suma' => $result['suma'] ?? '0.00'
            ];
            
            $totalCount += $result['count'];
            $totalSuma = bcadd($totalSuma, $result['suma'] ?? '0.00', 2);
        }

        $stats['total'] = [
            'count' => $totalCount,
            'suma' => $totalSuma
        ];

        return $stats;
    }

    /**
     * Znajdź faktury oczekujące na akceptację (dla skarbnika partii)
     */
    public function findPendingApproval(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.status = :status')
            ->setParameter('status', Faktura::STATUS_WPROWADZONE)
            ->orderBy('f.dataUtworzenia', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź pilne faktury
     */
    public function findUrgent(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.pilnosc = :pilnosc')
            ->andWhere('f.status IN (:statuses)')
            ->setParameter('pilnosc', Faktura::PILNOSC_PILNA)
            ->setParameter('statuses', [Faktura::STATUS_WPROWADZONE, Faktura::STATUS_ZAAKCEPTOWANE])
            ->orderBy('f.dataPlatnosci', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź faktury do realizacji w najbliższych dniach
     */
    public function findDueSoon(int $days = 7): array
    {
        $dueDate = new \DateTime('+' . $days . ' days');
        
        return $this->createQueryBuilder('f')
            ->andWhere('f.dataPlatnosci <= :dueDate')
            ->andWhere('f.status = :status')
            ->setParameter('dueDate', $dueDate)
            ->setParameter('status', Faktura::STATUS_ZAAKCEPTOWANE)
            ->orderBy('f.dataPlatnosci', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź ostatnie faktury dla kopiowania
     */
    public function findRecentForCopy(User $skarbnik, int $limit = 10): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.skarbnik = :skarbnik')
            ->setParameter('skarbnik', $skarbnik)
            ->orderBy('f.dataUtworzenia', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Policz faktury według okresu
     */
    public function countByPeriod(string $period = 'month', ?User $skarbnik = null): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)');

        if ($skarbnik) {
            $qb->andWhere('f.skarbnik = :skarbnik')
               ->setParameter('skarbnik', $skarbnik);
        }

        switch ($period) {
            case 'today':
                $qb->andWhere('DATE(f.dataUtworzenia) = CURRENT_DATE()');
                break;
            case 'week':
                $qb->andWhere('f.dataUtworzenia >= :weekStart')
                   ->setParameter('weekStart', new \DateTime('-7 days'));
                break;
            case 'month':
                $qb->andWhere('f.dataUtworzenia >= :monthStart')
                   ->setParameter('monthStart', new \DateTime('-30 days'));
                break;
            case 'year':
                $qb->andWhere('YEAR(f.dataUtworzenia) = YEAR(CURRENT_DATE())');
                break;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}