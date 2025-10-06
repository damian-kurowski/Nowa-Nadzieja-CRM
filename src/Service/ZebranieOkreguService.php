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
        return $this->entityManager->wrapInTransaction(function() use ($okreg, $obserwator, $utworzonyPrzez) {
            try {
                // Sprawdź czy nie ma już aktywnego zebrania dla tego okręgu (z PESSIMISTIC_WRITE lock)
                $activeZebranie = $this->entityManager->createQueryBuilder()
                    ->select('z')
                    ->from(ZebranieOkregu::class, 'z')
                    ->where('z.okreg = :okreg')
                    ->andWhere('z.status NOT IN (:statuses)')
                    ->setParameter('okreg', $okreg)
                    ->setParameter('statuses', [ZebranieOkregu::STATUS_ZAKONCZONE, ZebranieOkregu::STATUS_ANULOWANE])
                    ->getQuery()
                    ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
                    ->getOneOrNullResult();

                if ($activeZebranie) {
                    throw new \RuntimeException('Okręg ma już aktywne zebranie');
                }

                $zebranie = new ZebranieOkregu();
                $zebranie->setOkreg($okreg);
                $zebranie->setObserwator($obserwator);
                $zebranie->setUtworzonyPrzez($utworzonyPrzez);
                $zebranie->setStatus(ZebranieOkregu::STATUS_WYZNACZANIE_PROTOKOLANTA);

                $this->entityManager->persist($zebranie);
                
                // Nadaj rolę obserwatora zebrania z obsługą błędów
                try {
                    $this->nadajTymczasowaRole($obserwator, 'ROLE_OBSERWATOR_ZEBRANIA');
                } catch (\Exception $e) {
                    $this->logger->error('Błąd podczas nadawania roli obserwatora', [
                        'user_id' => $obserwator->getId(),
                        'error' => $e->getMessage()
                    ]);
                    throw new \RuntimeException('Nie udało się nadać roli obserwatora: ' . $e->getMessage());
                }
                
                $this->entityManager->flush();

                // Utwórz dokument wyznaczenia obserwatora zgodnie ze specyfikacją
                $this->createObserverAppointmentDocument($zebranie, $obserwator);

                // Odśwież token obserwatora jeśli to aktualnie zalogowany użytkownik
                $this->refreshUserTokenIfNeeded($obserwator);

                $this->logMeetingActivity($zebranie, 'meeting_started', [
                    'okreg' => $okreg->getNazwa(),
                    'obserwator' => $obserwator->getFullName(),
                    'obserwator_id' => $obserwator->getId(),
                    'utworzony_przez' => $utworzonyPrzez->getFullName(),
                ]);

                return $zebranie;
            } catch (\Exception $e) {
                $this->logger->error('Błąd podczas tworzenia zebrania okręgu', [
                    'okreg_id' => $okreg->getId(),
                    'obserwator_id' => $obserwator->getId(),
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Wyznacza protokolanta zebrania z automatycznym nadawaniem roli.
     */
    public function wyznaczProtokolanta(ZebranieOkregu $zebranie, User $protokolant): void
    {
        $this->entityManager->wrapInTransaction(function() use ($zebranie, $protokolant) {
            // Utwórz dokument wyznaczenia protokolanta
            $dokumentData = [
                'protokolant' => $protokolant,
                'data_wejscia_w_zycie' => new \DateTime(),
                'uzasadnienie' => sprintf(
                    'Wyznaczenie protokolanta na zebranie członków okręgu %s',
                    $zebranie->getOkreg()->getNazwa()
                ),
            ];

            // Twórcą wyznaczenia protokolanta jest OBSERWATOR
            $dokument = $this->dokumentService->createDocument(
                \App\Entity\Dokument::TYP_WYZNACZENIE_PROTOKOLANTA,
                $dokumentData,
                $zebranie->getObserwator(),
                true // Pomiń sprawdzanie uprawnień
            );

            // Powiąż dokument z zebraniem
            $dokument->setZebranieOkregu($zebranie);
            $zebranie->setProtokolant($protokolant);
            $zebranie->setStatus(ZebranieOkregu::STATUS_WYZNACZANIE_PROWADZACEGO);
            
            // Nadaj rolę protokolanta zebrania
            $this->nadajTymczasowaRole($protokolant, 'ROLE_PROTOKOLANT_ZEBRANIA');
            
            $this->entityManager->flush();

            // Automatycznie podpisz dokument przez obserwatora
            $this->dokumentService->signDocument($dokument, $zebranie->getObserwator());
            
            // Odśwież token protokolanta
            $this->refreshUserTokenIfNeeded($protokolant);

            $this->logMeetingActivity($zebranie, 'secretary_appointed', [
                'protokolant' => $protokolant->getFullName(),
                'protokolant_id' => $protokolant->getId(),
                'wyznaczajacy' => $zebranie->getObserwator()->getFullName(),
            ]);
        });
    }

    /**
     * Wyznacza prowadzącego zebrania z automatycznym nadawaniem roli.
     */
    public function wyznaczProwadzacego(ZebranieOkregu $zebranie, User $prowadzacy): void
    {
        $this->entityManager->wrapInTransaction(function() use ($zebranie, $prowadzacy) {
            // Utwórz dokument wyznaczenia prowadzącego
            $dokumentData = [
                'prowadzacy' => $prowadzacy,
                'data_wejscia_w_zycie' => new \DateTime(),
                'uzasadnienie' => sprintf(
                    'Wyznaczenie prowadzącego na zebranie członków okręgu %s',
                    $zebranie->getOkreg()->getNazwa()
                ),
            ];

            // Twórcą jest obserwator (prowadzący jeszcze nie istnieje w tym momencie)
            $dokument = $this->dokumentService->createDocument(
                \App\Entity\Dokument::TYP_WYZNACZENIE_PROWADZACEGO,
                $dokumentData,
                $zebranie->getObserwator(),
                true // Pomiń sprawdzanie uprawnień
            );

            // Powiąż dokument z zebraniem
            $dokument->setZebranieOkregu($zebranie);
            $zebranie->setProwadzacy($prowadzacy);
            $zebranie->setStatus(ZebranieOkregu::STATUS_WYBOR_PREZESA);
            
            // Nadaj rolę prowadzącego zebrania
            $this->nadajTymczasowaRole($prowadzacy, 'ROLE_PROWADZACY_ZEBRANIA');
            
            $this->entityManager->flush();

            // Automatycznie podpisz dokument przez obserwatora
            $this->dokumentService->signDocument($dokument, $zebranie->getObserwator());
            
            // Odśwież token prowadzącego
            $this->refreshUserTokenIfNeeded($prowadzacy);

            $this->logMeetingActivity($zebranie, 'chairman_appointed', [
                'prowadzacy' => $prowadzacy->getFullName(),
                'prowadzacy_id' => $prowadzacy->getId(),
                'wyznaczajacy' => $zebranie->getObserwator()->getFullName(),
            ]);
        });
    }

    /**
     * Kończy zebranie i usuwa tymczasowe role.
     */
    /**
     * Wybiera prezesa okręgu i przechodzi do wyboru wiceprezesów.
     */
    public function wyborPrezesa(ZebranieOkregu $zebranie, User $prezes): void
    {
        $this->entityManager->wrapInTransaction(function() use ($zebranie, $prezes) {
            // Usuń poprzedniego prezesa okręgu jeśli istnieje
            $this->odwolajPoprzedniegoPrezes($zebranie->getOkreg());
            
            $zebranie->setPrezesOkregu($prezes);
            $zebranie->setStatus(ZebranieOkregu::STATUS_WYBOR_WICEPREZESOW);
            
            // Nadaj rolę prezesa okręgu z walidacją konfliktów
            $this->validateAndAssignRole($prezes, 'ROLE_PREZES_OKREGU');
            
            $this->entityManager->flush();

            // NATYCHMIAST utwórz dokument powołania prezesa (zgodnie ze specyfikacją)
            $this->createPresidentAppointmentDocument($zebranie, $prezes);

            $this->logMeetingActivity($zebranie, 'president_selected', [
                'prezes' => $prezes->getFullName(),
                'prezes_id' => $prezes->getId(),
            ]);
        });
    }

    /**
     * Wybiera wiceprezesów okręgu i natychmiast tworzy dokumenty zgodnie ze specyfikacją.
     */
    public function wyborWiceprezesow(ZebranieOkregu $zebranie, User $wiceprezes1, ?User $wiceprezes2 = null): void
    {
        $this->entityManager->wrapInTransaction(function() use ($zebranie, $wiceprezes1, $wiceprezes2) {
            // Usuń poprzednich wiceprezesów okręgu jeśli istnieją
            $this->odwolajPoprzednichWiceprezesow($zebranie->getOkreg());
            
            $zebranie->setWiceprezes1($wiceprezes1);
            if ($wiceprezes2) {
                $zebranie->setWiceprezes2($wiceprezes2);
            }
            
            // Nadaj role wiceprezesów z walidacją konfliktów
            $this->validateAndAssignRole($wiceprezes1, 'ROLE_WICEPREZES_OKREGU');
            
            if ($wiceprezes2) {
                $this->validateAndAssignRole($wiceprezes2, 'ROLE_WICEPREZES_OKREGU');
            }

            // Przejdź do wyboru Sekretarza Okręgu
            $zebranie->setStatus(ZebranieOkregu::STATUS_WYBOR_SEKRETARZA);
            
            $this->entityManager->flush();

            // NATYCHMIAST utwórz dokumenty powołania wiceprezesów (zgodnie ze specyfikacją)
            $this->createVicePresidentAppointmentDocuments($zebranie, $wiceprezes1, $wiceprezes2);

            $this->logMeetingActivity($zebranie, 'vice_presidents_selected', [
                'wiceprezes1' => $wiceprezes1->getFullName(),
                'wiceprezes1_id' => $wiceprezes1->getId(),
                'wiceprezes2' => $wiceprezes2?->getFullName(),
                'wiceprezes2_id' => $wiceprezes2?->getId(),
            ]);
        });
    }

    /**
     * Wybór Sekretarza Okręgu na zebraniu walnym.
     */
    public function wyborSekretarza(ZebranieOkregu $zebranie, User $sekretarz): void
    {
        $this->entityManager->wrapInTransaction(function() use ($zebranie, $sekretarz) {
            // Usuń poprzedniego Sekretarza Okręgu jeśli istnieje
            $this->odwolajPoprzedniegoSekretarza($zebranie->getOkreg());

            $zebranie->setSekretarzOkregu($sekretarz);

            // Nadaj rolę Sekretarza z walidacją konfliktów
            $this->validateAndAssignRole($sekretarz, 'ROLE_SEKRETARZ_OKREGU');

            // Przejdź do wyboru Skarbnika Okręgu
            $zebranie->setStatus(ZebranieOkregu::STATUS_WYBOR_SKARBNIKA);

            $this->entityManager->flush();

            // Utwórz dokument wyboru Sekretarza
            $this->utworzDokumentWyboru(
                $zebranie,
                $sekretarz,
                Dokument::TYP_WYBOR_SEKRETARZA_OKREGU_WALNE,
                'Sekretarz Okręgu'
            );

            $this->logMeetingActivity($zebranie, 'secretary_selected', [
                'sekretarz' => $sekretarz->getFullName(),
                'sekretarz_id' => $sekretarz->getId(),
            ]);
        });
    }

    /**
     * Wybór Skarbnika Okręgu na zebraniu walnym.
     */
    public function wyborSkarbnika(ZebranieOkregu $zebranie, User $skarbnik): void
    {
        $this->entityManager->wrapInTransaction(function() use ($zebranie, $skarbnik) {
            // Usuń poprzedniego Skarbnika Okręgu jeśli istnieje
            $this->odwolajPoprzedniegoSkarbnika($zebranie->getOkreg());

            $zebranie->setSkarbnikOkregu($skarbnik);

            // Nadaj rolę Skarbnika z walidacją konfliktów
            $this->validateAndAssignRole($skarbnik, 'ROLE_SKARBNIK_OKREGU');

            // Przejdź do składania podpisów
            $zebranie->setStatus(ZebranieOkregu::STATUS_SKLADANIE_PODPISOW);

            $this->entityManager->flush();

            // Utwórz dokument wyboru Skarbnika
            $this->utworzDokumentWyboru(
                $zebranie,
                $skarbnik,
                Dokument::TYP_WYBOR_SKARBNIKA_OKREGU_WALNE,
                'Skarbnik Okręgu'
            );

            $this->logMeetingActivity($zebranie, 'treasurer_selected', [
                'skarbnik' => $skarbnik->getFullName(),
                'skarbnik_id' => $skarbnik->getId(),
            ]);
        });
    }

    /**
     * Odwołuje poprzedniego Sekretarza Okręgu.
     */
    private function odwolajPoprzedniegoSekretarza(Okreg $okreg): void
    {
        $users = $this->entityManager->getRepository(User::class)->findBy(['okreg' => $okreg]);

        foreach ($users as $user) {
            $roles = $user->getRoles();
            if (in_array('ROLE_SEKRETARZ_OKREGU', $roles)) {
                $this->odbierzRole($user, 'ROLE_SEKRETARZ_OKREGU');
            }
        }
    }

    /**
     * Odwołuje poprzedniego Skarbnika Okręgu.
     */
    private function odwolajPoprzedniegoSkarbnika(Okreg $okreg): void
    {
        $users = $this->entityManager->getRepository(User::class)->findBy(['okreg' => $okreg]);

        foreach ($users as $user) {
            $roles = $user->getRoles();
            if (in_array('ROLE_SKARBNIK_OKREGU', $roles)) {
                $this->odbierzRole($user, 'ROLE_SKARBNIK_OKREGU');
            }
        }
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
        if ($zebranie->getObserwator() && $zebranie->getObserwator()->getId() === $user->getId()) {
            $zebranie->setObserwatorZaakceptowal(true);
        } elseif ($zebranie->getProtokolant() && $zebranie->getProtokolant()->getId() === $user->getId()) {
            $zebranie->setProtokolantZaakceptowal(true);
        } elseif ($zebranie->getProwadzacy() && $zebranie->getProwadzacy()->getId() === $user->getId()) {
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
        if ($zebranie->getObserwator()) {
            $this->usunTymczasowaRole($zebranie->getObserwator(), 'ROLE_OBSERWATOR_ZEBRANIA');
        }
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
     * Usuwa poprzedniego prezesa okręgu (poprzez usunięcie roli).
     */
    private function odwolajPoprzedniegoPrezes(Okreg $okreg): void
    {
        $users = $this->entityManager->getRepository(User::class)->findBy(['okreg' => $okreg]);
        foreach ($users as $user) {
            $roles = $user->getRoles();
            if (in_array('ROLE_PREZES_OKREGU', $roles)) {
                // Usuń rolę prezesa
                $roles = array_filter($roles, fn($role) => $role !== 'ROLE_PREZES_OKREGU');
                $user->setRoles(array_values($roles));
                
                $this->logger->info('Odwołano poprzedniego prezesa okręgu', [
                    'user_id' => $user->getId(),
                    'user_name' => $user->getFullName(),
                    'okreg_id' => $okreg->getId(),
                ]);
            }
        }
    }

    /**
     * Usuwa poprzednich wiceprezesów okręgu (poprzez usunięcie roli).
     */
    private function odwolajPoprzednichWiceprezesow(Okreg $okreg): void
    {
        $users = $this->entityManager->getRepository(User::class)->findBy(['okreg' => $okreg]);
        foreach ($users as $user) {
            $roles = $user->getRoles();
            if (in_array('ROLE_WICEPREZES_OKREGU', $roles)) {
                // Usuń rolę wiceprezesa
                $roles = array_filter($roles, fn($role) => $role !== 'ROLE_WICEPREZES_OKREGU');
                $user->setRoles(array_values($roles));
                
                $this->logger->info('Odwołano poprzedniego wiceprezesa okręgu', [
                    'user_id' => $user->getId(),
                    'user_name' => $user->getFullName(),
                    'okreg_id' => $okreg->getId(),
                ]);
            }
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
        if ($zebranie->getObserwator()) {
            $this->usunTymczasowaRole($zebranie->getObserwator(), 'ROLE_OBSERWATOR_ZEBRANIA');
        }
        if ($zebranie->getProtokolant()) {
            $this->usunTymczasowaRole($zebranie->getProtokolant(), 'ROLE_PROTOKOLANT_ZEBRANIA');
        }
        if ($zebranie->getProwadzacy()) {
            $this->usunTymczasowaRole($zebranie->getProwadzacy(), 'ROLE_PROWADZACY_ZEBRANIA');
        }

        $this->entityManager->flush();

        // Odśwież tokeny wszystkich uczestników zebrania
        if ($zebranie->getObserwator()) {
            $this->refreshUserTokenIfNeeded($zebranie->getObserwator());
        }
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
     * Waliduje i nadaje rolę z kontrolą konfliktów.
     */
    private function validateAndAssignRole(User $user, string $newRole): void
    {
        $conflictingRoles = [
            'ROLE_PREZES_OKREGU' => ['ROLE_WICEPREZES_OKREGU', 'ROLE_SEKRETARZ_OKREGU', 'ROLE_SKARBNIK_OKREGU'],
            'ROLE_WICEPREZES_OKREGU' => ['ROLE_PREZES_OKREGU', 'ROLE_SEKRETARZ_OKREGU', 'ROLE_SKARBNIK_OKREGU'],
            'ROLE_SEKRETARZ_OKREGU' => ['ROLE_PREZES_OKREGU', 'ROLE_WICEPREZES_OKREGU', 'ROLE_SKARBNIK_OKREGU'],
            'ROLE_SKARBNIK_OKREGU' => ['ROLE_PREZES_OKREGU', 'ROLE_WICEPREZES_OKREGU', 'ROLE_SEKRETARZ_OKREGU'],
        ];

        $currentRoles = $user->getRoles();

        // Automatycznie odbierz konfliktujące role
        if (isset($conflictingRoles[$newRole])) {
            foreach ($conflictingRoles[$newRole] as $conflictRole) {
                if (in_array($conflictRole, $currentRoles)) {
                    // Usuń konfliktującą rolę
                    $currentRoles = array_values(array_diff($currentRoles, [$conflictRole]));

                    $this->logger->info('Automatycznie odebrano konfliktującą rolę przed nadaniem nowej', [
                        'user_id' => $user->getId(),
                        'user_name' => $user->getFullName(),
                        'removed_role' => $conflictRole,
                        'new_role' => $newRole,
                    ]);
                }
            }
        }

        // Nadaj rolę jeśli jej nie ma
        if (!in_array($newRole, $currentRoles)) {
            $currentRoles[] = $newRole;
            $user->setRoles($currentRoles);

            $this->logger->info('Nadano rolę z walidacją konfliktów', [
                'user_id' => $user->getId(),
                'user_name' => $user->getFullName(),
                'role' => $newRole,
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
     * Odbiera rolę okręgową od użytkownika (używane przy odwoływaniu z funkcji).
     */
    private function odbierzRole(User $user, string $role): void
    {
        $roles = $user->getRoles();
        $roles = array_filter($roles, fn($r) => $r !== $role);
        $user->setRoles(array_values($roles));

        $this->logger->info('Odebrano rolę okręgu', [
            'user_id' => $user->getId(),
            'user_name' => $user->getFullName(),
            'role' => $role,
        ]);

        // Odśwież token jeśli to aktualny użytkownik
        $this->refreshUserTokenIfNeeded($user);
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
        // Twórcą dokumentu jest prowadzący zebranie
        $tworcaDokumentu = $zebranie->getProwadzacy() ?? $zebranie->getUtworzonyPrzez();

        // KRYTYCZNE: Okręg dokumentu MUSI być okręgiem ZEBRANIA, nie prowadzącego!
        $okregZebrania = $zebranie->getOkreg();

        // Przygotuj dane do dokumentu
        $data = [
            'czlonek' => $wybranaOsoba,
            'data_wejscia_w_zycie' => new \DateTime(),
            'okreg' => $okregZebrania,  // ZAWSZE okręg zebrania (np. Sieradz)
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
        $this->logger->info('Dane przed createDocument', [
            'typ_dokumentu' => $typDokumentu,
            'okreg_w_data' => isset($data['okreg']) ? $data['okreg']->getNazwa() : 'BRAK',
            'czlonek_w_data' => isset($data['czlonek']) ? $data['czlonek']->getFullName() : 'BRAK',
            'czlonek_id' => isset($data['czlonek']) ? $data['czlonek']->getId() : 'BRAK',
            'wybrana_osoba' => $wybranaOsoba->getFullName(),
            'wybrana_osoba_id' => $wybranaOsoba->getId(),
            'tworca' => $tworcaDokumentu->getFullName(),
            'tworca_okreg' => $tworcaDokumentu->getOkreg() ? $tworcaDokumentu->getOkreg()->getNazwa() : 'NULL',
        ]);

        $dokument = $this->dokumentService->createDocument(
            $typDokumentu,
            $data,
            $tworcaDokumentu,
            true // Pomiń sprawdzanie uprawnień - dokumenty z zebrań są tworzone automatycznie
        );

        $this->logger->info('Dokument po createDocument', [
            'dokument_id' => $dokument->getId(),
            'dokument_okreg' => $dokument->getOkreg() ? $dokument->getOkreg()->getNazwa() : 'NULL',
            'dokument_czlonek' => $dokument->getCzlonek() ? $dokument->getCzlonek()->getFullName() : 'NULL',
            'dokument_czlonek_id' => $dokument->getCzlonek() ? $dokument->getCzlonek()->getId() : 'NULL',
        ]);

        // Ustaw dodatkowe właściwości specyficzne dla zebrania
        $dokument->setZebranieOkregu($zebranie);
        $dokument->setCzlonek($wybranaOsoba); // Ustaw członka, którego dotyczy dokument
        $dokument->setDataWejsciaWZycie(new \DateTime()); // Dokument wchodzi w życie natychmiast

        $this->logger->info('Dokument po ustawieniu dodatkowych właściwości', [
            'dokument_okreg' => $dokument->getOkreg() ? $dokument->getOkreg()->getNazwa() : 'NULL',
            'dokument_czlonek' => $dokument->getCzlonek() ? $dokument->getCzlonek()->getFullName() : 'NULL',
        ]);

        // Okręg już został ustawiony przez createDocument z danych $data['okreg']
        // NIE nadpisuj go ponownie - createDocument ustawia go poprawnie

        // Dodaj wymaganych podpisujących: Prowadzący + Protokolant + Obserwator
        $this->addDistrictMeetingSigners($dokument, $zebranie);

        $this->entityManager->persist($dokument);
        $this->entityManager->flush();

        $this->logger->info('Wygenerowano dokument wyboru z zebrania okręgu', [
            'dokument_id' => $dokument->getId(),
            'dokument_okreg' => $dokument->getOkreg() ? $dokument->getOkreg()->getNazwa() : 'NULL',
            'zebranie_okreg' => $zebranie->getOkreg()->getNazwa(),
        ]);

        $this->logger->info('Wygenerowano dokument wyboru z zebrania okręgu', [
            'dokument_id' => $dokument->getId(),
            'typ' => $typDokumentu,
            'zebranie_id' => $zebranie->getId(),
            'wybrana_osoba' => $wybranaOsoba->getFullName(),
            'stanowisko' => $stanowisko,
        ]);
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
            if ($podpis->getPodpisujacy() && $podpis->getPodpisujacy()->getId() === $user->getId()) {
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
     * Tworzy dokument powołania prezesa okręgu zgodnie ze specyfikacją.
     */
    private function createPresidentAppointmentDocument(ZebranieOkregu $zebranie, User $prezes): void
    {
        // Używamy typu dla wyboru prezesa okręgu przez walne zebranie członków
        $typDokumentu = \App\Entity\Dokument::TYP_WYBOR_PREZESA_OKREGU_WALNE;

        // Użyj nowej metody utworzDokumentWyboru która poprawnie obsługuje dane
        $this->utworzDokumentWyboru($zebranie, $prezes, $typDokumentu, 'Prezes Okręgu');

        $this->logger->info('Utworzono dokument powołania prezesa okręgu', [
            'zebranie_id' => $zebranie->getId(),
            'prezes_id' => $prezes->getId(),
        ]);
    }

    /**
     * Tworzy dokumenty powołania wiceprezesów okręgu zgodnie ze specyfikacją.
     */
    private function createVicePresidentAppointmentDocuments(ZebranieOkregu $zebranie, User $wiceprezes1, ?User $wiceprezes2 = null): void
    {
        // Dokument powołania wiceprezesa 1
        $this->createSingleVicePresidentDocument($zebranie, $wiceprezes1, 1);
        
        // Dokument powołania wiceprezesa 2 (opcjonalny)
        if ($wiceprezes2) {
            $this->createSingleVicePresidentDocument($zebranie, $wiceprezes2, 2);
        }
    }

    /**
     * Tworzy pojedynczy dokument powołania wiceprezesa.
     */
    private function createSingleVicePresidentDocument(ZebranieOkregu $zebranie, User $wiceprezes, int $numer): void
    {
        // Używamy typu dla wyboru wiceprezesa okręgu przez walne zebranie członków
        $typDokumentu = \App\Entity\Dokument::TYP_WYBOR_WICEPREZESA_OKREGU_WALNE;

        // Użyj nowej metody utworzDokumentWyboru która poprawnie obsługuje dane
        $this->utworzDokumentWyboru($zebranie, $wiceprezes, $typDokumentu, "Wiceprezes Okręgu ($numer)");

        $this->logger->info('Utworzono dokument powołania wiceprezesa okręgu', [
            'zebranie_id' => $zebranie->getId(),
            'wiceprezes_id' => $wiceprezes->getId(),
            'numer' => $numer,
        ]);
    }

    /**
     * Dodaje wymaganych podpisujących do dokumentu zebrania okręgu zgodnie ze specyfikacją.
     * Prowadzący (kolejność 1) + Protokolant (kolejność 2) + Obserwator (kolejność 3 - profilaktycznie).
     */
    private function addDistrictMeetingSigners(Dokument $dokument, ZebranieOkregu $zebranie): void
    {
        // Prowadzący - kolejność 1 (Przewodniczący Zgromadzenia w szablonie)
        if ($zebranie->getProwadzacy()) {
            $podpisProwadzacy = new \App\Entity\PodpisDokumentu();
            $podpisProwadzacy->setDokument($dokument);
            $podpisProwadzacy->setPodpisujacy($zebranie->getProwadzacy());
            $podpisProwadzacy->setStatus(\App\Entity\PodpisDokumentu::STATUS_OCZEKUJE);
            $podpisProwadzacy->setKolejnosc(1); // Przewodniczący Zgromadzenia
            $dokument->addPodpis($podpisProwadzacy);
            $this->entityManager->persist($podpisProwadzacy);
        }

        // Protokolant - kolejność 2 (Sekretarz Zgromadzenia w szablonie)
        if ($zebranie->getProtokolant()) {
            $podpisProtokolant = new \App\Entity\PodpisDokumentu();
            $podpisProtokolant->setDokument($dokument);
            $podpisProtokolant->setPodpisujacy($zebranie->getProtokolant());
            $podpisProtokolant->setStatus(\App\Entity\PodpisDokumentu::STATUS_OCZEKUJE);
            $podpisProtokolant->setKolejnosc(2); // Sekretarz Zgromadzenia
            $dokument->addPodpis($podpisProtokolant);
            $this->entityManager->persist($podpisProtokolant);
        }

        // Obserwator - kolejność 3 (podpisuje profilaktycznie, nie wyświetla się w szablonie)
        if ($zebranie->getObserwator()) {
            $podpisObserwator = new \App\Entity\PodpisDokumentu();
            $podpisObserwator->setDokument($dokument);
            $podpisObserwator->setPodpisujacy($zebranie->getObserwator());
            $podpisObserwator->setStatus(\App\Entity\PodpisDokumentu::STATUS_OCZEKUJE);
            $podpisObserwator->setKolejnosc(3); // Profilaktyczny podpis, nie wyświetla się
            $dokument->addPodpis($podpisObserwator);
            $this->entityManager->persist($podpisObserwator);
        }
    }

    /**
     * Tworzy dokument wyznaczenia obserwatora zebrania okręgu zgodnie ze specyfikacją.
     * Dokument podpisuje Sekretarz Partii.
     */
    private function createObserverAppointmentDocument(ZebranieOkregu $zebranie, User $obserwator): void
    {
        // Znajdź Sekretarza Partii do podpisania dokumentu
        $sekretarzPartii = $this->entityManager->getRepository(User::class)
            ->findOneByRole('ROLE_SEKRETARZ_PARTII');
        
        if (!$sekretarzPartii) {
            throw new \RuntimeException('Brak Sekretarza Partii do podpisania dokumentu wyznaczenia obserwatora');
        }

        $dokumentData = [
            'obserwator' => $obserwator,
            'data_wejscia_w_zycie' => new \DateTime(),
            'uzasadnienie' => sprintf(
                'Wyznaczenie obserwatora na zebranie członków okręgu %s',
                $zebranie->getOkreg()->getNazwa()
            ),
        ];

        // Utwórz dokument
        $dokument = $this->dokumentService->createDocument(
            \App\Entity\Dokument::TYP_WYZNACZENIE_OBSERWATORA,
            $dokumentData,
            $sekretarzPartii,
            true // Pomiń sprawdzanie uprawnień
        );

        // Przypisz do zebrania okręgu
        $dokument->setZebranieOkregu($zebranie);
        $this->entityManager->flush();

        // Automatycznie podpisz dokument przez Sekretarza Partii (zgodnie ze specyfikacją)
        $this->dokumentService->signDocument($dokument, $sekretarzPartii);

        $this->logger->info('Utworzono dokument wyznaczenia obserwatora zebrania okręgu', [
            'dokument_id' => $dokument->getId(),
            'zebranie_id' => $zebranie->getId(),
            'obserwator_id' => $obserwator->getId(),
            'sekretarz_partii_id' => $sekretarzPartii->getId(),
        ]);
    }
}