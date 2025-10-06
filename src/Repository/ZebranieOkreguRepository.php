<?php

namespace App\Repository;

use App\Entity\ZebranieOkregu;
use App\Entity\User;
use App\Entity\Okreg;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ZebranieOkregu>
 */
class ZebranieOkreguRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZebranieOkregu::class);
    }

    /**
     * Znajdź zebrania dla konkretnego obserwatora.
     */
    public function findForObserwator(User $user): array
    {
        return $this->createQueryBuilder('z')
            ->where('z.obserwator = :user')
            ->setParameter('user', $user)
            ->orderBy('z.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź zebrania dla konkretnego okręgu.
     */
    public function findForOkreg(Okreg $okreg): array
    {
        return $this->createQueryBuilder('z')
            ->where('z.okreg = :okreg')
            ->setParameter('okreg', $okreg)
            ->orderBy('z.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź aktywne zebrania (niezakończone).
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('z')
            ->where('z.status != :zakonczone')
            ->andWhere('z.status != :anulowane')
            ->setParameter('zakonczone', ZebranieOkregu::STATUS_ZAKONCZONE)
            ->setParameter('anulowane', ZebranieOkregu::STATUS_ANULOWANE)
            ->orderBy('z.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź zebrania w których użytkownik ma jakąś rolę.
     */
    public function findForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('z');
        return $qb
            ->where(
                $qb->expr()->orX(
                    'z.obserwator = :user',
                    'z.protokolant = :user',
                    'z.prowadzacy = :user',
                    'z.prezesOkregu = :user',
                    'z.wiceprezes1 = :user',
                    'z.wiceprezes2 = :user',
                    'z.sekretarzOkregu = :user',
                    'z.skarbnikOkregu = :user'
                )
            )
            ->setParameter('user', $user)
            ->orderBy('z.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sprawdź czy w okręgu trwa jakieś zebranie.
     */
    public function hasActiveForOkreg(Okreg $okreg): bool
    {
        $count = $this->createQueryBuilder('z')
            ->select('COUNT(z.id)')
            ->where('z.okreg = :okreg')
            ->andWhere('z.status != :zakonczone')
            ->andWhere('z.status != :anulowane')
            ->setParameter('okreg', $okreg)
            ->setParameter('zakonczone', ZebranieOkregu::STATUS_ZAKONCZONE)
            ->setParameter('anulowane', ZebranieOkregu::STATUS_ANULOWANE)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Statystyki zebrań.
     */
    public function getStats(): array
    {
        $total = $this->createQueryBuilder('z')
            ->select('COUNT(z.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $completed = $this->createQueryBuilder('z')
            ->select('COUNT(z.id)')
            ->where('z.status = :status')
            ->setParameter('status', ZebranieOkregu::STATUS_ZAKONCZONE)
            ->getQuery()
            ->getSingleScalarResult();

        $active = $this->createQueryBuilder('z')
            ->select('COUNT(z.id)')
            ->where('z.status != :zakonczone')
            ->andWhere('z.status != :anulowane')
            ->setParameter('zakonczone', ZebranieOkregu::STATUS_ZAKONCZONE)
            ->setParameter('anulowane', ZebranieOkregu::STATUS_ANULOWANE)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'completed' => $completed,
            'active' => $active,
            'cancelled' => $total - $completed - $active,
        ];
    }
}