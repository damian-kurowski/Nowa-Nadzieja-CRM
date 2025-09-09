<?php

namespace App\Controller;

use App\Entity\Dokument;
use App\Entity\User;
use App\Form\PodpisDokumentuType;
use App\Repository\DokumentRepository;
use App\Service\ActivityLogService;
use App\Service\DokumentService;
use App\Service\PdfService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dokumenty')]
#[IsGranted('ROLE_USER')]
class DokumentController extends AbstractController
{
    public function __construct(
        private DokumentService $dokumentService,
        private DokumentRepository $dokumentRepository,
        private PdfService $pdfService,
        private ActivityLogService $activityLogService,
    ) {
    }

    /**
     * Sprawdza czy użytkownik ma dostęp do dokumentu.
     */
    private function hasDocumentAccess(Dokument $dokument, User $user): bool
    {
        $userRoles = $user->getRoles();

        // Admin i zarząd krajowy mają dostęp do wszystkich dokumentów
        if (in_array('ROLE_ADMIN', $userRoles) || in_array('ROLE_ZARZAD_KRAJOWY', $userRoles)) {
            return true;
        }

        // Twórca dokumentu ma zawsze dostęp do swojego dokumentu
        if ($dokument->getTworca() === $user) {
            return true;
        }

        // Osoby, których dotyczy dokument (kandydat lub członek), mają zawsze dostęp
        if ($dokument->getKandydat() === $user || $dokument->getCzlonek() === $user) {
            return true;
        }

        // Osoby które mogą podpisywać dokument mają dostęp
        if ($dokument->canUserSign($user)) {
            return true;
        }

        // Zarząd okręgu ma dostęp do dokumentów ze swojego okręgu
        if ($dokument->getOkreg() === $user->getOkreg()) {
            $okregRoles = ['ROLE_PREZES_OKREGU', 'ROLE_WICEPREZES_OKREGU', 'ROLE_SEKRETARZ_OKREGU', 'ROLE_SKARBNIK_OKREGU'];
            foreach ($okregRoles as $role) {
                if (in_array($role, $userRoles)) {
                    return true;
                }
            }

            // Zwykli członkowie mają dostęp tylko do podpisanych dokumentów ze swojego okręgu
            return Dokument::STATUS_PODPISANY === $dokument->getStatus();
        }

        return false;
    }

    #[Route('/', name: 'dokument_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Kandydaci nie mają dostępu do dokumentów
        if ($this->isGranted('ROLE_KANDYDAT_PARTII')) {
            throw $this->createAccessDeniedException('Kandydaci nie mają dostępu do dokumentów.');
        }

        $status = $request->query->get('status');
        $myDocuments = $request->query->get('my_documents', null);

        // Sprawdź czy użytkownik chce widzieć tylko swoje dokumenty
        if (null !== $myDocuments || ($this->isGranted('ROLE_CZLONEK_PARTII') && !$this->isGranted('ROLE_FUNKCYJNY'))) {
            $documentsQuery = $this->dokumentRepository->findForMember($user, $status);
        } else {
            $documentsQuery = $this->dokumentRepository->findForUserDistrict($user, $status);
        }

        $pagination = $paginator->paginate(
            $documentsQuery,
            $request->query->getInt('page', 1),
            20,
            [
                'sortFieldWhitelist' => [], // Disable all sorting
                'defaultSortFieldName' => null,
                'defaultSortDirection' => null,
            ]
        );

        // Statystyki
        $stats = $this->dokumentService->getDocumentStats($user);

        // Dokumenty oczekujące na podpis użytkownika
        $awaitingMySignature = $this->dokumentRepository->findAwaitingUserSignature($user);

        // Sprawdź czy admin/zarząd krajowy widzi globalne dokumenty
        $userRoles = $user->getRoles();
        $isGlobalView = in_array('ROLE_ADMIN', $userRoles) || in_array('ROLE_ZARZAD_KRAJOWY', $userRoles);

        // Sprawdź czy użytkownik może przełączać widok
        $canToggleView = $this->isGranted('ROLE_FUNKCYJNY') || $this->isGranted('ROLE_ADMIN') || in_array('ROLE_ZARZAD_KRAJOWY', $userRoles);

