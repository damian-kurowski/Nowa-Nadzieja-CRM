<?php

namespace App\Service;

use App\Entity\BylyCzlonek;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CzlonekService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Przenosi członka do byłych członków.
     */
    public function przeniesDoByiychCzlonkow(User $user, string $powodZakonczenia, ?\DateTimeInterface $dataZakonczenia = null): BylyCzlonek
    {
        // Sprawdź czy to rzeczywiście członek
        if (!in_array($user->getTypUzytkownika(), ['czlonek', 'kandydat'])) {
            throw new \InvalidArgumentException('Można przenieść tylko członków i kandydatów do byłych członków');
        }

        if (null === $dataZakonczenia) {
            $dataZakonczenia = new \DateTime();
        }

        // Utwórz nowy rekord byłego członka
        $bylyCzlonek = new BylyCzlonek();
        $bylyCzlonek->setImie($user->getImie());
        $bylyCzlonek->setDrugieImie($user->getDrugieImie());
        $bylyCzlonek->setNazwisko($user->getNazwisko());
        $bylyCzlonek->setPesel($user->getPesel());
        $bylyCzlonek->setDataUrodzenia($user->getDataUrodzenia());
        $bylyCzlonek->setPlec($user->getPlec());
        $bylyCzlonek->setEmail($user->getEmail());
        $bylyCzlonek->setTelefon($user->getTelefon());
        $bylyCzlonek->setAdresZamieszkania($user->getAdresZamieszkania());
        $bylyCzlonek->setSocialMedia($user->getSocialMedia());
        $bylyCzlonek->setInformacjeOMnie($user->getInformacjeOmnie());
        $bylyCzlonek->setZdjecie($user->getZdjecie());
        $bylyCzlonek->setCv($user->getCv());
        $bylyCzlonek->setOkreg($user->getOkreg());
        $bylyCzlonek->setOddzial($user->getOddzial());
        $bylyCzlonek->setRodzajCzlonkostwa($user->getTypUzytkownika());
        $bylyCzlonek->setDataPrzyjecia($user->getDataPrzyjeciaDoPartii());
        $bylyCzlonek->setDataZlozeniaDeklaracji($user->getDataZlozeniaDeklaracji());
        $bylyCzlonek->setNumerWPartii($user->getNumerWPartii());
        $bylyCzlonek->setDodatkoweInformacje($user->getDodatkoweInformacje());
        $bylyCzlonek->setNotatkaWewnetrzna($user->getNotatkaWewnetrzna());
        $bylyCzlonek->setOldId($user->getOldId());

        // Ustaw dane związane z zakończeniem członkostwa
        $bylyCzlonek->setPowodZakonczeniaCzlonkostwa($powodZakonczenia);
        $bylyCzlonek->setDataZakonczeniaCzlonkostwa($dataZakonczenia);
        $bylyCzlonek->setOryginalnyIdCzlonka($user->getId());

        // Zapisz byłego członka
        $this->entityManager->persist($bylyCzlonek);

        // Usuń użytkownika (traci dostęp do systemu)
        $this->entityManager->remove($user);

        // Wykonaj operacje w transakcji
        $this->entityManager->flush();

        return $bylyCzlonek;
    }

    /**
     * Przywraca byłego członka do aktywnych członków.
     */
    public function przywrocBylegoClzonka(BylyCzlonek $bylyCzlonek, string $haslo): User
    {
        // Utwórz nowy rekord użytkownika
        $user = new User();
        $user->setImie($bylyCzlonek->getImie());
        $user->setDrugieImie($bylyCzlonek->getDrugieImie());
        $user->setNazwisko($bylyCzlonek->getNazwisko());
        $user->setPesel($bylyCzlonek->getPesel());
        $user->setDataUrodzenia($bylyCzlonek->getDataUrodzenia());
        $user->setPlec($bylyCzlonek->getPlec());
        $user->setEmail($bylyCzlonek->getEmail());
        $user->setTelefon($bylyCzlonek->getTelefon());
        $user->setAdresZamieszkania($bylyCzlonek->getAdresZamieszkania());
        $user->setSocialMedia($bylyCzlonek->getSocialMedia());
        $user->setInformacjeOmnie($bylyCzlonek->getInformacjeOMnie());
        $user->setZdjecie($bylyCzlonek->getZdjecie());
        $user->setCv($bylyCzlonek->getCv());
        $user->setOkreg($bylyCzlonek->getOkreg());
        $user->setOddzial($bylyCzlonek->getOddzial());
        
        // Przywróć jako członka (nie kandydata)
        $user->setTypUzytkownika('czlonek');
        $user->setStatus('aktywny');
        
        $user->setDataPrzyjeciaDoPartii($bylyCzlonek->getDataPrzyjecia());
        $user->setDataZlozeniaDeklaracji($bylyCzlonek->getDataZlozeniaDeklaracji());
        $user->setNumerWPartii($bylyCzlonek->getNumerWPartii());
        $user->setDodatkoweInformacje($bylyCzlonek->getDodatkoweInformacje());
        $user->setNotatkaWewnetrzna($bylyCzlonek->getNotatkaWewnetrzna());
        $user->setOldId($bylyCzlonek->getOldId());
        
        // Ustaw nowe hasło
        $hashedPassword = $this->passwordHasher->hashPassword($user, $haslo);
        $user->setPassword($hashedPassword);
        
        // Ustaw podstawową rolę
        $user->setRoles(['ROLE_USER', 'ROLE_CZLONEK_PARTII']);

        // Zapisz użytkownika
        $this->entityManager->persist($user);

        // Usuń byłego członka
        $this->entityManager->remove($bylyCzlonek);

        // Wykonaj operacje w transakcji
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Sprawdza czy użytkownik może zostać przeniesiony do byłych członków.
     */
    public function canMoveToFormerMembers(User $user): bool
    {
        // Sprawdź czy to członek lub kandydat
        if (!in_array($user->getTypUzytkownika(), ['czlonek', 'kandydat'])) {
            return false;
        }

        // Dodaj tutaj logikę sprawdzania czy można przenieść członka
        // np. czy nie ma aktywnych zobowiązań, dokumentów do podpisu itp.
        return true;
    }

    /**
     * Sprawdza czy były członek może zostać przywrócony.
     */
    public function canRestoreFormerMember(BylyCzlonek $bylyCzlonek): bool
    {
        // Sprawdź czy email nie jest już zajęty przez aktywnego użytkownika
        $existingUser = $this->userRepository->findOneBy(['email' => $bylyCzlonek->getEmail()]);

        return null === $existingUser;
    }

    /**
     * Konwertuje kandydata na członka.
     */
    public function promujKandydataNaCzlonka(User $kandydat): void
    {
        if ($kandydat->getTypUzytkownika() !== 'kandydat') {
            throw new \InvalidArgumentException('Tylko kandydaci mogą być promowani na członków');
        }

        $kandydat->setTypUzytkownika('czlonek');
        $kandydat->setDataPrzyjeciaDoPartii(new \DateTime());
        
        // Dodaj rolę członka partii
        $roles = $kandydat->getRoles();
        if (!in_array('ROLE_CZLONEK_PARTII', $roles)) {
            $roles[] = 'ROLE_CZLONEK_PARTII';
            $kandydat->setRoles($roles);
        }

        $this->entityManager->flush();
    }
}