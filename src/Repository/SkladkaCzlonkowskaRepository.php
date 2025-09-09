<?php

namespace App\Repository;

use App\Entity\SkladkaCzlonkowska;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SkladkaCzlonkowska>
 *
 * @method SkladkaCzlonkowska|null find($id, $lockMode = null, $lockVersion = null)
 * @method SkladkaCzlonkowska|null findOneBy(array $criteria, array $orderBy = null)
 * @method SkladkaCzlonkowska[]    findAll()
 * @method SkladkaCzlonkowska[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SkladkaCzlonkowskaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SkladkaCzlonkowska::class);
    }

    public function findByCzlonekAndOkres(User $czlonek, int $rok, int $miesiac): ?SkladkaCzlonkowska
    {
        return $this->findOneBy([
            'czlonek' => $czlonek,
            'rok' => $rok,
            'miesiac' => $miesiac
        ]);
    }

    public function findByCzlonek(User $czlonek, array $orderBy = ['rok' => 'DESC', 'miesiac' => 'DESC']): array
    {
        return $this->findBy(['czlonek' => $czlonek], $orderBy);
    }

    public function findOplaconeByCzlonek(User $czlonek): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.czlonek = :czlonek')
            ->andWhere('s.status = :status')
            ->setParameter('czlonek', $czlonek)
            ->setParameter('status', 'oplacona')
            ->orderBy('s.rok', 'DESC')
            ->addOrderBy('s.miesiac', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNieoplaconeByCzlonek(User $czlonek): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.czlonek = :czlonek')
            ->andWhere('s.status != :status')
            ->setParameter('czlonek', $czlonek)
            ->setParameter('status', 'oplacona')
            ->orderBy('s.rok', 'ASC')
            ->addOrderBy('s.miesiac', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOkres(int $rok, int $miesiac): array
    {
        return $this->findBy([
            'rok' => $rok,
            'miesiac' => $miesiac
        ], ['czlonek' => 'ASC']);
    }

    public function findOplaconeByOkres(int $rok, int $miesiac): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.rok = :rok')
            ->andWhere('s.miesiac = :miesiac')
            ->andWhere('s.status = :status')
            ->setParameter('rok', $rok)
            ->setParameter('miesiac', $miesiac)
            ->setParameter('status', 'oplacona')
            ->getQuery()
            ->getResult();
    }

    public function countOplaconeByOkres(int $rok, int $miesiac): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.rok = :rok')
            ->andWhere('s.miesiac = :miesiac')
            ->andWhere('s.status = :status')
            ->setParameter('rok', $rok)
            ->setParameter('miesiac', $miesiac)
            ->setParameter('status', 'oplacona')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getSumaKwotByOkres(int $rok, int $miesiac): string
    {
        $result = $this->createQueryBuilder('s')
            ->select('SUM(s.kwota)')
            ->andWhere('s.rok = :rok')
            ->andWhere('s.miesiac = :miesiac')
            ->andWhere('s.status = :status')
            ->setParameter('rok', $rok)
            ->setParameter('miesiac', $miesiac)
            ->setParameter('status', 'oplacona')
            ->getQuery()
            ->getSingleScalarResult();

        return (string) ($result ?? '0.00');
    }

    public function getAktualnaSkladka(User $czlonek): ?SkladkaCzlonkowska
    {
        $currentDate = new \DateTime();
        $currentYear = (int) $currentDate->format('Y');
        $currentMonth = (int) $currentDate->format('n');

        return $this->findByCzlonekAndOkres($czlonek, $currentYear, $currentMonth);
    }

    public function hasAktualnaSkladkeOplacona(User $czlonek): bool
    {
        $skladka = $this->getAktualnaSkladka($czlonek);
        
        return $skladka && $skladka->isOplacona() && $skladka->isWazna();
    }

    public function findWymagajaceUwagi(): array
    {
        $currentDate = new \DateTime();
        $currentDate->modify('-30 days');

        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->andWhere('s.dataRejestracji < :date')
            ->setParameter('status', 'oczekujaca')
            ->setParameter('date', $currentDate)
            ->orderBy('s.dataRejestracji', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatystykiRoczne(int $rok): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.miesiac, COUNT(s.id) as liczba, SUM(s.kwota) as suma')
            ->andWhere('s.rok = :rok')
            ->andWhere('s.status = :status')
            ->setParameter('rok', $rok)
            ->setParameter('status', 'oplacona')
            ->groupBy('s.miesiac')
            ->orderBy('s.miesiac', 'ASC');

        $results = $qb->getQuery()->getResult();
        
        $statystyki = [];
        foreach ($results as $result) {
            $statystyki[$result['miesiac']] = [
                'liczba' => (int) $result['liczba'],
                'suma' => (string) ($result['suma'] ?? '0.00')
            ];
        }

        // Wypełnij brakujące miesiące
        for ($i = 1; $i <= 12; $i++) {
            if (!isset($statystyki[$i])) {
                $statystyki[$i] = ['liczba' => 0, 'suma' => '0.00'];
            }
        }

        ksort($statystyki);
        return $statystyki;
    }
}