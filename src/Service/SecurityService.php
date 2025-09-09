<?php

namespace App\Service;

use App\Entity\LoginAttempt;
use App\Repository\LoginAttemptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class SecurityService
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const RATE_LIMIT_WINDOW = 15; // minutes
    private const BLOCK_DURATION = 30; // minutes

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoginAttemptRepository $loginAttemptRepository,
    ) {
    }

    /**
     * Check if request is from a bot or scraper.
     *
     * @return array<string, mixed>
     */
    public function isSuspiciousBot(Request $request): array
    {
        $userAgent = $request->headers->get('User-Agent', '');
        $ipAddress = $request->getClientIp() ?? 'unknown';

        // Known bot patterns
        $botPatterns = [
            '/bot/i', '/crawler/i', '/spider/i', '/scraper/i',
            '/wget/i', '/curl/i', '/python/i', '/ruby/i',
            '/perl/i', '/java/i', '/go-http/i', '/axios/i',
            '/requests/i', '/http/i', '/scraping/i', '/harvest/i',
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return [
                    'is_bot' => true,
                    'reason' => 'bot_user_agent',
                    'pattern' => $pattern,
                    'user_agent' => $userAgent,
                ];
            }
        }

        // Suspicious user agent patterns
        if (empty($userAgent) || strlen($userAgent) < 10) {
            return [
                'is_bot' => true,
                'reason' => 'missing_or_short_user_agent',
                'user_agent' => $userAgent,
            ];
        }

        // Check for rapid requests (simple rate limiting)
        $requestsInLastMinute = $this->loginAttemptRepository->countRecentAttempts($ipAddress, 1);
        if ($requestsInLastMinute > 60) { // More than 60 requests per minute
            return [
                'is_bot' => true,
                'reason' => 'too_many_requests',
                'requests_per_minute' => $requestsInLastMinute,
            ];
        }

        // Check for missing common browser headers
        $suspiciousHeaders = [];
        if (!$request->headers->has('Accept')) {
            $suspiciousHeaders[] = 'missing_accept';
        }
        if (!$request->headers->has('Accept-Language')) {
            $suspiciousHeaders[] = 'missing_accept_language';
        }
        if (!$request->headers->has('Accept-Encoding')) {
            $suspiciousHeaders[] = 'missing_accept_encoding';
        }

        if (count($suspiciousHeaders) >= 2) {
            return [
                'is_bot' => true,
                'reason' => 'missing_browser_headers',
                'missing_headers' => $suspiciousHeaders,
            ];
        }

        return ['is_bot' => false];
    }

    /**
     * Check if IP is allowed to attempt login.
     */
    /**
     * @return array<string, mixed>
     */
    public function canAttemptLogin(string $ipAddress, ?string $email = null): array
    {
        // Skip rate limiting for localhost in development
        if (in_array($ipAddress, ['127.0.0.1', '::1', 'localhost'])
            && 'dev' === $_ENV['APP_ENV']) {
            return [
                'allowed' => true,
                'reason' => 'dev_localhost_bypass',
                'message' => 'Development localhost - rate limiting bypassed',
            ];
        }

        // Check if IP is currently blocked
        if ($this->loginAttemptRepository->isIpBlocked($ipAddress)) {
            return [
                'allowed' => false,
                'reason' => 'ip_blocked',
                'message' => 'Adres IP jest tymczasowo zablokowany z powodu zbyt wielu nieudanych prób logowania',
            ];
        }

        // Check recent failed attempts for IP
        $recentIpFailures = $this->loginAttemptRepository->countRecentFailedAttempts(
            $ipAddress,
            self::RATE_LIMIT_WINDOW
        );

        if ($recentIpFailures >= self::MAX_LOGIN_ATTEMPTS) {
            // Block the IP
            $this->blockIpAddress($ipAddress, 'too_many_attempts');

            return [
                'allowed' => false,
                'reason' => 'rate_limited',
                'message' => sprintf('Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za %d minut.', self::BLOCK_DURATION),
                'attempts' => $recentIpFailures,
                'max_attempts' => self::MAX_LOGIN_ATTEMPTS,
            ];
        }

        return [
            'allowed' => true,
            'attempts_remaining' => self::MAX_LOGIN_ATTEMPTS - $recentIpFailures,
            'recent_failures' => $recentIpFailures,
        ];
    }

    /**
     * Record login attempt.
     */
    public function recordLoginAttempt(string $ipAddress, ?string $email, bool $success, ?string $failureReason = null): void
    {
        $attempt = new LoginAttempt();
        $attempt->setIpAddress($ipAddress)
                ->setEmail($email)
                ->setStatus($success ? 'success' : 'failed')
                ->setFailureReason($failureReason);

        $this->entityManager->persist($attempt);
        $this->entityManager->flush();
    }

    /**
     * Block IP address.
     */
    public function blockIpAddress(string $ipAddress, string $reason): void
    {
        $attempt = new LoginAttempt();
        $attempt->setIpAddress($ipAddress)
                ->setStatus('blocked')
                ->setFailureReason($reason);

        $this->entityManager->persist($attempt);
        $this->entityManager->flush();
    }

    /**
     * Check if IP is from Poland (geoblocking).
     */
    /**
     * @return array<string, mixed>
     */
    public function checkGeolocation(string $ipAddress): array
    {
        // Skip check for localhost/private IPs
        if ($this->isLocalOrPrivateIp($ipAddress)) {
            return ['allowed' => true, 'country' => 'localhost'];
        }

        $country = $this->getCountryFromIp($ipAddress);

        // Allow only Polish IPs
        if ('PL' !== $country) {
            return [
                'allowed' => false,
                'reason' => 'geoblocked',
                'message' => 'Access is restricted to Poland only',
                'country' => $country,
            ];
        }

        return ['allowed' => true, 'country' => $country];
    }

    /**
     * Get client IP address (handle proxies).
     */
    public function getClientIp(Request $request): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR',                // Standard
        ];

        foreach ($ipKeys as $key) {
            if ($request->server->has($key)) {
                $ip = $request->server->get($key);
                if ($ip) {
                    // Handle comma-separated IPs (take first one)
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);

                    // Validate IP
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }

        return $request->getClientIp() ?? '0.0.0.0';
    }

    /**
     * Get country from IP using free API.
     */
    private function getCountryFromIp(string $ipAddress): string
    {
        if ($this->isLocalOrPrivateIp($ipAddress)) {
            return 'localhost';
        }

        try {
            // Use ip-api.com (free, 1000 requests/month) with HTTPS
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'user_agent' => 'Nowa Nadzieja CRM Security Check',
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'cafile' => null, // Use system default CA bundle
                ],
            ]);

            // Try HTTPS first, fallback to HTTP if needed
            $response = file_get_contents("https://ip-api.com/json/{$ipAddress}?fields=countryCode", false, $context);

            // If HTTPS fails, try HTTP as fallback
            if (false === $response) {
                $response = file_get_contents("http://ip-api.com/json/{$ipAddress}?fields=countryCode", false, $context);
            }

            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['countryCode'])) {
                    return $data['countryCode'];
                }
            }
        } catch (\Exception $e) {
            // Log error but don't block - fail open for geolocation
        }

        // Default to unknown if API fails
        return 'UNKNOWN';
    }

    /**
     * Check if IP is localhost or private.
     */
    private function isLocalOrPrivateIp(string $ipAddress): bool
    {
        return in_array($ipAddress, ['127.0.0.1', '::1', '0.0.0.0'])
               || !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Clean old login attempts (should be called via cron).
     */
    public function cleanOldAttempts(): int
    {
        return $this->loginAttemptRepository->cleanOldAttempts(30);
    }

    /**
     * Get login statistics for dashboard.
     */
    /**
     * @return array<string, mixed>
     */
    public function getLoginStats(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        // Get stats for last 24 hours
        $since = new \DateTime('-24 hours');

        $result = $qb->select([
            'COUNT(l.id) as total_attempts',
            'SUM(CASE WHEN l.status = \'success\' THEN 1 ELSE 0 END) as successful',
            'SUM(CASE WHEN l.status = \'failed\' THEN 1 ELSE 0 END) as failed',
            'SUM(CASE WHEN l.status = \'blocked\' THEN 1 ELSE 0 END) as blocked',
            'COUNT(DISTINCT l.ipAddress) as unique_ips',
        ])
            ->from(LoginAttempt::class, 'l')
            ->where('l.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleResult();

        return [
            'total_attempts' => (int) $result['total_attempts'],
            'successful' => (int) $result['successful'],
            'failed' => (int) $result['failed'],
            'blocked' => (int) $result['blocked'],
            'unique_ips' => (int) $result['unique_ips'],
            'success_rate' => $result['total_attempts'] > 0
                ? round(($result['successful'] / $result['total_attempts']) * 100, 1)
                : 0,
        ];
    }
}
