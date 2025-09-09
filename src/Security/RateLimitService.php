<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RateLimitService
{
    private const DEFAULT_LIMIT = 60; // requests per minute
    private const STRICT_LIMIT = 30;  // for sensitive operations
    private const WINDOW = 60;        // time window in seconds

    public function __construct(
        private CacheInterface $cache,
        private TokenStorageInterface $tokenStorage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Check if request should be rate limited.
     */
    public function isRateLimited(Request $request, int $limit = self::DEFAULT_LIMIT): bool
    {
        $key = $this->generateRateLimitKey($request);
        $currentCount = $this->getCurrentCount($key);

        if ($currentCount >= $limit) {
            $this->logRateLimitExceeded($request, $currentCount, $limit);

            return true;
        }

        $this->incrementCounter($key);

        return false;
    }

    /**
     * Rate limit for API endpoints (stricter).
     */
    public function isApiRateLimited(Request $request): bool
    {
        return $this->isRateLimited($request, self::STRICT_LIMIT);
    }

    /**
     * Rate limit for sensitive operations (very strict).
     */
    public function isSensitiveOperationLimited(Request $request): bool
    {
        $key = $this->generateRateLimitKey($request, 'sensitive');
        $currentCount = $this->getCurrentCount($key);

        // Only 10 sensitive operations per minute
        if ($currentCount >= 10) {
            $this->logRateLimitExceeded($request, $currentCount, 10, 'sensitive');

            return true;
        }

        $this->incrementCounter($key);

        return false;
    }

    /**
     * Get remaining requests for user.
     */
    public function getRemainingRequests(Request $request, int $limit = self::DEFAULT_LIMIT): int
    {
        $key = $this->generateRateLimitKey($request);
        $currentCount = $this->getCurrentCount($key);

        return max(0, $limit - $currentCount);
    }

    /**
     * Create rate limit response headers.
     */
    public function addRateLimitHeaders(Response $response, Request $request, int $limit = self::DEFAULT_LIMIT): Response
    {
        $remaining = $this->getRemainingRequests($request, $limit);
        $resetTime = time() + self::WINDOW;

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);

        return $response;
    }

    /**
     * Generate unique key for rate limiting.
     */
    private function generateRateLimitKey(Request $request, string $prefix = 'api'): string
    {
        $user = $this->getCurrentUser();
        /** @var \App\Entity\User|null $userEntity */
        $userEntity = $user;
        $userId = $userEntity ? $userEntity->getId() : 'anonymous';
        $ip = $request->getClientIp() ?? 'unknown';
        $endpoint = $request->getPathInfo();

        return sprintf(
            'rate_limit_%s_%s_%s_%s_%d',
            $prefix,
            $userId,
            md5($ip),
            md5($endpoint),
            floor(time() / self::WINDOW)
        );
    }

    /**
     * Get current request count for key.
     */
    private function getCurrentCount(string $key): int
    {
        return $this->cache->get($key, function (ItemInterface $item): int {
            $item->expiresAfter(self::WINDOW);

            return 0;
        });
    }

    /**
     * Increment request counter.
     */
    private function incrementCounter(string $key): void
    {
        $currentCount = $this->getCurrentCount($key);

        $this->cache->get($key, function (ItemInterface $item) use ($currentCount): int {
            $item->expiresAfter(self::WINDOW);

            return $currentCount + 1;
        });
    }

    /**
     * Log rate limit exceeded events.
     */
    private function logRateLimitExceeded(Request $request, int $currentCount, int $limit, string $type = 'api'): void
    {
        $user = $this->getCurrentUser();
        /** @var \App\Entity\User|null $userEntity */
        $userEntity = $user;

        $this->logger->warning('Rate limit exceeded', [
            'type' => $type,
            'user_id' => $userEntity ? $userEntity->getId() : null,
            'user_name' => $userEntity ? $userEntity->getFullName() : 'Anonymous',
            'ip' => $request->getClientIp(),
            'endpoint' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'current_count' => $currentCount,
            'limit' => $limit,
            'user_agent' => $request->headers->get('User-Agent'),
            'timestamp' => date('c'),
        ]);
    }

    private function getCurrentUser(): ?\Symfony\Component\Security\Core\User\UserInterface
    {
        $token = $this->tokenStorage->getToken();

        return $token ? $token->getUser() : null;
    }
}
