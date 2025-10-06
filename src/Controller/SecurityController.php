<?php

namespace App\Controller;

use App\Service\SecurityService;
use App\Service\TelegramService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        SecurityService $securityService,
        Request $request,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        $ipAddress = $request->getClientIp() ?? '127.0.0.1';

        // Check honeypot fields (bot detection)
        if ($request->isMethod('POST')) {
            $honeypotWebsite = $request->request->get('website');
            $honeypotEmailConfirm = $request->request->get('email_confirm');

            if (!empty($honeypotWebsite) || !empty($honeypotEmailConfirm)) {
                // Bot detected - silently reject
                $securityService->recordLoginAttempt($ipAddress, 'bot_detected', false, 'honeypot_filled');

                // Return normal looking error to not reveal detection
                return $this->render('security/login.html.twig', [
                    'last_username' => $request->request->get('email', ''),
                    'error' => new \Symfony\Component\Security\Core\Exception\BadCredentialsException('Invalid credentials'),
                    'blocked' => false,
                ]);
            }
        }

        // Check if login is allowed from this IP
        $canLogin = $securityService->canAttemptLogin($ipAddress);
        if (!$canLogin['allowed']) {
            $this->addFlash('error', $canLogin['message']);

            return $this->render('security/login.html.twig', [
                'last_username' => '',
                'error' => null,
                'blocked' => true,
            ]);
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // If there was a login error, record the failed attempt
        if ($error) {
            $securityService->recordLoginAttempt($ipAddress, $lastUsername, false);
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'blocked' => false,
        ]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/2fa/cancel', name: '2fa_cancel', methods: ['POST'])]
    public function cancel2fa(Request $request, TokenStorageInterface $tokenStorage): Response
    {
        // Clear authentication token
        $tokenStorage->setToken(null);

        // Clear all session data including 2FA
        $session = $request->getSession();
        $session->invalidate();

        // Create response with redirect
        $response = new RedirectResponse($this->generateUrl('login'));

        // Clear all authentication cookies
        $response->headers->setCookie(Cookie::create('PHPSESSID', '', 1));
        $response->headers->setCookie(Cookie::create('REMEMBERME', '', 1));

        // Add cache control headers to prevent back button issues
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    #[Route(path: '/password-reset-request', name: 'password_reset_request', methods: ['GET', 'POST'])]
    public function passwordResetRequest(Request $request, TelegramService $telegramService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        if ($request->isMethod('POST')) {
            $data = [
                'email' => $request->request->get('email'),
                'full_name' => $request->request->get('full_name'),
                'phone' => $request->request->get('phone'),
                'okreg' => $request->request->get('okreg'),
                'additional_info' => $request->request->get('additional_info', ''),
            ];

            // Walidacja CSRF
            $token = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('password_reset', $token)) {
                $this->addFlash('error', 'Nieprawidłowy token CSRF');
                return $this->redirectToRoute('password_reset_request');
            }

            // Walidacja podstawowych danych
            if (empty($data['email']) || empty($data['full_name']) || empty($data['phone']) || empty($data['okreg'])) {
                $this->addFlash('error', 'Wszystkie wymagane pola muszą być wypełnione');
                return $this->redirectToRoute('password_reset_request');
            }

            // Walidacja formatu email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Nieprawidłowy format adresu e-mail');
                return $this->redirectToRoute('password_reset_request');
            }

            // Wysłanie prośby na Telegram
            $sent = $telegramService->sendPasswordResetRequest($data);

            if ($sent) {
                $this->addFlash('success', 'Twoja prośba o reset hasła została wysłana do administratora. Skontaktujemy się z Tobą wkrótce.');
            } else {
                $this->addFlash('success', 'Twoja prośba została zarejestrowana. Administrator skontaktuje się z Tobą wkrótce.');
            }

            return $this->redirectToRoute('password_reset_request');
        }

        return $this->render('security/password_reset_request.html.twig');
    }
}
