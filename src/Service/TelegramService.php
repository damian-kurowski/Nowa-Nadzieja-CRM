<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelegramService
{
    private const TELEGRAM_API_URL = 'https://api.telegram.org/bot';
    private const ADMIN_USERNAME = '@dkurowsky';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $telegramBotToken
    ) {
    }

    public function sendPasswordResetRequest(array $data): bool
    {
        $message = $this->formatPasswordResetMessage($data);

        try {
            // Najpierw pobieramy chat_id dla użytkownika
            $chatId = $this->getChatIdByUsername();

            if (!$chatId) {
                $this->logger->error('Nie można znaleźć chat_id dla użytkownika ' . self::ADMIN_USERNAME);
                return false;
            }

            $response = $this->httpClient->request('POST', self::TELEGRAM_API_URL . $this->telegramBotToken . '/sendMessage', [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $this->logger->info('Wysłano prośbę o reset hasła na Telegram', [
                    'email' => $data['email'],
                    'full_name' => $data['full_name'],
                ]);
                return true;
            }

            $this->logger->error('Błąd przy wysyłaniu wiadomości na Telegram', [
                'status_code' => $statusCode,
                'response' => $response->getContent(false),
            ]);

            return false;
        } catch (\Exception $e) {
            $this->logger->error('Wyjątek podczas wysyłania na Telegram: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return false;
        }
    }

    private function getChatIdByUsername(): ?string
    {
        try {
            // Pobierz ostatnie aktualizacje (updates) z bota
            $response = $this->httpClient->request('GET', self::TELEGRAM_API_URL . $this->telegramBotToken . '/getUpdates', [
                'query' => [
                    'offset' => -1,
                    'limit' => 100,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['result']) && is_array($data['result'])) {
                // Szukamy chat_id dla naszego użytkownika
                foreach (array_reverse($data['result']) as $update) {
                    if (isset($update['message']['from']['username'])) {
                        $username = '@' . $update['message']['from']['username'];
                        if ($username === self::ADMIN_USERNAME) {
                            return (string) $update['message']['chat']['id'];
                        }
                    }
                }
            }

            // Jeśli nie znaleziono w updates, zwróć null
            // Administrator musi najpierw wysłać wiadomość do bota
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas pobierania chat_id: ' . $e->getMessage());
            return null;
        }
    }

    private function formatPasswordResetMessage(array $data): string
    {
        $timestamp = (new \DateTime())->format('Y-m-d H:i:s');

        $message = "🔑 <b>PROŚBA O RESET HASŁA</b>\n\n";
        $message .= "📧 <b>Email:</b> {$data['email']}\n";
        $message .= "👤 <b>Imię i nazwisko:</b> {$data['full_name']}\n";
        $message .= "📱 <b>Telefon:</b> {$data['phone']}\n";
        $message .= "🏢 <b>Okręg:</b> {$data['okreg']}\n";

        if (!empty($data['additional_info'])) {
            $message .= "\n💬 <b>Dodatkowe informacje:</b>\n{$data['additional_info']}\n";
        }

        $message .= "\n⏰ <b>Data zgłoszenia:</b> {$timestamp}";

        return $message;
    }
}
