<?php

namespace App\Controller;

use App\Entity\Faktura;
use App\Entity\User;
use App\Form\FakturaType;
use App\Repository\FakturaRepository;
use App\Repository\OkregRepository;
use App\Service\ActivityLogService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/faktury')]
#[IsGranted('ROLE_USER')]
class FakturaController extends AbstractController
{
    public function __construct(
        private FakturaRepository $fakturaRepository,
        private OkregRepository $okregRepository,
        private ActivityLogService $activityLogService,
    ) {
    }

    #[Route('/', name: 'faktura_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userRoles = $user->getRoles();
        
        // Sprawdź czy użytkownik może przeglądać faktury
        $canViewFaktury = false;
        $canCreateFaktury = false;
        $isZarzadOkregu = false;
        
        // Skarbnik partii - pełny dostęp
        if (in_array('ROLE_SKARBNIK_PARTII', $userRoles)) {
            $canViewFaktury = true;
            $canCreateFaktury = true;
        }
        // Skarbnik okręgu - może przeglądać i tworzyć faktury ze swojego okręgu
        elseif (in_array('ROLE_SKARBNIK_OKREGU', $userRoles)) {
            $canViewFaktury = true;
            $canCreateFaktury = true;
        }
        // Zarząd okręgu (przewodniczący, zastępca, sekretarz) - może tylko przeglądać faktury ze swojego okręgu
        elseif (in_array('ROLE_PREZES_OKREGU', $userRoles) || 
                in_array('ROLE_WICEPREZES_OKREGU', $userRoles) || 
                in_array('ROLE_SEKRETARZ_OKREGU', $userRoles)) {
            $canViewFaktury = true;
            $isZarzadOkregu = true;
        }
        
        if (!$canViewFaktury) {
            $this->addFlash('error', 'Nie masz uprawnień do przeglądania faktur.');
            return $this->redirectToRoute('dashboard');
        }
        
        // Sprawdź czy to skarbnik partii
        $isSkarbnikPartii = in_array('ROLE_SKARBNIK_PARTII', $userRoles);
        
        // Pobierz filtry z request
        $filters = $this->getFiltersFromRequest($request);
        
        // Pobierz query na podstawie uprawnień użytkownika
        if ($isSkarbnikPartii) {
            $query = $this->fakturaRepository->findForSkarbnikPartii($filters);
        } else {
            // Dla skarbnika okręgu i zarządu okręgu - faktury z ich okręgu
            $query = $this->fakturaRepository->findForOkreg($user->getOkreg(), $filters);
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        // Pobierz statystyki
        $statistics = $this->fakturaRepository->getStatisticsForUser($user);
        
        // Pobierz pilne faktury i oczekujące na akceptację (dla skarbnika partii)
        $urgentFaktury = [];
        $pendingApproval = [];
        
        if ($isSkarbnikPartii) {
            $urgentFaktury = $this->fakturaRepository->findUrgent();
            $pendingApproval = $this->fakturaRepository->findPendingApproval();
        } else {
            // Dla skarbnika okręgu - tylko jego pilne faktury
            $urgentFaktury = $this->fakturaRepository->findBy([
                'skarbnik' => $user,
                'pilnosc' => Faktura::PILNOSC_PILNA,
                'status' => [Faktura::STATUS_WPROWADZONE, Faktura::STATUS_ZAAKCEPTOWANE]
            ], ['dataPlatnosci' => 'ASC']);
        }

        return $this->render('faktura/index.html.twig', [
            'pagination' => $pagination,
            'statistics' => $statistics,
            'urgentFaktury' => $urgentFaktury,
            'pendingApproval' => $pendingApproval,
            'currentFilters' => $filters,
            'isSkarbnikPartii' => $isSkarbnikPartii,
            'canCreateFaktury' => $canCreateFaktury,
            'isZarzadOkregu' => $isZarzadOkregu,
            'statusChoices' => Faktura::getStatusChoices(),
            'kategoriaChoices' => Faktura::getKategoriaChoices(),
            'pilnoscChoices' => Faktura::getPilnoscChoices(),
        ]);
    }

