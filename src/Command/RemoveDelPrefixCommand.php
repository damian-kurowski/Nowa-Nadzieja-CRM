<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:remove-del-prefix',
    description: 'Usuwa prefix "del_" z danych osobowych użytkowników',
)]
class RemoveDelPrefixCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Tylko pokaż co zostanie zrobione, bez zapisywania zmian');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->warning('Tryb DRY-RUN - żadne zmiany nie zostaną zapisane!');
        }

        $io->title('Usuwanie prefiksu "del_" z danych osobowych');

        $userRepository = $this->entityManager->getRepository(User::class);

        // Znajdź użytkowników byłych członków (którzy byli z del_)
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.typUzytkownika = :type')
            ->andWhere('u.email LIKE :prefix')
            ->setParameter('type', 'byly_czlonek')
            ->setParameter('prefix', 'del\_%')
            ->getQuery()
            ->getResult();

        $io->text(sprintf('Znaleziono %d byłych członków z prefiksem "del_"', count($users)));
        $io->newLine();

        $cleaned = 0;

        foreach ($users as $user) {
            $changes = [];

            // Usuń "del_" z imienia
            $imie = $user->getImie();
            if (str_starts_with($imie, 'del_')) {
                $newImie = substr($imie, 4); // Usuń pierwsze 4 znaki "del_"
                $changes[] = "Imię: '$imie' → '$newImie'";
                if (!$dryRun) {
                    $user->setImie($newImie);
                }
            }

            // Usuń "del_" z drugiego imienia
            $drugieImie = $user->getDrugieImie();
            if ($drugieImie && str_starts_with($drugieImie, 'del_')) {
                $newDrugieImie = substr($drugieImie, 4);
                $changes[] = "Drugie imię: '$drugieImie' → '$newDrugieImie'";
                if (!$dryRun) {
                    $user->setDrugieImie($newDrugieImie);
                }
            }

            // Usuń "del_" z nazwiska
            $nazwisko = $user->getNazwisko();
            if (str_starts_with($nazwisko, 'del_')) {
                $newNazwisko = substr($nazwisko, 4);
                $changes[] = "Nazwisko: '$nazwisko' → '$newNazwisko'";
                if (!$dryRun) {
                    $user->setNazwisko($newNazwisko);
                }
            }

            // Usuń "del_" z PESEL
            $pesel = $user->getPesel();
            if ($pesel && str_starts_with($pesel, 'del_')) {
                $newPesel = substr($pesel, 4);
                $changes[] = "PESEL: '$pesel' → '$newPesel'";
                if (!$dryRun) {
                    $user->setPesel($newPesel);
                }
            }

            // Usuń "del_" lub "del_48" z telefonu
            $telefon = $user->getTelefon();
            if ($telefon) {
                if (str_starts_with($telefon, 'del_48')) {
                    $newTelefon = substr($telefon, 6); // Usuń "del_48"
                    $changes[] = "Telefon: '$telefon' → '$newTelefon'";
                    if (!$dryRun) {
                        $user->setTelefon($newTelefon);
                    }
                } elseif (str_starts_with($telefon, 'del_')) {
                    $newTelefon = substr($telefon, 4); // Usuń "del_"
                    $changes[] = "Telefon: '$telefon' → '$newTelefon'";
                    if (!$dryRun) {
                        $user->setTelefon($newTelefon);
                    }
                }
            }

            // Usuń "del_" z emaila (zostaw tylko dla logów)
            $email = $user->getEmail();
            if (str_starts_with($email, 'del_')) {
                $newEmail = substr($email, 4);
                $changes[] = "Email: '$email' → '$newEmail'";
                if (!$dryRun) {
                    $user->setEmail($newEmail);
                }
            }

            if (!empty($changes)) {
                $io->section(sprintf('👤 %s %s (ID: %d)', $user->getImie(), $user->getNazwisko(), $user->getId()));
                foreach ($changes as $change) {
                    $io->text('  ✏️  ' . $change);
                }

                if (!$dryRun) {
                    $this->entityManager->persist($user);
                }

                $cleaned++;
            }

            // Flush co 50 rekordów
            if (!$dryRun && $cleaned % 50 === 0 && $cleaned > 0) {
                $this->entityManager->flush();
                $io->text("💾 Zapisano $cleaned zmian...");
            }
        }

        if (!$dryRun && $cleaned > 0) {
            $this->entityManager->flush();
            $io->success("Zmiany zostały zapisane! Wyczyszczono dane dla $cleaned użytkowników.");
        } elseif ($dryRun) {
            $io->info("Tryb DRY-RUN - znaleziono $cleaned użytkowników do wyczyszczenia. Uruchom bez --dry-run aby zapisać.");
        } else {
            $io->info('Nie znaleziono użytkowników wymagających czyszczenia.');
        }

        return Command::SUCCESS;
    }
}
