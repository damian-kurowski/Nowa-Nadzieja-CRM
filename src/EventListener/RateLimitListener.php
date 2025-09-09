<?php

namespace App\EventListener;

use App\Security\RateLimitService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
class RateLimitListener
{
    public function __construct(
        private RateLimitService $rateLimitService,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Skip rate limiting for certain routes
        if ($this->shouldSkipRateLimit($request)) {
            return;
        }

        // Apply different limits based on endpoint type
        $isRateLimited = match (true) {
            $this->isSensitiveOperation($request) => $this->rateLimitService->isSensitiveOperationLimited($request),
            $this->isApiEndpoint($request) => $this->rateLimitService->isApiRateLimited($request),
            default => $this->rateLimitService->isRateLimited($request),
        };

        if ($isRateLimited) {
            $response = new JsonResponse([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => 60,
            ], 429);

            $response->headers->set('Retry-After', '60');
            $response->headers->set('X-RateLimit-Exceeded', 'true');

            $event->setResponse($response);
        }
    }

    private function shouldSkipRateLimit(\Symfony\Component\HttpFoundation\Request $request): bool
    {
        $path = $request->getPathInfo();

        // Skip for static assets, health checks and GET requests to read-only endpoints
        if ($request->getMethod() === 'GET') {
            $readOnlyPaths = ['/css/', '/js/', '/images/', '/favicon', '/health', '/ping', '/dashboard', '/czlonek/', '/kandydat/'];
            foreach ($readOnlyPaths as $skipPath) {
                if (str_contains($path, $skipPath)) {
                    return true;
                }
            }
        }

        // Always skip static assets
        $staticPaths = ['/css/', '/js/', '/images/', '/favicon', '/build/'];
        foreach ($staticPaths as $skipPath) {
            if (str_contains($path, $skipPath)) {
                return true;
            }
        }

        return false;
    }

    private function isApiEndpoint(\Symfony\Component\HttpFoundation\Request $request): bool
    {
        return str_contains($request->getPathInfo(), '/api/')
               || str_contains($request->getPathInfo(), '/ajax/');
    }

    private function isSensitiveOperation(\Symfony\Component\HttpFoundation\Request $request): bool
    {
        $path = $request->getPathInfo();

        // Sensitive operations that need stricter limits
        $sensitivePatterns = [
            '/zebranie-oddzialu/.*/api/appoint',
            '/zebranie-oddzialu/.*/api/dismiss',
            '/zebranie-oddzialu/.*/zakoncz',
            '/dokument/.*/sign',
            '/dokument/.*/reject',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match('#'.str_replace('.*', '[^/]+', $pattern).'#', $path)) {
                return true;
            }
        }

        return false;
    }
}