    #[Route('/nowa', name: 'faktura_new')]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Tylko skarbnik okręgu i skarbnik partii mogą tworzyć faktury
        if (!in_array('ROLE_SKARBNIK_OKREGU', $user->getRoles()) && 
            !in_array('ROLE_SKARBNIK_PARTII', $user->getRoles())) {
            $this->addFlash('error', 'Tylko skarbnik okręgu może tworzyć nowe faktury.');
            return $this->redirectToRoute('faktura_index');
        }

        $faktura = new Faktura();
        $faktura->setSkarbnik($user);
        
        if (!$user->getOkreg()) {
            $this->addFlash('error', 'Nie masz przypisanego okręgu. Skontaktuj się z administratorem.');
            return $this->redirectToRoute('faktura_index');
        }
        
        $faktura->setOkreg($user->getOkreg());

        $form = $this->createForm(FakturaType::class, $faktura);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->fakturaRepository->save($faktura, true);

            // Log activity
            $this->activityLogService->log(
                'faktura_create',
                sprintf('Utworzono fakturę %s na kwotę %s PLN', $faktura->getNumerFaktury(), $faktura->getKwota()),
                'Faktura',
                $faktura->getId(),
                ['numer_faktury' => $faktura->getNumerFaktury(), 'kwota' => $faktura->getKwota()],
                $user
            );

