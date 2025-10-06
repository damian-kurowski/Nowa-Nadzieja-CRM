<?php

namespace App\Repository;

use App\Entity\Dokument;
use App\Entity\Okreg;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dokument>
 *
 * @method Dokument|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dokument|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method Dokument[]    findAll()
 * @method Dokument[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class DokumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dokument::class);
    }

    /**
     * Znajduje dokumenty dla okręgu użytkownika lub wszystkie dla admina/zarządu krajowego.
     *
     * @return array<int, Dokument>
     */
    public function findForUserDistrict(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.tworca', 'tworca')
            ->leftJoin('d.kandydat', 'kandydat')
            ->leftJoin('d.okreg', 'okreg')
            ->leftJoin('d.podpisy', 'podpisy')
            ->leftJoin('podpisy.podpisujacy', 'podpisujacy')
            ->addSelect('tworca', 'kandydat', 'okreg', 'podpisy', 'podpisujacy');

        $userRoles = $user->getRoles();

        // Admin i zarząd krajowy widzą wszystkie dokumenty
        if (in_array('ROLE_ADMIN', $userRoles) || in_array('ROLE_ZARZAD_KRAJOWY', $userRoles)) {
            // Admini widzą wszystkie dokumenty bez ograniczeń
            // Filtr statusu jest opcjonalny
        } elseif (in_array('ROLE_ZARZAD_OKREGU', $userRoles)) {
            // Zarząd okręgu widzi wszystkie podpisane dokumenty ze swojego okręgu + dokumenty, które dotyczą jego lub może podpisywać
            $qb->where(
                $qb->expr()->orX(
                    // Dokumenty, które dotyczą użytkownika (dowolny status)
                    'd.kandydat = :user',
                    'd.czlonek = :user',
                    // Dokumenty, które może podpisywać (dowolny status)
                    'podpisy.podpisujacy = :user',
                    // Wszystkie podpisane dokumenty z okręgu
                    $qb->expr()->andX(
                        'd.okreg = :okreg',
                        'd.status = :podpisanyStatus'
                    )
                )
            )
            ->setParameter('user', $user)
            ->setParameter('okreg', $user->getOkreg())
            ->setParameter('podpisanyStatus', Dokument::STATUS_PODPISANY);
        } elseif (in_array('ROLE_ZARZAD_ODDZIALU', $userRoles)) {
            // Zarząd oddziału widzi podpisane dokumenty dotyczące osób ze swojego oddziału + dokumenty które dotyczą jego lub może podpisywać
            $qb->leftJoin('d.kandydat', 'kandydatUser')
                ->leftJoin('d.czlonek', 'czlonekUser')
                ->where(
                    $qb->expr()->orX(
                        // Dokumenty które dotyczą użytkownika (dowolny status)
                        'd.kandydat = :user',
                        'd.czlonek = :user',
                        // Dokumenty które może podpisywać (dowolny status)
                        'podpisy.podpisujacy = :user',
                        // Podpisane dokumenty kandydatów/członków z oddziału
                        $qb->expr()->andX(
                            $qb->expr()->orX(
                                'kandydatUser.oddzial = :oddzial',
                                'czlonekUser.oddzial = :oddzial'
                            ),
                            'd.status = :podpisanyStatus'
                        )
                    )
                )
                ->setParameter('user', $user)
                ->setParameter('oddzial', $user->getOddzial())
                ->setParameter('podpisanyStatus', Dokument::STATUS_PODPISANY);
        } else {
            // Zwykli użytkownicy widzą dokumenty które:
            // 1. Ich dotyczą (są kandydatem lub członkiem)
            // 2. Mogą podpisywać
            // 3. Są z ich okręgu (tylko podpisane)
            $qb->where(
                $qb->expr()->orX(
                    // Dokumenty które dotyczą użytkownika
                    'd.kandydat = :user',
                    'd.czlonek = :user',
                    // Dokumenty które może podpisywać
                    'podpisy.podpisujacy = :user',
                    // Dokumenty z okręgu (tylko podpisane)
                    $qb->expr()->andX(
                        'd.okreg = :okreg',
                        'd.status = :podpisanyStatus'
                    )
                )
            )
            ->setParameter('user', $user)
            ->setParameter('okreg', $user->getOkreg())
            ->setParameter('podpisanyStatus', Dokument::STATUS_PODPISANY);
        }

        // Filtr statusu - jeśli podano konkretny status
        if ($status) {
            $qb->andWhere('d.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('d.dataUtworzenia', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Znajduje dokumenty oczekujące na podpis użytkownika.
     *
     * @return array<int, Dokument>
     */
    public function findAwaitingUserSignature(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.podpisy', 'p')
            ->leftJoin('d.tworca', 'tworca')
            ->leftJoin('d.kandydat', 'kandydat')
            ->leftJoin('d.okreg', 'okreg')
            ->addSelect('p', 'tworca', 'kandydat', 'okreg')
            ->where('p.podpisujacy = :user')
            ->andWhere('p.status = :status')
            ->andWhere('d.status = :dokStatus')
            ->setParameter('user', $user)
            ->setParameter('status', 'oczekuje')
            ->setParameter('dokStatus', Dokument::STATUS_CZEKA_NA_PODPIS)
            ->orderBy('d.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Generuje kolejny numer dokumentu dla danego dnia i okręgu.
     */
    public function generateNextDocumentNumber(string $typ, ?Okreg $okreg): string
    {
        $today = new \DateTime();
        $rok = $today->format('Y');
        $miesiac = $today->format('m');
        $dzien = $today->format('d');

        // Używamy statycznej metody z encji Dokument do uzyskania skrótu
        $typSkrot = Dokument::getTypSkrotStatic($typ);

        $okregSkrot = $okreg ? $okreg->getSkrot() : 'XX';

        // Znajdź ostatni numer dla tego dnia (wyklucz anulowane dokumenty)
        $queryBuilder = $this->createQueryBuilder('d')
            ->select('d.numerDokumentu')
            ->where('d.numerDokumentu LIKE :pattern')
            ->andWhere('d.typ = :typ')
            ->andWhere('d.status != :statusAnulowany')
            ->setParameter('pattern', "DOK/{$rok}/{$miesiac}/{$dzien}/%/{$okregSkrot}/{$typSkrot}")
            ->setParameter('typ', $typ)
            ->setParameter('statusAnulowany', Dokument::STATUS_ANULOWANY);

        if ($okreg) {
            $queryBuilder->andWhere('d.okreg = :okreg')
                        ->setParameter('okreg', $okreg);
        } else {
            $queryBuilder->andWhere('d.okreg IS NULL');
        }

        $lastNumber = $queryBuilder->orderBy('d.numerDokumentu', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $nextSequence = 1;
        if ($lastNumber) {
            // Wyciągnij numer sekwencyjny z numeru dokumentu
            $parts = explode('/', $lastNumber['numerDokumentu']);
            if (isset($parts[4])) {
                $nextSequence = (int) $parts[4] + 1;
            }
        }

        // Znajdź pierwszy dostępny numer (w przypadku luk po anulowanych dokumentach)
        $sequence = str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
        $candidateNumber = "DOK/{$rok}/{$miesiac}/{$dzien}/{$sequence}/{$okregSkrot}/{$typSkrot}";

        // Sprawdź czy ten numer już istnieje (może być anulowany dokument)
        $existingDoc = $this->findOneBy(['numerDokumentu' => $candidateNumber]);

        // Jeśli numer już istnieje, znajdź następny wolny
        while ($existingDoc !== null) {
            $nextSequence++;
            $sequence = str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
            $candidateNumber = "DOK/{$rok}/{$miesiac}/{$dzien}/{$sequence}/{$okregSkrot}/{$typSkrot}";
            $existingDoc = $this->findOneBy(['numerDokumentu' => $candidateNumber]);
        }

        return $candidateNumber;
    }

    /**
     * Znajduje dokumenty dla zwykłego członka partii - dokumenty dotyczące jego członkostwa lub które może podpisywać
     *
     * @return array<int, Dokument>
     */
    public function findForMember(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.tworca', 'tworca')
            ->leftJoin('d.kandydat', 'kandydat')
            ->leftJoin('d.czlonek', 'czlonek')
            ->leftJoin('d.okreg', 'okreg')
            ->leftJoin('d.podpisy', 'podpisy')
            ->leftJoin('podpisy.podpisujacy', 'podpisujacy')
            ->addSelect('tworca', 'kandydat', 'czlonek', 'okreg', 'podpisy', 'podpisujacy');

        $qb->where(
            $qb->expr()->orX(
                // Dokumenty których jest kandydatem (dokument przyjęcia)
                'd.kandydat = :user',
                // Dokumenty których jest członkiem (może być rezygnacja)
                'd.czlonek = :user',
                // Dokumenty które stworzył (rezygnacja z członkostwa)
                'd.tworca = :user',
                // Dokumenty które może podpisywać
                'podpisy.podpisujacy = :user'
            )
        )
        ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('d.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('d.dataUtworzenia', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Znajduje kandydatów gotowych do przyjęcia (100% postęp) dla danego okręgu.
     *
     * @return array<int, Dokument>
     */
    public function findCandidatesReadyForAcceptance(Okreg $okreg): array
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT u FROM App\Entity\User u
                WHERE u.typUzytkownika = :typ
                AND u.okreg = :okreg
                AND u.status = :status
                AND u.dataWypelnienieFormularza IS NOT NULL
                AND u.dataWeryfikacjaDokumentow IS NOT NULL
                AND u.dataRozmowaPrekwalifikacyjna IS NOT NULL
                AND u.dataOpiniaRadyOddzialu IS NOT NULL
                AND u.dataDecyzjaZarzadu IS NOT NULL
                AND u.dataPrzyjecieUroczyste IS NOT NULL
                AND NOT EXISTS (
                    SELECT d FROM App\Entity\Dokument d
                    WHERE d.kandydat = u
                    AND d.typ = :dokTyp
                    AND d.status != :anulowany
                )
                ORDER BY u.nazwisko, u.imie
            ')
            ->setParameters([
                'typ' => 'kandydat',
                'okreg' => $okreg,
                'status' => 'aktywny',
                'dokTyp' => Dokument::TYP_PRZYJECIE_CZLONKA,
                'anulowany' => Dokument::STATUS_ANULOWANY,
            ])
            ->getResult();
    }

    /**
     * Znajduje członków zarządu okręgu (bez prezesa) dla podpisów.
     *
     * @return array<int, Dokument>
     */
    public function findDistrictBoardMembers(Okreg $okreg, User $excludeUser): array
    {
        // Użyj IN subquery zamiast JOIN z DISTINCT/GROUP BY
        $subQueryBuilder = $this->getEntityManager()->createQueryBuilder();
        $subQuery = $subQueryBuilder
            ->select('IDENTITY(f2.user)')
            ->from('App\Entity\Funkcja', 'f2')
            ->where('f2.aktywna = true')
            ->andWhere($subQueryBuilder->expr()->orX(
                'f2.nazwa LIKE :wiceprezes',
                'f2.nazwa LIKE :sekretarz',
                'f2.nazwa LIKE :skarbnik',
                'f2.nazwa LIKE :czlonek_zarzadu'
            ))
            ->getDQL();

        $mainQueryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $mainQueryBuilder
            ->select('u')
            ->from('App\Entity\User', 'u')
            ->where('u.okreg = :okreg')
            ->andWhere('u.id != :excludeUser')
            ->andWhere('u.status = :status')
            ->andWhere("u.id IN ($subQuery)")
            ->orderBy('u.nazwisko, u.imie')
            ->setParameter('okreg', $okreg)
            ->setParameter('excludeUser', $excludeUser->getId())
            ->setParameter('status', 'aktywny')
            ->setParameter('wiceprezes', '%wiceprezes%okregu%')
            ->setParameter('sekretarz', '%sekretarz%okregu%')
            ->setParameter('skarbnik', '%skarbnik%okregu%')
            ->setParameter('czlonek_zarzadu', '%członek%zarządu%okregu%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statystyki dokumentów dla dashboardu.
     *
     * @return array<string, mixed>
     */
    public function getDocumentStats(User $user): array
    {
        $userRoles = $user->getRoles();
        $isAdminOrNational = in_array('ROLE_ADMIN', $userRoles) || in_array('ROLE_ZARZAD_KRAJOWY', $userRoles);

        // Dla admina i zarządu krajowego - globalne statystyki
        if ($isAdminOrNational) {
            $total = $this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $awaitingSignature = $this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->where('d.status = :status')
                ->setParameter('status', Dokument::STATUS_CZEKA_NA_PODPIS)
                ->getQuery()
                ->getSingleScalarResult();

            $signed = $this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->where('d.status = :status')
                ->setParameter('status', Dokument::STATUS_PODPISANY)
                ->getQuery()
                ->getSingleScalarResult();

            $myAwaiting = $this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->leftJoin('d.podpisy', 'p')
                ->where('p.podpisujacy = :user')
                ->andWhere('p.status = :podpisStatus')
                ->andWhere('d.status = :dokStatus')
                ->setParameter('user', $user)
                ->setParameter('podpisStatus', 'oczekuje')
                ->setParameter('dokStatus', Dokument::STATUS_CZEKA_NA_PODPIS)
                ->getQuery()
                ->getSingleScalarResult();

            return [
                'total' => (int) $total,
                'awaiting_signature' => (int) $awaitingSignature,
                'signed' => (int) $signed,
                'my_awaiting' => (int) $myAwaiting,
            ];
        }

        // Dla zwykłych użytkowników - statystyki obejmujące dokumenty dotyczące ich + okręg
        $okreg = $user->getOkreg();
        if (!$okreg) {
            return [
                'total' => 0,
                'awaiting_signature' => 0,
                'signed' => 0,
                'my_awaiting' => 0,
            ];
        }

        // Dokumenty które użytkownik może zobaczyć (dotyczące go + podpisane z okręgu)
        $total = $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->leftJoin('d.podpisy', 'p')
            ->where(
                $this->createQueryBuilder('d')->expr()->orX(
                    'd.kandydat = :user',
                    'd.czlonek = :user',
                    'p.podpisujacy = :user',
                    $this->createQueryBuilder('d')->expr()->andX(
                        'd.okreg = :okreg',
                        'd.status = :podpisanyStatus'
                    )
                )
            )
            ->setParameter('user', $user)
            ->setParameter('okreg', $okreg)
            ->setParameter('podpisanyStatus', Dokument::STATUS_PODPISANY)
            ->getQuery()
            ->getSingleScalarResult();

        $awaitingSignature = $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->leftJoin('d.podpisy', 'p')
            ->where(
                $this->createQueryBuilder('d')->expr()->orX(
                    'd.kandydat = :user',
                    'd.czlonek = :user',
                    'p.podpisujacy = :user',
                    'd.okreg = :okreg'
                )
            )
            ->andWhere('d.status = :status')
            ->setParameter('user', $user)
            ->setParameter('okreg', $okreg)
            ->setParameter('status', Dokument::STATUS_CZEKA_NA_PODPIS)
            ->getQuery()
            ->getSingleScalarResult();

        $signed = $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id)')
            ->leftJoin('d.podpisy', 'p')
            ->where(
                $this->createQueryBuilder('d')->expr()->orX(
                    'd.kandydat = :user',
                    'd.czlonek = :user',
                    'p.podpisujacy = :user',
                    'd.okreg = :okreg'
                )
            )
            ->andWhere('d.status = :status')
            ->setParameter('user', $user)
            ->setParameter('okreg', $okreg)
            ->setParameter('status', Dokument::STATUS_PODPISANY)
            ->getQuery()
            ->getSingleScalarResult();

        $myAwaiting = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->leftJoin('d.podpisy', 'p')
            ->where('d.okreg = :okreg')
            ->andWhere('p.podpisujacy = :user')
            ->andWhere('p.status = :podpisStatus')
            ->andWhere('d.status = :dokStatus')
            ->setParameter('okreg', $okreg)
            ->setParameter('user', $user)
            ->setParameter('podpisStatus', 'oczekuje')
            ->setParameter('dokStatus', Dokument::STATUS_CZEKA_NA_PODPIS)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'awaiting_signature' => (int) $awaitingSignature,
            'signed' => (int) $signed,
            'my_awaiting' => (int) $myAwaiting,
        ];
    }


    public function save(Dokument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Dokument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Znajdź niepodpisane dokumenty dla zebrania.
     *
     * @return array<int, Dokument>
     */
    public function findUnfinishedByZebranie(\App\Entity\ZebranieOddzialu $zebranie): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.zebranieOddzialu = :zebranie')
            ->andWhere('d.status IN (:pendingStatuses)')
            ->setParameter('zebranie', $zebranie)
            ->setParameter('pendingStatuses', [
                Dokument::STATUS_CZEKA_NA_PODPIS,
                Dokument::STATUS_DRAFT,
            ])
            ->orderBy('d.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Policz niepodpisane dokumenty dla zebrania.
     */
    public function countUnfinishedByZebranie(\App\Entity\ZebranieOddzialu $zebranie): int
    {
        $result = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.zebranieOddzialu = :zebranie')
            ->andWhere('d.status IN (:pendingStatuses)')
            ->setParameter('zebranie', $zebranie)
            ->setParameter('pendingStatuses', [
                Dokument::STATUS_CZEKA_NA_PODPIS,
                Dokument::STATUS_DRAFT,
            ])
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }

    /**
     * Znajdź dokumenty czekające na podpis użytkownika dla danego zebrania.
     *
     * @return array<int, Dokument>
     */
    public function findAwaitingSignatureByZebranieAndUser(\App\Entity\ZebranieOddzialu $zebranie, User $user): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.podpisy', 'p')
            ->where('d.zebranieOddzialu = :zebranie')
            ->andWhere('p.podpisujacy = :user')
            ->andWhere('p.status = :status')
            ->andWhere('d.status = :dokStatus')
            ->setParameter('zebranie', $zebranie)
            ->setParameter('user', $user)
            ->setParameter('status', 'oczekuje')
            ->setParameter('dokStatus', Dokument::STATUS_CZEKA_NA_PODPIS)
            ->orderBy('d.dataUtworzenia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajduje dokument z wszystkimi powiązanymi danymi w jednym zapytaniu.
     * Zapobiega problemom z memory exhaustion przez lazy loading.
     */
    public function findWithAllRelations(int $id): ?Dokument
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.tworca', 'tworca')
            ->leftJoin('d.kandydat', 'kandydat')
            ->leftJoin('d.okreg', 'okreg')
            ->leftJoin('d.podpisy', 'podpisy')
            ->leftJoin('podpisy.podpisujacy', 'podpisujacy')
            ->leftJoin('podpisujacy.funkcje', 'funkcje')
            ->leftJoin('d.zebranieOddzialu', 'zebranieOddzialu')
            ->leftJoin('d.zebranieOkregu', 'zebranieOkregu')
            ->addSelect('tworca', 'kandydat', 'okreg', 'podpisy', 'podpisujacy', 'funkcje', 'zebranieOddzialu', 'zebranieOkregu')
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
