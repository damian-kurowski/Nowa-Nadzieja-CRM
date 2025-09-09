<?php

namespace App\Controller;

use App\Entity\Dokument;
use App\Entity\Oddzial;
use App\Entity\User;
use App\Entity\ZebranieOddzialu;
use App\Repository\DokumentRepository;
use App\Repository\OddzialRepository;
use App\Repository\UserRepository;
use App\Repository\ZebranieOddzialuRepository;
use App\Security\CsrfService;
use App\Security\RateLimitService;
use App\Service\CacheService;
use App\Service\DokumentService;
use App\Service\ZebranieOddzialuService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/zebranie-oddzialu')]
class ZebranieOddzialuController extends AbstractController
{
    public function __construct(
        private ZebranieOddzialuService $zebranieService,
        private ZebranieOddzialuRepository $zebranieRepository,
        private DokumentRepository $dokumentRepository,
        private DokumentService $dokumentService,
        private EntityManagerInterface $entityManager,
        private CacheService $cacheService,
        private CsrfService $csrfService,
        private RateLimitService $rateLimitService,
    ) {
    }

    /**
     * Lista zebrań (dla obserwatorów i administratorów).
     */
    #[Route('/', name: 'zebranie_oddzialu_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $zebrania = [];

        // Sprawdź różne role i pokaż odpowiednie zebrania
        if ($this->isGranted('ROLE_ADMIN')) {
            $zebrania = $this->zebranieRepository->findBy([], ['dataRozpoczecia' => 'DESC'], 50);
        } elseif ($this->isGranted('ROLE_OBSERWATOR_ZEBRANIA') || $this->isGranted('ROLE_PROTOKOLANT_ZEBRANIA') || $this->isGranted('ROLE_PROWADZACY_ZEBRANIA')) {
            // Znajdź zebrania gdzie użytkownik pełni jakąś rolę
            $zebrania = $this->zebranieRepository->findUserMeetings($user);
            
            // Jeśli jest aktywne zebranie, przekieruj do niego
            foreach ($zebrania as $zebranie) {
                if ($zebranie->isAktywne()) {
                    $this->addFlash('info', sprintf(
                        'Przekierowano do aktywnego zebrania oddziału %s',
                        $zebranie->getOddzial()->getNazwa()
                    ));
                    return $this->redirectToRoute('zebranie_wizard', ['id' => $zebranie->getId()]);
                }
            }
        } elseif ($this->isGranted('ROLE_SEKRETARZ_OKREGU') && $user->getOkreg()) {
            $zebrania = $this->zebranieRepository->findByOkreg($user->getOkreg());
        } elseif ($this->isGranted('ROLE_CZLONEK_PARTII') && $user->getOddzial()) {
            // Zwykli członkowie widzą zebrania swojego oddziału
            $zebrania = $this->zebranieRepository->findBy(
                ['oddzial' => $user->getOddzial()], 
                ['dataRozpoczecia' => 'DESC'],
                10
            );
        }

