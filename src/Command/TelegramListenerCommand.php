<?php

namespace App\Command;

use App\Repository\PrzekazOdbiorcaRepository;
use App\Repository\UserRepository;
use App\Repository\VerificationCodeRepository;
use App\Service\TelegramBroadcastService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[AsCommand(
    name: 'app:telegram-listener',
    description: 'Listen for Telegram bot updates (long polling for development)',
)]
class TelegramListenerCommand extends Command
{
    private array $processedUpdates = [];

    public function __construct(
        private TelegramBroadcastService $telegramService,
        private UserRepository $userRepository,
        private VerificationCodeRepository $verificationCodeRepository,
        private PrzekazOdbiorcaRepository $odbiorcaRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Long polling timeout in seconds', 30)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of updates to fetch', 100)
            ->setHelp('This command listens for Telegram updates using long polling. Press Ctrl+C to stop.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Telegram Broadcast Bot Listener');
        $io->success('Bot started. Listening for messages...');
        $io->note('Press Ctrl+C to stop');
        $io->newLine();

        $offset = 0;
        $timeout = (int) $input->getOption('timeout');
        $limit = (int) $input->getOption('limit');

        while (true) {
            try {
                $updates = $this->telegramService->getUpdates($offset, $limit);

                foreach ($updates as $update) {
                    $updateId = $update['update_id'];

                    // Prevent processing the same update twice
                    if (in_array($updateId, $this->processedUpdates)) {
                        continue;
                    }

                    $this->processedUpdates[] = $updateId;
                    $offset = $updateId + 1;

                    // Handle message
                    if (isset($update['message'])) {
                        $this->handleMessage($update['message'], $io);
                    }

                    // Handle callback query (button presses)
                    if (isset($update['callback_query'])) {
                        $this->handleCallbackQuery($update['callback_query'], $io);
                    }

                    // Keep only last 1000 processed IDs in memory
                    if (count($this->processedUpdates) > 1000) {
                        $this->processedUpdates = array_slice($this->processedUpdates, -1000);
                    }
                }

                // Small delay to prevent excessive CPU usage
                usleep(100000); // 0.1 second

            } catch (\Exception $e) {
                $io->error('Error: ' . $e->getMessage());
                sleep(5); // Wait before retrying
            }
        }

        return Command::SUCCESS;
    }

    private function handleMessage(array $message, SymfonyStyle $io): void
    {
        $chatId = (string) $message['from']['id'];
        $username = $message['from']['username'] ?? 'unknown';
        $text = $message['text'] ?? '';

        $io->writeln(sprintf(
            '[%s] <info>Message from @%s</info> (chat_id: %s): %s',
            date('H:i:s'),
            $username,
            $chatId,
            substr($text, 0, 50)
        ));

        // Handle /start command with verification code
        if (preg_match('/^\/start\s+(\d{6})$/', $text, $matches)) {
            $code = $matches[1];
            $this->handleVerificationCode($chatId, $code, $username, $io);
            return;
        }

        // Let the service handle other messages
        $this->telegramService->handleMessage($message);
    }

    private function handleVerificationCode(string $chatId, string $code, ?string $username, SymfonyStyle $io): void
    {
        $io->writeln(sprintf(
            '[%s] <comment>Verification attempt</comment>: code=%s from @%s',
            date('H:i:s'),
            $code,
            $username
        ));

        // Sprawdź czy kod istnieje w bazie danych
        $verificationCode = $this->verificationCodeRepository->findValidCode($code);

        if (!$verificationCode) {
            $this->telegramService->sendMessage(
                $chatId,
                "❌ <b>Kod nieprawidłowy lub wygasły</b>\n\n" .
                "Sprawdź czy:\n" .
                "• Kod został poprawnie przepisany\n" .
                "• Kod nie wygasł (ważny 10 minut)\n" .
                "• Używasz komendy: <code>/start {$code}</code>"
            );
            $io->error("Code not found or invalid: {$code}");
            return;
        }

        // Pobierz użytkownika
        $user = $verificationCode->getUser();

        if (!$user) {
            $io->error("User not found for code: " . $code);
            $this->telegramService->sendMessage(
                $chatId,
                "❌ Błąd systemu\n\n" .
                "Nie można znaleźć Twojego konta. Skontaktuj się z administratorem."
            );
            return;
        }

        // Połącz konto
        $user->setTelegramChatId($chatId);
        $user->setTelegramUsername($username);
        $user->setTelegramConnectedAt(new \DateTime());
        $user->setIsTelegramConnected(true);

        // Oznacz kod jako użyty
        $verificationCode->setUsed(true);
        $verificationCode->setUsedAt(new \DateTime());

        $this->entityManager->flush();

        // Wyślij wiadomość powitalną
        $message = "✅ <b>Połączono pomyślnie!</b>\n\n";
        $message .= "Witaj <b>{$user->getImie()} {$user->getNazwisko()}</b>!\n\n";
        $message .= "Twoje konto w systemie CRM zostało połączone z Telegramem.\n\n";
        $message .= "📢 Będziesz otrzymywać przekazy medialne partii.\n";
        $message .= "🔗 Możesz udostępniać je dalej wysyłając nam linki do swoich postów.\n\n";
        $message .= "<b>Dostępne komendy:</b>\n";
        $message .= "/status - sprawdź status połączenia\n";
        $message .= "/statystyki - Twoje statystyki\n";
        $message .= "/help - pomoc";

        $this->telegramService->sendMessage($chatId, $message);

        $io->success("Successfully connected user {$user->getEmail()} with Telegram @{$username}");
    }

    private function handleCallbackQuery(array $callbackQuery, SymfonyStyle $io): void
    {
        $chatId = (string) $callbackQuery['from']['id'];
        $username = $callbackQuery['from']['username'] ?? 'unknown';
        $data = $callbackQuery['data'] ?? '';

        $io->writeln(sprintf(
            '[%s] <info>Callback from @%s</info>: %s',
            date('H:i:s'),
            $username,
            $data
        ));

        // Można dodać obsługę innych callback query w przyszłości
    }
}
