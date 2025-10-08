<?php

namespace App\Controller;

use App\Entity\KonferencjaPrasowa;
use App\Form\KonferencjaPrasowaType;
use App\Repository\KonferencjaPrasowaRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/konferencje-prasowe')]
#[IsGranted('ROLE_USER')]
class KonferencjaPrasowaController extends AbstractController
{
    #[Route('/', name: 'konferencja_prasowa_index')]
    public function index(Request $request, KonferencjaPrasowaRepository $repository, PaginatorInterface $paginator): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $queryBuilder = $repository->createQueryBuilder('k')
            ->select('DISTINCT k')
            ->leftJoin('k.zglaszajacy', 'zglaszajacy')
            ->leftJoin('k.mowcy', 'mowcy')
            ->orderBy('k.dataIGodzina', 'DESC');

        // Kandydaci nie mają dostępu do konferencji prasowych, członkowie tak
        if ($this->isGranted('ROLE_KANDYDAT_PARTII')) {
            throw $this->createAccessDeniedException('Kandydaci nie mają dostępu do konferencji prasowych.');
        }

        // Filtrowanie na podstawie uprawnień
        if ($this->isGranted('ROLE_RZECZNIK_PRASOWY')
            || $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_PREZES_PARTII')
            || $this->isGranted('ROLE_WICEPREZES_PARTII')
            || $this->isGranted('ROLE_SEKRETARZ_PARTII')
            || $this->isGranted('ROLE_SKARBNIK_PARTII')) {
            // Zarząd krajowy i admini widzą wszystkie konferencje
            // Nie dodajemy dodatkowych warunków
        } elseif ($this->isGranted('ROLE_ZARZAD_OKREGU')) {
            // Zarząd okręgu widzi konferencje ze swojego okręgu
            $queryBuilder->andWhere('zglaszajacy.okreg = :okreg')
                ->setParameter('okreg', $user->getOkreg());
        } elseif ($this->isGranted('ROLE_ZARZAD_ODDZIALU')) {
            // Zarząd oddziału widzi konferencje ze swojego oddziału
            $queryBuilder->andWhere('zglaszajacy.oddzial = :oddzial')
                ->setParameter('oddzial', $user->getOddzial());
        } elseif ($this->isGranted('ROLE_FUNKCYJNY')) {
            // Inni funkcyjni widzą konferencje ze swojego okręgu
            $queryBuilder->andWhere('zglaszajacy.okreg = :okreg')
                ->setParameter('okreg', $user->getOkreg());
        } elseif ($this->isGranted('ROLE_CZLONEK_PARTII')) {
            // Zwykły członek widzi tylko swoje konferencje (które zgłosił) lub te w których uczestniczy
            $queryBuilder->andWhere('k.zglaszajacy = :user OR :user MEMBER OF k.mowcy')
                ->setParameter('user', $user);
        } else {
            // Inni użytkownicy (kandydaci, darczyńcy, sympatycy) nie widzą konferencji prasowych
            $queryBuilder->andWhere('1 = 0');
        }

        // Aplikowanie filtrów
        $this->applyFilters($queryBuilder, $request);

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

        return $this->render('konferencja_prasowa/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/nowa', name: 'konferencja_prasowa_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogService $activityLogService): Response
    {
        // Kandydaci nie mogą dodawać konferencji prasowych, członkowie tak
        if ($this->isGranted('ROLE_KANDYDAT_PARTII')) {
            throw $this->createAccessDeniedException('Kandydaci nie mogą dodawać konferencji prasowych.');
        }

        $konferencja = new KonferencjaPrasowa();
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $konferencja->setZglaszajacy($user);

        $form = $this->createForm(KonferencjaPrasowaType::class, $konferencja);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($konferencja);
            $entityManager->flush();

            // Log activity
            /** @var int $conferenceId */
            $conferenceId = $konferencja->getId();
            $activityLogService->logPressConference(
                $conferenceId,
                $konferencja->getTytulKonferencji() ?: 'Konferencja prasowa'
            );

            $this->addFlash('success', 'Konferencja prasowa została dodana.');

            return $this->redirectToRoute('konferencja_prasowa_index');
        }

        return $this->render('konferencja_prasowa/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'konferencja_prasowa_show', requirements: ['id' => '\d+'])]
    public function show(KonferencjaPrasowa $konferencja): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $konferencja);

        return $this->render('konferencja_prasowa/show.html.twig', [
            'konferencja' => $konferencja,
        ]);
    }

