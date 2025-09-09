<?php

namespace App\Controller;

use App\Entity\WystepMedialny;
use App\Form\WystepMedialnyType;
use App\Repository\UserRepository;
use App\Repository\WystepMedialnyRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/wystapienia-medialne')]
#[IsGranted('ROLE_USER')]
class WystepMedialnyController extends AbstractController
{
    #[Route('/', name: 'wystep_medialny_index')]
    public function index(
        Request $request,
        WystepMedialnyRepository $repository,
        PaginatorInterface $paginator,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $queryBuilder = $repository->createQueryBuilder('w')
            ->leftJoin('w.mowcy', 'm')
            ->leftJoin('w.zglaszajacy', 'z')
            ->orderBy('w.dataIGodzina', 'DESC');

        // Kandydaci nie mają dostępu do wystąpień medialnych, członkowie tak
        if ($this->isGranted('ROLE_KANDYDAT_PARTII')) {
            throw $this->createAccessDeniedException('Kandydaci nie mają dostępu do wystąpień medialnych.');
        }

        // Filtrowanie na podstawie uprawnień
        if ($this->isGranted('ROLE_RZECZNIK_PRASOWY')
            || $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_PREZES_PARTII')
            || $this->isGranted('ROLE_WICEPREZES_PARTII')
            || $this->isGranted('ROLE_SEKRETARZ_PARTII')
            || $this->isGranted('ROLE_SKARBNIK_PARTII')) {
            // Zarząd krajowy i admini widzą wszystkie wystąpienia
            // Nie dodajemy dodatkowych warunków
        } elseif ($this->isGranted('ROLE_ZARZAD_OKREGU')) {
            // Zarząd okręgu widzi wystąpienia ze swojego okręgu
            $queryBuilder->leftJoin('w.zglaszajacy', 'zglaszajacy')
                ->andWhere('zglaszajacy.okreg = :okreg')
                ->setParameter('okreg', $user->getOkreg());
        } elseif ($this->isGranted('ROLE_ZARZAD_ODDZIALU')) {
            // Zarząd oddziału widzi wystąpienia ze swojego oddziału
            $queryBuilder->leftJoin('w.zglaszajacy', 'zglaszajacy')
                ->andWhere('zglaszajacy.oddzial = :oddzial')
                ->setParameter('oddzial', $user->getOddzial());
        } elseif ($this->isGranted('ROLE_FUNKCYJNY')) {
            // Inni funkcyjni widzą wystąpienia ze swojego okręgu
            $queryBuilder->leftJoin('w.zglaszajacy', 'zglaszajacy')
                ->andWhere('zglaszajacy.okreg = :okreg')
                ->setParameter('okreg', $user->getOkreg());
        } elseif ($this->isGranted('ROLE_CZLONEK_PARTII')) {
            // Zwykły członek widzi tylko swoje wystąpienia (które zgłosił) lub te w których uczestniczy
            $queryBuilder->andWhere('w.zglaszajacy = :user OR :user MEMBER OF w.mowcy')
                ->setParameter('user', $user);
        } else {
            // Inni użytkownicy (kandydaci, darczyńcy, sympatycy) nie widzą wystąpień medialnych
            $queryBuilder->andWhere('1 = 0');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20,
            [
                'sortFieldWhitelist' => [], // Disable all sorting
                'defaultSortFieldName' => null,
                'defaultSortDirection' => null,
            ]
        );

        return $this->render('wystep_medialny/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/nowy', name: 'wystep_medialny_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, WystepMedialnyRepository $repository, ActivityLogService $activityLogService): Response
    {
        // Kandydaci nie mogą dodawać wystąpień medialnych, członkowie tak
        if ($this->isGranted('ROLE_KANDYDAT_PARTII')) {
            throw $this->createAccessDeniedException('Kandydaci nie mogą dodawać wystąpień medialnych.');
        }

        $wystep = new WystepMedialny();
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        $wystep->setZglaszajacy($currentUser);

        $form = $this->createForm(WystepMedialnyType::class, $wystep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($wystep);
            $entityManager->flush();

            // Log activity
            /** @var int $mediaId */
            $mediaId = $wystep->getId();
            $activityLogService->logMediaAppearance(
                $mediaId,
                $wystep->getNazwaMediaRedakcji() ?: 'Wystąpienie medialne'
            );

            $this->addFlash('success', 'Wystąpienie medialne zostało zgłoszone pomyślnie.');

            return $this->redirectToRoute('wystep_medialny_index');
        }

        // Przygotowanie statystyk dla uprawnionej roli
        $stats = null;
        if ($this->isGranted('ROLE_RZECZNIK_PRASOWY')) {
            $stats = [
                'total_count' => $repository->countAll(),
                'this_month' => $repository->countByDateRange(
                    new \DateTime('first day of this month'),
                    new \DateTime('last day of this month')
                ),
                'upcoming' => $repository->findUpcomingAppearances(),
            ];
        }

        return $this->render('wystep_medialny/new.html.twig', [
            'form' => $form->createView(),
            'stats' => $stats,
        ]);
    }

    #[Route('/statystyki', name: 'wystep_medialny_stats')]
    #[IsGranted('ROLE_RZECZNIK_PRASOWY')]
    public function stats(WystepMedialnyRepository $repository): Response
    {
        $stats = [
            'total_count' => $repository->countAll(),
            'this_month' => $repository->countByDateRange(
                new \DateTime('first day of this month'),
                new \DateTime('last day of this month')
            ),
            'by_medium' => $repository->countByMedium(),
            'recent' => $repository->getRecentAppearances(5),
            'upcoming' => $repository->findUpcomingAppearances(),
        ];

        return $this->render('wystep_medialny/stats.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}', name: 'wystep_medialny_show', requirements: ['id' => '\d+'])]
    public function show(WystepMedialny $wystep): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $wystep);

        return $this->render('wystep_medialny/show.html.twig', [
            'wystep' => $wystep,
        ]);
    }

    #[Route('/{id}/edytuj', name: 'wystep_medialny_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        WystepMedialny $wystep,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('EDIT', $wystep);

        $form = $this->createForm(WystepMedialnyType::class, $wystep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Wystąpienie medialne zostało zaktualizowane.');

            return $this->redirectToRoute('wystep_medialny_show', ['id' => $wystep->getId()]);
        }

        return $this->render('wystep_medialny/edit.html.twig', [
            'wystep' => $wystep,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/usun', name: 'wystep_medialny_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_RZECZNIK_PRASOWY')]
    public function delete(Request $request, WystepMedialny $wystep, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$wystep->getId(), (string) $request->getPayload()->get('_token'))) {
            $entityManager->remove($wystep);
            $entityManager->flush();

            $this->addFlash('success', 'Wystąpienie medialne zostało usunięte.');
        }

        return $this->redirectToRoute('wystep_medialny_index');
    }
}