        return $this->render('dokument/index.html.twig', [
            'pagination' => $pagination,
            'stats' => $stats,
            'awaitingMySignature' => $awaitingMySignature,
            'currentStatus' => $status,
            'isGlobalView' => $isGlobalView,
            'myDocuments' => (bool) $myDocuments,
            'canToggleView' => $canToggleView,
        ]);
    }

    #[Route('/nowy', name: 'dokument_new')]
    public function new(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Pobierz dostępne typy dokumentów na podstawie uprawnień użytkownika
        $availableTypes = $this->dokumentService->getAvailableDocumentTypes($user);

        if (empty($availableTypes)) {
            $this->addFlash('info', 'Nie masz uprawnień do tworzenia żadnych dokumentów.');

            return $this->redirectToRoute('dokument_index');
        }

        return $this->render('dokument/new.html.twig', [
            'availableTypes' => $availableTypes,
        ]);
    }

    #[Route('/nowy/{type}', name: 'dokument_create', requirements: ['type' => '[a-z_]+'])]
    public function create(string $type, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź czy użytkownik może tworzyć ten typ dokumentu
        if (!$this->dokumentService->canCreateDocumentType($user, $type)) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do tworzenia tego typu dokumentu.');
        }

        // Pobierz definicję typu dokumentu
        $documentDefinition = $this->dokumentService->getDocumentDefinition($type);

        if (!$documentDefinition) {
            throw $this->createNotFoundException('Nieznany typ dokumentu.');
        }

        // Utwórz formularz na podstawie definicji
        $form = $this->dokumentService->createDocumentForm($type, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dokument = $this->dokumentService->createDocument($type, $form->getData(), $user);

                // Log activity
                $this->activityLogService->logDocumentCreate(
                    $dokument->getId() ?? 0,
                    $dokument->getTytul(),
                    $user
                );

                $this->addFlash('success', 'Dokument został utworzony pomyślnie.');

                return $this->redirectToRoute('dokument_show', ['id' => $dokument->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd podczas tworzenia dokumentu: '.$e->getMessage());
            }
        }

        return $this->render('dokument/create.html.twig', [
            'form' => $form->createView(),
            'documentDefinition' => $documentDefinition,
            'type' => $type,
        ]);
    }

    #[Route('/{id}', name: 'dokument_show', requirements: ['id' => '\d+'])]
    public function show(Dokument $dokument): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź dostęp do dokumentu
        if (!$this->hasDocumentAccess($dokument, $user)) {
            throw $this->createAccessDeniedException('Nie masz dostępu do tego dokumentu.');
        }

        // Check if user can sign this document
        $userSignature = $dokument->getUserSignature($user);
        $canSign = $userSignature && $userSignature->isPending()
                   && Dokument::STATUS_CZEKA_NA_PODPIS === $dokument->getStatus();

        // Check if this is admin view only
        $isViewOnlyForAdmin = !$userSignature
                              && in_array('ROLE_ADMIN', $user->getRoles());

        // Calculate signature progress
        $totalSignatures = $dokument->getPodpisy()->count();
        $signedCount = 0;
        foreach ($dokument->getPodpisy() as $podpis) {
            if ($podpis->isSigned()) {
                ++$signedCount;
            }
        }
        $progressPercent = $totalSignatures > 0 ? round(($signedCount / $totalSignatures) * 100) : 0;

        return $this->render('dokument/show.html.twig', [
            'dokument' => $dokument,
            'isIntegrityValid' => $dokument->verifyHash(),
            'canSign' => $canSign,
            'isViewOnlyForAdmin' => $isViewOnlyForAdmin,
            'userSignature' => $userSignature,
            'signedCount' => $signedCount,
            'progressPercent' => $progressPercent,
        ]);
    }

    #[Route('/{id}/podpisz', name: 'dokument_sign', requirements: ['id' => '\d+'])]
    public function sign(Dokument $dokument, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź czy użytkownik może podpisać dokument
        if (!$dokument->canUserSign($user)) {
            throw $this->createAccessDeniedException('Nie możesz podpisać tego dokumentu.');
        }

        $form = $this->createForm(PodpisDokumentuType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $podpisElektroniczny = $form->get('podpisElektroniczny')->getData();
                $komentarz = $form->get('komentarz')->getData();
                
                $this->dokumentService->signDocument($dokument, $user, $komentarz, $podpisElektroniczny);

                // Log activity
                $this->activityLogService->logDocumentSign(
                    $dokument->getId() ?? 0,
                    $dokument->getTytul(),
                    $user
                );

                $this->addFlash('success', 'Dokument został podpisany pomyślnie.');

                return $this->redirectToRoute('dokument_show', ['id' => $dokument->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd podczas podpisywania dokumentu: '.$e->getMessage());
            }
        }

        return $this->render('dokument/sign.html.twig', [
            'dokument' => $dokument,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/odrzuc', name: 'dokument_reject', requirements: ['id' => '\d+'])]
    public function reject(Dokument $dokument, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź czy użytkownik może odrzucić dokument
        if (!$dokument->canUserSign($user)) {
            throw $this->createAccessDeniedException('Nie możesz odrzucić tego dokumentu.');
        }

        $form = $this->createForm(PodpisDokumentuType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->dokumentService->rejectDocument($dokument, $user, $form->get('komentarz')->getData());

                // Log activity
                $this->activityLogService->logDocumentReject(
                    $dokument->getId() ?? 0,
                    $dokument->getTytul(),
                    $user
                );

                $this->addFlash('warning', 'Dokument został odrzucony.');

                return $this->redirectToRoute('dokument_show', ['id' => $dokument->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd podczas odrzucania dokumentu: '.$e->getMessage());
            }
        }

        return $this->render('dokument/reject.html.twig', [
            'dokument' => $dokument,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/pdf', name: 'dokument_pdf', requirements: ['id' => '\d+'])]
    public function generatePdf(Dokument $dokument): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sprawdź dostęp do dokumentu
        if (!$this->hasDocumentAccess($dokument, $user)) {
            throw $this->createAccessDeniedException('Nie masz dostępu do tego dokumentu.');
        }

        // Tylko podpisane dokumenty mogą być pobrane jako PDF
        if (Dokument::STATUS_PODPISANY !== $dokument->getStatus()) {
            throw $this->createAccessDeniedException('Można pobrać tylko podpisane dokumenty.');
        }

        try {
            $pdfContent = $this->pdfService->generateDocumentPdf($dokument);

            // Log activity
            $this->activityLogService->logDocumentDownload(
                $dokument->getId() ?? 0,
                $dokument->getTytul(),
                $user
            );

            $filename = sprintf('%s_%s.pdf',
                $dokument->getNumerDokumentu(),
                date('Y-m-d', $dokument->getDataPodpisania()?->getTimestamp() ?? time())
            );

            return new Response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd podczas generowania PDF: '.$e->getMessage());

            return $this->redirectToRoute('dokument_show', ['id' => $dokument->getId()]);
        }
    }
}
