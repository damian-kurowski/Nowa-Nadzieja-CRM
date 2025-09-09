<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SecurityListener
{
    public function __construct(
        private LoggerInterface $logger,
        private SecurityService $securityService,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[AsEventListener(event: LoginSuccessEvent::class)]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request?->getClientIp() ?? 'unknown';

        // Record successful login
        $this->securityService->recordLoginAttempt(
            $ipAddress,
            $user->getUserIdentifier(),
            true
        );

        // Update user's last login time
        if ($user instanceof User) {
            $user->setPreviousLoginAt($user->getLastLoginAt());
            $user->setLastLoginAt(new \DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // Log successful login
        $this->logger->info('Successful login', [
            'user' => $user->getUserIdentifier(),
            'ip' => $ipAddress,
            'user_agent' => $request?->headers->get('User-Agent'),
            'timestamp' => new \DateTime(),
        ]);
    }

    #[AsEventListener(event: LoginFailureEvent::class)]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request?->getClientIp() ?? 'unknown';
        $username = '';

        // Try to get username from request
        if ($request) {
            $username = $request->request->get('email', '');
        }

        // Record failed login attempt
        $this->securityService->recordLoginAttempt($ipAddress, is_string($username) ? $username : null, false);

        // Log failed login attempt
        $this->logger->warning('Failed login attempt', [
            'username' => $username,
            'ip' => $ipAddress,
            'user_agent' => $request?->headers->get('User-Agent'),
            'error' => $event->getException()->getMessage(),
            'timestamp' => new \DateTime(),
        ]);
    }
}
