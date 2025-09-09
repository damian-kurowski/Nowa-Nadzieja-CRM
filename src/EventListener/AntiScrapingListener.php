<?php

namespace App\EventListener;

use App\Service\SecurityService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class AntiScrapingListener
{
    public function __construct(
        private SecurityService $securityService,
        private LoggerInterface $logger,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Skip for internal requests, profiler, and static assets
        $path = $request->getPathInfo();
        if (!$event->isMainRequest()
            || str_starts_with($path, '/_')
            || str_contains($path, '/build/')
            || str_contains($path, '.css')
            || str_contains($path, '.js')
            || str_contains($path, '.png')
            || str_contains($path, '.jpg')
            || str_contains($path, '.gif')
            || 'XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return;
        }

        // Skip anti-scraping completely in development environment
        if ('dev' === $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null) {
            return;
        }

        // Skip anti-scraping for localhost in development
        $clientIp = $request->getClientIp();
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
            return;
        }

        // Check if request is from a bot
        $botCheck = $this->securityService->isSuspiciousBot($request);

        if ($botCheck['is_bot']) {
            $this->logger->warning('Suspicious bot activity detected', [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'path' => $request->getPathInfo(),
                'reason' => $botCheck['reason'],
                'details' => $botCheck,
            ]);

            // Return appropriate response based on request type
            if ($this->isApiRequest($request)) {
                $response = new JsonResponse([
                    'error' => 'Access denied',
                    'message' => 'Automated requests are not allowed',
                ], Response::HTTP_FORBIDDEN);
            } else {
                $response = new Response(
                    $this->getBotBlockPage(),
                    Response::HTTP_FORBIDDEN,
                    ['Content-Type' => 'text/html']
                );
            }

            // Add anti-scraping headers
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            $event->setResponse($response);
        }
    }

    private function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/')
               || 'application/json' === $request->headers->get('Accept')
               || 'application/json' === $request->headers->get('Content-Type');
    }

    private function getBotBlockPage(): string
    {
        return '<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>Dostęp zabroniony</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
            background-color: #f8f9fa;
            color: #343a40;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #dc3545; }
        .code {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚫 Dostęp zabroniony</h1>
        <p>Wykryto automatyczne żądanie. Ten system CRM jest przeznaczony wyłącznie dla autoryzowanych użytkowników.</p>
        <div class="code">HTTP 403 Forbidden</div>
        <p><small>Jeśli jesteś uprawniony do dostępu, skontaktuj się z administratorem systemu.</small></p>
        <p><small>ID: '.uniqid().'</small></p>
    </div>
</body>
</html>';
    }
}
