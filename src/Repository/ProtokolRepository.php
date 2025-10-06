<?php

namespace App\Repository;

use App\Entity\Protokol;
use App\Entity\User;
use App\Entity\Okreg;
use App\Entity\Oddzial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Protokol>
 */
class ProtokolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Protokol::class);
    }

    /**
     * Znajdź protokoły dla danego użytkownika
     */
    public function findForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('p');

        // Jeśli użytkownik ma rolę admin lub zarząd krajowy - widzi wszystko
        if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_ZARZAD_KRAJOWY')) {
            return $qb->orderBy('p.dataZebrania', 'DESC')->getQuery()->getResult();
        }

        // Jeśli użytkownik ma okręg - widzi protokoły swojego okręgu
        if ($user->getOkreg()) {
            $qb->where('p.okreg = :okreg')
                ->setParameter('okreg', $user->getOkreg());
        }

        // Dodatkowo widzi protokoły gdzie jest protokolantem lub przewodniczącym
        $qb->orWhere('p.protokolant = :user')
            ->orWhere('p.przewodniczacy = :user')
            ->setParameter('user', $user);

        return $qb->orderBy('p.dataZebrania', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź protokoły dla danego okręgu
     */
    public function findByOkreg(Okreg $okreg): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.okreg = :okreg')
            ->setParameter('okreg', $okreg)
            ->orderBy('p.dataZebrania', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź protokoły dla danego oddziału
     */
    public function findByOddzial(Oddzial $oddzial): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.oddzial = :oddzial')
            ->setParameter('oddzial', $oddzial)
            ->orderBy('p.dataZebrania', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Generuj następny numer protokołu dla danego okręgu/oddziału
     */
    public function generateNextNumber(Okreg $okreg = null, Oddzial $oddzial = null): string
    {
        $prefix = 'PROT';

        if ($okreg) {
            $prefix .= '/OK/' . str_pad((string)$okreg->getId(), 3, '0', STR_PAD_LEFT);
        } elseif ($oddzial) {
            $prefix .= '/OD/' . str_pad((string)$oddzial->getId(), 3, '0', STR_PAD_LEFT);
        }

        $date = (new \DateTime())->format('Y/m');

        // Znajdź ostatni numer w tym miesiącu
        $qb = $this->createQueryBuilder('p')
            ->select('p.numerProtokolu')
            ->where('p.numerProtokolu LIKE :prefix')
            ->setParameter('prefix', $prefix . '/' . $date . '%')
            ->orderBy('p.numerProtokolu', 'DESC')
            ->setMaxResults(1);

        $lastNumber = $qb->getQuery()->getOneOrNullResult();

        if ($lastNumber) {
            // Wyciągnij ostatnią część numeru i zwiększ
            $parts = explode('/', $lastNumber['numerProtokolu']);
            $sequenceNumber = (int)end($parts) + 1;
        } else {
            $sequenceNumber = 1;
        }

        return $prefix . '/' . $date . '/' . str_pad((string)$sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Statystyki protokołów
     */
    public function getStats(): array
    {
        $qb = $this->createQueryBuilder('p');

        $total = $qb->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $draft = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', Protokol::STATUS_DRAFT)
            ->getQuery()
            ->getSingleScalarResult();

        $pending = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', Protokol::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();

        $approved = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', Protokol::STATUS_APPROVED)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'draft' => $draft,
            'pending' => $pending,
            'approved' => $approved,
        ];
    }
}