        return $this->render('zebranie_oddzialu/index.html.twig', [
            'zebrania' => $zebrania,
        ]);
    }

    /**
     * Wyznaczenie obserwatora (dla Sekretarza Okręgu).
     */
    #[Route('/wyznacz-obserwatora', name: 'zebranie_wyznacz_obserwatora')]
    #[IsGranted('ROLE_SEKRETARZ_OKREGU')]
    public function wyznaczObserwatora(Request $request, OddzialRepository $oddzialRepository, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->getOkreg()) {
            $this->addFlash('error', 'Nie jesteś przypisany do żadnego okręgu');

            return $this->redirectToRoute('dashboard');
        }

        // Pobierz oddziały z okręgu
        $oddzialy = $oddzialRepository->findBy(['okreg' => $user->getOkreg()]);

        if (empty($oddzialy)) {
            $this->addFlash('warning', sprintf(
                'W okręgu "%s" nie ma przypisanych oddziałów.',
                $user->getOkreg()->getNazwa()
            ));
        }

        if ($request->isMethod('POST')) {
            $oddzialId = $request->request->get('oddzial');
            $obserwatorId = $request->request->get('obserwator');

            $oddzial = $oddzialRepository->find($oddzialId);
            $obserwator = $userRepository->find($obserwatorId);

            if (!$oddzial || !$obserwator) {
                $this->addFlash('error', 'Nieprawidłowe dane');

                return $this->redirectToRoute('zebranie_wyznacz_obserwatora');
            }

            // Sprawdź czy oddział nie ma już aktywnego zebrania
            $activeZebranie = $this->zebranieRepository->findActiveByOddzial($oddzial);
            if ($activeZebranie) {
                $this->addFlash('error', 'Ten oddział ma już aktywne zebranie');

                return $this->redirectToRoute('zebranie_wyznacz_obserwatora');
            }

            // Utwórz dokument wyznaczenia obserwatora
            $dokumentData = [
                'obserwator' => $obserwator,
                'oddzial' => $oddzial,
                'data_wejscia_w_zycie' => new \DateTime(),
                'uzasadnienie' => sprintf(
                    'Wyznaczenie obserwatora zebrania członków oddziału %s',
                    $oddzial->getNazwa()
                ),
            ];

            try {
                $dokument = $this->dokumentService->createDocument(
                    Dokument::TYP_WYZNACZENIE_OBSERWATORA,
                    $dokumentData,
                    $user
                );

                // Automatycznie podpisz dokument jako Sekretarz Okręgu
                // Podpis dokumentu automatycznie nada rolę obserwatora poprzez executeDocumentAction
                $this->dokumentService->signDocument($dokument, $user);

                // Rozpocznij zebranie
                $zebranie = $this->zebranieService->rozpocznijZebranie($oddzial, $obserwator);

                $this->entityManager->flush();

                $this->addFlash('success', sprintf(
                    'Wyznaczono obserwatora %s dla zebrania oddziału %s',
                    $obserwator->getFullName(),
                    $oddzial->getNazwa()
                ));

                return $this->redirectToRoute('zebranie_wizard', ['id' => $zebranie->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd podczas wyznaczania obserwatora: '.$e->getMessage());
            }
        }

        // Pobierz członków okręgu którzy mogą być obserwatorami
        $potencjalniObserwatorzy = $userRepository->createQueryBuilder('u')
            ->where('u.okreg = :okreg')
            ->andWhere('u.status = :status')
            ->setParameter('okreg', $user->getOkreg())
            ->setParameter('status', 'aktywny')
            ->orderBy('u.nazwisko', 'ASC')
            ->addOrderBy('u.imie', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('zebranie_oddzialu/wyznacz_obserwatora.html.twig', [
            'oddzialy' => $oddzialy,
            'potencjalni_obserwatorzy' => $potencjalniObserwatorzy,
        ]);
    }

    /**
     * Kreator zebrania - główny wizard.
     */
    #[Route('/{id}/wizard', name: 'zebranie_wizard')]
    public function wizard(ZebranieOddzialu $zebranie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź uprawnienia
        if (!$this->zebranieService->canUserManageMeeting($user, $zebranie)) {
            $this->addFlash('error', 'Nie masz uprawnień do zarządzania tym zebraniem');

            return $this->redirectToRoute('zebranie_oddzialu_index');
        }

        // Określ aktualny krok
        $step = $this->determineCurrentStep($zebranie);

        // Pobierz status zebrania dla dashboard
        $status = $this->zebranieService->getZebranieStatus($zebranie);

        // Pobierz dokumenty czekające na podpis dla użytkownika
        $awaitingDocuments = $this->zebranieService->getAwaitingDocuments($zebranie, $user);

        return $this->render('zebranie_oddzialu/wizard.html.twig', [
            'zebranie' => $zebranie,
            'current_step' => $step,
            'can_manage' => true,
            'zebranie_status' => $status,
            'awaiting_documents' => $awaitingDocuments,
        ]);
    }



    /**
     * Wyznaczenie protokolanta przez obserwatora.
     */
    #[Route('/{id}/wyznacz-protokolanta', name: 'zebranie_oddzialu_wyznacz_protokolanta', methods: ['POST'])]
    public function wyznaczProtokolantaPost(ZebranieOddzialu $zebranie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('wyznacz_protokolanta' . $zebranie->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        // Sprawdź uprawnienia
        if (!$zebranie->canAssignProtokolant($user)) {
            $this->addFlash('danger', 'Nie masz uprawnień do wyznaczenia protokolanta.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        $protokolantId = $request->request->get('protokolant');
        if (!$protokolantId) {
            $this->addFlash('danger', 'Musisz wybrać protokolanta.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        $protokolant = $this->entityManager->getRepository(User::class)->find($protokolantId);
        if (!$protokolant) {
            $this->addFlash('danger', 'Wybrany użytkownik nie istnieje.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        try {
            $this->zebranieService->wyznaczProtokolanta($zebranie, $protokolant, $user);
            $this->addFlash('success', 'Protokolant został wyznaczony.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Wystąpił błąd: ' . $e->getMessage());
        }

        return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
    }

    /**
     * Wyznaczenie prowadzącego przez obserwatora.
     */
    #[Route('/{id}/wyznacz-prowadzacego', name: 'zebranie_oddzialu_wyznacz_prowadzacego', methods: ['POST'])]
    public function wyznaczProwadzacegoPost(ZebranieOddzialu $zebranie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('wyznacz_prowadzacego' . $zebranie->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        // Sprawdź uprawnienia
        if (!$zebranie->canAssignProwadzacy($user)) {
            $this->addFlash('danger', 'Nie masz uprawnień do wyznaczenia prowadzącego.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        $prowadzacyId = $request->request->get('prowadzacy');
        if (!$prowadzacyId) {
            $this->addFlash('danger', 'Musisz wybrać prowadzącego.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        $prowadzacy = $this->entityManager->getRepository(User::class)->find($prowadzacyId);
        if (!$prowadzacy) {
            $this->addFlash('danger', 'Wybrany użytkownik nie istnieje.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        try {
            $this->zebranieService->wyznaczProwadzacego($zebranie, $prowadzacy, $user);
            $this->addFlash('success', 'Prowadzący został wyznaczony.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Wystąpił błąd: ' . $e->getMessage());
        }

        return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
    }

    /**
     * Składanie podpisów elektronicznych przez uczestników zebrania.
     */
    #[Route('/{id}/zloz-podpis', name: 'zebranie_oddzialu_zloz_podpis', methods: ['POST'])]
    public function zlozPodpis(ZebranieOddzialu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('sign_' . $zebranie->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Nieprawidłowy token bezpieczeństwa.']);
        }

        // Sprawdź czy użytkownik może złożyć podpis
        $canSign = false;
        $action = '';
        
        if ($zebranie->canUserPerformAction($user, 'podpisz_obserwator')) {
            $canSign = true;
            $action = 'obserwator';
        } elseif ($zebranie->canUserPerformAction($user, 'podpisz_protokolant')) {
            $canSign = true;
            $action = 'protokolant';
        } elseif ($zebranie->canUserPerformAction($user, 'podpisz_prowadzacy')) {
            $canSign = true;
            $action = 'prowadzacy';
        } elseif ($zebranie->canUserPerformAction($user, 'podpisz_przewodniczacy')) {
            $canSign = true;
            $action = 'przewodniczacy';
        } elseif ($zebranie->canUserPerformAction($user, 'podpisz_zastepca1')) {
            $canSign = true;
            $action = 'zastepca1';
        } elseif ($zebranie->canUserPerformAction($user, 'podpisz_zastepca2')) {
            $canSign = true;
            $action = 'zastepca2';
        }

        if (!$canSign) {
            return $this->json(['success' => false, 'error' => 'Nie masz uprawnień do złożenia podpisu lub już podpisałeś.']);
        }

        // Pobierz dane podpisu
        $signatureData = $request->request->get('signature');
        if (!$signatureData) {
            return $this->json(['success' => false, 'error' => 'Brak danych podpisu.']);
        }

        try {
            // Złóż podpis w zależności od roli
            match($action) {
                'obserwator' => $zebranie->podpiszJakoObserwator(),
                'protokolant' => $zebranie->podpiszJakoProtokolant(),
                'prowadzacy' => $zebranie->podpiszJakoProwadzacy(),
                'przewodniczacy' => $zebranie->podpiszJakoPrzewodniczacy(),
                'zastepca1' => $zebranie->podpiszJakoZastepca1(),
                'zastepca2' => $zebranie->podpiszJakoZastepca2(),
            };

            // Sprawdź czy wszyscy już podpisali
            if ($zebranie->czyWszyscyPodpisali()) {
                $zebranie->setStatus(ZebranieOddzialu::STATUS_ZAKONCZONE);
                $zebranie->setDataZakonczenia(new \DateTime());
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Podpis został złożony pomyślnie.',
                'all_signed' => $zebranie->czyWszyscyPodpisali()
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Wystąpił błąd podczas składania podpisu: ' . $e->getMessage()]);
        }
    }

    /**
     * Wyświetla szczegóły zebrania.
     */
    #[Route('/{id}', name: 'zebranie_oddzialu_show', requirements: ['id' => '\d+'])]
    public function show(ZebranieOddzialu $zebranie, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Pobierz dostępnych członków do wyboru
        $availableMembers = [];
        if ($zebranie->getOddzial()) {
            $availableMembers = $userRepository->findByOddzial($zebranie->getOddzial());
        }

        return $this->render('zebranie_oddzialu/show.html.twig', [
            'zebranie' => $zebranie,
            'availableMembers' => $availableMembers,
            'zebranie_service' => $this->zebranieService,
        ]);
    }

    /**
     * Wybór przewodniczącego zebrania przez protokolanta i prowadzącego.
     */
    #[Route('/{id}/wybor-przewodniczacego', name: 'zebranie_oddzialu_wybor_przewodniczacego', methods: ['POST'])]
    public function wyborPrzewodniczacego(ZebranieOddzialu $zebranie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('wybor_przewodniczacego' . $zebranie->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        // Sprawdź uprawnienia
        if (!$zebranie->canSelectPrzewodniczacy($user)) {
            $this->addFlash('danger', 'Nie masz uprawnień do wyboru przewodniczącego.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        $przewodniczacyId = $request->request->get('przewodniczacy');
        if (!$przewodniczacyId) {
            $this->addFlash('danger', 'Musisz wybrać przewodniczącego.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        $przewodniczacy = $this->entityManager->getRepository(User::class)->find($przewodniczacyId);
        if (!$przewodniczacy) {
            $this->addFlash('danger', 'Wybrany użytkownik nie istnieje.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        try {
            // Użyj metody z service do zarządzania przewodniczącym
            $this->zebranieService->zarzadzajPrzewodniczacym(
                $zebranie,
                'powolaj',
                $przewodniczacy,
                $user
            );
            
            $this->addFlash('success', 'Przewodniczący zebrania został wybrany.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Wystąpił błąd: ' . $e->getMessage());
        }

        return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
    }

    /**
     * Wybór zastępcy przewodniczącego przez przewodniczącego.
     */
    #[Route('/{id}/wybor-zastepcy/{numer}', name: 'zebranie_oddzialu_wybor_zastepcy', requirements: ['numer' => '1|2'], methods: ['POST'])]
    public function wyborZastepcy(ZebranieOddzialu $zebranie, int $numer, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        $csrfToken = 'wybor_zastepcy' . $numer . '_' . $zebranie->getId();
        if (!$this->csrfService->validateToken($csrfToken, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        // Sprawdź uprawnienia
        if (!$zebranie->canSelectZastepcy($user)) {
            $this->addFlash('danger', 'Nie masz uprawnień do wyboru zastępców.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        $zastepcaId = $request->request->get('zastepca');
        if (!$zastepcaId) {
            $this->addFlash('danger', 'Musisz wybrać zastępcę.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        $zastepca = $this->entityManager->getRepository(User::class)->find($zastepcaId);
        if (!$zastepca) {
            $this->addFlash('danger', 'Wybrany użytkownik nie istnieje.');
            return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
        }

        try {
            // Użyj metody z service do zarządzania zastępcą
            $this->zebranieService->zarzadzajZastepca(
                $zebranie,
                'powolaj',
                $zastepca,
                $user
            );
            
            $this->addFlash('success', sprintf('%s zastępca przewodniczącego został wybrany.', $numer == 1 ? 'Pierwszy' : 'Drugi'));
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Wystąpił błąd: ' . $e->getMessage());
        }

        return $this->redirectToRoute('zebranie_oddzialu_show', ['id' => $zebranie->getId()]);
    }

    /**
     * Krok 3: Wybór zarządu oddziału.
     */
    #[Route('/{id}/wybor-zarzadu', name: 'zebranie_wybor_zarzadu')]
    public function wyborZarzadu(ZebranieOddzialu $zebranie, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Tylko prowadzący lub protokolant może wybierać zarząd
        if (!$zebranie->canManagePositions($user)) {
            $this->addFlash('error', 'Nie masz uprawnień do wyboru zarządu');

            return $this->redirectToRoute('zebranie_wizard', ['id' => $zebranie->getId()]);
        }

        if ($request->isMethod('POST')) {
            $przewodniczacyId = $request->request->get('przewodniczacy');
            $zastepca1Id = $request->request->get('zastepca1');
            $zastepca2Id = $request->request->get('zastepca2');
            $sekretarzId = $request->request->get('sekretarz');

            try {
                $przewodniczacy = $this->entityManager->getRepository(User::class)->find($przewodniczacyId);
                $zastepca1 = $zastepca1Id ? $this->entityManager->getRepository(User::class)->find($zastepca1Id) : null;
                $zastepca2 = $zastepca2Id ? $this->entityManager->getRepository(User::class)->find($zastepca2Id) : null;
                $sekretarz = $this->entityManager->getRepository(User::class)->find($sekretarzId);

                if (!$przewodniczacy || !$sekretarz) {
                    throw new \InvalidArgumentException('Przewodniczący i sekretarz są wymagani');
                }

                $this->zebranieService->wyznaczZarzadOddzialu(
                    $zebranie,
                    $przewodniczacy,
                    $zastepca1,
                    $zastepca2,
                    $sekretarz,
                    $user
                );

                $this->addFlash('success', 'Zarząd oddziału został wybrany');

                return $this->redirectToRoute('zebranie_wizard', ['id' => $zebranie->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd: '.$e->getMessage());
            }
        }

        // Pobierz członków oddziału
        $czlonkowieOddzialu = $this->entityManager->getRepository(User::class)
            ->findBy([
                'oddzial' => $zebranie->getOddzial(),
                'status' => 'aktywny',
            ]);

        return $this->render('zebranie_oddzialu/wybor_zarzadu.html.twig', [
            'zebranie' => $zebranie,
            'czlonkowie' => $czlonkowieOddzialu,
        ]);
    }

    /**
     * AJAX API: Pobierz kandydatów na funkcję.
     */
    #[Route('/{id}/api/candidates', name: 'zebranie_api_candidates', methods: ['GET'])]
    public function apiCandidates(ZebranieOddzialu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!in_array($user, [$zebranie->getProwadzacy(), $zebranie->getProtokolant()])) {
            return new JsonResponse(['success' => false, 'message' => 'Brak uprawnień'], 403);
        }

        $functionType = $request->query->get('function');
        if (!is_string($functionType) || !in_array($functionType, ['przewodniczacy', 'zastepca', 'sekretarz'])) {
            return new JsonResponse(['success' => false, 'message' => 'Nieprawidłowy typ funkcji'], 400);
        }

        try {
            // Use cached candidates for better performance
            $candidates = $this->cacheService->getMeetingCandidates($zebranie, $functionType);

            return new JsonResponse([
                'success' => true,
                'candidates' => $candidates,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Błąd podczas pobierania kandydatów: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX API: Powołaj na funkcję.
     */
    #[Route('/{id}/api/appoint', name: 'zebranie_api_appoint', methods: ['POST'])]
    public function apiAppoint(ZebranieOddzialu $zebranie, Request $request): JsonResponse
    {
        // Rate limiting check
        if ($this->rateLimitService->isSensitiveOperationLimited($request)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Rate limit exceeded. Please wait before trying again.',
            ], 429);
        }

        // CSRF validation
        /** @var int $meetingId */
        $meetingId = $zebranie->getId();
        if (!$this->csrfService->validateMeetingToken($request, $meetingId)) {
            return $this->csrfService->createCsrfErrorResponse();
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!in_array($user, [$zebranie->getProwadzacy(), $zebranie->getProtokolant()])) {
            return new JsonResponse(['success' => false, 'message' => 'Brak uprawnień'], 403);
        }

        $functionType = $request->request->get('function');
        $candidateId = $request->request->get('candidate');

        if (!in_array($functionType, ['przewodniczacy', 'zastepca', 'sekretarz'])) {
            return new JsonResponse(['success' => false, 'message' => 'Nieprawidłowy typ funkcji'], 400);
        }

        /** @var string $functionType */
        $candidate = $this->entityManager->getRepository(User::class)->find($candidateId);
        if (!$candidate) {
            return new JsonResponse(['success' => false, 'message' => 'Nieprawidłowy kandydat'], 400);
        }

        try {
            // Sprawdź czy zebranie jest aktywne
            if (!$zebranie->isAktywne()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Zebranie nie jest aktywne',
                ], 400);
            }

            // Sprawdź czy kandydat może zostać powołany
            $this->validateAppointment($zebranie, $functionType, $candidate);

            // Powołaj na funkcję
            match ($functionType) {
                'przewodniczacy' => $this->zebranieService->zarzadzajPrzewodniczacym($zebranie, 'powolaj', $candidate, $user),
                'zastepca' => $this->zebranieService->zarzadzajZastepca($zebranie, 'powolaj', $candidate, $user),
                'sekretarz' => $this->zebranieService->zarzadzajSekretarzemOddzialu($zebranie, 'powolaj', $candidate, $user),
                default => throw new \InvalidArgumentException('Nieznana funkcja'),
            };

            // Dokument został utworzony - protokolant i prowadzący muszą go ręcznie podpisać
            // Invalidate cache since positions may change after document is signed
            $this->cacheService->invalidateOddzialCache($zebranie->getOddzial());
            $this->cacheService->invalidateZebranieCache($zebranie);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Utworzono dokument powołania %s na stanowisko %s. Dokument czeka na podpisy protokolanta i prowadzącego.',
                    $candidate->getFullName(),
                    $this->getNazweFunkcji($functionType)),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Błąd podczas powoływania: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX API: Odwołaj z funkcji.
     */
    #[Route('/{id}/api/dismiss', name: 'zebranie_api_dismiss', methods: ['POST'])]
    public function apiDismiss(ZebranieOddzialu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!in_array($user, [$zebranie->getProwadzacy(), $zebranie->getProtokolant()])) {
            return new JsonResponse(['success' => false, 'message' => 'Brak uprawnień'], 403);
        }

        $functionType = $request->request->get('function');
        $userId = $request->request->get('user');

        if (!in_array($functionType, ['przewodniczacy', 'zastepca', 'sekretarz'])) {
            return new JsonResponse(['success' => false, 'message' => 'Nieprawidłowy typ funkcji'], 400);
        }

        /** @var string $functionType */
        $userToDismiss = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$userToDismiss) {
            return new JsonResponse(['success' => false, 'message' => 'Nieprawidłowy użytkownik'], 400);
        }

        try {
            // Sprawdź czy zebranie jest aktywne
            if (!$zebranie->isAktywne()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Zebranie nie jest aktywne',
                ], 400);
            }

            // Odwołaj z funkcji
            match ($functionType) {
                'przewodniczacy' => $this->zebranieService->zarzadzajPrzewodniczacym($zebranie, 'odwolaj', $userToDismiss, $user),
                'zastepca' => $this->zebranieService->zarzadzajZastepca($zebranie, 'odwolaj', $userToDismiss, $user),
                'sekretarz' => $this->zebranieService->zarzadzajSekretarzemOddzialu($zebranie, 'odwolaj', $userToDismiss, $user),
                default => throw new \InvalidArgumentException('Nieznana funkcja'),
            };

            // Dokument został utworzony - protokolant i prowadzący muszą go ręcznie podpisać
            // Nie podpisujemy automatycznie - dokumenty czekają na podpisy

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Utworzono dokument odwołania %s z stanowiska %s. Dokument czeka na podpisy protokolanta i prowadzącego.',
                    $userToDismiss->getFullName(),
                    $this->getNazweFunkcji($functionType)),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Błąd podczas odwoływania: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Zakończ zebranie.
     */
    #[Route('/{id}/zakoncz', name: 'zebranie_zakoncz')]
    public function zakonczZebranie(ZebranieOddzialu $zebranie): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($zebranie->getObserwator() !== $user) {
            $this->addFlash('error', 'Tylko obserwator może zakończyć zebranie');

            return $this->redirectToRoute('zebranie_wizard', ['id' => $zebranie->getId()]);
        }

        try {
            $this->zebranieService->zakonczZebranie($zebranie, $user);
            $this->addFlash('success', 'Zebranie zostało zakończone');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd: '.$e->getMessage());
        }

        return $this->redirectToRoute('zebranie_oddzialu_index');
    }

    /**
     * API: Get CSRF tokens for JavaScript.
     */
    #[Route('/{id}/api/csrf-tokens', name: 'zebranie_api_csrf_tokens', methods: ['GET'])]
    public function apiCsrfTokens(ZebranieOddzialu $zebranie): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->zebranieService->canUserManageMeeting($user, $zebranie)) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        /** @var int $meetingId */
        $meetingId = $zebranie->getId();

        return new JsonResponse([
            'meeting_token' => $this->csrfService->generateMeetingToken($meetingId),
            'ajax_token' => $this->csrfService->generateAjaxToken(),
        ]);
    }

    /**
     * API: Real-time status check.
     */
    #[Route('/{id}/api/status', name: 'zebranie_api_status', methods: ['GET'])]
    public function apiStatus(ZebranieOddzialu $zebranie): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->zebranieService->canUserManageMeeting($user, $zebranie)) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $status = $this->zebranieService->getZebranieStatus($zebranie);
        $awaitingDocuments = $this->zebranieService->getAwaitingDocuments($zebranie, $user);

        // Calculate progress
        $progress = $this->calculateProgress($zebranie);

        return new JsonResponse([
            'status' => $status['status'],
            'awaiting_documents' => array_map(fn ($d) => [
                'id' => $d->getId(),
                'title' => $d->getTytul(),
                'created_at' => $d->getDataUtworzenia()->format('H:i'),
            ], $awaitingDocuments),
            'progress' => $progress,
            'last_update' => time(),
        ]);
    }

    /**
     * API: Refresh dynamic content.
     */
    #[Route('/{id}/api/refresh', name: 'zebranie_api_refresh', methods: ['GET'])]
    public function apiRefresh(ZebranieOddzialu $zebranie): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->zebranieService->canUserManageMeeting($user, $zebranie)) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $awaitingDocuments = $this->zebranieService->getAwaitingDocuments($zebranie, $user);

        return new JsonResponse([
            'awaiting_documents_html' => $this->renderView('zebranie_oddzialu/_awaiting_documents.html.twig', [
                'awaiting_documents' => $awaitingDocuments,
            ]),
            'status_html' => $this->renderView('zebranie_oddzialu/_meeting_status.html.twig', [
                'zebranie_status' => $this->zebranieService->getZebranieStatus($zebranie),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateProgress(ZebranieOddzialu $zebranie): array
    {
        $totalSteps = 5;

        // Step 1: Observer assigned (always completed if we're here)
        $completedSteps = 1;

        // Step 2: Secretary assigned
        if ($zebranie->getProtokolant()) {
            $completedSteps = 2;
        }

        // Step 3: Chairman assigned
        if ($zebranie->getProwadzacy()) {
            $completedSteps = 3;
        }

        // Step 4: Management phase (if meeting is active and has both roles)
        if ($zebranie->isAktywne() && $zebranie->getProtokolant() && $zebranie->getProwadzacy()) {
            $completedSteps = 4;
        }

        // Step 5: Meeting completed
        if ($zebranie->isZakonczone()) {
            $completedSteps = 5;
        }

        return [
            'completed_steps' => $completedSteps,
            'total_steps' => $totalSteps,
            'percentage' => ($completedSteps / $totalSteps) * 100,
        ];
    }

    /**
     * AJAX: Zarządzaj funkcją.
     */
    #[Route('/{id}/ajax/zarzadzaj-funkcja', name: 'zebranie_ajax_zarzadzaj_funkcja', methods: ['POST'])]
    public function ajaxZarzadzajFunkcja(ZebranieOddzialu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!in_array($user, [$zebranie->getProwadzacy(), $zebranie->getProtokolant()])) {
            return new JsonResponse(['error' => 'Brak uprawnień'], 403);
        }

        $funkcja = $request->request->get('funkcja');
        $akcja = (string) $request->request->get('akcja');
        $osobaId = $request->request->get('osoba');

        if (!is_string($funkcja) || !in_array($funkcja, ['przewodniczacy', 'zastepca', 'sekretarz'])) {
            return new JsonResponse(['error' => 'Nieprawidłowa funkcja'], 400);
        }
        try {
            $osoba = $osobaId ? $this->entityManager->getRepository(User::class)->find($osobaId) : null;

            $dokument = match ($funkcja) {
                'przewodniczacy' => $this->zebranieService->zarzadzajPrzewodniczacym($zebranie, $akcja, $osoba, $user),
                'zastepca' => $this->zebranieService->zarzadzajZastepca($zebranie, $akcja, $osoba, $user),
                'sekretarz' => $this->zebranieService->zarzadzajSekretarzemOddzialu($zebranie, $akcja, $osoba, $user),
                default => throw new \InvalidArgumentException('Nieznana funkcja'),
            };

            // Automatycznie podpisz dokument jako prowadzący/protokolant
            $this->dokumentService->signDocument($dokument, $user);

            // Jeśli jest drugi podpisujący (prowadzący lub protokolant)
            $drugiPodpisujacy = $user === $zebranie->getProwadzacy() ? $zebranie->getProtokolant() : $zebranie->getProwadzacy();
            if ($drugiPodpisujacy) {
                $this->dokumentService->signDocument($dokument, $drugiPodpisujacy);
            }

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%s %s pomyślnie',
                    'powolaj' === $akcja ? 'Powołano' : 'Odwołano',
                    $this->getNazweFunkcji($funkcja)
                ),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Pomocnicze: Określ aktualny krok wizarda.
     */
    private function determineCurrentStep(ZebranieOddzialu $zebranie): string
    {
        return $zebranie->getCurrentStep();
    }

    /**
     * Historia zebrań.
     */
    #[Route('/historia', name: 'zebranie_historia')]
    public function historia(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $okreg = $user->getOkreg();

        if (!$okreg) {
            $this->addFlash('error', 'Nie masz przypisanego okręgu');

            return $this->redirectToRoute('dashboard_index');
        }

        // Pobierz statystyki zebrań
        $stats = $this->zebranieRepository->getMeetingStats($okreg);

        // Pobierz zakończone zebrania
        $completedMeetings = $this->zebranieRepository->findCompletedByOkreg($okreg, 20);

        // Pobierz zebrania użytkownika
        $userMeetings = $this->zebranieRepository->findUserMeetings($user, 10);

        return $this->render('zebranie_oddzialu/historia.html.twig', [
            'stats' => $stats,
            'completed_meetings' => $completedMeetings,
            'user_meetings' => $userMeetings,
            'okreg' => $okreg,
        ]);
    }

    /**
     * Szczegóły zakończonego zebrania (archiwum).
     */
    #[Route('/{id}/archiwum', name: 'zebranie_archiwum', requirements: ['id' => '\d+'])]
    public function archiwum(ZebranieOddzialu $zebranie): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź czy zebranie jest zakończone
        if (!$zebranie->isZakonczone()) {
            $this->addFlash('error', 'Zebranie nie jest zakończone');

            return $this->redirectToRoute('zebranie_oddzialu_index');
        }

        // Sprawdź uprawnienia - czy użytkownik ma dostęp do zebrań z tego okręgu
        if ($zebranie->getOddzial()->getOkreg() !== $user->getOkreg() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Nie masz uprawnień do tego zebrania');

            return $this->redirectToRoute('zebranie_oddzialu_index');
        }

        // Pobierz dokumenty związane z zebraniem
        $dokumenty = $this->dokumentRepository->createQueryBuilder('d')
            ->where('d.zebranieOddzialu = :zebranie')
            ->setParameter('zebranie', $zebranie)
            ->orderBy('d.dataUtworzenia', 'ASC')
            ->getQuery()
            ->getResult();

        // Pobierz logi aktywności zebrania
        $activityLog = $this->zebranieService->getMeetingActivityLog($zebranie);

        return $this->render('zebranie_oddzialu/archiwum.html.twig', [
            'zebranie' => $zebranie,
            'dokumenty' => $dokumenty,
            'activity_log' => $activityLog,
        ]);
    }

    /**
     * Pomocnicze: Pobierz obecne stanowiska w oddziale.
     *
     * @return array<string, mixed>
     */
    private function pobierzObecneStanowiska(Oddzial $oddzial): array
    {
        $stanowiska = [
            'przewodniczacy' => null,
            'zastepcy' => [],
            'sekretarz' => null,
        ];

        foreach ($oddzial->getCzlonkowie() as $czlonek) {
            $roles = $czlonek->getRoles();

            if (in_array('ROLE_PRZEWODNICZACY_ODDZIALU', $roles)) {
                $stanowiska['przewodniczacy'] = $czlonek;
            }
            if (in_array('ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU', $roles)) {
                $stanowiska['zastepcy'][] = $czlonek;
            }
            if (in_array('ROLE_SEKRETARZ_ODDZIALU', $roles)) {
                $stanowiska['sekretarz'] = $czlonek;
            }
        }

        return $stanowiska;
    }

    /**
     * Pomocnicze: Nazwa funkcji.
     */
    private function getNazweFunkcji(string $funkcja): string
    {
        return match ($funkcja) {
            'przewodniczacy' => 'Przewodniczący Oddziału',
            'zastepca' => 'Zastępca Przewodniczącego',
            'sekretarz' => 'Sekretarz Oddziału',
            default => 'Nieznana funkcja',
        };
    }

    /**
     * Pomocnicze: Sprawdź czy można powołać kandydata.
     */
    private function validateAppointment(ZebranieOddzialu $zebranie, string $functionType, User $candidate): void
    {
        // Sprawdź czy kandydat należy do oddziału
        if ($candidate->getOddzial() !== $zebranie->getOddzial()) {
            throw new \InvalidArgumentException('Kandydat nie należy do tego oddziału');
        }

        // Protokolant i prowadzący zebrania MOGĄ być powołani na stanowiska w oddziale
        // To są różne role - tymczasowe role zebrania vs stałe funkcje w oddziale
        // Usunięto błędną walidację blokującą te powołania

        $obecneStanowiska = $this->pobierzObecneStanowiska($zebranie->getOddzial());

        // Sprawdź limity dla poszczególnych funkcji
        if ('przewodniczacy' === $functionType) {
            // Zgodnie z ksztalt.txt: Powołanie nowego wymaga odwołania poprzedniego w tym samym przepływie
            if ($obecneStanowiska['przewodniczacy']) {
                // Sprawdź czy w tym zebraniu został już odwołany poprzedni przewodniczący
                $odwolanieWZebraniu = $this->sprawdzCzyOdwolanoWZebraniu($zebranie, 'przewodniczacy', $obecneStanowiska['przewodniczacy']);
                if (!$odwolanieWZebraniu) {
                    throw new \InvalidArgumentException('Aby powołać nowego Przewodniczącego Oddziału, najpierw należy odwołać obecnego w tym samym zebraniu');
                }
            }
        } elseif ('zastepca' === $functionType) {
            if (count($obecneStanowiska['zastepcy']) >= 2) {
                throw new \InvalidArgumentException('Maksymalna liczba Zastępców Przewodniczącego została osiągnięta (2)');
            }
            if (in_array($candidate, $obecneStanowiska['zastepcy'])) {
                throw new \InvalidArgumentException('Ta osoba już pełni funkcję Zastępcy Przewodniczącego');
            }
            if ($obecneStanowiska['przewodniczacy'] && $obecneStanowiska['przewodniczacy'] === $candidate) {
                throw new \InvalidArgumentException('Przewodniczący Oddziału nie może być jednocześnie Zastępcą');
            }
        } elseif ('sekretarz' === $functionType) {
            if ($obecneStanowiska['sekretarz']) {
                // Sprawdź czy w tym zebraniu został już odwołany poprzedni sekretarz
                $odwolanieWZebraniu = $this->sprawdzCzyOdwolanoWZebraniu($zebranie, 'sekretarz', $obecneStanowiska['sekretarz']);
                if (!$odwolanieWZebraniu) {
                    throw new \InvalidArgumentException('Aby powołać nowego Sekretarza Oddziału, najpierw należy odwołać obecnego w tym samym zebraniu');
                }
            }
        }
    }

    /**
     * Sprawdź czy osoba została odwołana z funkcji w tym zebraniu.
     */
    private function sprawdzCzyOdwolanoWZebraniu(ZebranieOddzialu $zebranie, string $funkcja, User $osoba): bool
    {
        $typDokumentu = match ($funkcja) {
            'przewodniczacy' => Dokument::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU,
            'zastepca' => Dokument::TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO,
            'sekretarz' => Dokument::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU,
            default => null,
        };

        if (!$typDokumentu) {
            return false;
        }

        // Sprawdź dokumenty podpisane w tym zebraniu
        foreach ($zebranie->getDokumenty() as $dokument) {
            if ($dokument->getTyp() === $typDokumentu
                && $dokument->getCzlonek() === $osoba
                && Dokument::STATUS_PODPISANY === $dokument->getStatus()) {
                return true;
            }
        }

        return false;
    }
}
