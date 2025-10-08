<?php

namespace App\Service;

use App\Entity\PrzekazMedialny;
use App\Entity\PrzekazOdbiorca;
use App\Entity\PrzekazOdpowiedz;
use App\Entity\User;
use App\Repository\PrzekazMedialnyRepository;
use App\Repository\PrzekazOdbiorcaRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelegramBroadcastService
{
    private const TELEGRAM_API_URL = 'https://api.telegram.org/bot';
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRY_MINUTES = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private PrzekazMedialnyRepository $przekazRepository,
        private PrzekazOdbiorcaRepository $odbiorcaRepository,
        private string $telegramBroadcastBotToken
    ) {
    }

    /**
     * Generuje 6-cyfrowy kod weryfikacyjny
     */
    public function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Sprawdza czy kod jest prawidłowy i nie wygasł
     */
    public function validateCode(string $code, array $sessionData): bool
    {
        if (!isset($sessionData['code']) || !isset($sessionData['expires_at'])) {
            return false;
        }

        if ($sessionData['code'] !== $code) {
            return false;
        }

        $expiresAt = new \DateTime($sessionData['expires_at']);
        $now = new \DateTime();

        if ($now > $expiresAt) {
            return false;
        }

        return true;
    }

    /**
     * Połącz konto użytkownika z Telegramem
     */
    public function connectUserTelegram(User $user, string $chatId, ?string $username = null): bool
    {
        try {
            $user->setTelegramChatId($chatId);
            $user->setTelegramUsername($username);
            $user->setTelegramConnectedAt(new \DateTime());
            $user->setIsTelegramConnected(true);

            $this->entityManager->flush();

            $this->logger->info('Połączono konto z Telegramem', [
                'user_id' => $user->getId(),
                'telegram_chat_id' => $chatId,
                'telegram_username' => $username,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas łączenia konta z Telegramem: ' . $e->getMessage(), [
                'user_id' => $user->getId(),
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Wyślij wiadomość powitalną po połączeniu konta
     */
    public function sendWelcomeMessage(string $chatId, User $user): bool
    {
        $message = "✅ <b>Połączono pomyślnie!</b>\n\n";
        $message .= "Witaj <b>{$user->getImie()} {$user->getNazwisko()}</b>!\n\n";
        $message .= "Twoje konto w systemie CRM zostało połączone z Telegramem.\n\n";
        $message .= "📢 Będziesz otrzymywać przekazy medialne partii.\n";
        $message .= "🔗 Możesz udostępniać je dalej wysyłając nam linki do swoich postów.\n\n";
        $message .= "<b>Dostępne komendy:</b>\n";
        $message .= "/status - sprawdź status połączenia\n";
        $message .= "/statystyki - Twoje statystyki\n";
        $message .= "/help - pomoc";

        return $this->sendMessage($chatId, $message);
    }

    /**
     * Wyślij wiadomość do użytkownika
     */
    public function sendMessage(string $chatId, string $text, ?array $keyboard = null): bool
    {
        $result = $this->sendMessageToUser($chatId, $text);
        return $result['ok'] ?? false;
    }

    /**
     * Wyślij wiadomość do użytkownika (pełna odpowiedź API)
     */
    public function sendMessageToUser(string $chatId, string $text, ?int $przekazId = null): array
    {
        try {
            $data = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            $response = $this->httpClient->request('POST',
                self::TELEGRAM_API_URL . $this->telegramBroadcastBotToken . '/sendMessage',
                ['json' => $data]
            );

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode === 200 && isset($content['ok']) && $content['ok']) {
                return $content;
            }

            $this->logger->error('Błąd przy wysyłaniu wiadomości Telegram', [
                'status_code' => $statusCode,
                'response' => $content,
            ]);

            return ['ok' => false, 'description' => $content['description'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            $this->logger->error('Wyjątek podczas wysyłania wiadomości Telegram: ' . $e->getMessage(), [
                'exception' => $e,
                'chat_id' => $chatId,
            ]);
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Wyślij media (zdjęcie lub wideo) do użytkownika
     */
    private function sendMediaToUser(string $chatId, string $mediaPath, string $caption, string $mediaType): array
    {
        try {
            $formFields = [
                'chat_id' => $chatId,
                'caption' => $caption,
                'parse_mode' => 'HTML',
                $mediaType => DataPart::fromPath($mediaPath),
            ];

            $formData = new FormDataPart($formFields);
            $endpoint = $mediaType === 'photo' ? '/sendPhoto' : '/sendVideo';

            $response = $this->httpClient->request('POST',
                self::TELEGRAM_API_URL . $this->telegramBroadcastBotToken . $endpoint,
                [
                    'headers' => $formData->getPreparedHeaders()->toArray(),
                    'body' => $formData->bodyToIterable(),
                ]
            );

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode === 200 && isset($content['ok']) && $content['ok']) {
                return $content;
            }

            $this->logger->error("Błąd przy wysyłaniu {$mediaType} Telegram", [
                'status_code' => $statusCode,
                'response' => $content,
                'media_type' => $mediaType,
            ]);

            return ['ok' => false, 'description' => $content['description'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            $this->logger->error("Wyjątek podczas wysyłania {$mediaType} Telegram: " . $e->getMessage(), [
                'exception' => $e,
                'chat_id' => $chatId,
                'media_type' => $mediaType,
            ]);
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Wyślij zdjęcie do użytkownika
     */
    public function sendPhotoToUser(string $chatId, string $photoPath, string $caption): array
    {
        return $this->sendMediaToUser($chatId, $photoPath, $caption, 'photo');
    }

    /**
     * Wyślij wideo do użytkownika
     */
    public function sendVideoToUser(string $chatId, string $videoPath, string $caption): array
    {
        return $this->sendMediaToUser($chatId, $videoPath, $caption, 'video');
    }

    /**
     * Pobierz aktualizacje z bota (dla polling mode)
     */
    public function getUpdates(int $offset = 0, int $limit = 100): array
    {
        try {
            $response = $this->httpClient->request('GET',
                self::TELEGRAM_API_URL . $this->telegramBroadcastBotToken . '/getUpdates',
                [
                    'query' => [
                        'offset' => $offset,
                        'limit' => $limit,
                        'timeout' => 30,
                    ],
                ]
            );

            $data = $response->toArray();

            if (isset($data['result']) && is_array($data['result'])) {
                return $data['result'];
            }

            return [];
        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas pobierania updates: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obsłuż wiadomość od użytkownika
     */
    public function handleMessage(array $message): void
    {
        if (!isset($message['from']['id']) || !isset($message['text'])) {
            return;
        }

        $chatId = (string) $message['from']['id'];
        $text = trim($message['text']);
        $username = $message['from']['username'] ?? null;

        // Komenda /start z kodem
        if (str_starts_with($text, '/start ')) {
            $code = trim(substr($text, 7));
            $this->handleStartCommand($chatId, $code, $username);
            return;
        }

        // Komenda /start bez kodu
        if ($text === '/start') {
            $this->sendMessage($chatId,
                "👋 Witaj!\n\n" .
                "Aby połączyć swoje konto, użyj kodu weryfikacyjnego z systemu CRM:\n\n" .
                "<code>/start TWOJ_KOD</code>\n\n" .
                "Kod znajdziesz w systemie podczas pierwszego logowania."
            );
            return;
        }

        // Komenda /status
        if ($text === '/status') {
            $this->handleStatusCommand($chatId);
            return;
        }

        // Komenda /help
        if ($text === '/help') {
            $this->handleHelpCommand($chatId);
            return;
        }

        // Komenda /statystyki
        if ($text === '/statystyki') {
            $this->handleStatisticsCommand($chatId);
            return;
        }

        // Sprawdź czy to link (odpowiedź na przekaz)
        if ($this->isUrl($text)) {
            $this->handleLinkResponse($chatId, $text);
            return;
        }

        // Nierozpoznana wiadomość
        $this->sendMessage($chatId,
            "Nie rozumiem tej komendy 🤔\n\n" .
            "Użyj /help aby zobaczyć dostępne komendy."
        );
    }

    /**
     * Obsłuż komendę /start z kodem
     */
    private function handleStartCommand(string $chatId, string $code, ?string $username): void
    {
        $this->logger->info('Otrzymano komendę /start z kodem', [
            'chat_id' => $chatId,
            'code' => $code,
            'username' => $username,
        ]);

        // Sprawdź czy kod istnieje w plikach tymczasowych
        $codesDir = __DIR__ . '/../../var/telegram_codes';
        $codeFile = $codesDir . '/' . $code . '.json';

        if (!file_exists($codeFile)) {
            $this->sendMessage($chatId,
                "❌ <b>Kod nieprawidłowy lub wygasły</b>\n\n" .
                "Sprawdź czy:\n" .
                "• Kod został poprawnie przepisany\n" .
                "• Kod nie wygasł (ważny 10 minut)\n" .
                "• Używasz komendy: <code>/start {$code}</code>"
            );
            return;
        }

        // Odczytaj dane z pliku
        $codeData = json_decode(file_get_contents($codeFile), true);

        // Sprawdź czy kod nie wygasł
        $expiresAt = new \DateTime($codeData['expires_at']);
        $now = new \DateTime();

        if ($now > $expiresAt) {
            unlink($codeFile); // Usuń wygasły kod
            $this->sendMessage($chatId,
                "⏱ <b>Kod wygasł</b>\n\n" .
                "Kod weryfikacyjny jest ważny tylko 10 minut.\n" .
                "Odśwież stronę w systemie aby wygenerować nowy kod."
            );
            return;
        }

        // Pobierz użytkownika
        $user = $this->userRepository->find($codeData['user_id']);

        if (!$user) {
            $this->logger->error('Nie znaleziono użytkownika dla kodu', [
                'user_id' => $codeData['user_id'],
                'code' => $code,
            ]);
            unlink($codeFile);
            $this->sendMessage($chatId,
                "❌ Błąd systemu\n\n" .
                "Nie można znaleźć Twojego konta. Skontaktuj się z administratorem."
            );
            return;
        }

        // Połącz konto
        if ($this->connectUserTelegram($user, $chatId, $username)) {
            unlink($codeFile); // Usuń użyty kod
            $this->sendWelcomeMessage($chatId, $user);
        } else {
            $this->sendMessage($chatId,
                "❌ Błąd podczas łączenia konta\n\n" .
                "Spróbuj ponownie za chwilę lub skontaktuj się z administratorem."
            );
        }
    }

    /**
     * Obsłuż komendę /status
     */
    private function handleStatusCommand(string $chatId): void
    {
        $user = $this->userRepository->findOneBy(['telegramChatId' => $chatId]);

        if (!$user) {
            $this->sendMessage($chatId,
                "❌ Twoje konto nie jest połączone.\n\n" .
                "Użyj kodu z systemu CRM:\n" .
                "<code>/start TWOJ_KOD</code>"
            );
            return;
        }

        $connectedAt = $user->getTelegramConnectedAt();
        $connectedDate = $connectedAt ? $connectedAt->format('Y-m-d H:i') : 'nieznana';

        $this->sendMessage($chatId,
            "✅ <b>Konto połączone</b>\n\n" .
            "👤 <b>Użytkownik:</b> {$user->getImie()} {$user->getNazwisko()}\n" .
            "📧 <b>Email:</b> {$user->getEmail()}\n" .
            "📅 <b>Połączono:</b> {$connectedDate}\n\n" .
            "📊 Użyj /statystyki aby zobaczyć szczegóły"
        );
    }

    /**
     * Obsłuż komendę /help
     */
    private function handleHelpCommand(string $chatId): void
    {
        $this->sendMessage($chatId,
            "📖 <b>Pomoc - Przekazy Medialne</b>\n\n" .
            "<b>Dostępne komendy:</b>\n" .
            "/start KOD - połącz konto z systemem\n" .
            "/status - sprawdź status połączenia\n" .
            "/statystyki - Twoje statystyki przekazów\n" .
            "/help - ta wiadomość\n\n" .
            "<b>Jak to działa?</b>\n" .
            "1️⃣ Otrzymujesz przekaz medialny od partii\n" .
            "2️⃣ Udostępniasz go na swoich social media\n" .
            "3️⃣ Wysyłasz nam link do swojego posta\n" .
            "4️⃣ System liczy Twoje zaangażowanie\n\n" .
            "<b>Przykład linku:</b>\n" .
            "https://twitter.com/twoj_post\n" .
            "https://facebook.com/twoj_post"
        );
    }

    /**
     * Obsłuż komendę /statystyki
     */
    private function handleStatisticsCommand(string $chatId): void
    {
        $user = $this->userRepository->findOneBy(['telegramChatId' => $chatId]);

        if (!$user) {
            $this->sendMessage($chatId, "❌ Twoje konto nie jest połączone.");
            return;
        }

        // TODO: Implementacja po dodaniu encji PrzekazMedialny
        $this->sendMessage($chatId,
            "📊 <b>Twoje statystyki</b>\n\n" .
            "👤 {$user->getImie()} {$user->getNazwisko()}\n\n" .
            "📨 Otrzymane przekazy: 0\n" .
            "👁 Przeczytane: 0\n" .
            "🔗 Udostępnione: 0\n\n" .
            "Statystyki będą dostępne po pełnej implementacji systemu."
        );
    }

    /**
     * Obsłuż odpowiedź z linkiem
     */
    private function handleLinkResponse(string $chatId, string $url): void
    {
        $user = $this->userRepository->findOneBy(['telegramChatId' => $chatId]);

        if (!$user) {
            $this->sendMessage($chatId, "❌ Twoje konto nie jest połączone.");
            return;
        }

        // Znajdź ostatni przekaz wysłany do użytkownika
        $ostatniOdbiorca = $this->odbiorcaRepository->createQueryBuilder('po')
            ->andWhere('po.odbiorca = :user')
            ->andWhere('po.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'sent')
            ->orderBy('po.dataWyslania', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$ostatniOdbiorca) {
            $this->sendMessage($chatId,
                "❌ Nie znaleziono ostatnio wysłanego przekazu.\n\n" .
                "Linki możesz wysyłać tylko w odpowiedzi na otrzymane przekazy."
            );
            return;
        }

        $przekaz = $ostatniOdbiorca->getPrzekazMedialny();

        // Utwórz odpowiedź
        $odpowiedz = new PrzekazOdpowiedz();
        $odpowiedz->setPrzekazMedialny($przekaz);
        $odpowiedz->setOdbiorca($user);
        $odpowiedz->setLinkUrl($url);
        $odpowiedz->detectTypFromUrl(); // Automatyczna detekcja platformy
        $odpowiedz->setZweryfikowany(false);

        $this->entityManager->persist($odpowiedz);

        // Aktualizuj licznik odpowiedzi
        $przekaz->setLiczbaOdpowiedzi($przekaz->getLiczbaOdpowiedzi() + 1);

        $this->entityManager->flush();

        $platform = $odpowiedz->getTypNazwa();

        $this->sendMessage($chatId,
            "✅ <b>Link zapisany!</b>\n\n" .
            "🔗 Platforma: {$platform}\n" .
            "📎 Link: <a href=\"{$url}\">Zobacz post</a>\n\n" .
            "Dziękujemy za udostępnienie przekazu! 🎉\n\n" .
            "Twoja odpowiedź zostanie zweryfikowana przez koordynatora."
        );
    }

    /**
     * Sprawdź czy tekst to URL
     */
    private function isUrl(string $text): bool
    {
        return filter_var($text, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Wykryj platformę social media z URL
     */
    private function detectPlatform(string $url): string
    {
        if (str_contains($url, 'twitter.com') || str_contains($url, 'x.com')) {
            return 'Twitter/X';
        }
        if (str_contains($url, 'facebook.com') || str_contains($url, 'fb.com')) {
            return 'Facebook';
        }
        if (str_contains($url, 'instagram.com')) {
            return 'Instagram';
        }
        if (str_contains($url, 'linkedin.com')) {
            return 'LinkedIn';
        }
        if (str_contains($url, 'tiktok.com')) {
            return 'TikTok';
        }
        return 'Inna platforma';
    }

    /**
     * Wyślij broadcast do wszystkich połączonych użytkowników
     */
    public function sendBroadcast(string $message, ?array $keyboard = null): array
    {
        $users = $this->userRepository->findBy(['isTelegramConnected' => true]);
        $results = [
            'sent' => 0,
            'failed' => 0,
            'total' => count($users),
        ];

        foreach ($users as $user) {
            $chatId = $user->getTelegramChatId();
            if (!$chatId) {
                continue;
            }

            $sent = $this->sendMessage($chatId, $message, $keyboard);

            if ($sent) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }

            // Rate limiting - max 30 msg/s
            usleep(35000); // 35ms delay = ~28 msg/s
        }

        $this->logger->info('Broadcast wysłany', $results);

        return $results;
    }
}
