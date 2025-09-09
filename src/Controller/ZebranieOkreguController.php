<?php

namespace App\Controller;

use App\Entity\ZebranieOkregu;
use App\Entity\User;
use App\Entity\Okreg;
use App\Repository\ZebranieOkreguRepository;
use App\Repository\UserRepository;
use App\Repository\OkregRepository;
use App\Security\CsrfService;
use App\Service\ZebranieOkreguService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/zebranie-okregu')]
class ZebranieOkreguController extends AbstractController
{
    public function __construct(
        private ZebranieOkreguRepository $zebranieRepository,
        private UserRepository $userRepository,
        private OkregRepository $okregRepository,
        private EntityManagerInterface $entityManager,
        private CsrfService $csrfService,
        private ZebranieOkreguService $zebranieOkreguService,
    ) {
    }

    /**
     * Lista zebrań okręgu (głównie dla sekretarza partii i obserwatorów).
     */
    #[Route('/', name: 'zebranie_okregu_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Sekretarz partii widzi wszystkie zebrania
        if ($user->hasRole('ROLE_SEKRETARZ_PARTII')) {
            $zebrania = $this->zebranieRepository->findAll();
        } else {
            // Inni użytkownicy widzą tylko zebrania w których uczestniczą
            $zebrania = $this->zebranieRepository->findForUser($user);
        }

        $stats = $this->zebranieRepository->getStats();

