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
    name: 'app:cleanup-deleted-users',
    description: 'Przenosi członków z prefiksem del_ do byłych członków lub usuwa kandydatów',
)]
class CleanupDeletedUsersCommand extends Command
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

        $io->title('Czyszczenie użytkowników z prefiksem "del_"');

        $userRepository = $this->entityManager->getRepository(User::class);

        // Znajdź użytkowników z emailem zaczynającym się od "del_"
        // Używamy LIKE 'del\_%' aby wykluczyć słowa typu "delicyjka"
        $deletedUsers = $userRepository->createQueryBuilder('u')
            ->where('u.email LIKE :prefix')
            ->setParameter('prefix', 'del\_%')
            ->getQuery()
            ->getResult();

        $io->text(sprintf('Znaleziono %d użytkowników z prefiksem "del_"', count($deletedUsers)));
        $io->newLine();

        $movedToFormer = 0;
        $deletedCandidates = 0;
        $skipped = 0;

        foreach ($deletedUsers as $user) {
            $email = $user->getEmail();
            $fullName = $user->getImie() . ' ' . $user->getNazwisko();
            $type = $user->getTypUzytkownika();

            if ($type === 'mlodziezowka' || $type === 'czlonek') {
                // Członkowie i młodzieżówka → przenieś do byłych członków
                $io->text(sprintf(
                    '👤 %s (%s) - typ: %s → przenoszę do byłych członków',
                    $fullName,
                    $email,
                    $type
                ));

                if (!$dryRun) {
                    $user->setTypUzytkownika('byly_czlonek');
                    $user->setStatus('nieaktywny');

                    $this->entityManager->persist($user);
                }

                $movedToFormer++;

            } elseif ($type === 'kandydat') {
                // Kandydaci → usuń
                $io->text(sprintf(
                    '🗑️  %s (%s) - typ: kandydat → usuwam z bazy',
                    $fullName,
                    $email
                ));

                if (!$dryRun) {
                    // Usuń powiązane rekordy
                    $postepKandydata = $user->getPostepKandydataEntity();
                    if ($postepKandydata) {
                        $this->entityManager->remove($postepKandydata);
                    }

                    // Usuń składki członkowskie
                    foreach ($user->getSkladkiCzlonkowskie() as $skladka) {
                        $this->entityManager->remove($skladka);
                    }

                    // Usuń opinie
                    foreach ($user->getOpinie() as $opinia) {
                        $this->entityManager->remove($opinia);
                    }

                    // Usuń płatności
                    foreach ($user->getPlatnosci() as $platnosc) {
                        $this->entityManager->remove($platnosc);
                    }

                    // Usuń funkcje
                    foreach ($user->getFunkcje() as $funkcja) {
                        $this->entityManager->remove($funkcja);
                    }

                    $this->entityManager->remove($user);
                }

                $deletedCandidates++;

            } else {
                // Inny typ - pomiń
                $io->text(sprintf(
                    '⏭️  %s (%s) - typ: %s → pomijam (nieznany typ)',
                    $fullName,
                    $email,
                    $type
                ));
                $skipped++;
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
            $io->success('Zmiany zostały zapisane!');
        } else {
            $io->info('Tryb DRY-RUN - żadne zmiany nie zostały zapisane. Uruchom bez --dry-run aby zapisać.');
        }

        $io->newLine();
        $io->table(
            ['Akcja', 'Liczba'],
            [
                ['Przeniesiono do byłych członków', $movedToFormer],
                ['Usunięto kandydatów', $deletedCandidates],
                ['Pominięto', $skipped],
                ['Razem przetworzono', $movedToFormer + $deletedCandidates + $skipped],
            ]
        );

        return Command::SUCCESS;
    }
}
