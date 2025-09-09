<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfService
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Validate CSRF token for AJAX requests.
     */
    public function validateAjaxToken(Request $request, string $tokenId = 'ajax'): bool
    {
        $submittedToken = $request->headers->get('X-CSRF-Token')
            ?? $request->request->get('_token')
            ?? $request->query->get('_token');

        if (!$submittedToken) {
            $this->logCsrfViolation($request, 'missing_token');

            return false;
        }

        $token = new CsrfToken($tokenId, (string) $submittedToken);
        $isValid = $this->csrfTokenManager->isTokenValid($token);

        if (!$isValid) {
            $this->logCsrfViolation($request, 'invalid_token');
        }

        return $isValid;
    }

    /**
     * Generate CSRF token for JavaScript usage.
     */
    public function generateAjaxToken(string $tokenId = 'ajax'): string
    {
        return $this->csrfTokenManager->getToken($tokenId)->getValue();
    }

    /**
     * Create CSRF error response.
     */
    public function createCsrfErrorResponse(): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => 'CSRF token validation failed',
            'message' => 'Security token is invalid. Please refresh the page and try again.',
            'code' => 'CSRF_INVALID',
        ], 403);
    }

    /**
     * Validate token for specific meeting operations.
     */
    public function validateMeetingToken(Request $request, int $meetingId): bool
    {
        $tokenId = sprintf('meeting_%d', $meetingId);

        return $this->validateAjaxToken($request, $tokenId);
    }

    /**
     * Generate token for specific meeting operations.
     */
    public function generateMeetingToken(int $meetingId): string
    {
        $tokenId = sprintf('meeting_%d', $meetingId);

        return $this->generateAjaxToken($tokenId);
    }

    /**
     * Validate token for document operations.
     */
    public function validateDocumentToken(Request $request, int $documentId): bool
    {
        $tokenId = sprintf('document_%d', $documentId);

        return $this->validateAjaxToken($request, $tokenId);
    }

    /**
     * Generate token for document operations.
     */
    public function generateDocumentToken(int $documentId): string
    {
        $tokenId = sprintf('document_%d', $documentId);

        return $this->generateAjaxToken($tokenId);
    }

    /**
     * Refresh token - useful for long-running pages.
     */
    public function refreshToken(string $tokenId = 'ajax'): string
    {
        // Remove old token
        $this->csrfTokenManager->removeToken($tokenId);

        // Generate new token
        return $this->generateAjaxToken($tokenId);
    }

    /**
     * Log CSRF token violations.
     */
    private function logCsrfViolation(Request $request, string $violationType): void
    {
        $this->logger->warning('CSRF token violation', [
            'violation_type' => $violationType,
            'ip' => $request->getClientIp(),
            'endpoint' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'user_agent' => $request->headers->get('User-Agent'),
            'referer' => $request->headers->get('Referer'),
            'timestamp' => date('c'),
        ]);
    }
}
