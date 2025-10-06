<?php

namespace App\Service;

use App\Entity\Protokol;
use App\Entity\PodpisProtokolu;
use App\Entity\User;
use App\Entity\ZebranieOkregu;
use App\Entity\ZebranieOddzialu;
use App\Repository\ProtokolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ProtokolService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProtokolRepository $protokolRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Tworzy nowy protokół z zebrania okręgu
     */
    public function createFromZebranieOkregu(ZebranieOkregu $zebranie, User $creator): Protokol
    {
        $protokol = new Protokol();
        $protokol->setZebranieOkregu($zebranie);
        $protokol->setOkreg($zebranie->getOkreg());
        $protokol->setDataZebrania($zebranie->getDataUtworzenia());
        $protokol->setUtworzonePrzez($creator);

        // Ustaw protokolanta i przewodniczącego z zebrania
        if ($zebranie->getProtokolant()) {
            $protokol->setProtokolant($zebranie->getProtokolant());
        }

        if ($zebranie->getProwadzacy()) {
            $protokol->setPrzewodniczacy($zebranie->getProwadzacy());
        }

        // Wygeneruj numer protokołu
        $numerProtokolu = $this->protokolRepository->generateNextNumber($zebranie->getOkreg());
        $protokol->setNumerProtokolu($numerProtokolu);

        // Ustaw tytuł
        $tytul = sprintf(
            'Protokół zebrania okręgu %s z dnia %s',
            $zebranie->getOkreg()->getNazwa(),
            $zebranie->getDataUtworzenia()->format('d.m.Y')
        );
        $protokol->setTytul($tytul);

        // Wygeneruj początkową treść protokołu
        $tresc = $this->generateTrescForZebranieOkregu($zebranie);
        $protokol->setTresc($tresc);

        // Dodaj podpisy
        $this->addSignatures($protokol);

        $this->entityManager->persist($protokol);
        $this->entityManager->flush();

        $this->logger->info('Created protocol from district meeting', [
            'protokol_id' => $protokol->getId(),
            'zebranie_id' => $zebranie->getId(),
        ]);

        return $protokol;
    }

    /**
     * Tworzy nowy protokół z zebrania oddziału
     */
    public function createFromZebranieOddzialu(ZebranieOddzialu $zebranie, User $creator): Protokol
    {
        $protokol = new Protokol();
        $protokol->setZebranieOddzialu($zebranie);
        $protokol->setOddzial($zebranie->getOddzial());
        $protokol->setDataZebrania($zebranie->getDataRozpoczecia());
        $protokol->setUtworzonePrzez($creator);

        // Ustaw protokolanta i przewodniczącego z zebrania
        if ($zebranie->getProtokolant()) {
            $protokol->setProtokolant($zebranie->getProtokolant());
        }

        if ($zebranie->getProwadzacy()) {
            $protokol->setPrzewodniczacy($zebranie->getProwadzacy());
        }

        // Wygeneruj numer protokołu
        $numerProtokolu = $this->protokolRepository->generateNextNumber(null, $zebranie->getOddzial());
        $protokol->setNumerProtokolu($numerProtokolu);

        // Ustaw tytuł
        $tytul = sprintf(
            'Protokół zebrania oddziału %s z dnia %s',
            $zebranie->getOddzial()->getNazwa(),
            $zebranie->getDataRozpoczecia()->format('d.m.Y')
        );
        $protokol->setTytul($tytul);

        // Wygeneruj początkową treść protokołu
        $tresc = $this->generateTrescForZebranieOddzialu($zebranie);
        $protokol->setTresc($tresc);

        // Dodaj podpisy
        $this->addSignatures($protokol);

        $this->entityManager->persist($protokol);
        $this->entityManager->flush();

        $this->logger->info('Created protocol from branch meeting', [
            'protokol_id' => $protokol->getId(),
            'zebranie_id' => $zebranie->getId(),
        ]);

        return $protokol;
    }

    /**
     * Dodaje wymagane podpisy do protokołu
     */
    private function addSignatures(Protokol $protokol): void
    {
        $kolejnosc = 1;

        // Podpis protokolanta
        $podpisProtokolanta = new PodpisProtokolu();
        $podpisProtokolanta->setProtokol($protokol);
        $podpisProtokolanta->setPodpisujacy($protokol->getProtokolant());
        $podpisProtokolanta->setKolejnosc($kolejnosc++);
        $podpisProtokolanta->setStatus(PodpisProtokolu::STATUS_OCZEKUJE);
        $this->entityManager->persist($podpisProtokolanta);

        // Podpis przewodniczącego
        $podpisPrzewodniczacego = new PodpisProtokolu();
        $podpisPrzewodniczacego->setProtokol($protokol);
        $podpisPrzewodniczacego->setPodpisujacy($protokol->getPrzewodniczacy());
        $podpisPrzewodniczacego->setKolejnosc($kolejnosc++);
        $podpisPrzewodniczacego->setStatus(PodpisProtokolu::STATUS_OCZEKUJE);
        $this->entityManager->persist($podpisPrzewodniczacego);
    }

    /**
     * Generuje treść protokołu dla zebrania okręgu
     */
    private function generateTrescForZebranieOkregu(ZebranieOkregu $zebranie): string
    {
        $tresc = sprintf("PROTOKÓŁ ZEBRANIA OKRĘGU %s\n\n", strtoupper($zebranie->getOkreg()->getNazwa()));
        $tresc .= sprintf("Data: %s\n", $zebranie->getDataUtworzenia()->format('d.m.Y H:i'));
        $tresc .= sprintf("Miejsce: Online/CRM System\n\n");

        $tresc .= "UCZESTNICY:\n";
        $tresc .= sprintf("- Obserwator: %s\n", $zebranie->getObserwator()->getFullName());

        if ($zebranie->getProtokolant()) {
            $tresc .= sprintf("- Protokolant: %s\n", $zebranie->getProtokolant()->getFullName());
        }

        if ($zebranie->getProwadzacy()) {
            $tresc .= sprintf("- Przewodniczący: %s\n", $zebranie->getProwadzacy()->getFullName());
        }

        $tresc .= "\n\nPORZĄDEK OBRAD:\n";
        $tresc .= "1. Otwarcie zebrania\n";
        $tresc .= "2. Wybór protokolanta\n";
        $tresc .= "3. Wybór przewodniczącego zebrania\n";

        if ($zebranie->getPrezesOkregu()) {
            $tresc .= "4. Wybór Prezesa Okręgu\n";
            $tresc .= sprintf("   Wybrany: %s\n", $zebranie->getPrezesOkregu()->getFullName());
        }

        if ($zebranie->getWiceprezes1() || $zebranie->getWiceprezes2()) {
            $tresc .= "5. Wybór Wiceprezesów Okręgu\n";

            if ($zebranie->getWiceprezes1()) {
                $tresc .= sprintf("   Wiceprezes I: %s\n", $zebranie->getWiceprezes1()->getFullName());
            }

            if ($zebranie->getWiceprezes2()) {
                $tresc .= sprintf("   Wiceprezes II: %s\n", $zebranie->getWiceprezes2()->getFullName());
            }
        }

        $tresc .= "\n6. Zamknięcie zebrania\n\n";

        if ($zebranie->getNotatki()) {
            $tresc .= "NOTATKI:\n";
            $tresc .= $zebranie->getNotatki() . "\n\n";
        }

        return $tresc;
    }

    /**
     * Generuje treść protokołu dla zebrania oddziału
     */
    private function generateTrescForZebranieOddzialu(ZebranieOddzialu $zebranie): string
    {
        $tresc = sprintf("PROTOKÓŁ ZEBRANIA ODDZIAŁU %s\n\n", strtoupper($zebranie->getOddzial()->getNazwa()));
        $tresc .= sprintf("Data: %s\n", $zebranie->getDataRozpoczecia()->format('d.m.Y H:i'));
        $tresc .= sprintf("Miejsce: Online/CRM System\n\n");

        $tresc .= "UCZESTNICY:\n";
        if ($zebranie->getObserwator()) {
            $tresc .= sprintf("- Obserwator: %s\n", $zebranie->getObserwator()->getFullName());
        }

        if ($zebranie->getProtokolant()) {
            $tresc .= sprintf("- Protokolant: %s\n", $zebranie->getProtokolant()->getFullName());
        }

        if ($zebranie->getProwadzacy()) {
            $tresc .= sprintf("- Przewodniczący: %s\n", $zebranie->getProwadzacy()->getFullName());
        }

        $tresc .= "\n\nPORZĄDEK OBRAD:\n";
        $tresc .= "1. Otwarcie zebrania\n";
        $tresc .= "2. Wybór protokolanta\n";
        $tresc .= "3. Wybór przewodniczącego zebrania\n";

        if ($zebranie->getPrzewodniczacy()) {
            $tresc .= "4. Wybór Przewodniczącego Oddziału\n";
            $tresc .= sprintf("   Wybrany: %s\n", $zebranie->getPrzewodniczacy()->getFullName());
        }

        if ($zebranie->getZastepca1() || $zebranie->getZastepca2()) {
            $tresc .= "5. Wybór Zastępców Przewodniczącego\n";

            if ($zebranie->getZastepca1()) {
                $tresc .= sprintf("   Zastępca I: %s\n", $zebranie->getZastepca1()->getFullName());
            }

            if ($zebranie->getZastepca2()) {
                $tresc .= sprintf("   Zastępca II: %s\n", $zebranie->getZastepca2()->getFullName());
            }
        }

        $tresc .= "\n6. Zamknięcie zebrania\n\n";

        return $tresc;
    }

    /**
     * Podpisuje protokół
     */
    public function signProtokol(Protokol $protokol, User $user, string $signatureData): bool
    {
        // Znajdź podpis użytkownika
        foreach ($protokol->getPodpisy() as $podpis) {
            if ($podpis->getPodpisujacy() === $user && $podpis->getStatus() === PodpisProtokolu::STATUS_OCZEKUJE) {
                $podpis->setStatus(PodpisProtokolu::STATUS_PODPISANY);
                $podpis->setDataPodpisania(new \DateTime());
                $podpis->setPodpisElektroniczny($signatureData);

                $this->entityManager->persist($podpis);
                $this->entityManager->flush();

                // Sprawdź czy wszystkie podpisy zostały złożone
                if ($protokol->czyPodpisanyPrzezWszystkich()) {
                    $protokol->setStatus(Protokol::STATUS_SIGNED);
                    $this->entityManager->flush();
                }

                $this->logger->info('Protocol signed', [
                    'protokol_id' => $protokol->getId(),
                    'user_id' => $user->getId(),
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Zatwierdza protokół
     */
    public function approveProtokol(Protokol $protokol): void
    {
        $protokol->setStatus(Protokol::STATUS_APPROVED);
        $protokol->setDataZatwierdzenia(new \DateTime());

        $this->entityManager->flush();

        $this->logger->info('Protocol approved', [
            'protokol_id' => $protokol->getId(),
        ]);
    }

    /**
     * Aktualizuje treść protokołu
     */
    public function updateTresc(Protokol $protokol, string $tresc): void
    {
        $protokol->setTresc($tresc);
        $this->entityManager->flush();

        $this->logger->info('Protocol content updated', [
            'protokol_id' => $protokol->getId(),
        ]);
    }
}