        return $this->render('zebranie_okregu/index.html.twig', [
            'zebrania' => $zebrania,
            'stats' => $stats,
            'user' => $user,
        ]);
    }

    /**
     * Nowe zebranie okręgu - tylko sekretarz partii może utworzyć.
     */
    #[Route('/new', name: 'zebranie_okregu_new')]
    #[IsGranted('ROLE_SEKRETARZ_PARTII')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $okregId = $request->request->get('okreg_id');
            $obserwatorId = $request->request->get('obserwator_id');

            $okreg = $this->okregRepository->find($okregId);
            $obserwator = $this->userRepository->find($obserwatorId);

            if (!$okreg || !$obserwator) {
                $this->addFlash('error', 'Nieprawidłowe dane okręgu lub obserwatora.');
                return $this->redirectToRoute('zebranie_okregu_new');
            }

            // Sprawdź czy w okręgu nie trwa już zebranie
            if ($this->zebranieRepository->hasActiveForOkreg($okreg)) {
                $this->addFlash('error', 'W tym okręgu trwa już aktywne zebranie.');
                return $this->redirectToRoute('zebranie_okregu_new');
            }

            // Użyj serwisu do utworzenia zebrania z automatycznym nadawaniem ról
            $zebranie = $this->zebranieOkreguService->rozpocznijZebranie($okreg, $obserwator, $this->getUser());

            $this->addFlash('success', 'Zebranie okręgu zostało utworzone. Obserwator otrzymał rolę i może teraz wyznaczyć protokolanta.');

            return $this->redirectToRoute('zebranie_okregu_show', ['id' => $zebranie->getId()]);
        }

        $okregi = $this->okregRepository->findAll();
        $users = $this->userRepository->findAll(); // Obserwatorem może być każdy użytkownik

        return $this->render('zebranie_okregu/new.html.twig', [
            'okregi' => $okregi,
            'users' => $users,
        ]);
    }

    /**
     * Szczegóły zebrania okręgu.
     */
    #[Route('/{id}', name: 'zebranie_okregu_show')]
    public function show(ZebranieOkregu $zebranie): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź czy użytkownik ma dostęp do tego zebrania
        $hasAccess = $user->hasRole('ROLE_SEKRETARZ_PARTII') || 
                    $zebranie->getObserwator() === $user ||
                    $zebranie->getProtokolant() === $user ||
                    $zebranie->getProwadzacy() === $user;

        if (!$hasAccess) {
            throw $this->createAccessDeniedException('Nie masz dostępu do tego zebrania.');
        }

        // Pobierz użytkowników z okręgu dla wyboru funkcji
        $usersInOkreg = $this->userRepository->findBy(['okreg' => $zebranie->getOkreg()]);

        return $this->render('zebranie_okregu/show.html.twig', [
            'zebranie' => $zebranie,
            'usersInOkreg' => $usersInOkreg,
            'user' => $user,
        ]);
    }

    /**
     * Wyznacz protokolanta - tylko obserwator może.
     */
    #[Route('/{id}/wyznacz-protokolanta', name: 'zebranie_okregu_wyznacz_protokolanta', methods: ['POST'])]
    public function wyznaczProtokolanta(ZebranieOkregu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$zebranie->canUserPerformAction($user, 'wyznacz_protokolanta')) {
            return new JsonResponse(['error' => 'Nie możesz wykonać tej akcji.'], 403);
        }

        $protokolantId = $request->request->get('protokolant_id');
        $protokolant = $this->userRepository->find($protokolantId);

        if (!$protokolant) {
            return new JsonResponse(['error' => 'Protokolant nie znaleziony.'], 400);
        }

        // Sprawdź czy protokolant jest z właściwego okręgu
        if ($protokolant->getOkreg() !== $zebranie->getOkreg()) {
            return new JsonResponse(['error' => 'Protokolant musi być z okręgu, którego dotyczy zebranie.'], 400);
        }

        // Użyj serwisu do wyznaczenia protokolanta z automatycznym nadawaniem ról
        $this->zebranieOkreguService->wyznaczProtokolanta($zebranie, $protokolant);

        return new JsonResponse(['success' => true, 'message' => 'Protokolant został wyznaczony.']);
    }

    /**
     * Wyznacz prowadzącego - tylko obserwator może.
     */
    #[Route('/{id}/wyznacz-prowadzacego', name: 'zebranie_okregu_wyznacz_prowadzacego', methods: ['POST'])]
    public function wyznaczProwadzacego(ZebranieOkregu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$zebranie->canUserPerformAction($user, 'wyznacz_prowadzacego')) {
            return new JsonResponse(['error' => 'Nie możesz wykonać tej akcji.'], 403);
        }

        $prowadzacyId = $request->request->get('prowadzacy_id');
        $prowadzacy = $this->userRepository->find($prowadzacyId);

        if (!$prowadzacy) {
            return new JsonResponse(['error' => 'Prowadzący nie znaleziony.'], 400);
        }

        // Sprawdź czy prowadzący jest z właściwego okręgu
        if ($prowadzacy->getOkreg() !== $zebranie->getOkreg()) {
            return new JsonResponse(['error' => 'Prowadzący musi być z okręgu, którego dotyczy zebranie.'], 400);
        }

        // Użyj serwisu do wyznaczenia prowadzącego z automatycznym nadawaniem ról
        $this->zebranieOkreguService->wyznaczProwadzacego($zebranie, $prowadzacy);

        return new JsonResponse(['success' => true, 'message' => 'Prowadzący został wyznaczony. Może rozpocząć wybór zarządu okręgu.']);
    }

    /**
     * Wybór Prezesa Okręgu - krok 4.
     */
    #[Route('/{id}/wybor-prezesa', name: 'zebranie_okregu_wybor_prezesa', methods: ['POST'])]
    public function wyborPrezesa(ZebranieOkregu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$zebranie->canUserPerformAction($user, 'wybor_prezesa')) {
            return new JsonResponse(['error' => 'Nie możesz wykonać tej akcji.'], 403);
        }

        $prezesId = $request->request->get('prezes_okregu_id');
        if (!$prezesId) {
            return new JsonResponse(['error' => 'Prezes musi być wybrany.'], 400);
        }

        $prezes = $this->userRepository->find($prezesId);
        if (!$prezes) {
            return new JsonResponse(['error' => 'Nie znaleziono wybranej osoby.'], 400);
        }

        if ($prezes->getOkreg() !== $zebranie->getOkreg()) {
            return new JsonResponse(['error' => 'Prezes musi być z okręgu, którego dotyczy zebranie.'], 400);
        }

        $this->zebranieOkreguService->wyborPrezesa($zebranie, $prezes);

        return new JsonResponse([
            'success' => true, 
            'message' => 'Prezes Okręgu został wybrany. Przejdź do wyboru Wiceprezesów.',
            'next_step' => $zebranie->getCurrentStepNumber()
        ]);
    }

    /**
     * Wybór Wiceprezesów Okręgu - krok 5.
     */
    #[Route('/{id}/wybor-wiceprezesow', name: 'zebranie_okregu_wybor_wiceprezesow', methods: ['POST'])]
    public function wyborWiceprezesow(ZebranieOkregu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$zebranie->canUserPerformAction($user, 'wybor_wiceprezesow')) {
            return new JsonResponse(['error' => 'Nie możesz wykonać tej akcji.'], 403);
        }

        $wiceprezes1Id = $request->request->get('wiceprezes1_id');
        $wiceprezes2Id = $request->request->get('wiceprezes2_id');

        if (!$wiceprezes1Id || !$wiceprezes2Id) {
            return new JsonResponse(['error' => 'Dwóch wiceprezesów musi być wybranych.'], 400);
        }

        $wiceprezes1 = $this->userRepository->find($wiceprezes1Id);
        $wiceprezes2 = $this->userRepository->find($wiceprezes2Id);

        if (!$wiceprezes1 || !$wiceprezes2) {
            return new JsonResponse(['error' => 'Nie znaleziono wybranych osób.'], 400);
        }

        $expectedOkreg = $zebranie->getOkreg();
        if ($wiceprezes1->getOkreg() !== $expectedOkreg || $wiceprezes2->getOkreg() !== $expectedOkreg) {
            return new JsonResponse(['error' => 'Wiceprezesi muszą być z okręgu, którego dotyczy zebranie.'], 400);
        }

        $this->zebranieOkreguService->wyborWiceprezesow($zebranie, $wiceprezes1, $wiceprezes2);

        return new JsonResponse([
            'success' => true, 
            'message' => 'Wiceprezesi Okręgu zostali wybrani. Oczekuje na akceptację wszystkich uczestników.',
            'next_step' => $zebranie->getCurrentStepNumber()
        ]);
    }

    /**
     * Składa podpis uczestnika zebrania - krok 6.
     */
    #[Route('/{id}/zloz-podpis', name: 'zebranie_okregu_zloz_podpis', methods: ['POST'])]
    public function zlozPodpis(Request $request, ZebranieOkregu $zebranie): JsonResponse
    {
        // Sprawdź CSRF token
        if (!$this->csrfService->validateAjaxToken($request, 'sign_' . $zebranie->getId())) {
            return $this->json(['error' => 'Invalid CSRF token'], 400);
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Sprawdź czy user może składać podpis w tym zebraniu
        $canSign = ($zebranie->getObserwator()->getId() === $user->getId()) ||
                   ($zebranie->getProtokolant() && $zebranie->getProtokolant()->getId() === $user->getId()) ||
                   ($zebranie->getProwadzacy() && $zebranie->getProwadzacy()->getId() === $user->getId());
        
        if (!$canSign) {
            return $this->json(['error' => 'Nie masz uprawnień do składania podpisu w tym zebraniu'], 403);
        }
        
        // Sprawdź czy zebranie jest w stanie składania podpisów
        if ($zebranie->getStatus() !== ZebranieOkregu::STATUS_SKLADANIE_PODPISOW) {
            return $this->json(['error' => 'Zebranie nie jest w stanie umożliwiającym składanie podpisów'], 400);
        }
        
        // Sprawdź czy user już podpisał
        $juzPodpisal = false;
        if ($user === $zebranie->getObserwator() && $zebranie->getObserwatorPodpisal()) {
            $juzPodpisal = true;
        } elseif ($user === $zebranie->getProtokolant() && $zebranie->getProtokolantPodpisal()) {
            $juzPodpisal = true;
        } elseif ($user === $zebranie->getProwadzacy() && $zebranie->getProwadzacyPodpisal()) {
            $juzPodpisal = true;
        }
        
        if ($juzPodpisal) {
            return $this->json(['error' => 'Już złożyłeś podpis w tym zebraniu'], 400);
        }
        
        // Pobierz dane podpisu
        $signatureData = $request->request->get('signature');
        if (!$signatureData) {
            return $this->json(['error' => 'Brak danych podpisu'], 400);
        }
        
        // Waliduj format podpisu (sprawdź czy to prawidłowy base64 image)
        if (!str_starts_with($signatureData, 'data:image/png;base64,')) {
            return $this->json(['error' => 'Nieprawidłowy format podpisu'], 400);
        }
        
        try {
            $this->zebranieOkreguService->zlozPodpis($zebranie, $user, $signatureData);
            
            return $this->json([
                'success' => true,
                'message' => 'Podpis został pomyślnie złożony',
                'wszystcy_podpisali' => $zebranie->czyWszyscyPodpisali(),
                'status' => $zebranie->getStatus(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Wystąpił błąd podczas składania podpisu: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Akceptacja zebrania przez uczestnika - krok 7.
     */
    #[Route('/{id}/akceptuj', name: 'zebranie_okregu_akceptuj', methods: ['POST'])]
    public function akceptujZebranie(ZebranieOkregu $zebranie): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $action = match (true) {
            $user === $zebranie->getObserwator() => 'akceptuj_obserwator',
            $user === $zebranie->getProtokolant() => 'akceptuj_protokolant',
            $user === $zebranie->getProwadzacy() => 'akceptuj_prowadzacy',
            default => null
        };

        if (!$action || !$zebranie->canUserPerformAction($user, $action)) {
            return new JsonResponse(['error' => 'Nie możesz wykonać tej akcji.'], 403);
        }

        $this->zebranieOkreguService->akceptujZebranie($zebranie, $user);

        $message = 'Twoja akceptacja została zapisana.';
        if ($zebranie->czyWszyscyZaakceptowali()) {
            $message .= ' Zebranie zostało zakończone - wszyscy uczestnicy zaakceptowali wyniki.';
        } else {
            $brakujace = [];
            if (!$zebranie->getObserwatorZaakceptowal()) $brakujace[] = 'Obserwator';
            if (!$zebranie->getProtokolantZaakceptowal()) $brakujace[] = 'Protokolant';
            if (!$zebranie->getProwadzacyZaakceptowal()) $brakujace[] = 'Prowadzący';
            $message .= ' Oczekujemy na akceptację: ' . implode(', ', $brakujace);
        }

        return new JsonResponse([
            'success' => true, 
            'message' => $message,
            'completed' => $zebranie->czyWszyscyZaakceptowali(),
            'missing_acceptances' => [
                'obserwator' => !$zebranie->getObserwatorZaakceptowal(),
                'protokolant' => !$zebranie->getProtokolantZaakceptowal(),
                'prowadzacy' => !$zebranie->getProwadzacyZaakceptowal()
            ]
        ]);
    }

    /**
     * Zakończ zebranie.
     */
    #[Route('/{id}/zakoncz', name: 'zebranie_okregu_zakoncz', methods: ['POST'])]
    public function zakonczZebranie(ZebranieOkregu $zebranie): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Tylko obserwator może zakończyć zebranie
        if ($zebranie->getObserwator() !== $user && !$user->hasRole('ROLE_SEKRETARZ_PARTII')) {
            return new JsonResponse(['error' => 'Nie możesz zakończyć tego zebrania.'], 403);
        }

        // Użyj serwisu do zakończenia zebrania z automatycznym usuwaniem ról
        $this->zebranieOkreguService->zakonczZebranie($zebranie);

        return new JsonResponse(['success' => true, 'message' => 'Zebranie zostało zakończone.']);
    }

    /**
     * Anuluj zebranie.
     */
    #[Route('/{id}/anuluj', name: 'zebranie_okregu_anuluj', methods: ['POST'])]
    #[IsGranted('ROLE_SEKRETARZ_PARTII')]
    public function anulujZebranie(ZebranieOkregu $zebranie): JsonResponse
    {
        $zebranie->setStatus(ZebranieOkregu::STATUS_ANULOWANE);
        $zebranie->setDataZakonczenia(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Zebranie zostało anulowane.']);
    }

    /**
     * Historia zebrań dla okręgu - widoczna dla członków danego okręgu.
     */
    #[Route('/historia/{okregId}', name: 'zebranie_okregu_historia')]
    public function historiaZebran(int $okregId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $okreg = $this->okregRepository->find($okregId);
        if (!$okreg) {
            throw $this->createNotFoundException('Okręg nie znaleziony.');
        }

        // Sprawdź czy użytkownik ma dostęp do tego okręgu
        $hasAccess = $user->hasRole('ROLE_SEKRETARZ_PARTII') || 
                    $user->getOkreg() === $okreg;

        if (!$hasAccess) {
            throw $this->createAccessDeniedException('Nie masz dostępu do historii zebrań tego okręgu.');
        }

        // Pobierz historię zebrań okręgu (tylko zakończone)
        $zebrania = $this->zebranieRepository->findBy(
            ['okreg' => $okreg, 'status' => ZebranieOkregu::STATUS_ZAKONCZONE],
            ['dataZakonczenia' => 'DESC']
        );

        return $this->render('zebranie_okregu/historia.html.twig', [
            'okreg' => $okreg,
            'zebrania' => $zebrania,
            'user' => $user,
        ]);
    }

    /**
     * Szczegóły historycznego zebrania.
     */
    #[Route('/historia/{okregId}/{zebranieId}', name: 'zebranie_okregu_historia_szczegoly')]
    public function historiaZebraniaSzczegoly(int $okregId, int $zebranieId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $okreg = $this->okregRepository->find($okregId);
        $zebranie = $this->zebranieRepository->find($zebranieId);
        
        if (!$okreg || !$zebranie || $zebranie->getOkreg() !== $okreg) {
            throw $this->createNotFoundException('Zebranie nie znalezione.');
        }

        // Sprawdź czy użytkownik ma dostęp do tego okręgu
        $hasAccess = $user->hasRole('ROLE_SEKRETARZ_PARTII') || 
                    $user->getOkreg() === $okreg;

        if (!$hasAccess) {
            throw $this->createAccessDeniedException('Nie masz dostępu do tego zebrania.');
        }

        return $this->render('zebranie_okregu/historia_szczegoly.html.twig', [
            'okreg' => $okreg,
            'zebranie' => $zebranie,
            'user' => $user,
        ]);
    }

    /**
     * Podpisuje dokument przez protokolanta lub prowadzącego.
     */
    #[Route('/{id}/podpisz-dokument', name: 'zebranie_okregu_podpisz_dokument', methods: ['POST'])]
    public function podpiszDokument(Request $request, ZebranieOkregu $zebranie): JsonResponse
    {
        // Sprawdź CSRF token
        if (!$this->csrfService->isValidCsrfToken($request, 'sign_document_' . $zebranie->getId())) {
            return $this->json(['error' => 'Invalid CSRF token'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź czy user może podpisywać dokumenty tego zebrania
        $canSign = ($zebranie->getProtokolant() && $zebranie->getProtokolant()->getId() === $user->getId()) ||
                   ($zebranie->getProwadzacy() && $zebranie->getProwadzacy()->getId() === $user->getId());

        if (!$canSign) {
            return $this->json(['error' => 'Nie masz uprawnień do podpisywania dokumentów tego zebrania'], 403);
        }

        // Sprawdź czy zebranie jest w stanie oczekiwania na akceptację
        if ($zebranie->getStatus() !== ZebranieOkregu::STATUS_OCZEKUJE_NA_AKCEPTACJE) {
            return $this->json(['error' => 'Zebranie nie jest w stanie umożliwiającym podpisywanie dokumentów'], 400);
        }

        $dokumentId = $request->request->get('dokument_id');
        if (!$dokumentId) {
            return $this->json(['error' => 'Nie podano ID dokumentu'], 400);
        }

        // Znajdź dokument
        $dokument = null;
        foreach ($zebranie->getDokumenty() as $doc) {
            if ($doc->getId() == $dokumentId) {
                $dokument = $doc;
                break;
            }
        }

        if (!$dokument) {
            return $this->json(['error' => 'Dokument nie znaleziony'], 404);
        }

        // Podpisz dokument
        $result = $this->zebranieOkreguService->podpiszDokument($dokument, $user);

        if (!$result) {
            return $this->json(['error' => 'Nie udało się podpisać dokumentu'], 400);
        }

        // Sprawdź czy wszystkie dokumenty są podpisane
        $allDocumentsSigned = $this->zebranieOkreguService->czyWszystkieDokumentyPodpisane($zebranie);

        return $this->json([
            'success' => true,
            'message' => 'Dokument został podpisany',
            'document_status' => $dokument->getStatus(),
            'protokolant_signed' => $dokument->getProtokolantPodpisal() ? $dokument->getProtokolantPodpisal()->format('Y-m-d H:i:s') : null,
            'prowadzacy_signed' => $dokument->getProwadzacyPodpisal() ? $dokument->getProwadzacyPodpisal()->format('Y-m-d H:i:s') : null,
            'all_documents_signed' => $allDocumentsSigned,
        ]);
    }
}