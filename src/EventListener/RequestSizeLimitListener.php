<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 15)]
class RequestSizeLimitListener
{
    private const MAX_REQUEST_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_POST_PARAMS = 100;
    private const MAX_GET_PARAMS = 50;
    private const MAX_HEADER_SIZE = 8192; // 8KB
    private const MAX_URL_LENGTH = 2048;

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Skip for internal requests and profiler
        if (!$event->isMainRequest() || str_starts_with($request->getPathInfo(), '/_')) {
            return;
        }

        // Check request size
        $contentLength = $request->headers->get('Content-Length');
        if ($contentLength && $contentLength > self::MAX_REQUEST_SIZE) {
            $this->logSuspiciousActivity('request_too_large', $request, [
                'content_length' => $contentLength,
                'max_allowed' => self::MAX_REQUEST_SIZE,
            ]);

            throw new BadRequestHttpException('Request size too large');
        }

        // Check URL length
        $url = $request->getRequestUri();
        if (strlen($url) > self::MAX_URL_LENGTH) {
            $this->logSuspiciousActivity('url_too_long', $request, [
                'url_length' => strlen($url),
                'max_allowed' => self::MAX_URL_LENGTH,
            ]);

            throw new BadRequestHttpException('URL too long');
        }

        // Check number of parameters
        $postCount = count($request->request->all());
        $getCount = count($request->query->all());

        if ($postCount > self::MAX_POST_PARAMS) {
            $this->logSuspiciousActivity('too_many_post_params', $request, [
                'post_count' => $postCount,
                'max_allowed' => self::MAX_POST_PARAMS,
            ]);

            throw new BadRequestHttpException('Too many POST parameters');
        }

        if ($getCount > self::MAX_GET_PARAMS) {
            $this->logSuspiciousActivity('too_many_get_params', $request, [
                'get_count' => $getCount,
                'max_allowed' => self::MAX_GET_PARAMS,
            ]);

            throw new BadRequestHttpException('Too many GET parameters');
        }

        // Check for suspicious parameter patterns
        $this->checkSuspiciousParameters($request);

        // Check header sizes
        $this->checkHeaderSizes($request);
    }

    private function checkSuspiciousParameters(\Symfony\Component\HttpFoundation\Request $request): void
    {
        $allParams = array_merge($request->query->all(), $request->request->all());

        foreach ($allParams as $key => $value) {
            // Check for excessively long parameter names
            if (strlen($key) > 100) {
                $this->logSuspiciousActivity('long_param_name', $request, [
                    'param_name' => substr($key, 0, 50).'...',
                    'param_length' => strlen($key),
                ]);

                throw new BadRequestHttpException('Parameter name too long');
            }

            // Check for excessively long parameter values (except for specific fields)
            $allowedLongFields = ['content', 'description', 'text', 'message', 'tresc', 'signature'];
            if (!in_array($key, $allowedLongFields) && is_string($value) && strlen($value) > 10000) {
                $this->logSuspiciousActivity('long_param_value', $request, [
                    'param_name' => $key,
                    'value_length' => strlen($value),
                ]);

                throw new BadRequestHttpException('Parameter value too long');
            }

            // Check for SQL injection patterns
            if (is_string($value) && $this->containsSqlInjectionPatterns($value)) {
                $this->logSuspiciousActivity('sql_injection_attempt', $request, [
                    'param_name' => $key,
                    'suspicious_value' => substr($value, 0, 100),
                ]);

                throw new BadRequestHttpException('Invalid request');
            }

            // Check for XSS patterns
            if (is_string($value) && $this->containsXssPatterns($value)) {
                $this->logSuspiciousActivity('xss_attempt', $request, [
                    'param_name' => $key,
                    'suspicious_value' => substr($value, 0, 100),
                ]);

                throw new BadRequestHttpException('Invalid request');
            }
        }
    }

    private function checkHeaderSizes(\Symfony\Component\HttpFoundation\Request $request): void
    {
        $headers = $request->headers->all();
        $totalHeaderSize = 0;

        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $totalHeaderSize += strlen($name) + strlen((string) $value) + 4; // +4 for ": \r\n"

                // Check individual header size
                if (strlen((string) $value) > 4096) { // 4KB per header
                    $this->logSuspiciousActivity('large_header', $request, [
                        'header_name' => $name,
                        'header_size' => strlen((string) $value),
                    ]);

                    throw new BadRequestHttpException('Header too large');
                }
            }
        }

        if ($totalHeaderSize > self::MAX_HEADER_SIZE) {
            $this->logSuspiciousActivity('headers_too_large', $request, [
                'total_size' => $totalHeaderSize,
                'max_allowed' => self::MAX_HEADER_SIZE,
            ]);

            throw new BadRequestHttpException('Headers too large');
        }
    }

    private function containsSqlInjectionPatterns(string $value): bool
    {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bALTER\b.*\bTABLE\b)/i',
            '/(\bCREATE\b.*\bTABLE\b)/i',
            '/(\'.*OR.*\'.*=.*\')/i',
            '/(\'.*AND.*\'.*=.*\')/i',
            '/(\bOR\b.*1.*=.*1)/i',
            '/(\bAND\b.*1.*=.*1)/i',
            '/(\'.*;\s*--)/i',
            '/(\/\*.*\*\/)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    private function containsXssPatterns(string $value): bool
    {
        $patterns = [
            '/<script[^>]*>.*<\/script>/i',
            '/<iframe[^>]*>.*<\/iframe>/i',
            '/<object[^>]*>.*<\/object>/i',
            '/<embed[^>]*>/i',
            '/<link[^>]*>/i',
            '/<meta[^>]*>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:text\/html/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
            '/<img[^>]*onerror/i',
            '/<svg[^>]*onload/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logSuspiciousActivity(string $type, \Symfony\Component\HttpFoundation\Request $request, array $details = []): void
    {
        $this->logger->warning('Request limit violation detected', [
            'type' => $type,
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'url' => $request->getRequestUri(),
            'method' => $request->getMethod(),
            'details' => $details,
            'timestamp' => new \DateTime(),
        ]);
    }
}
