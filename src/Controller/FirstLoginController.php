<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\VerificationCode;
use App\Repository\VerificationCodeRepository;
use App\Service\TelegramBroadcastService;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class FirstLoginController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private GoogleAuthenticatorInterface $googleAuthenticator,
        private ValidatorInterface $validator,
        private TelegramBroadcastService $telegramBroadcastService,
        private VerificationCodeRepository $verificationCodeRepository,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    #[Route('/first-login', name: 'first_login_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->requiresFirstLoginSetup()) {
            return $this->redirectToRoute('dashboard');
        }

        // Krok 1: Konfiguracja zgód API (wyświetlanie danych na stronie)
        if (!$user->isFirstLoginApiConsentsConfigured()) {
            return $this->redirectToRoute('first_login_accept_rodo');
        }

        // Krok 2: Zmiana hasła
        if ($user->isPasswordChangeRequired()) {
            return $this->redirectToRoute('first_login_change_password');
        }

        // Krok 3: Konfiguracja 2FA
        if (!$user->isTwoFactorEnabled()) {
            return $this->redirectToRoute('first_login_setup_2fa');
        }

        // Krok 4: Upload zdjęcia
        if (null === $user->getZdjecie()) {
            return $this->redirectToRoute('first_login_upload_photo');
        }

        // Krok 5: Połączenie z Telegramem
        if (!$user->isTelegramConnected()) {
            return $this->redirectToRoute('first_login_connect_telegram');
        }

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/first-login/accept-rodo', name: 'first_login_accept_rodo')]
    public function acceptRodo(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Jeśli już skonfigurowano zgody API, przekieruj dalej
        if ($user->isFirstLoginApiConsentsConfigured()) {
            return $this->redirectToRoute('first_login_index');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $acceptRodo = $request->request->get('accept_rodo');
            $acceptMonitoring = $request->request->get('accept_monitoring');

            // Opcjonalne zgody na API (domyślnie false jeśli nie zaznaczone)
            $apiEmail = $request->request->get('api_email') === 'on';
            $apiTelefon = $request->request->get('api_telefon') === 'on';
            $apiZdjecie = $request->request->get('api_zdjecie') === 'on';

            if ($acceptRodo === 'on' && $acceptMonitoring === 'on') {
                $user->setZgodaRodo(true);
                $user->setDataZgodyRodo(new \DateTime());

                // Zapisz zgody na API
                $user->setZgodaApiEmail($apiEmail);
                $user->setZgodaApiTelefon($apiTelefon);
                $user->setZgodaApiZdjecie($apiZdjecie);

                // Oznacz że użytkownik przeszedł przez konfigurację zgód API
                $user->setFirstLoginApiConsentsConfigured(true);

                $this->entityManager->flush();
                $this->refreshUserInToken($user);

                $this->addFlash('success', 'Zgoda na przetwarzanie danych została zaakceptowana');

                return $this->redirectToRoute('first_login_index');
            } else {
                $error = 'Musisz zaakceptować wszystkie wymagane zgody, aby kontynuować korzystanie z systemu';
            }
        }

        return $this->render('security/first_login_rodo.html.twig', [
            'error' => $error,
            'user' => $user,
        ]);
    }

    #[Route('/first-login/change-password', name: 'first_login_change_password')]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź, czy zgody API zostały skonfigurowane
        if (!$user->isFirstLoginApiConsentsConfigured()) {
            return $this->redirectToRoute('first_login_accept_rodo');
        }

        if (!$user->isPasswordChangeRequired()) {
            return $this->redirectToRoute('first_login_index');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $error = 'Nieprawidłowe obecne hasło';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Nowe hasła nie są identyczne';
            } else {
                $passwordConstraint = new Assert\NotCompromisedPassword([
                    'message' => 'To hasło zostało ujawnione w wyciekach danych i nie może być użyte',
                ]);

                $lengthConstraint = new Assert\Length([
                    'min' => 12,
                    'minMessage' => 'Hasło musi mieć co najmniej {{ limit }} znaków',
                ]);

                $regexConstraint = new Assert\Regex([
                    'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                    'message' => 'Hasło musi zawierać: wielką literę, małą literę i cyfrę',
                ]);

                $violations = $this->validator->validate($newPassword, [
                    $lengthConstraint,
                    $regexConstraint,
                    $passwordConstraint,
                ]);

                if (count($violations) > 0) {
                    $error = $violations[0]->getMessage();
                } else {
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                    $user->setIsPasswordChangeRequired(false);
                    $this->entityManager->flush();
                    $this->refreshUserInToken($user);

                    $this->addFlash('success', 'Hasło zostało zmienione pomyślnie');

                    return $this->redirectToRoute('first_login_index');
                }
            }
        }

        return $this->render('security/first_login_password.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/first-login/setup-2fa', name: 'first_login_setup_2fa')]
    public function setup2FA(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isFirstLoginApiConsentsConfigured()) {
            return $this->redirectToRoute('first_login_accept_rodo');
        }

        if ($user->isPasswordChangeRequired()) {
            return $this->redirectToRoute('first_login_change_password');
        }

        if ($user->isTwoFactorEnabled()) {
            return $this->redirectToRoute('dashboard');
        }

        if (!$user->getGoogleAuthenticatorSecret()) {
            $secret = $this->googleAuthenticator->generateSecret();
            $user->setGoogleAuthenticatorSecret($secret);
            $this->entityManager->flush();
        }

        $qrCodeContent = $this->googleAuthenticator->getQRContent($user);

        return $this->render('security/first_login_2fa_setup.html.twig', [
            'qrCodeContent' => $qrCodeContent,
            'secret' => $user->getGoogleAuthenticatorSecret(),
        ]);
    }

    #[Route('/first-login/verify-2fa', name: 'first_login_verify_2fa', methods: ['POST'])]
    public function verify2FA(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $code = $request->request->get('code');

        if ($this->googleAuthenticator->checkCode($user, $code)) {
            $user->setIsTwoFactorEnabled(true);
            $this->entityManager->flush();
            $this->refreshUserInToken($user);

            $this->addFlash('success', 'Uwierzytelnianie dwuskładnikowe zostało aktywowane');

            return $this->redirectToRoute('first_login_index');
        }

        $this->addFlash('error', 'Nieprawidłowy kod weryfikacyjny');

        return $this->redirectToRoute('first_login_setup_2fa');
    }

    #[Route('/first-login/upload-photo', name: 'first_login_upload_photo')]
    public function uploadPhoto(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isFirstLoginApiConsentsConfigured()) {
            return $this->redirectToRoute('first_login_accept_rodo');
        }

        if ($user->isPasswordChangeRequired()) {
            return $this->redirectToRoute('first_login_change_password');
        }

        if (!$user->isTwoFactorEnabled()) {
            return $this->redirectToRoute('first_login_setup_2fa');
        }

        if (null !== $user->getZdjecie()) {
            return $this->redirectToRoute('dashboard');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('photo');

            if (!$uploadedFile) {
                $error = 'Proszę wybrać zdjęcie';
            } else {
                $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $maxFileSize = 5 * 1024 * 1024;

                if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
                    $error = 'Dozwolone formaty: JPG, PNG, GIF';
                } elseif ($uploadedFile->getSize() > $maxFileSize) {
                    $error = 'Maksymalny rozmiar pliku to 5MB';
                } else {
                    $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';

                    if (!is_dir($uploadsDirectory)) {
                        mkdir($uploadsDirectory, 0777, true);
                    }

                    $newFilename = uniqid() . '_' . $user->getId() . '.' . $uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move($uploadsDirectory, $newFilename);
                        $user->setZdjecie($newFilename);
                        $this->entityManager->flush();
                        $this->refreshUserInToken($user);

                        $this->addFlash('success', 'Zdjęcie profilowe zostało dodane pomyślnie');

                        return $this->redirectToRoute('first_login_index');
                    } catch (\Exception $e) {
                        $error = 'Błąd podczas zapisywania pliku: ' . $e->getMessage();
                    }
                }
            }
        }

        return $this->render('security/first_login_photo.html.twig', [
            'error' => $error,
            'user' => $user,
        ]);
    }

    #[Route('/first-login/connect-telegram', name: 'first_login_connect_telegram')]
    public function connectTelegram(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Guards - sprawdź czy poprzednie kroki zakończone (pomiń jeśli użytkownik wraca z profilu)
        $fromProfile = $request->query->get('reconnect') === '1';

        if (!$fromProfile) {
            if (!$user->isFirstLoginApiConsentsConfigured()) {
                return $this->redirectToRoute('first_login_accept_rodo');
            }
            if ($user->isPasswordChangeRequired()) {
                return $this->redirectToRoute('first_login_change_password');
            }
            if (!$user->isTwoFactorEnabled()) {
                return $this->redirectToRoute('first_login_setup_2fa');
            }
            if (null === $user->getZdjecie()) {
                return $this->redirectToRoute('first_login_upload_photo');
            }
        }

        // Jeśli użytkownik ponownie łączy konto, resetuj poprzednie połączenie
        if ($user->isTelegramConnected() && $fromProfile) {
            $user->setIsTelegramConnected(false);
            $user->setTelegramChatId(null);
            $user->setTelegramUsername(null);
            $user->setTelegramConnectedAt(null);
            $this->entityManager->flush();
        }

        // Oznacz wszystkie stare nieużyte kody użytkownika jako użyte
        $this->verificationCodeRepository->invalidateUserCodes($user->getId());

        // Utwórz nowy kod weryfikacyjny
        $verificationCode = new VerificationCode();
        $verificationCode->setCode($this->telegramBroadcastService->generateVerificationCode());
        $verificationCode->setUser($user);
        $verificationCode->setExpiresAt(new \DateTime('+10 minutes'));

        $this->entityManager->persist($verificationCode);
        $this->entityManager->flush();

        $code = $verificationCode->getCode();
        $expiresInMinutes = max(0, ceil(($verificationCode->getExpiresAt()->getTimestamp() - (new \DateTime())->getTimestamp()) / 60));

        return $this->render('security/first_login_telegram.html.twig', [
            'code' => $code,
            'expires_in' => $expiresInMinutes,
        ]);
    }

    #[Route('/first-login/check-telegram', name: 'first_login_check_telegram', methods: ['POST'])]
    public function checkTelegramConnection(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Odśwież dane użytkownika z bazy
        $this->entityManager->refresh($user);

        return new JsonResponse([
            'connected' => $user->isTelegramConnected(),
        ]);
    }

    #[Route('/first-login/skip-telegram', name: 'first_login_skip_telegram', methods: ['POST'])]
    public function skipTelegram(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Walidacja CSRF
        if (!$this->isCsrfTokenValid('skip_telegram', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token CSRF');
            return $this->redirectToRoute('first_login_connect_telegram');
        }

        // Sprawdź czy użytkownik przyszedł z profilu
        $fromProfile = $request->request->get('from_profile') === '1';

        // Oznacz jako pominięte (ustawiamy connected = true żeby nie blokować dostępu)
        // Użytkownik będzie mógł połączyć później w ustawieniach
        $user->setIsTelegramConnected(true);
        $this->entityManager->flush();
        $this->refreshUserInToken($user);

        $this->addFlash('info', 'Telegram został pominięty. Możesz połączyć konto później w ustawieniach.');

        // Wróć do profilu jeśli stamtąd przyszedł, inaczej do dashboardu
        return $this->redirectToRoute($fromProfile ? 'profil_index' : 'dashboard');
    }

    /**
     * Odświeża obiekt użytkownika w security tokenie po zapisie zmian do bazy.
     * To jest konieczne, aby EventSubscriber widział aktualne dane użytkownika.
     */
    private function refreshUserInToken(User $user): void
    {
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof User) {
            // Odśwież dane z bazy
            $this->entityManager->refresh($user);
            // Zaktualizuj obiekt użytkownika w tokenie
            $token->setUser($user);
            $this->tokenStorage->setToken($token);
        }
    }
}
