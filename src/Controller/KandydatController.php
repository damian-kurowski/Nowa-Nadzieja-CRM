<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CzlonekType;
use App\Repository\DokumentRepository;
use App\Repository\OddzialRepository;
use App\Repository\OkregRepository;
use App\Repository\OpiniaCzlonkaRepository;
use App\Repository\UserRepository;
use App\Service\PaymentStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/kandydaci')]
class KandydatController extends AbstractController
{
    #[Route('/', name: 'kandydat_index')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        OkregRepository $okregRepository,
        OddzialRepository $oddzialRepository,
        PaginatorInterface $paginator,
        PaymentStatusService $paymentStatusService,
    ): Response {
        // Sprawdź uprawnienia - ROLE_FUNKCYJNY lub ROLE_PELNOMOCNIK_STRUKTUR
        if (!$this->isGranted('ROLE_FUNKCYJNY') && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            throw $this->createAccessDeniedException('Brak uprawnień do przeglądania kandydatów');
        }

        $currentUser = $this->getUser();

        // Filtrowanie na podstawie uprawnień - kandydaci
        $queryBuilder = $userRepository->createQueryBuilderForUser($currentUser, 'kandydat');

        // Zaawansowane filtry
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('u.imie LIKE :search OR u.nazwisko LIKE :search OR u.email LIKE :search OR u.telefon LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($imie = $request->query->get('imie')) {
            $queryBuilder->andWhere('u.imie LIKE :imie')
                ->setParameter('imie', '%'.$imie.'%');
        }

        if ($nazwisko = $request->query->get('nazwisko')) {
            $queryBuilder->andWhere('u.nazwisko LIKE :nazwisko')
                ->setParameter('nazwisko', '%'.$nazwisko.'%');
        }

        if ($email = $request->query->get('email')) {
            $queryBuilder->andWhere('u.email LIKE :email')
                ->setParameter('email', '%'.$email.'%');
        }

        if ($telefon = $request->query->get('telefon')) {
            $queryBuilder->andWhere('u.telefon LIKE :telefon')
                ->setParameter('telefon', '%'.$telefon.'%');
        }

        if ($status = $request->query->get('status')) {
            $queryBuilder->andWhere('u.status = :status')
                ->setParameter('status', $status);
        }

        if ($okreg = $request->query->get('okreg')) {
            $queryBuilder->andWhere('u.okreg = :okreg')
                ->setParameter('okreg', $okreg);
        }

        if ($oddzial = $request->query->get('oddzial')) {
            $queryBuilder->andWhere('u.oddzial = :oddzial')
                ->setParameter('oddzial', $oddzial);
        }

        if ($plec = $request->query->get('plec')) {
            $queryBuilder->andWhere('u.plec = :plec')
                ->setParameter('plec', $plec);
        }

        // Filtr postępu rekrutacji (1-6 etapów)
        if ($postep = $request->query->get('postep')) {
            switch ($postep) {
                case '1':
                    $queryBuilder->andWhere('u.dataWypelnienieFormularza IS NOT NULL');
                    break;
                case '2':
                    $queryBuilder->andWhere('u.dataWeryfikacjaDokumentow IS NOT NULL');
                    break;
                case '3':
                    $queryBuilder->andWhere('u.dataRozmowaPrekwalifikacyjna IS NOT NULL');
                    break;
                case '4':
                    $queryBuilder->andWhere('u.dataOpiniaRadyOddzialu IS NOT NULL');
                    break;
                case '5':
                    $queryBuilder->andWhere('u.dataDecyzjaZarzadu IS NOT NULL');
                    break;
                case '6':
                    $queryBuilder->andWhere('u.dataPrzyjecieUroczyste IS NOT NULL');
                    break;
            }
        }

        // Filtr dat zgłoszenia
        if ($dataOd = $request->query->get('data_zgloszenia_od')) {
            // Kandydaci używają dataZlozeniaDeklaracji lub dataRejestracji jako datę zgłoszenia
            $queryBuilder->andWhere('(u.dataZlozeniaDeklaracji >= :dataOd OR (u.dataZlozeniaDeklaracji IS NULL AND u.dataRejestracji >= :dataOd))')
                ->setParameter('dataOd', new \DateTime($dataOd));
        }

        if ($dataDo = $request->query->get('data_zgloszenia_do')) {
            $queryBuilder->andWhere('(u.dataZlozeniaDeklaracji <= :dataDo OR (u.dataZlozeniaDeklaracji IS NULL AND u.dataRejestracji <= :dataDo))')
                ->setParameter('dataDo', new \DateTime($dataDo));
        }

        // Filtr wieku
        if ($wiekOd = $request->query->get('wiek_od')) {
            $dataDoUrodzenia = new \DateTime();
            $dataDoUrodzenia->sub(new \DateInterval('P'.$wiekOd.'Y'));
            $queryBuilder->andWhere('u.dataUrodzenia <= :dataDoUrodzenia')
                ->setParameter('dataDoUrodzenia', $dataDoUrodzenia);
        }

        if ($wiekDo = $request->query->get('wiek_do')) {
            $dataOdUrodzenia = new \DateTime();
            $dataOdUrodzenia->sub(new \DateInterval('P'.((int) $wiekDo + 1).'Y'));
            $queryBuilder->andWhere('u.dataUrodzenia > :dataOdUrodzenia')
                ->setParameter('dataOdUrodzenia', $dataOdUrodzenia);
        }

        // Filtr zatrudnienia
        if ($zatrudnienie = $request->query->get('zatrudnienie_spolki')) {
            switch ($zatrudnienie) {
                case 'miejskie':
                    $queryBuilder->andWhere('u.zatrudnienieSpolkiMiejskie IS NOT NULL AND u.zatrudnienieSpolkiMiejskie != \'\'');
                    break;
                case 'skarbu_panstwa':
                    $queryBuilder->andWhere('u.zatrudnienieSpolkiSkarbuPanstwa IS NOT NULL AND u.zatrudnienieSpolkiSkarbuPanstwa != \'\'');
                    break;
                case 'komunalne':
                    $queryBuilder->andWhere('u.zatrudnienieSpolkiKomunalne IS NOT NULL AND u.zatrudnienieSpolkiKomunalne != \'\'');
                    break;
                case 'ma_zatrudnienie':
                    $queryBuilder->andWhere('(u.zatrudnienieSpolkiMiejskie IS NOT NULL AND u.zatrudnienieSpolkiMiejskie != \'\') OR (u.zatrudnienieSpolkiSkarbuPanstwa IS NOT NULL AND u.zatrudnienieSpolkiSkarbuPanstwa != \'\') OR (u.zatrudnienieSpolkiKomunalne IS NOT NULL AND u.zatrudnienieSpolkiKomunalne != \'\')');
                    break;
                case 'bez_zatrudnienia':
                    $queryBuilder->andWhere('(u.zatrudnienieSpolkiMiejskie IS NULL OR u.zatrudnienieSpolkiMiejskie = \'\') AND (u.zatrudnienieSpolkiSkarbuPanstwa IS NULL OR u.zatrudnienieSpolkiSkarbuPanstwa = \'\') AND (u.zatrudnienieSpolkiKomunalne IS NULL OR u.zatrudnienieSpolkiKomunalne = \'\')');
                    break;
            }
        }

        // Filtr historii wyborów
        if ($historia = $request->query->get('historia_wyborow')) {
            switch ($historia) {
                case 'ma_historie':
                    $queryBuilder->andWhere('u.historiaWyborow IS NOT NULL AND u.historiaWyborow != \'\'');
                    break;
                case 'bez_historii':
                    $queryBuilder->andWhere('u.historiaWyborow IS NULL OR u.historiaWyborow = \'\'');
                    break;
            }
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

        // Pobierz listy do filtrów
        $okregi = $okregRepository->findAll();
        $oddzialy = $oddzialRepository->findAll();
        
        // Oblicz profesjonalne statystyki kandydatów
        $totalCandidates = $userRepository->createQueryBuilderForUser($currentUser, 'kandydat')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
            
        $activeCandidates = $userRepository->createQueryBuilderForUser($currentUser, 'kandydat')
            ->select('COUNT(u.id)')
            ->andWhere('u.status = :status')
            ->setParameter('status', 'aktywny')
            ->getQuery()
            ->getSingleScalarResult();
            
        $readyForAcceptance = $userRepository->createQueryBuilderForUser($currentUser, 'kandydat')
            ->select('COUNT(u.id)')
            ->andWhere('u.dataPrzyjecieUroczyste IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
            
        $withEmployment = $userRepository->createQueryBuilderForUser($currentUser, 'kandydat')
            ->select('COUNT(u.id)')
            ->andWhere('(u.zatrudnienieSpolkiMiejskie IS NOT NULL AND u.zatrudnienieSpolkiMiejskie != \'\') OR (u.zatrudnienieSpolkiSkarbuPanstwa IS NOT NULL AND u.zatrudnienieSpolkiSkarbuPanstwa != \'\') OR (u.zatrudnienieSpolkiKomunalne IS NOT NULL AND u.zatrudnienieSpolkiKomunalne != \'\')')
            ->getQuery()
            ->getSingleScalarResult();
        
        $stats = [
            'total' => $totalCandidates,
            'active' => $activeCandidates,
            'readyForAcceptance' => $readyForAcceptance,
            'withEmployment' => $withEmployment,
        ];

        // Oblicz statusy składek dla wyświetlonych kandydatów
        $paymentStatuses = [];
        foreach ($pagination->getItems() as $kandydat) {
            $paymentStatuses[$kandydat->getId()] = $paymentStatusService->getPaymentStatus($kandydat);
        }
        
        return $this->render('kandydat/index.html.twig', [
            'pagination' => $pagination,
            'okregi' => $okregi,
            'oddzialy' => $oddzialy,
            'stats' => $stats,
            'paymentStatuses' => $paymentStatuses,
        ]);
    }

    #[Route('/{id}', name: 'kandydat_show')]
    public function show(
        User $kandydat, 
        OpiniaCzlonkaRepository $opiniaRepository,
        DokumentRepository $dokumentRepository
    ): Response {
        $opinie = [];

        // Opinie są widoczne tylko dla osób funkcyjnych
        if ($this->isGranted('ROLE_FUNKCYJNY')) {
            $opinie = $opiniaRepository->findOpinieByCzlonek($kandydat);
        }

        // Dokumenty związane z tym kandydatem
        $dokumenty = $dokumentRepository->findForMember($kandydat);

        return $this->render('kandydat/show.html.twig', [
            'kandydat' => $kandydat,
            'opinie' => $opinie,
            'dokumenty' => $dokumenty,
        ]);
    }

    #[Route('/{id}/edytuj', name: 'kandydat_edit')]
    public function edit(Request $request, User $kandydat, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CzlonekType::class, $kandydat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Dane kandydata zostały zaktualizowane.');

            return $this->redirectToRoute('kandydat_show', ['id' => $kandydat->getId()]);
        }

        return $this->render('kandydat/edit.html.twig', [
            'kandydat' => $kandydat,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/akceptuj', name: 'kandydat_accept')]
    public function accept(User $kandydat, EntityManagerInterface $entityManager): Response
    {
        $kandydat->setTypUzytkownika('czlonek');
        $kandydat->setDataPrzyjeciaDoPartii(new \DateTime());
        $entityManager->flush();

        $this->addFlash('success', 'Kandydat został przyjęty jako członek partii.');

        return $this->redirectToRoute('kandydat_show', ['id' => $kandydat->getId()]);
    }

    #[Route('/{id}/odrzuc', name: 'kandydat_reject')]
    public function reject(User $kandydat, EntityManagerInterface $entityManager): Response
    {
        $kandydat->setStatus('odrzucony');
        $entityManager->flush();

        $this->addFlash('warning', 'Kandydatura została odrzucona.');

        return $this->redirectToRoute('kandydat_show', ['id' => $kandydat->getId()]);
    }

    #[Route('/{id}/etap/{etap}/ukoncz', name: 'kandydat_complete_stage', methods: ['POST'])]
    public function completeStage(
        User $kandydat,
        string $etap,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // Sprawdzenie CSRF token
        $data = json_decode($request->getContent(), true);
        if (!$this->isCsrfTokenValid('update_progress', $data['_token'] ?? '')) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        // Sprawdzenie czy user to kandydat
        if ('kandydat' !== $kandydat->getTypUzytkownika()) {
            return new JsonResponse(['success' => false, 'message' => 'Użytkownik nie jest kandydatem'], 400);
        }

        $now = new \DateTime();

        // Aktualizacja odpowiedniego etapu na podstawie parametru
        try {
            switch ($etap) {
                case 'etap_1':
                    $kandydat->setDataWypelnienieFormularza($now);
                    break;
                case 'etap_2':
                    $kandydat->setDataWeryfikacjaDokumentow($now);
                    break;
                case 'etap_3':
                    $kandydat->setDataRozmowaPrekwalifikacyjna($now);
                    break;
                case 'etap_4':
                    $kandydat->setDataOpiniaRadyOddzialu($now);
                    break;
                case 'etap_5':
                    $kandydat->setDataDecyzjaZarzadu($now);
                    break;
                case 'etap_6':
                    $kandydat->setDataPrzyjecieUroczyste($now);
                    break;
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Nieznany etap'], 400);
            }

            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Etap został oznaczony jako ukończony',
                'progress' => $kandydat->getPostepKandydata(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Błąd podczas zapisywania: '.$e->getMessage()], 500);
        }
    }

    #[Route('/export/csv', name: 'kandydat_export_csv')]
    public function exportCsv(
        Request $request,
        UserRepository $userRepository,
    ): Response {
        try {
            $currentUser = $this->getUser();

            // Get the same filtered data as in index
            $queryBuilder = $userRepository->createQueryBuilderForUser($currentUser, 'kandydat');

            // Apply filters (same as in index method)
            if ($search = $request->query->get('search')) {
                $queryBuilder->andWhere('u.imie LIKE :search OR u.nazwisko LIKE :search OR u.email LIKE :search OR u.telefon LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
            }

            if ($status = $request->query->get('status')) {
                $queryBuilder->andWhere('u.status = :status')
                    ->setParameter('status', $status);
            }

            if ($okreg = $request->query->get('okreg')) {
                $queryBuilder->andWhere('u.okreg = :okreg')
                    ->setParameter('okreg', $okreg);
            }

            if ($oddzial = $request->query->get('oddzial')) {
                $queryBuilder->andWhere('u.oddzial = :oddzial')
                    ->setParameter('oddzial', $oddzial);
            }

            // Filter by selected IDs if provided
            if ($selectedIds = $request->query->get('ids')) {
                $idsArray = array_map('intval', explode(',', $selectedIds));
                $queryBuilder->andWhere('u.id IN (:selectedIds)')
                    ->setParameter('selectedIds', $idsArray);
            }

            $kandydaci = $queryBuilder->getQuery()->getResult();

            // Create CSV content
            $csvContent = "\xEF\xBB\xBF"; // UTF-8 BOM

            // Add headers
            $headers = [
                'Imię i nazwisko',
                'Email',
                'Telefon',
                'Okręg',
                'Oddział',
                'Data złożenia',
                'Status',
                'Postęp',
            ];
            $csvContent .= implode(';', $headers)."\n";

            // Add data
            foreach ($kandydaci as $kandydat) {
                $postepPercent = $kandydat->getPostepKandydata();
                $szczegory = $kandydat->getSzczegoryPostepuKandydata();
                $ukonczone = count(array_filter($szczegory, fn ($etap) => $etap['wykonane']));
                $postepText = $ukonczone.'/8 ('.$postepPercent.'%)';

                $row = [
                    $kandydat->getFullName(),
                    $kandydat->getEmail(),
                    $kandydat->getTelefon() ?? '',
                    $kandydat->getOkreg() ? $kandydat->getOkreg()->getNazwa() : '',
                    $kandydat->getOddzial() ? $kandydat->getOddzial()->getNazwa() : '',
                    $kandydat->getDataZlozeniaDeklaracji() ? $kandydat->getDataZlozeniaDeklaracji()->format('d.m.Y') : '',
                    ucfirst($kandydat->getStatus()),
                    $postepText,
                ];
                $csvContent .= implode(';', $row)."\n";
            }

            $response = new Response($csvContent);
            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');

            // Create filename with candidate count info
            $filename = 'kandydaci_'.date('Y-m-d');
            $selectedIds = $request->query->get('ids');
            if ($selectedIds) {
                $filename .= '_wybrani_'.count($kandydaci);
            }
            $filename .= '.csv';

            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

            return $response;
        } catch (\Exception $e) {
            return new Response('Error: '.$e->getMessage(), 500);
        }
    }

    #[Route('/export/excel', name: 'kandydat_export_excel')]
    public function exportExcel(
        Request $request,
        UserRepository $userRepository,
    ): Response {
        try {
            $currentUser = $this->getUser();

            // Get the same filtered data as in index
            $queryBuilder = $userRepository->createQueryBuilderForUser($currentUser, 'kandydat');

            // Apply filters (same as in index method)
            if ($search = $request->query->get('search')) {
                $queryBuilder->andWhere('u.imie LIKE :search OR u.nazwisko LIKE :search OR u.email LIKE :search OR u.telefon LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
            }

            if ($status = $request->query->get('status')) {
                $queryBuilder->andWhere('u.status = :status')
                    ->setParameter('status', $status);
            }

            if ($okreg = $request->query->get('okreg')) {
                $queryBuilder->andWhere('u.okreg = :okreg')
                    ->setParameter('okreg', $okreg);
            }

            if ($oddzial = $request->query->get('oddzial')) {
                $queryBuilder->andWhere('u.oddzial = :oddzial')
                    ->setParameter('oddzial', $oddzial);
            }

            // Filter by selected IDs if provided
            if ($selectedIds = $request->query->get('ids')) {
                $idsArray = array_map('intval', explode(',', $selectedIds));
                $queryBuilder->andWhere('u.id IN (:selectedIds)')
                    ->setParameter('selectedIds', $idsArray);
            }

            $kandydaci = $queryBuilder->getQuery()->getResult();

            // Create simple HTML table for Excel
            $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            $html .= '<head><meta charset="utf-8"><style>td { mso-number-format:\@; }</style></head>';
            $html .= '<body><table border="1">';

            // Headers
            $html .= '<tr>';
            $html .= '<th>Imię i nazwisko</th>';
            $html .= '<th>Email</th>';
            $html .= '<th>Telefon</th>';
            $html .= '<th>Okręg</th>';
            $html .= '<th>Oddział</th>';
            $html .= '<th>Data złożenia</th>';
            $html .= '<th>Status</th>';
            $html .= '<th>Postęp</th>';
            $html .= '</tr>';

            // Data
            foreach ($kandydaci as $kandydat) {
                $postepPercent = $kandydat->getPostepKandydata();
                $szczegory = $kandydat->getSzczegoryPostepuKandydata();
                $ukonczone = count(array_filter($szczegory, fn ($etap) => $etap['wykonane']));
                $postepText = $ukonczone.'/8 ('.$postepPercent.'%)';

                $html .= '<tr>';
                $html .= '<td>'.htmlspecialchars($kandydat->getFullName()).'</td>';
                $html .= '<td>'.htmlspecialchars($kandydat->getEmail()).'</td>';
                $html .= '<td>'.htmlspecialchars($kandydat->getTelefon() ?? '').'</td>';
                $html .= '<td>'.htmlspecialchars($kandydat->getOkreg() ? $kandydat->getOkreg()->getNazwa() : '').'</td>';
                $html .= '<td>'.htmlspecialchars($kandydat->getOddzial() ? $kandydat->getOddzial()->getNazwa() : '').'</td>';
                $html .= '<td>'.($kandydat->getDataZlozeniaDeklaracji() ? $kandydat->getDataZlozeniaDeklaracji()->format('d.m.Y') : '').'</td>';
                $html .= '<td>'.ucfirst($kandydat->getStatus()).'</td>';
                $html .= '<td>'.$postepText.'</td>';
                $html .= '</tr>';
            }

            $html .= '</table></body></html>';

            $response = new Response($html);
            $response->headers->set('Content-Type', 'application/vnd.ms-excel');

            // Create filename with candidate count info
            $filename = 'kandydaci_'.date('Y-m-d');
            $selectedIds = $request->query->get('ids');
            if ($selectedIds) {
                $filename .= '_wybrani_'.count($kandydaci);
            }
            $filename .= '.xls';

            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;
        } catch (\Exception $e) {
            return new Response('Error: '.$e->getMessage(), 500);
        }
    }
}