    #[Route('/{id}/edytuj', name: 'konferencja_prasowa_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, KonferencjaPrasowa $konferencja, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $konferencja);

        $form = $this->createForm(KonferencjaPrasowaType::class, $konferencja);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Konferencja prasowa została zaktualizowana.');

            return $this->redirectToRoute('konferencja_prasowa_show', ['id' => $konferencja->getId()]);
        }

        return $this->render('konferencja_prasowa/edit.html.twig', [
            'konferencja' => $konferencja,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/usun', name: 'konferencja_prasowa_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, KonferencjaPrasowa $konferencja, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$konferencja->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($konferencja);
            $entityManager->flush();

            $this->addFlash('success', 'Konferencja prasowa została usunięta.');
        }

        return $this->redirectToRoute('konferencja_prasowa_index');
    }

    #[Route('/search-speakers', name: 'konferencja_prasowa_search_speakers', methods: ['GET'])]
    public function searchSpeakers(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $request->query->get('q', '');

        // Require minimum 3 characters
        if (strlen($query) < 3) {
            return $this->json([]);
        }

        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        $conn = $entityManager->getConnection();

        // Build the base query for members with ROLE_CZLONEK_PARTII or higher roles
        $sql = "
            SELECT u.id, u.imie, u.nazwisko, u.email, u.roles FROM \"user\" u
            WHERE (u.roles::jsonb @> '[\"ROLE_CZLONEK_PARTII\"]'::jsonb
                   OR u.roles::jsonb @> '[\"ROLE_FUNKCYJNY\"]'::jsonb
                   OR u.roles::jsonb @> '[\"ROLE_PREZES_PARTII\"]'::jsonb
                   OR u.roles::jsonb @> '[\"ROLE_SEKRETARZ_PARTII\"]'::jsonb)
            AND (LOWER(u.imie) LIKE :query OR LOWER(u.nazwisko) LIKE :query OR LOWER(u.email) LIKE :query)
        ";

        $params = ['query' => '%'.strtolower($query).'%'];

        // Apply restrictions based on user roles
        if ($this->isGranted('ROLE_ZARZAD_KRAJOWY') || $this->isGranted('ROLE_ADMIN')) {
            // Zarząd krajowy i admini widzą wszystkich
        } elseif ($this->isGranted('ROLE_ZARZAD_OKREGU')) {
            if ($currentUser->getOkreg()) {
                $sql .= ' AND (u.okreg_id = :okreg_id OR u.id = :current_user_id)';
                $params['okreg_id'] = $currentUser->getOkreg()->getId();
                $params['current_user_id'] = $currentUser->getId();
            }
        } elseif ($this->isGranted('ROLE_ZARZAD_ODDZIALU')) {
            if ($currentUser->getOddzial()) {
                $sql .= ' AND (u.oddzial_id = :oddzial_id OR u.id = :current_user_id)';
                $params['oddzial_id'] = $currentUser->getOddzial()->getId();
                $params['current_user_id'] = $currentUser->getId();
            }
        } elseif ($this->isGranted('ROLE_FUNKCYJNY') || $this->isGranted('ROLE_CZLONEK_PARTII')) {
            if ($currentUser->getOkreg()) {
                $sql .= ' AND (u.okreg_id = :okreg_id OR u.id = :current_user_id)';
                $params['okreg_id'] = $currentUser->getOkreg()->getId();
                $params['current_user_id'] = $currentUser->getId();
            }
        }

        $sql .= ' ORDER BY u.nazwisko ASC, u.imie ASC LIMIT 50';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery($params);
        $users = $resultSet->fetchAllAssociative();

        // Format results for JSON response
        $results = array_map(function ($user) {
            return [
                'id' => $user['id'],
                'name' => $user['imie'].' '.$user['nazwisko'],
                'email' => $user['email'],
            ];
        }, $users);

        return $this->json($results);
    }

    private function applyFilters(\Doctrine\ORM\QueryBuilder $queryBuilder, Request $request): void
    {
        // Szybkie wyszukiwanie
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('
                k.tytulKonferencji LIKE :search
                OR k.organizatorWydarzenia LIKE :search
                OR k.miejsceWydarzenia LIKE :search
                OR zglaszajacy.imie LIKE :search
                OR zglaszajacy.nazwisko LIKE :search
                OR mowcy.imie LIKE :search
                OR mowcy.nazwisko LIKE :search
            ')
            ->setParameter('search', '%'.$search.'%');
        }

        // Filtrowanie po statusie
        if ($status = $request->query->get('status')) {
            $now = new \DateTime();
            switch ($status) {
                case 'planowana':
                    $queryBuilder->andWhere('k.dataIGodzina > :now')
                        ->setParameter('now', $now);
                    break;
                case 'zakonczona':
                    $queryBuilder->andWhere('k.dataIGodzina < :now')
                        ->setParameter('now', $now);
                    break;
                case 'dzisiaj':
                    $today = new \DateTime('today');
                    $tomorrow = new \DateTime('tomorrow');
                    $queryBuilder->andWhere('k.dataIGodzina >= :today AND k.dataIGodzina < :tomorrow')
                        ->setParameter('today', $today)
                        ->setParameter('tomorrow', $tomorrow);
                    break;
                case 'nadchodzace':
                    $next7Days = new \DateTime('+7 days');
                    $queryBuilder->andWhere('k.dataIGodzina > :now AND k.dataIGodzina <= :next7Days')
                        ->setParameter('now', $now)
                        ->setParameter('next7Days', $next7Days);
                    break;
            }
        }

        // Filtrowanie po organizatorze
        if ($organizator = $request->query->get('organizator')) {
            $queryBuilder->andWhere('k.organizatorWydarzenia LIKE :organizator')
                ->setParameter('organizator', '%'.$organizator.'%');
        }

        // Filtrowanie po tytule
        if ($tytul = $request->query->get('tytul')) {
            $queryBuilder->andWhere('k.tytulKonferencji LIKE :tytul')
                ->setParameter('tytul', '%'.$tytul.'%');
        }

        // Filtrowanie po miejscu
        if ($miejsce = $request->query->get('miejsce')) {
            $queryBuilder->andWhere('k.miejsceWydarzenia LIKE :miejsce')
                ->setParameter('miejsce', '%'.$miejsce.'%');
        }

        // Filtrowanie po zgłaszającym
        if ($zglaszajacy = $request->query->get('zglaszajacy')) {
            $queryBuilder->andWhere('zglaszajacy.imie LIKE :zglaszajacy OR zglaszajacy.nazwisko LIKE :zglaszajacy')
                ->setParameter('zglaszajacy', '%'.$zglaszajacy.'%');
        }

        // Filtrowanie po dacie od
        if ($dataOd = $request->query->get('data_od')) {
            try {
                $dateFrom = new \DateTime($dataOd);
                $queryBuilder->andWhere('k.dataIGodzina >= :dateFrom')
                    ->setParameter('dateFrom', $dateFrom);
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }

        // Filtrowanie po dacie do
        if ($dataDo = $request->query->get('data_do')) {
            try {
                $dateTo = new \DateTime($dataDo.' 23:59:59');
                $queryBuilder->andWhere('k.dataIGodzina <= :dateTo')
                    ->setParameter('dateTo', $dateTo);
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }

        // Filtrowanie po godzinie od
        if ($godzinaOd = $request->query->get('godzina_od')) {
            $queryBuilder->andWhere('TIME(k.dataIGodzina) >= :timeFrom')
                ->setParameter('timeFrom', $godzinaOd);
        }

        // Filtrowanie po godzinie do
        if ($godzinaDo = $request->query->get('godzina_do')) {
            $queryBuilder->andWhere('TIME(k.dataIGodzina) <= :timeTo')
                ->setParameter('timeTo', $godzinaDo);
        }

        // Filtrowanie po mówcy
        if ($mowca = $request->query->get('mowca')) {
            $queryBuilder->andWhere('mowcy.imie LIKE :mowca OR mowcy.nazwisko LIKE :mowca')
                ->setParameter('mowca', '%'.$mowca.'%');
        }

        // Filtrowanie po telefonie
        if ($telefon = $request->query->get('telefon')) {
            $queryBuilder->andWhere('k.numerTelefonu LIKE :telefon')
                ->setParameter('telefon', '%'.$telefon.'%');
        }
    }
}
