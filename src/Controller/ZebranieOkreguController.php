<?php

namespace App\Controller;

use App\Entity\ZebranieOkregu;
use App\Entity\User;
use App\Entity\Okreg;
use App\Repository\ZebranieOkreguRepository;
use App\Repository\UserRepository;
use App\Repository\OkregRepository;
use App\Repository\DokumentRepository;
use App\Security\CsrfService;
use App\Service\ZebranieOkreguService;
use App\Service\DokumentService;
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
        private DokumentRepository $dokumentRepository,
        private EntityManagerInterface $entityManager,
        private CsrfService $csrfService,
        private ZebranieOkreguService $zebranieOkreguService,
        private DokumentService $dokumentService,
    ) {
    }

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

    #[Route('/new', name: 'zebranie_okregu_new')]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Tylko Sekretarz Partii lub Prezes Okręgu mogą tworzyć zebrania
        if (!$user->hasRole('ROLE_SEKRETARZ_PARTII') && !$user->hasRole('ROLE_PREZES_OKREGU')) {
            throw $this->createAccessDeniedException('Tylko Sekretarz Partii lub Prezes Okręgu może tworzyć zebrania okręgu.');
        }
        if ($request->isMethod('POST')) {
            $okregId = $request->request->get('okreg_id');
            $obserwatorId = $request->request->get('obserwator_id');

            $okreg = $this->okregRepository->find($okregId);
            $obserwator = $this->userRepository->find($obserwatorId);

            if (!$okreg || !$obserwator) {
                $this->addFlash('error', 'Nieprawidłowe dane okręgu lub obserwatora.');
                return $this->redirectToRoute('zebranie_okregu_new');
            }

            // Sprawdź czy obserwator jest z INNEGO okręgu (neutralny obserwator)
            if ($obserwator->getOkreg() && $obserwator->getOkreg()->getId() === $okreg->getId()) {
                $this->addFlash('error', 'Obserwator zebrania musi być z innego okręgu (neutralny obserwator).');
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

    #[Route('/{id}', name: 'zebranie_okregu_show', requirements: ['id' => '\d+'])]
    public function show(ZebranieOkregu $zebranie): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź czy użytkownik ma dostęp do tego zebrania
        $hasAccess = $user->hasRole('ROLE_SEKRETARZ_PARTII') ||
                    $user->hasRole('ROLE_PREZES_OKREGU') ||
                    ($zebranie->getObserwator() && $zebranie->getObserwator()->getId() === $user->getId()) ||
                    ($zebranie->getProtokolant() && $zebranie->getProtokolant()->getId() === $user->getId()) ||
                    ($zebranie->getProwadzacy() && $zebranie->getProwadzacy()->getId() === $user->getId());

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

    #[Route('/{id}/wyznacz-protokolanta', name: 'zebranie_okregu_wyznacz_protokolanta', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function wyznaczProtokolanta(ZebranieOkregu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('wyznacz_protokolanta_' . $zebranie->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Nieprawidłowy token bezpieczeństwa.'], 403);
        }

        if (!$zebranie->canUserPerformAction($user, 'wyznacz_protokolanta')) {
            return new JsonResponse(['error' => 'Nie możesz wykonać tej akcji.'], 403);
        }

        $protokolantId = $request->request->get('protokolant_id');
        if (!$protokolantId || !is_numeric($protokolantId)) {
            return new JsonResponse(['error' => 'Nieprawidłowy identyfikator protokolanta.'], 400);
        }

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

    #[Route('/{id}/wyznacz-prowadzacego', name: 'zebranie_okregu_wyznacz_prowadzacego', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function wyznaczProwadzacego(ZebranieOkregu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('wyznacz_prowadzacego_' . $zebranie->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Nieprawidłowy token bezpieczeństwa.'], 403);
        }

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

    #[Route('/{id}/wybor-prezesa', name: 'zebranie_okregu_wybor_prezesa', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function wyborPrezesa(ZebranieOkregu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('wybor_prezesa_' . $zebranie->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Nieprawidłowy token bezpieczeństwa.'], 403);
        }

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

    #[Route('/{id}/wybor-wiceprezesow', name: 'zebranie_okregu_wybor_wiceprezesow', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function wyborWiceprezesow(ZebranieOkregu $zebranie, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('wybor_wiceprezesow_' . $zebranie->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Nieprawidłowy token bezpieczeństwa.'], 403);
        }

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
            'message' => 'Wiceprezesi Okręgu zostali wybrani. Przejdź do wyboru Sekretarza Okręgu.',
            'next_step' => $zebranie->getCurrentStepNumber()
        ]);
    }

    #[Route('/{id}/wybor-sekretarza', name: 'zebranie_okregu_wybor_sekretarza', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function wyborSekretarza(ZebranieOkregu $zebranie, Request $request): JsonResponse|Response
    {
        // Redirect GET requests to show page
        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('zebranie_okregu_show', ['id' => $zebranie->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('wybor_sekretarza_' . $zebranie->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Nieprawidłowy token bezpieczeństwa.'], 403);
        }

        if (!$zebranie->canUserPerformAction($user, 'wybor_sekretarza')) {
            return new JsonResponse(['error' => 'Nie możesz wykonać tej akcji.'], 403);
        }

        $sekretarzId = $request->request->get('sekretarz_id');

        if (!$sekretarzId) {
            return new JsonResponse(['error' => 'Sekretarz musi być wybrany.'], 400);
        }

        $sekretarz = $this->userRepository->find($sekretarzId);

        if (!$sekretarz) {
            return new JsonResponse(['error' => 'Nie znaleziono wybranej osoby.'], 400);
        }

        $expectedOkreg = $zebranie->getOkreg();
        if ($sekretarz->getOkreg() !== $expectedOkreg) {
            return new JsonResponse(['error' => 'Sekretarz musi być z okręgu, którego dotyczy zebranie.'], 400);
        }

        $this->zebranieOkreguService->wyborSekretarza($zebranie, $sekretarz);

        return new JsonResponse([
            'success' => true,
            'message' => 'Sekretarz Okręgu został wybrany. Przejdź do wyboru Skarbnika Okręgu.',
            'next_step' => $zebranie->getCurrentStepNumber()
        ]);
    }

    #[Route('/{id}/wybor-skarbnika', name: 'zebranie_okregu_wybor_skarbnika', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function wyborSkarbnika(ZebranieOkregu $zebranie, Request $request): JsonResponse|Response
    {
        // Redirect GET requests to show page
        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('zebranie_okregu_show', ['id' => $zebranie->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('wybor_skarbnika_' . $zebranie->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Nieprawidłowy token bezpieczeństwa.'], 403);
        }

        if (!$zebranie->canUserPerformAction($user, 'wybor_skarbnika')) {
            return new JsonResponse(['error' => 'Nie możesz wykonać tej akcji.'], 403);
        }

        $skarbnikId = $request->request->get('skarbnik_id');

        if (!$skarbnikId) {
            return new JsonResponse(['error' => 'Skarbnik musi być wybrany.'], 400);
        }

        $skarbnik = $this->userRepository->find($skarbnikId);

        if (!$skarbnik) {
            return new JsonResponse(['error' => 'Nie znaleziono wybranej osoby.'], 400);
        }

        $expectedOkreg = $zebranie->getOkreg();
        if ($skarbnik->getOkreg() !== $expectedOkreg) {
            return new JsonResponse(['error' => 'Skarbnik musi być z okręgu, którego dotyczy zebranie.'], 400);
        }

        $this->zebranieOkreguService->wyborSkarbnika($zebranie, $skarbnik);

        return new JsonResponse([
            'success' => true,
            'message' => 'Skarbnik Okręgu został wybrany. Przejdź do składania podpisów.',
            'next_step' => $zebranie->getCurrentStepNumber()
        ]);
    }

    #[Route('/{id}/zloz-podpis', name: 'zebranie_okregu_zloz_podpis', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function zlozPodpis(Request $request, ZebranieOkregu $zebranie): JsonResponse
    {
        return $this->json([
            'error' => 'Endpoint /zloz-podpis jest przestarzały. Dokumenty są teraz podpisywane osobno przez /sign-document/{documentId}',
            'help' => 'Użyj /zebranie-okregu/' . $zebranie->getId() . '/api/pending-documents aby zobaczyć dokumenty do podpisania.'
        ], 410);
    }

    #[Route('/{id}/api/pending-documents', name: 'zebranie_okregu_api_pending_documents', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function apiPendingDocuments(ZebranieOkregu $zebranie): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź czy użytkownik ma dostęp do tego zebrania
        $hasAccess = $user->hasRole('ROLE_SEKRETARZ_PARTII') ||
                    $user->hasRole('ROLE_PREZES_OKREGU') ||
                    ($zebranie->getObserwator() && $zebranie->getObserwator()->getId() === $user->getId()) ||
                    ($zebranie->getProtokolant() && $zebranie->getProtokolant()->getId() === $user->getId()) ||
                    ($zebranie->getProwadzacy() && $zebranie->getProwadzacy()->getId() === $user->getId());

        if (!$hasAccess) {
            return $this->json(['error' => 'Nie masz dostępu do tego zebrania'], 403);
        }

        // Znajdź dokumenty tego zebrania oczekujące na podpis przez tego użytkownika
        $awaitingDocuments = $this->dokumentRepository->createQueryBuilder('d')
            ->leftJoin('d.podpisy', 'p')
            ->where('d.zebranieOkregu = :zebranie')
            ->andWhere('p.podpisujacy = :user')
            ->andWhere('p.status = :status')
            ->setParameter('zebranie', $zebranie)
            ->setParameter('user', $user)
            ->setParameter('status', \App\Entity\PodpisDokumentu::STATUS_OCZEKUJE)
            ->getQuery()
            ->getResult();

        $documents = array_map(fn($doc) => [
            'id' => $doc->getId(),
            'title' => $doc->getTytul(),
            'type' => $doc->getTyp(),
            'created_at' => $doc->getDataUtworzenia()->format('Y-m-d H:i:s'),
            'status' => $doc->getStatus(),
        ], $awaitingDocuments);

        return $this->json([
            'success' => true,
            'documents' => $documents,
        ]);
    }

    #[Route('/{id}/sign-document/{documentId}', name: 'zebranie_okregu_sign_document', requirements: ['id' => '\d+', 'documentId' => '\d+'], methods: ['POST'])]
    public function signDocument(ZebranieOkregu $zebranie, int $documentId, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź token CSRF
        if (!$this->csrfService->validateToken('sign_doc_' . $documentId, $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Nieprawidłowy token bezpieczeństwa.'], 403);
        }

        // Sprawdź dostęp do zebrania
        $hasAccess = $user->hasRole('ROLE_SEKRETARZ_PARTII') ||
                    ($zebranie->getObserwator() && $zebranie->getObserwator()->getId() === $user->getId()) ||
                    ($zebranie->getProtokolant() && $zebranie->getProtokolant()->getId() === $user->getId()) ||
                    ($zebranie->getProwadzacy() && $zebranie->getProwadzacy()->getId() === $user->getId());

        if (!$hasAccess) {
            return $this->json(['success' => false, 'error' => 'Nie masz uprawnień do tego zebrania.'], 403);
        }

        // Znajdź dokument
        $dokument = $this->dokumentRepository->find($documentId);
        if (!$dokument || $dokument->getZebranieOkregu() !== $zebranie) {
            return $this->json(['success' => false, 'error' => 'Dokument nie znaleziony lub nie należy do tego zebrania.'], 404);
        }

        // Pobierz dane podpisu z walidacją
        $signatureData = $request->request->get('signature');
        if ($signatureData) {
            $signatureData = trim(strip_tags($signatureData));
            if (empty($signatureData) || strlen($signatureData) < 10) {
                return $this->json(['success' => false, 'error' => 'Nieprawidłowe dane podpisu.'], 400);
            }
            
            if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $signatureData)) {
                return $this->json(['success' => false, 'error' => 'Nieprawidłowy format podpisu elektronicznego.'], 400);
            }
        }

        try {
            // Podpisz dokument używając DokumentService
            $this->dokumentService->signDocument($dokument, $user, null, $signatureData);

            // Sprawdź czy wszystkie dokumenty zostały w pełni podpisane
            if ($this->areAllDistrictDocumentsFullySigned($zebranie)) {
                $zebranie->setStatus(ZebranieOkregu::STATUS_ZAKONCZONE);
                $zebranie->setDataZakonczenia(new \DateTime());
                $this->entityManager->flush();
            }

            return $this->json([
                'success' => true,
                'message' => 'Dokument został podpisany pomyślnie.',
                'meeting_completed' => $zebranie->getStatus() === ZebranieOkregu::STATUS_ZAKONCZONE
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Wystąpił błąd podczas podpisywania dokumentu: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/akceptuj', name: 'zebranie_okregu_akceptuj', requirements: ['id' => '\d+'], methods: ['POST'])]
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

    #[Route('/{id}/zakoncz', name: 'zebranie_okregu_zakoncz', requirements: ['id' => '\d+'], methods: ['POST'])]
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

    #[Route('/{id}/anuluj', name: 'zebranie_okregu_anuluj', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_PREZES_OKREGU')]
    public function anulujZebranie(ZebranieOkregu $zebranie): JsonResponse
    {
        $zebranie->setStatus(ZebranieOkregu::STATUS_ANULOWANE);
        $zebranie->setDataZakonczenia(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Zebranie zostało anulowane.']);
    }

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

    private function areAllDistrictDocumentsFullySigned(ZebranieOkregu $zebranie): bool
    {
        $dokumenty = $this->dokumentRepository->createQueryBuilder('d')
            ->where('d.zebranieOkregu = :zebranie')
            ->setParameter('zebranie', $zebranie)
            ->getQuery()
            ->getResult();

        if (empty($dokumenty)) {
            return false; // Brak dokumentów - zebranie nie może być zakończone
        }

        foreach ($dokumenty as $dokument) {
            // Sprawdź czy dokument ma wszystkie wymagane podpisy
            $allSignaturesCompleted = true;
            foreach ($dokument->getPodpisy() as $podpis) {
                if ($podpis->getStatus() !== \App\Entity\PodpisDokumentu::STATUS_PODPISANY) {
                    $allSignaturesCompleted = false;
                    break;
                }
            }
            
            if (!$allSignaturesCompleted) {
                return false; // Przynajmniej jeden dokument nie jest w pełni podpisany
            }
        }

        return true; // Wszystkie dokumenty są w pełni podpisane
    }

    #[Route('/{id}/podpisz-dokument', name: 'zebranie_okregu_podpisz_dokument', requirements: ['id' => '\d+'], methods: ['POST'])]
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

        // Jeśli wszystkie dokumenty są podpisane, zakończ zebranie
        if ($allDocumentsSigned && $zebranie->getStatus() !== ZebranieOkregu::STATUS_ZAKONCZONE) {
            $this->zebranieOkreguService->zakonczZebranie($zebranie);
        }

        return $this->json([
            'success' => true,
            'message' => 'Dokument został podpisany',
            'document_status' => $dokument->getStatus(),
            'all_documents_signed' => $allDocumentsSigned,
            'meeting_status' => $zebranie->getStatus(),
            'meeting_completed' => $zebranie->getStatus() === ZebranieOkregu::STATUS_ZAKONCZONE,
        ]);
    }
}