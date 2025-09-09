<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds caching headers to API responses for better performance
 */
class CacheResponseListener implements EventSubscriberInterface
{
    private array $cacheableRoutes = [
        'czlonek_index' => 300,        // 5 minutes
        'czlonek_show' => 600,         // 10 minutes
        'kandydat_index' => 300,       // 5 minutes
        'sympatyk_index' => 300,       // 5 minutes
        'faktura_index' => 180,        // 3 minutes
        'dokument_index' => 180,       // 3 minutes
        'dashboard' => 60,             // 1 minute
        'mlodziezowka_index' => 600,   // 10 minutes
        'bylo_czlonek_index' => 900,   // 15 minutes
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Only apply to master requests
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $route = $request->attributes->get('_route');

        // Skip if not a GET request
        if ($request->getMethod() !== 'GET') {
            return;
        }

        // Apply cache headers for cacheable routes
        if (isset($this->cacheableRoutes[$route])) {
            $maxAge = $this->cacheableRoutes[$route];
            
            // Set cache headers
            $response->setSharedMaxAge($maxAge);
            $response->headers->addCacheControlDirective('must-revalidate');
            
            // Add ETag for validation
            $etag = md5($response->getContent());
            $response->setEtag($etag);
            
            // Check if client has valid cached version
            $response->isNotModified($request);
            
            // Add Vary header for proper caching with different representations
            $response->setVary(['Accept', 'Accept-Encoding', 'X-Requested-With']);
        }

        // For AJAX/API requests, add specific cache headers
        if ($request->isXmlHttpRequest() || str_starts_with($route ?? '', 'api_')) {
            $this->handleApiResponse($response, $route);
        }

        // Add performance timing header
        if ($startTime = $request->server->get('REQUEST_TIME_FLOAT')) {
            $duration = (microtime(true) - $startTime) * 1000;
            $response->headers->set('X-Response-Time', sprintf('%.2fms', $duration));
        }
    }

    private function handleApiResponse(Response $response, ?string $route): void
    {
        // Default cache time for API responses
        $cacheTime = 60; // 1 minute default
        
        // Specific cache times for API endpoints
        $apiCacheTimes = [
            'api_statistics' => 300,     // 5 minutes
            'api_user_list' => 180,      // 3 minutes
            'api_payment_list' => 120,   // 2 minutes
        ];
        
        if ($route && isset($apiCacheTimes[$route])) {
            $cacheTime = $apiCacheTimes[$route];
        }
        
        // Set cache headers for API
        $response->setMaxAge($cacheTime);
        $response->setSharedMaxAge($cacheTime);
        $response->headers->set('X-Cache-TTL', (string)$cacheTime);
        
        // Allow CORS caching
        if ($response->headers->has('Access-Control-Allow-Origin')) {
            $response->headers->set('Access-Control-Max-Age', '3600');
        }
    }
}