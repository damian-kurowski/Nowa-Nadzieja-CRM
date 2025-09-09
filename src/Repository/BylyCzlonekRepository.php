<?php

namespace App\Repository;

use App\Entity\BylyCzlonek;
use App\Entity\Oddzial;
use App\Entity\Okreg;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BylyCzlonek>
 */
class BylyCzlonekRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BylyCzlonek::class);
    }

    /**
     * Znajdź byłego członka po oryginalnym ID członka.
     */
    public function findByOryginalnyIdCzlonka(int $oryginalnyId): ?BylyCzlonek
    {
        return $this->findOneBy(['oryginalnyIdCzlonka' => $oryginalnyId]);
    }

    /**
     * Znajdź byłych członków w danym okresie zakończenia członkostwa.
     *
     * @return array<int, BylyCzlonek>
     */
    public function findByDataZakonczenia(\DateTimeInterface $dataOd, \DateTimeInterface $dataDo): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.dataZakonczeniaCzlonkostwa >= :dataOd')
            ->andWhere('b.dataZakonczeniaCzlonkostwa <= :dataDo')
            ->setParameter('dataOd', $dataOd)
            ->setParameter('dataDo', $dataDo)
            ->orderBy('b.dataZakonczeniaCzlonkostwa', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź byłych członków według okręgu.
     *
     * @return array<int, BylyCzlonek>
     */
    public function findByOkreg(Okreg $okreg): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.okreg = :okreg')
            ->setParameter('okreg', $okreg)
            ->orderBy('b.dataZakonczeniaCzlonkostwa', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź byłych członków według oddziału.
     *
     * @return array<int, BylyCzlonek>
     */
    public function findByOddzial(Oddzial $oddzial): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.oddzial = :oddzial')
            ->setParameter('oddzial', $oddzial)
            ->orderBy('b.dataZakonczeniaCzlonkostwa', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Wyszukaj byłych członków według frazy.
     *
     * @return array<int, BylyCzlonek>
     */
    public function searchByPhrase(string $phrase): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.imie LIKE :phrase OR b.nazwisko LIKE :phrase OR b.email LIKE :phrase')
            ->setParameter('phrase', '%'.$phrase.'%')
            ->orderBy('b.nazwisko', 'ASC')
            ->addOrderBy('b.imie', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