            $this->addFlash('success', 'Faktura została utworzona pomyślnie.');

            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }

        return $this->render('faktura/new.html.twig', [
            'form' => $form->createView(),
            'faktura' => $faktura,
        ]);
    }

    #[Route('/{id}', name: 'faktura_show', requirements: ['id' => '\d+'])]
    public function show(Faktura $faktura): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Sprawdź dostęp do faktury
        if (!$this->canAccessFaktura($faktura, $user)) {
            $this->addFlash('error', 'Nie masz dostępu do tej faktury.');
            return $this->redirectToRoute('faktura_index');
        }

        $isSkarbnikPartii = in_array('ROLE_SKARBNIK_PARTII', $user->getRoles());
        $canEdit = $this->canEditFaktura($faktura, $user);
        $canManageStatus = $isSkarbnikPartii && !$faktura->isZrealizowane();

        return $this->render('faktura/show.html.twig', [
            'faktura' => $faktura,
            'canEdit' => $canEdit,
            'canManageStatus' => $canManageStatus,
            'isSkarbnikPartii' => $isSkarbnikPartii,
        ]);
    }

    #[Route('/{id}/edytuj', name: 'faktura_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Faktura $faktura): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->canEditFaktura($faktura, $user)) {
            $this->addFlash('error', 'Nie możesz edytować tej faktury.');
            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }

        $form = $this->createForm(FakturaType::class, $faktura);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->fakturaRepository->save($faktura, true);

            // Log activity
            $this->activityLogService->log(
                'faktura_edit',
                sprintf('Zmodyfikowano fakturę %s', $faktura->getNumerFaktury()),
                'Faktura',
                $faktura->getId(),
                ['numer_faktury' => $faktura->getNumerFaktury()],
                $user
            );

            $this->addFlash('success', 'Faktura została zaktualizowana.');

            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }

        return $this->render('faktura/edit.html.twig', [
            'form' => $form->createView(),
            'faktura' => $faktura,
        ]);
    }

    #[Route('/{id}/kopiuj', name: 'faktura_copy', requirements: ['id' => '\d+'])]
    public function copy(Request $request, Faktura $originalFaktura): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->canAccessFaktura($originalFaktura, $user)) {
            $this->addFlash('error', 'Nie masz dostępu do tej faktury.');
            return $this->redirectToRoute('faktura_index');
        }

        // Utwórz kopię faktury
        $newFaktura = new Faktura();
        $newFaktura->setSkarbnik($user);
        
        if (!$user->getOkreg()) {
            $this->addFlash('error', 'Nie masz przypisanego okręgu. Skontaktuj się z administratorem.');
            return $this->redirectToRoute('faktura_index');
        }
        
        $newFaktura->setOkreg($user->getOkreg());
        $newFaktura->setKwota($originalFaktura->getKwota());
        $newFaktura->setNumerKonta($originalFaktura->getNumerKonta());
        $newFaktura->setCelPlatnosci($originalFaktura->getCelPlatnosci());
        $newFaktura->setKategoria($originalFaktura->getKategoria());
        $newFaktura->setPilnosc($originalFaktura->getPilnosc());
        $newFaktura->setNazwaDostaway($originalFaktura->getNazwaDostaway());
        $newFaktura->setAdresDostaway($originalFaktura->getAdresDostaway());
        
        // Ustaw domyślną datę płatności na dzisiaj + 7 dni
        $newFaktura->setDataPlatnosci(new \DateTime('+7 days'));

        $form = $this->createForm(FakturaType::class, $newFaktura);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->fakturaRepository->save($newFaktura, true);

            // Log activity
            $this->activityLogService->log(
                'faktura_copy',
                sprintf('Skopiowano fakturę %s jako nową fakturę %s', 
                    $originalFaktura->getNumerFaktury(), 
                    $newFaktura->getNumerFaktury()),
                'Faktura',
                $newFaktura->getId(),
                ['original_id' => $originalFaktura->getId(), 'original_numer' => $originalFaktura->getNumerFaktury(), 'new_numer' => $newFaktura->getNumerFaktury()],
                $user
            );

            $this->addFlash('success', 'Faktura została skopiowana pomyślnie.');

            return $this->redirectToRoute('faktura_show', ['id' => $newFaktura->getId()]);
        }

        return $this->render('faktura/copy.html.twig', [
            'form' => $form->createView(),
            'originalFaktura' => $originalFaktura,
            'newFaktura' => $newFaktura,
        ]);
    }

    #[Route('/{id}/akceptuj', name: 'faktura_accept', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function accept(Request $request, Faktura $faktura): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isCsrfTokenValid('accept' . $faktura->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }
        
        if (!$faktura->isWprowadzone()) {
            $this->addFlash('error', 'Można zaakceptować tylko faktury o statusie "Wprowadzone".');
            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }

        $faktura->setStatus(Faktura::STATUS_ZAAKCEPTOWANE);
        $faktura->setDataAkceptacji(new \DateTime());
        $faktura->setSkarbnikPartiiAkceptujacy($user);

        $this->fakturaRepository->save($faktura, true);

        // Log activity
        $this->activityLogService->log(
            'faktura_accept',
            sprintf('Zaakceptowano fakturę %s', $faktura->getNumerFaktury()),
            'Faktura',
            $faktura->getId(),
            ['numer_faktury' => $faktura->getNumerFaktury()],
            $user
        );

        $this->addFlash('success', 'Faktura została zaakceptowana do realizacji.');

        return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
    }

    #[Route('/{id}/odrzuc', name: 'faktura_reject', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function reject(Request $request, Faktura $faktura): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$faktura->isWprowadzone()) {
            $this->addFlash('error', 'Można odrzucić tylko faktury o statusie "Wprowadzone".');
            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }

        if ($request->isMethod('POST')) {
            $uzasadnienie = $request->request->get('uzasadnienie');
            
            if (empty($uzasadnienie)) {
                $this->addFlash('error', 'Uzasadnienie odrzucenia jest wymagane.');
                return $this->redirectToRoute('faktura_reject', ['id' => $faktura->getId()]);
            }

            $faktura->setStatus(Faktura::STATUS_ODRZUCONE);
            $faktura->setUzasadnienieOdrzucenia($uzasadnienie);
            $faktura->setSkarbnikPartiiAkceptujacy($user);

            $this->fakturaRepository->save($faktura, true);

            // Log activity
            $this->activityLogService->log(
                'faktura_reject',
                sprintf('Odrzucono fakturę %s: %s', $faktura->getNumerFaktury(), $uzasadnienie),
                'Faktura',
                $faktura->getId(),
                ['numer_faktury' => $faktura->getNumerFaktury(), 'uzasadnienie' => $uzasadnienie],
                $user
            );

            $this->addFlash('warning', 'Faktura została odrzucona.');

            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }

        return $this->render('faktura/reject.html.twig', [
            'faktura' => $faktura,
        ]);
    }

    #[Route('/{id}/zrealizuj', name: 'faktura_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function complete(Request $request, Faktura $faktura): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isCsrfTokenValid('complete' . $faktura->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }
        
        if (!$faktura->isZaakceptowane()) {
            $this->addFlash('error', 'Można zrealizować tylko zaakceptowane faktury.');
            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }

        $faktura->setStatus(Faktura::STATUS_ZREALIZOWANE);
        $faktura->setDataRealizacji(new \DateTime());

        $this->fakturaRepository->save($faktura, true);

        // Log activity
        $this->activityLogService->log(
            'faktura_complete',
            sprintf('Zrealizowano fakturę %s', $faktura->getNumerFaktury()),
            'Faktura',
            $faktura->getId(),
            ['numer_faktury' => $faktura->getNumerFaktury()],
            $user
        );

        $this->addFlash('success', 'Faktura została oznaczona jako zrealizowana.');

        return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
    }

    #[Route('/{id}/usun', name: 'faktura_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Faktura $faktura): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->canDeleteFaktura($faktura, $user)) {
            $this->addFlash('error', 'Nie możesz usunąć tej faktury.');
            return $this->redirectToRoute('faktura_show', ['id' => $faktura->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $faktura->getId(), $request->request->get('_token'))) {
            $numerFaktury = $faktura->getNumerFaktury();
            $this->fakturaRepository->remove($faktura, true);

            // Log activity
            $this->activityLogService->log(
                'faktura_delete',
                sprintf('Usunięto fakturę %s', $numerFaktury),
                'Faktura',
                $faktura->getId(),
                ['numer_faktury' => $numerFaktury],
                $user
            );

            $this->addFlash('success', 'Faktura została usunięta.');
        }

        return $this->redirectToRoute('faktura_index');
    }

    // Helper methods
    private function canAccessFaktura(Faktura $faktura, User $user): bool
    {
        $userRoles = $user->getRoles();
        
        // Skarbnik partii ma dostęp do wszystkich faktur
        if (in_array('ROLE_SKARBNIK_PARTII', $userRoles)) {
            return true;
        }
        
        // Skarbnik okręgu ma dostęp do faktur ze swojego okręgu
        if (in_array('ROLE_SKARBNIK_OKREGU', $userRoles)) {
            return $faktura->getOkreg() === $user->getOkreg();
        }
        
        // Zarząd okręgu (przewodniczący, zastępca, sekretarz) ma dostęp do faktur ze swojego okręgu
        if (in_array('ROLE_PREZES_OKREGU', $userRoles) || 
            in_array('ROLE_WICEPREZES_OKREGU', $userRoles) || 
            in_array('ROLE_SEKRETARZ_OKREGU', $userRoles)) {
            return $faktura->getOkreg() === $user->getOkreg();
        }
        
        return false;
    }

    private function canEditFaktura(Faktura $faktura, User $user): bool
    {
        // Można edytować tylko wprowadzone faktury
        if (!$faktura->isWprowadzone()) {
            return false;
        }
        
        // Tylko twórca faktury może ją edytować
        return $faktura->getSkarbnik() === $user;
    }

    private function canDeleteFaktura(Faktura $faktura, User $user): bool
    {
        // Można usunąć tylko wprowadzone lub odrzucone faktury
        if (!$faktura->isWprowadzone() && !$faktura->isOdrzucone()) {
            return false;
        }
        
        // Tylko twórca faktury może ją usunąć
        return $faktura->getSkarbnik() === $user;
    }

    private function getFiltersFromRequest(Request $request): array
    {
        return [
            'status' => $request->query->get('status'),
            'kategoria' => $request->query->get('kategoria'),
            'pilnosc' => $request->query->get('pilnosc'),
            'data_od' => $request->query->get('data_od'),
            'data_do' => $request->query->get('data_do'),
            'kwota_od' => $request->query->get('kwota_od'),
            'kwota_do' => $request->query->get('kwota_do'),
            'numer_faktury' => $request->query->get('numer_faktury'),
            'okreg_id' => $request->query->get('okreg_id'),
            'sortuj_po' => $request->query->get('sortuj_po', 'data_utworzenia'),
            'kierunek' => $request->query->get('kierunek', 'desc'),
        ];
    }
}