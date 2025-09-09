<?php

namespace App\Controller;

use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
}
