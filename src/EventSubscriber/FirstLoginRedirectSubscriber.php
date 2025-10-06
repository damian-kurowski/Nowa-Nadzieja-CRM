<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FirstLoginRedirectSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ROUTES = [
        'first_login_index',
        'first_login_accept_rodo',
        'first_login_change_password',
        'first_login_setup_2fa',
        'first_login_verify_2fa',
        'first_login_upload_photo',
        'first_login_connect_telegram',
        'first_login_check_telegram',
        'first_login_skip_telegram',
        'logout',
        '2fa_login',
        '2fa_login_check',
        '2fa_cancel',
    ];

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -10],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (str_starts_with($route, '_') ||
            str_starts_with($route, 'api_') ||
            str_starts_with($request->getPathInfo(), '/api/') ||
            str_starts_with($request->getPathInfo(), '/_')) {
            return;
        }

        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            return;
        }

        /** @var User $user */
        $user = $token->getUser();

        if ($user->requiresFirstLoginSetup()) {
            $event->setController(function() {
                return new RedirectResponse($this->urlGenerator->generate('first_login_index'));
            });
        }
    }
}
