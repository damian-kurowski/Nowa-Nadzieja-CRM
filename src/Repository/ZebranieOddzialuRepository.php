<?php

namespace App\Repository;

use App\Entity\Oddzial;
use App\Entity\Okreg;
use App\Entity\User;
use App\Entity\ZebranieOddzialu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ZebranieOddzialu>
 */
class ZebranieOddzialuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZebranieOddzialu::class);
    }

    /**
     * Znajdź aktywne zebranie dla oddziału (with eager loading).
     */
    public function findActiveByOddzial(Oddzial $oddzial): ?ZebranieOddzialu
    {
        return $this->createQueryBuilder('z')
            ->leftJoin('z.obserwator', 'o')
            ->leftJoin('z.protokolant', 'p')
            ->leftJoin('z.prowadzacy', 'pr')
            ->addSelect('o', 'p', 'pr')
            ->andWhere('z.oddzial = :oddzial')
            ->andWhere('z.status NOT IN (:statuses)')
            ->setParameter('oddzial', $oddzial)
            ->setParameter('statuses', [ZebranieOddzialu::STATUS_ZAKONCZONE, ZebranieOddzialu::STATUS_ANULOWANE])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Znajdź zebrania dla użytkownika jako obserwator.
     *
     * @return array<int, ZebranieOddzialu>
     */
    public function findByObserwator(User $obserwator): array
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.obserwator = :obserwator')
            ->setParameter('obserwator', $obserwator)
            ->orderBy('z.dataRozpoczecia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź aktywne zebrania dla użytkownika jako obserwator.
     *
     * @return array<int, ZebranieOddzialu>
     */
    public function findActiveByObserwator(User $obserwator): array
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.obserwator = :obserwator')
            ->andWhere('z.status NOT IN (:statuses)')
            ->setParameter('obserwator', $obserwator)
            ->setParameter('statuses', [ZebranieOddzialu::STATUS_ZAKONCZONE, ZebranieOddzialu::STATUS_ANULOWANE])
            ->orderBy('z.dataRozpoczecia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sprawdź czy użytkownik może być obserwatorem zebrania dla danego oddziału.
     */
    public function canUserBeObserver(User $user, Oddzial $oddzial): bool
    {
        // Obserwatorem może być osoba z tego samego okręgu co oddział
        $userOkreg = $user->getOkreg();
        $oddzialOkreg = $oddzial->getOkreg();
        if (null === $userOkreg) {
            return false;
        }

        return $userOkreg->getId() === $oddzialOkreg->getId();
    }

    /**
     * Sprawdź czy użytkownik może być protokolantem/prowadzącym dla danego oddziału.
     */
    public function canUserBeMeetingRole(User $user, Oddzial $oddzial): bool
    {
        // Protokolant/Prowadzący musi należeć do oddziału
        return $user->getOddzial() && $user->getOddzial()->getId() === $oddzial->getId();
    }

    /**
     * Znajdź wszystkie zebrania w okręgu.
     *
     * @return array<int, ZebranieOddzialu>
     */
    /**
     * @return array<int, ZebranieOddzialu>
     */
    public function findByOkreg(Okreg $okreg, int $limit = 50): array
    {
        return $this->createQueryBuilder('z')
            ->join('z.oddzial', 'o')
            ->andWhere('o.okreg = :okreg')
            ->setParameter('okreg', $okreg)
            ->orderBy('z.dataRozpoczecia', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź zakończone zebrania dla okręgu (historia).
     *
     * @return array<int, ZebranieOddzialu>
     */
    /**
     * @return array<int, ZebranieOddzialu>
     */
    public function findCompletedByOkreg(Okreg $okreg, int $limit = 20): array
    {
        return $this->createQueryBuilder('z')
            ->join('z.oddzial', 'o')
            ->leftJoin('z.obserwator', 'obs')
            ->leftJoin('z.protokolant', 'prot')
            ->leftJoin('z.prowadzacy', 'prow')
            ->addSelect('o', 'obs', 'prot', 'prow')
            ->andWhere('o.okreg = :okreg')
            ->andWhere('z.status = :status')
            ->setParameter('okreg', $okreg)
            ->setParameter('status', ZebranieOddzialu::STATUS_ZAKONCZONE)
            ->orderBy('z.dataZakonczenia', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź wszystkie zebrania użytkownika (jako uczestnik).
     */
    /**
     * @return array<int, ZebranieOddzialu>
     */
    public function findUserMeetings(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('z')
            ->join('z.oddzial', 'o')
            ->addSelect('o')
            ->where(
                $this->createQueryBuilder('z')->expr()->orX(
                    'z.obserwator = :user',
                    'z.protokolant = :user',
                    'z.prowadzacy = :user'
                )
            )
            ->setParameter('user', $user)
            ->orderBy('z.dataRozpoczecia', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statystyki zebrań dla danego okręgu.
     */
    /**
     * @return array<string, mixed>
     */
    public function getMeetingStats(Okreg $okreg): array
    {
        $total = $this->createQueryBuilder('z')
            ->select('COUNT(z.id)')
            ->join('z.oddzial', 'o')
            ->where('o.okreg = :okreg')
            ->setParameter('okreg', $okreg)
            ->getQuery()
            ->getSingleScalarResult();

        $active = $this->createQueryBuilder('z')
            ->select('COUNT(z.id)')
            ->join('z.oddzial', 'o')
            ->where('o.okreg = :okreg')
            ->andWhere('z.status NOT IN (:statuses)')
            ->setParameter('okreg', $okreg)
            ->setParameter('statuses', [ZebranieOddzialu::STATUS_ZAKONCZONE, ZebranieOddzialu::STATUS_ANULOWANE])
            ->getQuery()
            ->getSingleScalarResult();

        $completed = $this->createQueryBuilder('z')
            ->select('COUNT(z.id)')
            ->join('z.oddzial', 'o')
            ->where('o.okreg = :okreg')
            ->andWhere('z.status = :status')
            ->setParameter('okreg', $okreg)
            ->setParameter('status', ZebranieOddzialu::STATUS_ZAKONCZONE)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'active' => (int) $active,
            'completed' => (int) $completed,
        ];
    }
}
