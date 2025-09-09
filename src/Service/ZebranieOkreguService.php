<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\ZebranieOkregu;
use App\Entity\Okreg;
use App\Entity\Dokument;
use App\Entity\PodpisDokumentu;
use App\Service\DokumentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ZebranieOkreguService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private DokumentService $dokumentService,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    /**
     * Rozpoczyna nowe zebranie okręgu z automatycznym nadawaniem ról.
     */
    public function rozpocznijZebranie(Okreg $okreg, User $obserwator, User $utworzonyPrzez): ZebranieOkregu
    {
        $zebranie = new ZebranieOkregu();
        $zebranie->setOkreg($okreg);
        $zebranie->setObserwator($obserwator);
        $zebranie->setUtworzonyPrzez($utworzonyPrzez);
        $zebranie->setStatus(ZebranieOkregu::STATUS_WYZNACZANIE_PROTOKOLANTA);

        $this->entityManager->persist($zebranie);
        
        // Nadaj rolę obserwatora zebrania
        $this->nadajTymczasowaRole($obserwator, 'ROLE_OBSERWATOR_ZEBRANIA');
        
        $this->entityManager->flush();

        // Odśwież token obserwatora jeśli to aktualnie zalogowany użytkownik
        $this->refreshUserTokenIfNeeded($obserwator);

        $this->logMeetingActivity($zebranie, 'meeting_started', [
            'okreg' => $okreg->getNazwa(),
            'obserwator' => $obserwator->getFullName(),
            'obserwator_id' => $obserwator->getId(),
            'utworzony_przez' => $utworzonyPrzez->getFullName(),
        ]);

        return $zebranie;
    }

    /**
     * Wyznacza protokolanta zebrania z automatycznym nadawaniem roli.
     */
    public function wyznaczProtokolanta(ZebranieOkregu $zebranie, User $protokolant): void
    {
        $zebranie->setProtokolant($protokolant);
        $zebranie->setStatus(ZebranieOkregu::STATUS_WYZNACZANIE_PROWADZACEGO);
        
        // Nadaj rolę protokolanta zebrania
        $this->nadajTymczasowaRole($protokolant, 'ROLE_PROTOKOLANT_ZEBRANIA');
        
        $this->entityManager->flush();
        
        // Odśwież token protokolanta
        $this->refreshUserTokenIfNeeded($protokolant);

        $this->logMeetingActivity($zebranie, 'secretary_appointed', [
            'protokolant' => $protokolant->getFullName(),
            'protokolant_id' => $protokolant->getId(),
        ]);
    }

    /**
     * Wyznacza prowadzącego zebrania z automatycznym nadawaniem roli.
     */
    public function wyznaczProwadzacego(ZebranieOkregu $zebranie, User $prowadzacy): void
    {
        $zebranie->setProwadzacy($prowadzacy);
        $zebranie->setStatus(ZebranieOkregu::STATUS_WYBOR_PREZESA);
        
        // Nadaj rolę prowadzącego zebrania
        $this->nadajTymczasowaRole($prowadzacy, 'ROLE_PROWADZACY_ZEBRANIA');
        
        $this->entityManager->flush();
        
        // Odśwież token prowadzącego
        $this->refreshUserTokenIfNeeded($prowadzacy);

        $this->logMeetingActivity($zebranie, 'chairman_appointed', [
            'prowadzacy' => $prowadzacy->getFullName(),
            'prowadzacy_id' => $prowadzacy->getId(),
        ]);
    }

    /**
     * Kończy zebranie i usuwa tymczasowe role.
     */
    /**
     * Wybiera prezesa okręgu i przechodzi do wyboru wiceprezesów.
     */
    public function wyborPrezesa(ZebranieOkregu $zebranie, User $prezes): void
    {
        // Usuń poprzedniego prezesa okręgu jeśli istnieje
        $this->odwolajPoprzedniegoPrezes($zebranie->getOkreg());
        
        $zebranie->setPrezesOkregu($prezes);
        $zebranie->setStatus(ZebranieOkregu::STATUS_WYBOR_WICEPREZESOW);
        
        $this->entityManager->flush();

        $this->logMeetingActivity($zebranie, 'president_selected', [
            'prezes' => $prezes->getFullName(),
            'prezes_id' => $prezes->getId(),
        ]);
    }

    /**
     * Wybiera wiceprezesów okręgu i przechodzi do akceptacji.
     */
    public function wyborWiceprezesow(ZebranieOkregu $zebranie, User $wiceprezes1, ?User $wiceprezes2 = null): void
    {
        // Usuń poprzednich wiceprezesów okręgu jeśli istnieją
        $this->odwolajPoprzednichWiceprezesow($zebranie->getOkreg());
        
        $zebranie->setWiceprezes1($wiceprezes1);
        if ($wiceprezes2) {
            $zebranie->setWiceprezes2($wiceprezes2);
        }
        $zebranie->setStatus(ZebranieOkregu::STATUS_SKLADANIE_PODPISOW);
        
        $this->entityManager->flush();

        $this->logMeetingActivity($zebranie, 'vice_presidents_selected', [
            'wiceprezes1' => $wiceprezes1->getFullName(),
            'wiceprezes1_id' => $wiceprezes1->getId(),
            'wiceprezes2' => $wiceprezes2?->getFullName(),
            'wiceprezes2_id' => $wiceprezes2?->getId(),
        ]);
    }

    /**
     * Składa podpis uczestnika zebrania.
     */
    public function zlozPodpis(ZebranieOkregu $zebranie, User $user, string $podpisData = null): void
    {
        if ($user === $zebranie->getObserwator()) {
            $zebranie->setObserwatorPodpisal(true);
            if ($podpisData) {
                $zebranie->setPodpisObserwatora($podpisData);
            }
        } elseif ($user === $zebranie->getProtokolant()) {
            $zebranie->setProtokolantPodpisal(true);
            if ($podpisData) {
                $zebranie->setPodpisProtokolanta($podpisData);
            }
        } elseif ($user === $zebranie->getProwadzacy()) {
            $zebranie->setProwadzacyPodpisal(true);
            if ($podpisData) {
                $zebranie->setPodpisProwadzacego($podpisData);
            }
        }

        $this->entityManager->flush();

        // Sprawdź czy wszyscy podpisali - jeśli tak, generuj dokumenty i przejdź do akceptacji
        if ($zebranie->czyWszyscyPodpisali()) {
            $this->logger->info('Wszyscy uczestnicy podpisali - rozpoczynam generowanie dokumentów', [
                'zebranie_id' => $zebranie->getId(),
            ]);
            $this->generujDokumentyIprzejdzDoAkceptacji($zebranie);
        } else {
            $this->logger->info('Nie wszyscy podpisali jeszcze', [
                'zebranie_id' => $zebranie->getId(),
                'obserwator' => $zebranie->getObserwatorPodpisal(),
                'protokolant' => $zebranie->getProtokolantPodpisal(),
                'prowadzacy' => $zebranie->getProwadzacyPodpisal(),
            ]);
        }

        $this->logMeetingActivity($zebranie, 'signature_submitted', [
            'signatory' => $user->getFullName(),
            'signatory_id' => $user->getId(),
            'wszystkie_podpisy' => $zebranie->czyWszyscyPodpisali(),
            'has_signature_data' => $podpisData !== null,
        ]);
    }

    /**
     * Generuje dokumenty i przechodzi do fazy akceptacji.
     */
    private function generujDokumentyIprzejdzDoAkceptacji(ZebranieOkregu $zebranie): void
    {
        // Generuj dokumenty wyborów
        $this->generujDokumentyWyborow($zebranie);

        // Przejdź do fazy akceptacji
        $zebranie->setStatus(ZebranieOkregu::STATUS_OCZEKUJE_NA_AKCEPTACJE);
        $this->entityManager->flush();

        $this->logMeetingActivity($zebranie, 'documents_generated_and_ready_for_acceptance', [
            'documents_count' => $zebranie->getDokumenty()->count(),
        ]);
    }

    /**
     * Akceptuje zebranie przez uczestnika.
     */
    public function akceptujZebranie(ZebranieOkregu $zebranie, User $user): void
    {
        if ($user === $zebranie->getObserwator()) {
            $zebranie->setObserwatorZaakceptowal(true);
        } elseif ($user === $zebranie->getProtokolant()) {
            $zebranie->setProtokolantZaakceptowal(true);
        } elseif ($user === $zebranie->getProwadzacy()) {
            $zebranie->setProwadzacyZaakceptowal(true);
        }

        $this->entityManager->flush();

        // Sprawdź czy wszyscy zaakceptowali - jeśli tak, zakończ zebranie
        if ($zebranie->czyWszyscyZaakceptowali()) {
            $this->finalizujZebranie($zebranie);
        }

        $this->logMeetingActivity($zebranie, 'meeting_accepted', [
            'acceptor' => $user->getFullName(),
            'acceptor_id' => $user->getId(),
            'wszystkie_akceptacje' => $zebranie->czyWszyscyZaakceptowali(),
        ]);
    }

    /**
     * Finalizuje zebranie - nadaje nowe role i generuje dokumenty.
     */
    private function finalizujZebranie(ZebranieOkregu $zebranie): void
    {
        // Nadaj nowe role w okręgu
        if ($zebranie->getPrezesOkregu()) {
            $this->nadajStalaRole($zebranie->getPrezesOkregu(), 'ROLE_PREZES_OKREGU');
        }
        if ($zebranie->getWiceprezes1()) {
            $this->nadajStalaRole($zebranie->getWiceprezes1(), 'ROLE_WICEPREZES_OKREGU');
        }
        if ($zebranie->getWiceprezes2()) {
            $this->nadajStalaRole($zebranie->getWiceprezes2(), 'ROLE_WICEPREZES_OKREGU');
        }

        $zebranie->setStatus(ZebranieOkregu::STATUS_ZAKONCZONE);
        $zebranie->setDataZakonczenia(new \DateTime());

        $this->entityManager->flush();

        // Usuń tymczasowe role
        $this->usunTymczasowaRole($zebranie->getObserwator(), 'ROLE_OBSERWATOR_ZEBRANIA');
        if ($zebranie->getProtokolant()) {
            $this->usunTymczasowaRole($zebranie->getProtokolant(), 'ROLE_PROTOKOLANT_ZEBRANIA');
        }
        if ($zebranie->getProwadzacy()) {
            $this->usunTymczasowaRole($zebranie->getProwadzacy(), 'ROLE_PROWADZACY_ZEBRANIA');
        }

        $this->entityManager->flush();

        $this->logMeetingActivity($zebranie, 'meeting_finalized', [
            'data_zakonczenia' => $zebranie->getDataZakonczenia()?->format('Y-m-d H:i:s'),
            'prezes' => $zebranie->getPrezesOkregu()?->getFullName(),
            'wiceprezes1' => $zebranie->getWiceprezes1()?->getFullName(),
            'wiceprezes2' => $zebranie->getWiceprezes2()?->getFullName(),
        ]);
    }

    /**
     * Usuwa poprzedniego prezesa okręgu.
     */
    private function odwolajPoprzedniegoPrezes(Okreg $okreg): void
    {
        $users = $this->entityManager->getRepository(User::class)->findBy(['okreg' => $okreg]);
        foreach ($users as $user) {
            $roles = $user->getRoles();
            $roles = array_filter($roles, fn($role) => $role !== 'ROLE_PREZES_OKREGU');
            $user->setRoles(array_values($roles));
        }
    }

    /**
     * Usuwa poprzednich wiceprezesów okręgu.
     */
    private function odwolajPoprzednichWiceprezesow(Okreg $okreg): void
    {
        $users = $this->entityManager->getRepository(User::class)->findBy(['okreg' => $okreg]);
        foreach ($users as $user) {
            $roles = $user->getRoles();
            $roles = array_filter($roles, fn($role) => $role !== 'ROLE_WICEPREZES_OKREGU');
            $user->setRoles(array_values($roles));
        }
    }

    /**
     * Nadaje stałą rolę użytkownikowi.
     */
    private function nadajStalaRole(User $user, string $role): void
    {
        $roles = $user->getRoles();
        if (!in_array($role, $roles)) {
            $roles[] = $role;
            $user->setRoles($roles);
            
            $this->logger->info('Nadano stałą rolę okręgu', [
                'user_id' => $user->getId(),
                'user_name' => $user->getFullName(),
                'role' => $role,
            ]);
        }
    }

    public function zakonczZebranie(ZebranieOkregu $zebranie): void
    {
        $zebranie->setStatus(ZebranieOkregu::STATUS_ZAKONCZONE);
        $zebranie->setDataZakonczenia(new \DateTime());

        // Usuń tymczasowe role
        $this->usunTymczasowaRole($zebranie->getObserwator(), 'ROLE_OBSERWATOR_ZEBRANIA');
        if ($zebranie->getProtokolant()) {
            $this->usunTymczasowaRole($zebranie->getProtokolant(), 'ROLE_PROTOKOLANT_ZEBRANIA');
        }
        if ($zebranie->getProwadzacy()) {
            $this->usunTymczasowaRole($zebranie->getProwadzacy(), 'ROLE_PROWADZACY_ZEBRANIA');
        }

        $this->entityManager->flush();

        // Odśwież tokeny wszystkich uczestników zebrania
        $this->refreshUserTokenIfNeeded($zebranie->getObserwator());
        if ($zebranie->getProtokolant()) {
            $this->refreshUserTokenIfNeeded($zebranie->getProtokolant());
        }
        if ($zebranie->getProwadzacy()) {
            $this->refreshUserTokenIfNeeded($zebranie->getProwadzacy());
        }

        $this->logMeetingActivity($zebranie, 'meeting_ended', [
            'data_zakonczenia' => $zebranie->getDataZakonczenia()?->format('Y-m-d H:i:s'),
        ]);
    }


    /**
     * Nadaj tymczasową rolę użytkownikowi.
     */
    private function nadajTymczasowaRole(User $user, string $role): void
    {
        $roles = $user->getRoles();
        if (!in_array($role, $roles)) {
            $roles[] = $role;
            $user->setRoles($roles);
            
            $this->logger->info('Nadano tymczasową rolę zebrania', [
                'user_id' => $user->getId(),
                'user_name' => $user->getFullName(),
                'role' => $role,
            ]);
        }
    }

    /**
     * Usuń tymczasową rolę po zakończeniu zebrania.
     */
    private function usunTymczasowaRole(User $user, string $role): void
    {
        $roles = $user->getRoles();
        $roles = array_filter($roles, fn ($r) => $r !== $role);
        $user->setRoles(array_values($roles));
        
        $this->logger->info('Usunięto tymczasową rolę zebrania', [
            'user_id' => $user->getId(),
            'user_name' => $user->getFullName(),
            'role' => $role,
        ]);
    }

    /**
     * Odświeża token bezpieczeństwa użytkownika jeśli to aktualnie zalogowany użytkownik.
     */
    private function refreshUserTokenIfNeeded(User $user): void
    {
        if (!$this->tokenStorage) {
            return; // Brak tokenu storage (np. w console commands)
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return; // Brak aktywnego tokenu
        }

        $currentUser = $token->getUser();
        if (!$currentUser instanceof User || $currentUser->getId() !== $user->getId()) {
            return; // To nie jest aktualnie zalogowany użytkownik
        }

        // Utwórz nowy token z odświeżonymi rolami
        $newToken = new UsernamePasswordToken(
            $user,
            'main', // nazwa firewalla
            $user->getRoles()
        );

        $this->tokenStorage->setToken($newToken);

        $this->logger->info('Odświeżono token bezpieczeństwa po nadaniu roli zebrania okręgu', [
            'user_id' => $user->getId(),
            'user_name' => $user->getFullName(),
            'roles' => $user->getRoles(),
        ]);
    }

    /**
     * Loguje aktywność zebrania dla celów audytu i śledzenia.
     *
     * @param array<string, mixed> $context
     */
    private function logMeetingActivity(ZebranieOkregu $zebranie, string $action, array $context = []): void
    {
        $logContext = array_merge([
            'zebranie_okregu_id' => $zebranie->getId(),
            'okreg_id' => $zebranie->getOkreg()->getId(),
            'okreg_nazwa' => $zebranie->getOkreg()->getNazwa(),
            'zebranie_status' => $zebranie->getStatus(),
            'action' => $action,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ], $context);

        $this->logger->info("District meeting activity: $action", $logContext);
    }

    /**
     * Generuje dokumenty dla wyników zebrania okręgu.
     */
    private function generujDokumentyWyborow(ZebranieOkregu $zebranie): void
    {
        $this->logger->info('Rozpoczynam generowanie dokumentów dla zebrania', [
            'zebranie_id' => $zebranie->getId(),
            'prezes' => $zebranie->getPrezesOkregu()?->getFullName(),
            'wiceprezes1' => $zebranie->getWiceprezes1()?->getFullName(),
            'wiceprezes2' => $zebranie->getWiceprezes2()?->getFullName(),
        ]);

        // Dokument wyboru prezesa
        if ($zebranie->getPrezesOkregu()) {
            $this->utworzDokumentWyboru(
                $zebranie,
                $zebranie->getPrezesOkregu(),
                Dokument::TYP_WYBOR_PREZESA_OKREGU_WALNE,
                'Prezes Okręgu'
            );
        }

        // Dokumenty wyboru wiceprezesów
        if ($zebranie->getWiceprezes1()) {
            $this->utworzDokumentWyboru(
                $zebranie,
                $zebranie->getWiceprezes1(),
                Dokument::TYP_WYBOR_WICEPREZESA_OKREGU_WALNE,
                'Wiceprezes Okręgu I'
            );
        }

        if ($zebranie->getWiceprezes2()) {
            $this->utworzDokumentWyboru(
                $zebranie,
                $zebranie->getWiceprezes2(),
                Dokument::TYP_WYBOR_WICEPREZESA_OKREGU_WALNE,
                'Wiceprezes Okręgu II'
            );
        }
    }

    /**
     * Tworzy dokument wyboru dla konkretnej osoby.
     */
    private function utworzDokumentWyboru(ZebranieOkregu $zebranie, User $wybranaOsoba, string $typDokumentu, string $stanowisko): void
    {
        // Przygotuj dane do dokumentu
        $data = [
            'czlonek' => $wybranaOsoba,
            'data_wejscia_w_zycie' => new \DateTime(),
            'okreg' => $zebranie->getOkreg()->getNazwa(),
            'miejsce_zebrania' => 'Online/CRM System',
            'data' => (new \DateTime())->format('d.m.Y'),
            'data_wejscia' => (new \DateTime())->format('d.m.Y'),
            'prowadzacy_walnego' => $zebranie->getProwadzacy()?->getFullName() ?? 'N/A',
            'sekretarz_walnego' => $zebranie->getProtokolant()?->getFullName() ?? 'N/A',
            'przewodniczacy_walnego' => $zebranie->getProwadzacy()?->getFullName() ?? 'N/A',
            'protokolant' => $zebranie->getProtokolant()?->getFullName() ?? 'N/A',
            'obserwator_walnego' => $zebranie->getObserwator()->getFullName(),
            // Dodatkowe dane dla dokumentów wyboru
            'liczba_uprawnionych' => count($zebranie->getUczestnicy()),
            'liczba_obecnych' => count($zebranie->getUczestnicy()),
            'liczba_kart' => count($zebranie->getUczestnicy()),
            'glosy_wazne' => count($zebranie->getUczestnicy()),
            'glosy_niewazne' => 0,
            'glosy_za' => count($zebranie->getUczestnicy()),
            'glosy_przeciw' => 0,
            'glosy_wstrzymujace' => 0,
            'procent_za' => 100,
            'procent_przeciw' => 0,
            'procent_wstrzymujace' => 0,
            'typ_wiekszosci' => 'bezwzględną głosów',
            'kworum' => count($zebranie->getUczestnicy()),
            'procent_kworum' => 100,
            'zawod' => 'Członek partii',
            'dotychczasowe_funkcje' => 'Brak wcześniejszych funkcji'
        ];

        // Użyj nowego systemu DocumentService do tworzenia dokumentu
        $dokument = $this->dokumentService->createDocument(
            $typDokumentu,
            $data,
            $zebranie->getUtworzonyPrzez()
        );

        // Ustaw dodatkowe właściwości specyficzne dla zebrania
        $dokument->setZebranieOkregu($zebranie);
        $dokument->setDataWejsciaWZycie(new \DateTime()); // Dokument wchodzi w życie natychmiast

        $this->entityManager->persist($dokument);
        $this->entityManager->flush();
        
        // Dodaj podpisy uczestników zebrania z ich podpisami elektronicznymi
        $this->dodajPodpisyUczestnikowDoZebrania($dokument, $zebranie);

        $this->logger->info('Wygenerowano dokument wyboru z zebrania okręgu', [
            'dokument_id' => $dokument->getId(),
            'typ' => $typDokumentu,
            'zebranie_id' => $zebranie->getId(),
            'wybrana_osoba' => $wybranaOsoba->getFullName(),
            'stanowisko' => $stanowisko,
        ]);
    }
    
    /**
     * Dodaje podpisy uczestników zebrania do dokumentu.
     */
    private function dodajPodpisyUczestnikowDoZebrania(Dokument $dokument, ZebranieOkregu $zebranie): void
    {
        $kolejnosc = 1;
        
        // Podpis obserwatora
        if ($zebranie->getObserwator() && $zebranie->getObserwatorPodpisal()) {
            $podpis = new PodpisDokumentu();
            $podpis->setDokument($dokument);
            $podpis->setPodpisujacy($zebranie->getObserwator());
            $podpis->setStatus(PodpisDokumentu::STATUS_PODPISANY);
            $podpis->setKolejnosc($kolejnosc++);
            $podpis->setDataUtworzenia(new \DateTime());
            $podpis->setDataPodpisania($zebranie->getDataPodpisuObserwatora());
            if ($zebranie->getPodpisObserwatora()) {
                $podpis->setPodpisElektroniczny($zebranie->getPodpisObserwatora());
            }
            $this->entityManager->persist($podpis);
        }
        
        // Podpis protokolanta
        if ($zebranie->getProtokolant() && $zebranie->getProtokolantPodpisal()) {
            $podpis = new PodpisDokumentu();
            $podpis->setDokument($dokument);
            $podpis->setPodpisujacy($zebranie->getProtokolant());
            $podpis->setStatus(PodpisDokumentu::STATUS_PODPISANY);
            $podpis->setKolejnosc($kolejnosc++);
            $podpis->setDataUtworzenia(new \DateTime());
            $podpis->setDataPodpisania($zebranie->getDataPodpisuProtokolanta());
            if ($zebranie->getPodpisProtokolanta()) {
                $podpis->setPodpisElektroniczny($zebranie->getPodpisProtokolanta());
            }
            $this->entityManager->persist($podpis);
        }
        
        // Podpis prowadzącego
        if ($zebranie->getProwadzacy() && $zebranie->getProwadzacyPodpisal()) {
            $podpis = new PodpisDokumentu();
            $podpis->setDokument($dokument);
            $podpis->setPodpisujacy($zebranie->getProwadzacy());
            $podpis->setStatus(PodpisDokumentu::STATUS_PODPISANY);
            $podpis->setKolejnosc($kolejnosc++);
            $podpis->setDataUtworzenia(new \DateTime());
            $podpis->setDataPodpisania($zebranie->getDataPodpisuProwadzacego());
            if ($zebranie->getPodpisProwadzacego()) {
                $podpis->setPodpisElektroniczny($zebranie->getPodpisProwadzacego());
            }
            $this->entityManager->persist($podpis);
        }
        
        $this->entityManager->flush();
        
        // Ustaw status dokumentu jako podpisany jeśli wszystkie podpisy zostały dodane
        if ($kolejnosc > 1) {
            $dokument->setStatus(Dokument::STATUS_PODPISANY);
            $dokument->setDataPodpisania(new \DateTime());
            $this->entityManager->flush();
        }
    }

    /**
     * Podpisuje dokument zebrania.
     */
    public function podpiszDokument(Dokument $dokument, User $user): bool
    {
        // Sprawdź czy dokument jest przypisany do zebrania
        $zebranie = $dokument->getZebranieOkregu();
        if (!$zebranie) {
            $this->logger->error('Dokument nie jest przypisany do zebrania okręgu', [
                'dokument_id' => $dokument->getId(),
            ]);
            return false;
        }

        // Znajdź podpis dla tego użytkownika
        foreach ($dokument->getPodpisy() as $podpis) {
            if ($podpis->getPodpisujacy() === $user) {
                // Podpisz dokument
                $podpis->setStatus(PodpisDokumentu::STATUS_PODPISANY);
                $podpis->setDataPodpisania(new \DateTime());
                
                $this->entityManager->flush();
                
                $this->logger->info('Użytkownik podpisał dokument zebrania', [
                    'dokument_id' => $dokument->getId(),
                    'user_id' => $user->getId(),
                    'user_name' => $user->getFullName(),
                ]);
                
                return true;
            }
        }
        
        $this->logger->warning('Nie znaleziono podpisu użytkownika dla dokumentu', [
            'dokument_id' => $dokument->getId(),
            'user_id' => $user->getId(),
        ]);
        
        return false;
    }

    /**
     * Sprawdza czy wszystkie dokumenty zebrania są podpisane.
     */
    public function czyWszystkieDokumentyPodpisane(ZebranieOkregu $zebranie): bool
    {
        $dokumenty = $zebranie->getDokumenty();
        
        if ($dokumenty->isEmpty()) {
            $this->logger->warning('Zebranie nie ma żadnych dokumentów', [
                'zebranie_id' => $zebranie->getId(),
            ]);
            return false;
        }
        
        foreach ($dokumenty as $dokument) {
            // Sprawdź czy wszystkie wymagane podpisy są złożone
            foreach ($dokument->getPodpisy() as $podpis) {
                if ($podpis->getStatus() !== PodpisDokumentu::STATUS_PODPISANY) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Generuje treść dokumentu wyboru.
     */
}