<?php

namespace App\Command;

use App\Entity\PostepKandydata;
use App\Entity\User;
use App\Repository\PostepKandydataRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-postep-kandydata',
    description: 'Initialize PostepKandydata records for all candidates who don\'t have them',
)]
class InitializePostepKandydataCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private PostepKandydataRepository $postepRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without making changes')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force creation even if records exist')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        $io->title('Initializing PostepKandydata Records');

        // Get all candidates
        $candidates = $this->userRepository->findBy(['typUzytkownika' => 'kandydat']);
        $io->info(sprintf('Found %d candidates in the system', count($candidates)));

        $created = 0;
        $skipped = 0;
        $errors = 0;

        $io->progressStart(count($candidates));

        foreach ($candidates as $candidate) {
            try {
                // Check if record already exists
                $existingPostep = $this->postepRepository->findOneBy(['kandydat' => $candidate]);

                if ($existingPostep && !$force) {
                    $skipped++;
                    $io->progressAdvance();
                    continue;
                }

                if ($existingPostep && $force) {
                    // Remove existing record if forcing
                    $this->entityManager->remove($existingPostep);
                    $this->entityManager->flush();
                }

                if (!$dryRun) {
                    // Create new PostepKandydata record
                    $postep = new PostepKandydata();
                    $postep->setKandydat($candidate);
                    $postep->setDataRozpoczecia(new \DateTime());
                    $postep->setAktualnyEtap(1);

                    // Automatically check some steps based on existing data
                    $this->autoCheckSteps($postep, $candidate);

                    $this->entityManager->persist($postep);

                    // Flush every 50 records to avoid memory issues
                    if ($created % 50 === 0) {
                        $this->entityManager->flush();
                    }
                }

                $created++;
                $io->progressAdvance();

            } catch (\Exception $e) {
                $errors++;
                $io->error(sprintf('Error processing candidate %s: %s',
                    $candidate->getFullName(),
                    $e->getMessage()
                ));
            }
        }

        // Final flush
        if (!$dryRun && $created > 0) {
            $this->entityManager->flush();
        }

        $io->progressFinish();

        $io->success(sprintf(
            'Process completed: %d records created, %d skipped, %d errors%s',
            $created,
            $skipped,
            $errors,
            $dryRun ? ' (DRY RUN - no changes made)' : ''
        ));

        return Command::SUCCESS;
    }

    /**
     * Automatically check steps based on existing candidate data
     */
    private function autoCheckSteps(PostepKandydata $postep, User $candidate): void
    {
        // Check if candidate has uploaded photo
        if ($candidate->getZdjecie()) {
            $postep->setKrok2WgranieZdjecia(true);
            $postep->setKrok2DataOdznaczenia(new \DateTime());
        }

        // Check if candidate has CV (if there's a CV field)
        if (method_exists($candidate, 'getCv') && $candidate->getCv()) {
            $postep->setKrok3WgranieCv(true);
            $postep->setKrok3DataOdznaczenia(new \DateTime());
        }

        // Check if profile is complete
        if ($this->isProfileComplete($candidate)) {
            $postep->setKrok4UzupelnienieProfilu(true);
            $postep->setKrok4DataOdznaczenia(new \DateTime());
        }

        // Update actual stage based on completed steps
        $completedSteps = 0;
        if ($postep->isKrok1OplacenieSkladki()) $completedSteps = 1;
        if ($postep->isKrok2WgranieZdjecia()) $completedSteps = 2;
        if ($postep->isKrok3WgranieCv()) $completedSteps = 3;
        if ($postep->isKrok4UzupelnienieProfilu()) $completedSteps = 4;
        if ($postep->isKrok5RozmowaPrekwalifikacyjna()) $completedSteps = 5;
        if ($postep->isKrok6OpiniaRadyOddzialu()) $completedSteps = 6;
        if ($postep->isKrok7UdzialWZebraniach()) $completedSteps = 7;
        if ($postep->isKrok8Decyzja()) $completedSteps = 8;

        $postep->setAktualnyEtap(max(1, $completedSteps));
    }

    /**
     * Check if user profile is complete
     */
    private function isProfileComplete(User $user): bool
    {
        // Check required fields
        $requiredFields = [
            $user->getImie(),
            $user->getNazwisko(),
            $user->getEmail(),
            $user->getTelefon(),
            $user->getAdresZamieszkania(),
        ];

        foreach ($requiredFields as $field) {
            if (empty($field)) {
                return false;
            }
        }

        return true;
    }
}