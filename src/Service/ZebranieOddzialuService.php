<?php

namespace App\Service;

use App\Entity\Dokument;
use App\Entity\Oddzial;
use App\Entity\PodpisDokumentu;
use App\Entity\User;
use App\Entity\ZebranieOddzialu;
use App\Repository\DokumentRepository;
use App\Repository\ZebranieOddzialuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ZebranieOddzialuService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ZebranieOddzialuRepository $zebranieRepository,
        private DokumentRepository $dokumentRepository,
        private DokumentService $dokumentService,
        private LoggerInterface $logger,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    /**
     * Rozpoczyna nowe zebranie oddziału.
     */
    public function rozpocznijZebranie(Oddzial $oddzial, User $obserwator): ZebranieOddzialu
    {
        // Sprawdź czy obserwator może prowadzić zebranie dla tego oddziału
        if (!$this->zebranieRepository->canUserBeObserver($obserwator, $oddzial)) {
            throw new AccessDeniedException('Nie możesz być obserwatorem zebrania dla tego oddziału');
        }

        // Sprawdź czy nie ma już aktywnego zebrania dla tego oddziału
        $activeZebranie = $this->zebranieRepository->findActiveByOddzial($oddzial);
        if ($activeZebranie) {
            throw new \RuntimeException('Oddział ma już aktywne zebranie');
        }

        $zebranie = new ZebranieOddzialu();
        $zebranie->setOddzial($oddzial);
        $zebranie->setObserwator($obserwator);

        $this->entityManager->persist($zebranie);
        $this->entityManager->flush();

        $this->logMeetingActivity($zebranie, 'meeting_started', [
            'oddzial' => $oddzial->getNazwa(),
            'obserwator' => $obserwator->getFullName(),
            'obserwator_id' => $obserwator->getId(),
        ]);

        return $zebranie;
    }

    /**
     * Wyznaczy protokolanta zebrania.
     */
    public function wyznaczProtokolanta(ZebranieOddzialu $zebranie, User $protokolant, User $wyznaczajacy): void
    {
        // Sprawdź czy zebranie jest w odpowiednim stanie
        if (!$zebranie->canAssignProtokolant($wyznaczajacy)) {
            throw new AccessDeniedException('Nie można teraz wyznaczyć protokolanta');
        }

        // Sprawdź czy protokolant należy do oddziału
        if (!$this->zebranieRepository->canUserBeMeetingRole($protokolant, $zebranie->getOddzial())) {
            throw new \InvalidArgumentException('Protokolant musi należeć do oddziału');
        }

        // Utworz dokument wyznaczenia protokolanta
        $dokumentData = [
            'protokolant' => $protokolant,
            'data_wejscia_w_zycie' => new \DateTime(),
            'uzasadnienie' => 'Wyznaczenie protokolanta na zebranie członków oddziału '.$zebranie->getOddzial()->getNazwa(),
        ];

        $dokument = $this->dokumentService->createDocument(
            Dokument::TYP_WYZNACZENIE_PROTOKOLANTA,
            $dokumentData,
            $wyznaczajacy
        );

        // Powiąż dokument z zebraniem
        $dokument->setZebranieOddzialu($zebranie);
        $zebranie->setProtokolant($protokolant);
        
        // Zmień status zebrania
        $zebranie->setStatus(ZebranieOddzialu::STATUS_OCZEKUJE_NA_PROWADZACEGO);

        $this->entityManager->flush();

        // Automatycznie podpisz dokument (co spowoduje nadanie roli przez executeDocumentAction)
        $this->dokumentService->signDocument($dokument, $wyznaczajacy);

        $this->logMeetingActivity($zebranie, 'secretary_appointed', [
            'protokolant' => $protokolant->getFullName(),
            'protokolant_id' => $protokolant->getId(),
            'wyznaczajacy' => $wyznaczajacy->getFullName(),
            'wyznaczajacy_id' => $wyznaczajacy->getId(),
        ]);
    }

    /**
     * Wyznaczy prowadzącego zebrania.
     */
    public function wyznaczProwadzacego(ZebranieOddzialu $zebranie, User $prowadzacy, User $wyznaczajacy): void
    {
        // Sprawdź czy zebranie jest w odpowiednim stanie
        if (!$zebranie->canAssignProwadzacy($wyznaczajacy)) {
            throw new AccessDeniedException('Nie można teraz wyznaczyć prowadzącego');
        }

        // Sprawdź czy prowadzący należy do oddziału
        if (!$this->zebranieRepository->canUserBeMeetingRole($prowadzacy, $zebranie->getOddzial())) {
            throw new \InvalidArgumentException('Prowadzący musi należeć do oddziału');
        }

        // Sprawdź czy prowadzący nie jest protokolantem
        if ($zebranie->getProtokolant() && $zebranie->getProtokolant()->getId() === $prowadzacy->getId()) {
            throw new \InvalidArgumentException('Prowadzący nie może być jednocześnie protokolantem');
        }

        // Utworz dokument wyznaczenia prowadzącego
        $dokumentData = [
            'prowadzacy' => $prowadzacy,
            'data_wejscia_w_zycie' => new \DateTime(),
            'uzasadnienie' => 'Wyznaczenie prowadzącego na zebranie członków oddziału '.$zebranie->getOddzial()->getNazwa(),
        ];

        $dokument = $this->dokumentService->createDocument(
            Dokument::TYP_WYZNACZENIE_PROWADZACEGO,
            $dokumentData,
            $wyznaczajacy
        );

        // Powiąż dokument z zebraniem
        $dokument->setZebranieOddzialu($zebranie);
        $zebranie->setProwadzacy($prowadzacy);
        
        // Zmień status zebrania - zgodnie z procedurą statutową protokolant i prowadzący wybierają przewodniczącego
        $zebranie->setStatus(ZebranieOddzialu::STATUS_WYBOR_PRZEWODNICZACEGO);

        $this->entityManager->flush();

        // Automatycznie podpisz dokument (co spowoduje nadanie roli przez executeDocumentAction)
        $this->dokumentService->signDocument($dokument, $wyznaczajacy);

        $this->logMeetingActivity($zebranie, 'chairman_appointed', [
            'prowadzacy' => $prowadzacy->getFullName(),
            'prowadzacy_id' => $prowadzacy->getId(),
            'wyznaczajacy' => $wyznaczajacy->getFullName(),
            'wyznaczajacy_id' => $wyznaczajacy->getId(),
        ]);
    }

    /**
     * Wyznacza przewodniczącego, zastępców i sekretarza oddziału.
     */
    public function wyznaczZarzadOddzialu(
        ZebranieOddzialu $zebranie,
        User $przewodniczacy,
        ?User $zastepca1,
        ?User $zastepca2,
        User $sekretarz,
        User $wyznaczajacy
    ): void {
        // Sprawdź czy zebranie jest w odpowiednim stanie
        if (!$zebranie->canManagePositions($wyznaczajacy)) {
            throw new AccessDeniedException('Nie można teraz wyznaczyć zarządu');
        }

        // Walidacja - wszyscy muszą być z tego samego oddziału
        $oddzial = $zebranie->getOddzial();
        foreach ([$przewodniczacy, $zastepca1, $zastepca2, $sekretarz] as $user) {
            if ($user && $user->getOddzial() !== $oddzial) {
                throw new \InvalidArgumentException('Wszyscy członkowie zarządu muszą należeć do tego samego oddziału');
            }
        }

        // Walidacja - nie może być duplikatów
        $zarzad = array_filter([$przewodniczacy, $zastepca1, $zastepca2, $sekretarz]);
        if (count($zarzad) !== count(array_unique(array_map(fn($u) => $u->getId(), $zarzad)))) {
            throw new \InvalidArgumentException('Ta sama osoba nie może pełnić wielu funkcji w zarządzie');
        }

        // Zapisz zarząd w zebraniu
        $zebranie->setPrzewodniczacy($przewodniczacy);
        $zebranie->setZastepca1($zastepca1);
        $zebranie->setZastepca2($zastepca2);
        $zebranie->setSekretarz($sekretarz);
        
        // Zmień status zebrania
        $zebranie->setStatus(ZebranieOddzialu::STATUS_OCZEKUJE_NA_PODPISY);

        $this->entityManager->flush();

        // Teraz utwórz dokumenty powołania dla każdego stanowiska
        $this->createBoardAppointmentDocuments($zebranie, $przewodniczacy, $zastepca1, $zastepca2, $sekretarz, $wyznaczajacy);

        $this->logMeetingActivity($zebranie, 'board_appointed', [
            'przewodniczacy' => $przewodniczacy->getFullName(),
            'zastepca1' => $zastepca1 ? $zastepca1->getFullName() : null,
            'zastepca2' => $zastepca2 ? $zastepca2->getFullName() : null,
            'sekretarz' => $sekretarz->getFullName(),
            'wyznaczajacy' => $wyznaczajacy->getFullName(),
        ]);
    }

    /**
     * Tworzy dokumenty powołania dla członków zarządu.
     */
    private function createBoardAppointmentDocuments(
        ZebranieOddzialu $zebranie,
        User $przewodniczacy,
        ?User $zastepca1,
        ?User $zastepca2,
        User $sekretarz,
        User $wyznaczajacy
    ): void {
        // Dokument powołania przewodniczącego
        $this->createBoardDocument(
            $zebranie,
            $przewodniczacy,
            Dokument::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU,
            'przewodniczący oddziału',
            $wyznaczajacy
        );

        // Dokument powołania sekretarza
        $this->createBoardDocument(
            $zebranie,
            $sekretarz,
            Dokument::TYP_POWOLANIE_SEKRETARZA_ODDZIALU,
            'sekretarz oddziału',
            $wyznaczajacy
        );

        // Dokumenty powołania zastępców (jeśli zostali wybrani)
        if ($zastepca1) {
            $this->createBoardDocument(
                $zebranie,
                $zastepca1,
                Dokument::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO,
                'zastępca przewodniczącego oddziału',
                $wyznaczajacy
            );
        }

        if ($zastepca2) {
            $this->createBoardDocument(
                $zebranie,
                $zastepca2,
                Dokument::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO,
                'zastępca przewodniczącego oddziału',
                $wyznaczajacy
            );
        }
    }


    /**
     * Tworzy dokument powołania na stanowisko w zarządzie.
     */
    private function createBoardDocument(
        ZebranieOddzialu $zebranie,
        User $czlonek,
        string $typDokumentu,
        string $stanowisko,
        User $tworca
    ): void {
        $dokumentData = [
            'czlonek' => $czlonek,
            'data_wejscia_w_zycie' => new \DateTime(),
            'uzasadnienie' => sprintf(
                'Powołanie na stanowisko %s w oddziale %s na zebraniu członków oddziału',
                $stanowisko,
                $zebranie->getOddzial()->getNazwa()
            ),
        ];

        // Utwórz dokument normalnie - to ustawi podstawowe podpisy (ale nie meeting_* bo brak relacji)
        $dokument = $this->dokumentService->createDocument($typDokumentu, $dokumentData, $tworca);
        
        // Ustaw relację do zebrania 
        $dokument->setZebranieOddzialu($zebranie);

        // Teraz dodaj brakujące podpisy meeting_protokolant i meeting_prowadzacy ręcznie
        $this->addMeetingSignersToDocument($dokument, $zebranie);

        // Zapisz zmiany
        $this->entityManager->flush();
    }

    /**
     * Dodaje podpisy protokolanta i prowadzącego zebrania do dokumentu.
     */
    private function addMeetingSignersToDocument(Dokument $dokument, ZebranieOddzialu $zebranie): void
    {
        // Znajdź największą kolejność już istniejących podpisów
        $maxKolejnosc = 0;
        foreach ($dokument->getPodpisy() as $podpis) {
            if ($podpis->getKolejnosc() > $maxKolejnosc) {
                $maxKolejnosc = $podpis->getKolejnosc();
            }
        }

        // Dodaj protokolanta jako podpisującego
        if ($zebranie->getProtokolant()) {
            $podpisProtokolant = new \App\Entity\PodpisDokumentu();
            $podpisProtokolant->setDokument($dokument);
            $podpisProtokolant->setPodpisujacy($zebranie->getProtokolant());
            $podpisProtokolant->setStatus(\App\Entity\PodpisDokumentu::STATUS_OCZEKUJE);
            $podpisProtokolant->setKolejnosc(++$maxKolejnosc);
            $dokument->addPodpis($podpisProtokolant);
            $this->entityManager->persist($podpisProtokolant);
        }

        // Dodaj prowadzącego jako podpisującego
        if ($zebranie->getProwadzacy()) {
            $podpisProwadzacy = new \App\Entity\PodpisDokumentu();
            $podpisProwadzacy->setDokument($dokument);
            $podpisProwadzacy->setPodpisujacy($zebranie->getProwadzacy());
            $podpisProwadzacy->setStatus(\App\Entity\PodpisDokumentu::STATUS_OCZEKUJE);
            $podpisProwadzacy->setKolejnosc(++$maxKolejnosc);
            $dokument->addPodpis($podpisProwadzacy);
            $this->entityManager->persist($podpisProwadzacy);
        }
    }

    /**
     * Powołuje lub odwołuje przewodniczącego oddziału.
     */
    public function zarzadzajPrzewodniczacym(
        ZebranieOddzialu $zebranie,
        string $akcja,
        ?User $przewodniczacy = null,
        ?User $tworca = null,
    ): Dokument {
        // Sprawdź uprawnienia - tylko prowadzący i protokolant mogą wybierać przewodniczącego
        $tworca = $tworca ?? $zebranie->getProwadzacy();
        if (!$zebranie->canSelectPrzewodniczacy($tworca)) {
            throw new AccessDeniedException('Nie można wybierać przewodniczącego w obecnym stanie zebrania');
        }

        $typDokumentu = match ($akcja) {
            'powolaj' => Dokument::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU,
            'odwolaj' => Dokument::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU,
            default => throw new \InvalidArgumentException('Nieprawidłowa akcja'),
        };

        // Walidacja biznesowa dla przewodniczącego
        if ('powolaj' === $akcja) {
            if (!$przewodniczacy) {
                throw new \InvalidArgumentException('Musisz wskazać osobę do powołania');
            }

            // Sprawdź czy osoba należy do oddziału
            if (!$this->zebranieRepository->canUserBeMeetingRole($przewodniczacy, $zebranie->getOddzial())) {
                throw new \InvalidArgumentException('Przewodniczący musi należeć do oddziału');
            }

            // Sprawdź czy nie ma już przewodniczącego (walidacja w DokumentService)
        }

        $dokumentData = [
            'czlonek' => $przewodniczacy,
            'data_wejscia_w_zycie' => new \DateTime(),
            'uzasadnienie' => sprintf(
                '%s przewodniczącego oddziału %s na zebraniu członków',
                'powolaj' === $akcja ? 'Powołanie' : 'Odwołanie',
                $zebranie->getOddzial()->getNazwa()
            ),
        ];

        // Najpierw utwórz dokument z podstawowymi danymi
        /** @var User $creator */
        $creator = $tworca;
        $dokument = $this->dokumentService->createDocument($typDokumentu, $dokumentData, $creator);

        // Ustaw relację do zebrania PRZED flush (ważne dla podpisów)
        $dokument->setZebranieOddzialu($zebranie);

        // Add signers after relation is set - now meeting secretary and chairman can be found
        $definition = $this->dokumentService->getDocumentDefinition($typDokumentu);
        if ($definition && isset($definition['signers'])) {
            /** @var User $creator */
            $creator = $tworca;
            $this->dokumentService->addSignersAfterRelation($dokument, $definition, $dokumentData, $creator);
        }

        // Ustaw przewodniczącego w zebraniu i przejdź do wyboru zastępców
        if ('powolaj' === $akcja && $przewodniczacy) {
            $zebranie->setPrzewodniczacy($przewodniczacy);
            $zebranie->setStatus(ZebranieOddzialu::STATUS_WYBOR_ZASTEPCOW);
            
            $this->logMeetingActivity($zebranie, 'chairman_selected', [
                'przewodniczacy' => $przewodniczacy->getFullName(),
                'przewodniczacy_id' => $przewodniczacy->getId(),
                'tworca' => $tworca->getFullName(),
                'tworca_id' => $tworca->getId(),
            ]);
        } elseif ('odwolaj' === $akcja) {
            $zebranie->setPrzewodniczacy(null);
            // Pozostań w statusie wyboru przewodniczącego
        }

        $this->entityManager->flush();

        return $dokument;
    }

    /**
     * Zarządza zastępcami przewodniczącego.
     */
    public function zarzadzajZastepca(
        ZebranieOddzialu $zebranie,
        string $akcja,
        ?User $zastepca = null,
        ?User $tworca = null,
    ): Dokument {
        // Sprawdź uprawnienia - tylko przewodniczący może wybierać zastępców
        $tworca = $tworca ?? $zebranie->getPrzewodniczacy();
        if (!$zebranie->canSelectZastepcy($tworca)) {
            throw new AccessDeniedException('Nie można wybierać zastępców w obecnym stanie zebrania');
        }

        $typDokumentu = match ($akcja) {
            'powolaj' => Dokument::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO,
            'odwolaj' => Dokument::TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO,
            default => throw new \InvalidArgumentException('Nieprawidłowa akcja'),
        };

        if ('powolaj' === $akcja && !$zastepca) {
            throw new \InvalidArgumentException('Musisz wskazać osobę do powołania');
        }

        if ($zastepca && !$this->zebranieRepository->canUserBeMeetingRole($zastepca, $zebranie->getOddzial())) {
            throw new \InvalidArgumentException('Zastępca musi należeć do oddziału');
        }

        $dokumentData = [
            'czlonek' => $zastepca,
            'data_wejscia_w_zycie' => new \DateTime(),
            'uzasadnienie' => sprintf(
                '%s zastępcy przewodniczącego oddziału %s na zebraniu członków',
                'powolaj' === $akcja ? 'Powołanie' : 'Odwołanie',
                $zebranie->getOddzial()->getNazwa()
            ),
        ];

        // Najpierw utwórz dokument z podstawowymi danymi
        /** @var User $creator */
        $creator = $tworca;
        $dokument = $this->dokumentService->createDocument($typDokumentu, $dokumentData, $creator);

        // Ustaw relację do zebrania PRZED flush (ważne dla podpisów)
        $dokument->setZebranieOddzialu($zebranie);

        // Add signers after relation is set - now meeting secretary and chairman can be found
        $definition = $this->dokumentService->getDocumentDefinition($typDokumentu);
        if ($definition && isset($definition['signers'])) {
            /** @var User $creator */
            $creator = $tworca;
            $this->dokumentService->addSignersAfterRelation($dokument, $definition, $dokumentData, $creator);
        }

        // Ustaw zastępcę w zebraniu - gdy mamy obu zastępców, przechodzimy do wyboru zarządu
        if ('powolaj' === $akcja && $zastepca) {
            if (!$zebranie->getZastepca1()) {
                $zebranie->setZastepca1($zastepca);
                $this->logMeetingActivity($zebranie, 'deputy1_selected', [
                    'zastepca' => $zastepca->getFullName(),
                    'zastepca_id' => $zastepca->getId(),
                ]);
            } elseif (!$zebranie->getZastepca2()) {
                $zebranie->setZastepca2($zastepca);
                // Przejdź do wyboru zarządu gdy mamy obu zastępców
                $zebranie->setStatus(ZebranieOddzialu::STATUS_WYBOR_ZARZADU);
                $this->logMeetingActivity($zebranie, 'deputy2_selected_board_selection', [
                    'zastepca' => $zastepca->getFullName(),
                    'zastepca_id' => $zastepca->getId(),
                ]);
            }
        }

        $this->entityManager->flush();

        return $dokument;
    }

    /**
     * Zarządza sekretarzem oddziału.
     */
    public function zarzadzajSekretarzemOddzialu(
        ZebranieOddzialu $zebranie,
        string $akcja,
        ?User $sekretarz = null,
        ?User $tworca = null,
    ): Dokument {
        $tworca = $tworca ?? $zebranie->getProwadzacy();
        if (!in_array($tworca, [$zebranie->getProwadzacy(), $zebranie->getProtokolant()])) {
            throw new AccessDeniedException('Tylko prowadzący lub protokolant może zarządzać sekretarzem');
        }

        $typDokumentu = match ($akcja) {
            'powolaj' => Dokument::TYP_POWOLANIE_SEKRETARZA_ODDZIALU,
            'odwolaj' => Dokument::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU,
            default => throw new \InvalidArgumentException('Nieprawidłowa akcja'),
        };

        if ('powolaj' === $akcja && !$sekretarz) {
            throw new \InvalidArgumentException('Musisz wskazać osobę do powołania');
        }

        if ($sekretarz && !$this->zebranieRepository->canUserBeMeetingRole($sekretarz, $zebranie->getOddzial())) {
            throw new \InvalidArgumentException('Sekretarz musi należeć do oddziału');
        }

        $dokumentData = [
            'czlonek' => $sekretarz,
            'data_wejscia_w_zycie' => new \DateTime(),
            'uzasadnienie' => sprintf(
                '%s sekretarza oddziału %s na zebraniu członków',
                'powolaj' === $akcja ? 'Powołanie' : 'Odwołanie',
                $zebranie->getOddzial()->getNazwa()
            ),
        ];

        // Najpierw utwórz dokument z podstawowymi danymi
        /** @var User $creator */
        $creator = $tworca;
        $dokument = $this->dokumentService->createDocument($typDokumentu, $dokumentData, $creator);

        // Ustaw relację do zebrania PRZED flush (ważne dla podpisów)
        $dokument->setZebranieOddzialu($zebranie);

        // Teraz dodaj podpisujących z zebrania (protokolanta i prowadzącego)
        // jeśli to dokument wymagający ich podpisów
        if (in_array($typDokumentu, [
            Dokument::TYP_POWOLANIE_SEKRETARZA_ODDZIALU,
            Dokument::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU,
        ])) {
            // Dodaj protokolanta jako podpisującego
            if ($zebranie->getProtokolant()) {
                $podpisProtokolant = new PodpisDokumentu();
                $podpisProtokolant->setDokument($dokument);
                $podpisProtokolant->setPodpisujacy($zebranie->getProtokolant());
                $podpisProtokolant->setStatus(PodpisDokumentu::STATUS_OCZEKUJE);
                // Protokolant zawsze ma kolejność 1 (pierwszy podpis)
                $podpisProtokolant->setKolejnosc(1);
                $dokument->addPodpis($podpisProtokolant);
                $this->entityManager->persist($podpisProtokolant);
            }

            // Dodaj prowadzącego jako podpisującego
            if ($zebranie->getProwadzacy()) {
                $podpisProwadzacy = new PodpisDokumentu();
                $podpisProwadzacy->setDokument($dokument);
                $podpisProwadzacy->setPodpisujacy($zebranie->getProwadzacy());
                $podpisProwadzacy->setStatus(PodpisDokumentu::STATUS_OCZEKUJE);
                // Prowadzący zawsze ma kolejność 2 (drugi podpis)
                $podpisProwadzacy->setKolejnosc(2);
                $dokument->addPodpis($podpisProwadzacy);
                $this->entityManager->persist($podpisProwadzacy);
            }
        }

        $this->entityManager->flush();

        return $dokument;
    }

    /**
     * Kończy zebranie.
     */
    public function zakonczZebranie(ZebranieOddzialu $zebranie, User $user): void
    {
        // Sprawdź uprawnienia
        if ($zebranie->getObserwator() !== $user) {
            throw new AccessDeniedException('Tylko obserwator może zakończyć zebranie');
        }

        if (!$zebranie->isAktywne()) {
            throw new \RuntimeException('Zebranie nie jest aktywne');
        }

        // Sprawdź czy są niepodpisane dokumenty związane z zebraniem
        $niepodpisaneDokumenty = $this->dokumentRepository->findUnfinishedByZebranie($zebranie);
        if (!empty($niepodpisaneDokumenty)) {
            $tytuly = array_map(fn ($d) => $d->getTytul(), $niepodpisaneDokumenty);
            throw new \RuntimeException(sprintf('Nie można zakończyć zebrania - pozostały %d niepodpisanych dokumentów: %s', count($niepodpisaneDokumenty), implode(', ', $tytuly)));
        }

        // Sprawdź kompletność zebrania
        $this->validateZebranieCompleteness($zebranie);

        // Zakończ zebranie
        $zebranie->zakonczZebranie();

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
            'oddzial' => $zebranie->getOddzial()->getNazwa(),
            'obserwator' => $user->getFullName(),
            'data_zakonczenia' => $zebranie->getDataZakonczenia()?->format('Y-m-d H:i:s'),
            'protokolant' => $zebranie->getProtokolant()?->getFullName(),
            'prowadzacy' => $zebranie->getProwadzacy()?->getFullName(),
        ]);
    }

    /**
     * Sprawdź czy użytkownik może zarządzać zebraniem.
     */
    public function canUserManageMeeting(User $user, ZebranieOddzialu $zebranie): bool
    {
        return $zebranie->getObserwator() === $user
               || $zebranie->getProwadzacy() === $user
               || $zebranie->getProtokolant() === $user;
    }

    /**
     * Usuń tymczasową rolę po zakończeniu zebrania.
     */
    private function usunTymczasowaRole(User $user, string $role): void
    {
        $roles = $user->getRoles();
        $roles = array_filter($roles, fn ($r) => $r !== $role);
        $user->setRoles(array_values($roles));
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

        $this->logger->info('Odświeżono token bezpieczeństwa po nadaniu roli zebrania', [
            'user_id' => $user->getId(),
            'user_name' => $user->getFullName(),
            'roles' => $user->getRoles(),
        ]);
    }

    /**
     * Waliduje kompletność zebrania przed zakończeniem.
     */
    private function validateZebranieCompleteness(ZebranieOddzialu $zebranie): void
    {
        $errors = [];

        // Sprawdź czy protokolant został wyznaczony
        if (!$zebranie->getProtokolant()) {
            $errors[] = 'Protokolant zebrania nie został wyznaczony';
        }

        // Sprawdź czy prowadzący został wyznaczony
        if (!$zebranie->getProwadzacy()) {
            $errors[] = 'Prowadzący zebrania nie został wyznaczony';
        }

        if (!empty($errors)) {
            throw new \RuntimeException(sprintf('Nie można zakończyć zebrania - niespełnione wymagania: %s', implode('; ', $errors)));
        }
    }

    /**
     * Pobierz informacje o stanie zebrania (dla dashboard).
     *
     * @return array<string, mixed>
     */
    public function getZebranieStatus(ZebranieOddzialu $zebranie): array
    {
        $niepodpisaneDokumenty = $this->dokumentRepository->countUnfinishedByZebranie($zebranie);

        return [
            'id' => $zebranie->getId(),
            'oddzial' => $zebranie->getOddzial()->getNazwa(),
            'status' => $zebranie->getStatus(),
            'data_rozpoczecia' => $zebranie->getDataRozpoczecia(),
            'data_zakonczenia' => $zebranie->getDataZakonczenia(),
            'obserwator' => $zebranie->getObserwator()->getFullName(),
            'protokolant' => $zebranie->getProtokolant()?->getFullName(),
            'prowadzacy' => $zebranie->getProwadzacy()?->getFullName(),
            'niepodpisane_dokumenty' => $niepodpisaneDokumenty,
            'kompletne' => $zebranie->getProtokolant() && $zebranie->getProwadzacy(),
            'moze_zakonczyc' => 0 === $niepodpisaneDokumenty
                              && $zebranie->getProtokolant()
                              && $zebranie->getProwadzacy(),
        ];
    }

    /**
     * Pobierz dokumenty czekające na podpis dla użytkownika w danym zebraniu.
     *
     * @return array<int, Dokument>
     */
    public function getAwaitingDocuments(ZebranieOddzialu $zebranie, User $user): array
    {
        return $this->dokumentRepository->findAwaitingSignatureByZebranieAndUser($zebranie, $user);
    }

    /**
     * Loguje aktywność zebrania dla celów audytu i śledzenia.
     *
     * @param array<string, mixed> $context
     */
    private function logMeetingActivity(ZebranieOddzialu $zebranie, string $action, array $context = []): void
    {
        $logContext = array_merge([
            'zebranie_id' => $zebranie->getId(),
            'oddzial_id' => $zebranie->getOddzial()->getId(),
            'oddzial_nazwa' => $zebranie->getOddzial()->getNazwa(),
            'zebranie_status' => $zebranie->getStatus(),
            'action' => $action,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ], $context);

        $this->logger->info("Meeting activity: $action", $logContext);
    }

    /**
     * Pobierz logi aktywności dla zebrania (do wyświetlenia w archiwum).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMeetingActivityLog(ZebranieOddzialu $zebranie): array
    {
        // Symulacja logów na podstawie danych zebrania
        $activities = [];

        // Log rozpoczęcia zebrania
        $activities[] = [
            'action' => 'meeting_started',
            'timestamp' => $zebranie->getDataRozpoczecia(),
            'user' => $zebranie->getObserwator()->getFullName(),
            'description' => 'Rozpoczęto zebranie oddziału '.$zebranie->getOddzial()->getNazwa(),
        ];

        // Log wyznaczenia protokolanta
        if ($zebranie->getProtokolant()) {
            $activities[] = [
                'action' => 'secretary_appointed',
                'timestamp' => $zebranie->getDataRozpoczecia(), // Przybliżony czas
                'user' => $zebranie->getObserwator()->getFullName(),
                'description' => 'Wyznaczono protokolanta: '.$zebranie->getProtokolant()->getFullName(),
            ];
        }

        // Log wyznaczenia prowadzącego
        if ($zebranie->getProwadzacy()) {
            $activities[] = [
                'action' => 'chairman_appointed',
                'timestamp' => $zebranie->getDataRozpoczecia(), // Przybliżony czas
                'user' => $zebranie->getObserwator()->getFullName(),
                'description' => 'Wyznaczono prowadzącego: '.$zebranie->getProwadzacy()->getFullName(),
            ];
        }

        // Log zakończenia zebrania
        if ($zebranie->getDataZakonczenia()) {
            $activities[] = [
                'action' => 'meeting_ended',
                'timestamp' => $zebranie->getDataZakonczenia(),
                'user' => $zebranie->getObserwator()->getFullName(),
                'description' => 'Zakończono zebranie',
            ];
        }

        return $activities;
    }
}
