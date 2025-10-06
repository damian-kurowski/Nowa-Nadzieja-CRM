<?php

namespace App\Command;

use App\Repository\DokumentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:remove-expired-temporary-roles',
    description: 'Remove temporary roles that have expired based on document end date',
)]
class RemoveExpiredTemporaryRolesCommand extends Command
{
    public function __construct(
        private DokumentRepository $dokumentRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Removing Expired Temporary Roles');

        $now = new \DateTime();
        $removedCount = 0;

        // Find all "wyznaczenie tymczasowe" documents with expired end dates
        $qb = $this->dokumentRepository->createQueryBuilder('d');
        $expiredDocuments = $qb
            ->where('d.typDokumentu = :type')
            ->andWhere("JSON_EXTRACT(d.daneDodatkowe, '$.data_zakonczenia') IS NOT NULL")
            ->andWhere("STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(d.daneDodatkowe, '$.data_zakonczenia')), '%Y-%m-%d') < :now")
            ->setParameter('type', 'wyznaczenie_osoby_tymczasowej')
            ->setParameter('now', $now->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        $io->writeln(sprintf('Found %d expired temporary role assignments', count($expiredDocuments)));

        foreach ($expiredDocuments as $dokument) {
            $czlonek = $dokument->getCzlonek();
            if (!$czlonek) {
                continue;
            }

            $daneDodatkowe = $dokument->getDaneDodatkowe();
            if (!isset($daneDodatkowe['funkcja_tymczasowa'])) {
                continue;
            }

            $roleToRemove = $daneDodatkowe['funkcja_tymczasowa'];
            $currentRoles = $czlonek->getRoles();

            if (in_array($roleToRemove, $currentRoles)) {
                // Remove the temporary role
                $currentRoles = array_filter($currentRoles, fn($r) => $r !== $roleToRemove);
                $czlonek->setRoles(array_values($currentRoles));

                $this->entityManager->persist($czlonek);
                $removedCount++;

                $io->writeln(sprintf(
                    '  Removed role %s from %s (Document ID: %d)',
                    $roleToRemove,
                    $czlonek->getFullName(),
                    $dokument->getId()
                ));
            }
        }

        if ($removedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Successfully removed %d expired temporary roles', $removedCount));
        } else {
            $io->info('No expired temporary roles to remove');
        }

        return Command::SUCCESS;
    }
}
