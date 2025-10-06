<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:reset-all-passwords',
    description: 'Reset all user passwords to admin123 (DEVELOPMENT ONLY)',
)]
class ResetAllPasswordsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force password reset without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Safety check - confirm action (unless --force is used)
        if (!$input->getOption('force')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'UWAGA! Ta komenda zmieni hasła WSZYSTKICH użytkowników na "admin123". Kontynuować? (tak/nie) [nie]: ',
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                $io->info('Operacja anulowana.');
                return Command::SUCCESS;
            }
        }

        $io->title('Resetowanie haseł wszystkich użytkowników');
        $io->warning('To narzędzie jest przeznaczone TYLKO dla środowiska deweloperskiego!');

        // Get all users
        $users = $this->userRepository->findAll();
        $io->writeln(sprintf('Znaleziono %d użytkowników', count($users)));

        $progressBar = $io->createProgressBar(count($users));
        $progressBar->start();

        $resetCount = 0;
        foreach ($users as $user) {
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'admin123');
            $user->setPassword($hashedPassword);

            // Force password change on next login
            $user->setIsPasswordChangeRequired(true);

            $this->entityManager->persist($user);
            $resetCount++;

            $progressBar->advance();
        }

        // Flush all changes
        $this->entityManager->flush();
        $progressBar->finish();

        $io->newLine(2);
        $io->success(sprintf('Pomyślnie zresetowano hasła dla %d użytkowników', $resetCount));
        $io->note('Nowe hasło dla wszystkich: admin123');
        $io->note('Wszyscy użytkownicy będą musieli zmienić hasło przy następnym logowaniu');

        return Command::SUCCESS;
    }
}
