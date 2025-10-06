<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countByType(string $type): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.typUzytkownika = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }

    public function countByTypeAndOkreg(string $type, ?\App\Entity\Okreg $okreg): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.typUzytkownika = :type')
            ->andWhere('u.okreg = :okreg')
            ->setParameter('type', $type)
            ->setParameter('okreg', $okreg)
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }

    public function countByTypeAndOddzial(string $type, ?\App\Entity\Oddzial $oddzial): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.typUzytkownika = :type')
            ->andWhere('u.oddzial = :oddzial')
            ->setParameter('type', $type)
            ->setParameter('oddzial', $oddzial)
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }

    /**
     * @return array<int, User>
     */
    public function findByTypeAndOkreg(string $type, \App\Entity\Okreg $okreg): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.typUzytkownika = :type')
            ->andWhere('u.okreg = :okreg')
            ->setParameter('type', $type)
            ->setParameter('okreg', $okreg)
            ->orderBy('u.nazwisko', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, User>
     */
    public function findByTypeAndOddzial(string $type, \App\Entity\Oddzial $oddzial): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.typUzytkownika = :type')
            ->andWhere('u.oddzial = :oddzial')
            ->setParameter('type', $type)
            ->setParameter('oddzial', $oddzial)
            ->orderBy('u.nazwisko', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, User>
     */
    public function findCzlonkowieWithOpinie(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.opinie', 'o')
            ->andWhere('u.typUzytkownika IN (:types)')
            ->setParameter('types', ['czlonek', 'kandydat'])
            ->addSelect('o')
            ->orderBy('u.nazwisko', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function createQueryBuilderForUser(?\Symfony\Component\Security\Core\User\UserInterface $user, ?string $type = null): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        if ($type) {
            $qb->andWhere('u.typUzytkownika = :type')
               ->setParameter('type', $type);
        }

        // Type assertion to access User-specific methods
        /** @var User $userEntity */
        $userEntity = $user;

        // Filtrowanie na podstawie uprawnień użytkownika
        if (in_array('ROLE_ADMIN', $userEntity->getRoles())
            || in_array('ROLE_PREZES_PARTII', $userEntity->getRoles())
            || in_array('ROLE_WICEPREZES_PARTII', $userEntity->getRoles())
            || in_array('ROLE_SEKRETARZ_PARTII', $userEntity->getRoles())
            || in_array('ROLE_SKARBNIK_PARTII', $userEntity->getRoles())
            || in_array('ROLE_RZECZNIK_PRASOWY', $userEntity->getRoles())
            || in_array('ROLE_PELNOMOCNIK_STRUKTUR', $userEntity->getRoles())) {
            // Widzi wszystkich na poziomie krajowym
        } elseif ($userEntity->getOkreg() && (
            in_array('ROLE_PREZES_OKREGU', $userEntity->getRoles())
            || in_array('ROLE_WICEPREZES_OKREGU', $userEntity->getRoles())
            || in_array('ROLE_SEKRETARZ_OKREGU', $userEntity->getRoles())
            || in_array('ROLE_SKARBNIK_OKREGU', $userEntity->getRoles())
        )) {
            // Widzi tylko swój okręg
            $qb->andWhere('u.okreg = :okreg')
               ->setParameter('okreg', $userEntity->getOkreg());
        } elseif ($userEntity->getOddzial() && (
            in_array('ROLE_PRZEWODNICZACY_ODDZIALU', $userEntity->getRoles())
            || in_array('ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU', $userEntity->getRoles())
            || in_array('ROLE_SEKRETARZ_ODDZIALU', $userEntity->getRoles())
        )) {
            // Widzi tylko swój oddział
            $qb->andWhere('u.oddzial = :oddzial')
               ->setParameter('oddzial', $userEntity->getOddzial());
        } else {
            // Zwykły członek widzi tylko siebie
            $qb->andWhere('u.id = :userId')
               ->setParameter('userId', $userEntity->getId());
        }

        return $qb;
    }

    /**
     * @return array<int, User>
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode([$role]))
            ->getQuery()
            ->getResult();
    }

    /**
     * Zwraca użytkowników którym dany użytkownik może wysłać zaproszenie na wydarzenie.
     */
    /**
     * @return array<int, User>
     */
    public function getInvitableUsersForEvent(User $currentUser, mixed $wydarzenie = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.okreg', 'o')
            ->leftJoin('u.oddzial', 'od')
            ->where('u.status = :status')
            ->andWhere('u.czyBylyCzlonek = false')
            ->setParameter('status', 'aktywny');

        // Logika filtrowania na podstawie uprawnień organizatora wydarzenia
        if (in_array('ROLE_ADMIN', $currentUser->getRoles())
            || in_array('ROLE_PREZES_PARTII', $currentUser->getRoles())
            || in_array('ROLE_WICEPREZES_PARTII', $currentUser->getRoles())
            || in_array('ROLE_SEKRETARZ_PARTII', $currentUser->getRoles())
            || in_array('ROLE_SKARBNIK_PARTII', $currentUser->getRoles())
            || in_array('ROLE_RZECZNIK_PRASOWY', $currentUser->getRoles())) {
            // Zarząd krajowy może zapraszać wszystkich
            // Jeśli wydarzenie ma przypisany okręg/oddział, zawęź do tego zakresu
            if ($wydarzenie) {
                if ($wydarzenie->getOddzial()) {
                    $qb->andWhere('u.oddzial = :oddzial')
                       ->setParameter('oddzial', $wydarzenie->getOddzial());
                } elseif ($wydarzenie->getOkreg()) {
                    $qb->andWhere('u.okreg = :okreg')
                       ->setParameter('okreg', $wydarzenie->getOkreg());
                }
            }
        } elseif (
            in_array('ROLE_PREZES_OKREGU', $currentUser->getRoles())
            || in_array('ROLE_WICEPREZES_OKREGU', $currentUser->getRoles())
            || in_array('ROLE_SEKRETARZ_OKREGU', $currentUser->getRoles())
            || in_array('ROLE_SKARBNIK_OKREGU', $currentUser->getRoles())
        ) {
            // Zarząd okręgu może zapraszać tylko ze swojego okręgu
            $qb->andWhere('u.okreg = :okreg')
               ->setParameter('okreg', $currentUser->getOkreg());
        } elseif (
            in_array('ROLE_PRZEWODNICZACY_ODDZIALU', $currentUser->getRoles())
            || in_array('ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU', $currentUser->getRoles())
            || in_array('ROLE_SEKRETARZ_ODDZIALU', $currentUser->getRoles())
        ) {
            // Zarząd oddziału może zapraszać tylko ze swojego oddziału
            $qb->andWhere('u.oddzial = :oddzial')
               ->setParameter('oddzial', $currentUser->getOddzial());
        } else {
            // Zwykli użytkownicy nie mogą nikogo zapraszać (tylko funkcyjni mogą tworzyć wydarzenia)
            $qb->andWhere('1 = 0'); // Zawsze zwróci pusty wynik
        }

        return $qb->orderBy('u.nazwisko', 'ASC')
                  ->addOrderBy('u.imie', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Sprawdza czy użytkownik może zaprosić daną osobę na wydarzenie.
     */
    public function canUserInviteToEvent(User $inviter, User $invitee, mixed $wydarzenie = null): bool
    {
        // Admin i zarząd krajowy może zapraszać wszystkich
        if (in_array('ROLE_ADMIN', $inviter->getRoles())
            || in_array('ROLE_PREZES_PARTII', $inviter->getRoles())
            || in_array('ROLE_WICEPREZES_PARTII', $inviter->getRoles())
            || in_array('ROLE_SEKRETARZ_PARTII', $inviter->getRoles())
            || in_array('ROLE_SKARBNIK_PARTII', $inviter->getRoles())
            || in_array('ROLE_RZECZNIK_PRASOWY', $inviter->getRoles())) {
            return true;
        }

        // Zarząd okręgu może zapraszać tylko ze swojego okręgu
        if (in_array('ROLE_PREZES_OKREGU', $inviter->getRoles())
            || in_array('ROLE_WICEPREZES_OKREGU', $inviter->getRoles())
            || in_array('ROLE_SEKRETARZ_OKREGU', $inviter->getRoles())
            || in_array('ROLE_SKARBNIK_OKREGU', $inviter->getRoles())) {
            return $inviter->getOkreg() && $inviter->getOkreg() === $invitee->getOkreg();
        }

        // Zarząd oddziału może zapraszać tylko ze swojego oddziału
        if (in_array('ROLE_PRZEWODNICZACY_ODDZIALU', $inviter->getRoles())
            || in_array('ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU', $inviter->getRoles())
            || in_array('ROLE_SEKRETARZ_ODDZIALU', $inviter->getRoles())) {
            return $inviter->getOddzial() && $inviter->getOddzial() === $invitee->getOddzial();
        }

        return false;
    }

    /**
     * Znajdź użytkownika z określoną rolą.
     * 
     * @param string $role Rola do wyszukania (np. 'ROLE_SEKRETARZ_PARTII')
     * @return User|null
     */
    public function findOneByRole(string $role): ?User
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Używamy native SQL dla PostgreSQL z JSON_CONTAINS lub @> operator
        $sql = 'SELECT * FROM "user" u WHERE u.roles::jsonb @> :role LIMIT 1';
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['role' => '["' . $role . '"]']);
        
        $data = $result->fetchAssociative();
        if (!$data) {
            return null;
        }
        
        // Hydratuj entity
        return $this->getEntityManager()->find(User::class, $data['id']);
    }
}
