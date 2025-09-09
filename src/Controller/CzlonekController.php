<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CzlonekType;
use App\Form\PhotoUploadType;
use App\Repository\DokumentRepository;
use App\Repository\KonferencjaPrasowaRepository;
use App\Repository\OddzialRepository;
use App\Repository\OkregRepository;
use App\Repository\OpiniaCzlonkaRepository;
use App\Repository\UserRepository;
use App\Repository\WystepMedialnyRepository;
use App\Service\PaymentStatusService;
use App\Service\PhotoService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/czlonkowie')]
class CzlonekController extends AbstractController
{
    #[Route('/', name: 'czlonek_index')]
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
            throw $this->createAccessDeniedException('Brak uprawnień do przeglądania członków');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Filtrowanie na podstawie uprawnień
        $queryBuilder = $userRepository->createQueryBuilderForUser($currentUser, 'czlonek');

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

        // Zawsze filtruj tylko członków - ignoruj parametr typ z URL
        // Inne typy mają swoje osobne sekcje

        if ($okreg = $request->query->get('okreg')) {
            $queryBuilder->andWhere('u.okreg = :okreg')
                ->setParameter('okreg', $okreg);
        }

        if ($oddzial = $request->query->get('oddzial')) {
            $queryBuilder->andWhere('u.oddzial = :oddzial')
                ->setParameter('oddzial', $oddzial);
        }

        // Filtr składki - używamy pól aktualizowanych przez PaymentStatusService
        if ($skladka = $request->query->get('skladka')) {
            $currentDate = new \DateTime();
            
            switch ($skladka) {
                case 'oplacona':
                    // Członkowie z ważną składką (data ważności >= dziś)
                    $queryBuilder->andWhere('u.dataWaznosciSkladki >= :current_date')
                        ->setParameter('current_date', $currentDate->format('Y-m-d'));
                    break;
                    
                case 'nieoplacona':
                    // Członkowie bez ważnej składki (data ważności < dziś lub NULL)
                    $queryBuilder->andWhere('u.dataWaznosciSkladki < :current_date OR u.dataWaznosciSkladki IS NULL')
                        ->setParameter('current_date', $currentDate->format('Y-m-d'));
                    break;
                    
                case 'przeterminowana':
                    // Członkowie z zaległościami (data ważności < 3 miesiące temu lub NULL)
                    $threeMonthsAgo = (clone $currentDate)->modify('-3 months');
                    $queryBuilder->andWhere('u.dataWaznosciSkladki < :three_months_ago OR u.dataWaznosciSkladki IS NULL')
                        ->setParameter('three_months_ago', $threeMonthsAgo->format('Y-m-d'));
                    break;
            }
        }

        // Filtr dat przyjęcia
        if ($dataOd = $request->query->get('data_przyjecia_od')) {
            $queryBuilder->andWhere('u.dataPrzyjeciaDoPartii >= :dataOd')
                ->setParameter('dataOd', new \DateTime($dataOd));
        }

        if ($dataDo = $request->query->get('data_przyjecia_do')) {
            $queryBuilder->andWhere('u.dataPrzyjeciaDoPartii <= :dataDo')
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
        
        // Oblicz profesjonalne statystyki
        $totalMembers = $userRepository->createQueryBuilderForUser($currentUser, 'czlonek')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
            
        $activeMembers = $userRepository->createQueryBuilderForUser($currentUser, 'czlonek')
            ->select('COUNT(u.id)')
            ->andWhere('u.status = :status')
            ->setParameter('status', 'aktywny')
            ->getQuery()
            ->getSingleScalarResult();
            
        $paidMemberships = $userRepository->createQueryBuilderForUser($currentUser, 'czlonek')
            ->select('COUNT(u.id)')
            ->andWhere('u.skladkaOplacona = true')
            ->getQuery()
            ->getSingleScalarResult();
            
        $withEmployment = $userRepository->createQueryBuilderForUser($currentUser, 'czlonek')
            ->select('COUNT(u.id)')
            ->andWhere('(u.zatrudnienieSpolkiMiejskie IS NOT NULL AND u.zatrudnienieSpolkiMiejskie != \'\') OR (u.zatrudnienieSpolkiSkarbuPanstwa IS NOT NULL AND u.zatrudnienieSpolkiSkarbuPanstwa != \'\') OR (u.zatrudnienieSpolkiKomunalne IS NOT NULL AND u.zatrudnienieSpolkiKomunalne != \'\')')
            ->getQuery()
            ->getSingleScalarResult();
        
        $stats = [
            'total' => $totalMembers,
            'active' => $activeMembers,
            'paidMemberships' => $paidMemberships,
            'withEmployment' => $withEmployment,
        ];

        // Oblicz statusy składek dla wyświetlonych użytkowników
        $paymentStatuses = [];
        foreach ($pagination->getItems() as $user) {
            $paymentStatuses[$user->getId()] = $paymentStatusService->getPaymentStatus($user);
        }
        
        return $this->render('czlonek/index.html.twig', [
            'pagination' => $pagination,
            'okregi' => $okregi,
            'oddzialy' => $oddzialy,
            'stats' => $stats,
            'paymentStatuses' => $paymentStatuses,
        ]);
    }

    #[Route('/{id}', name: 'czlonek_show')]
    public function show(
        User $czlonek, 
        OpiniaCzlonkaRepository $opiniaRepository,
        DokumentRepository $dokumentRepository,
        KonferencjaPrasowaRepository $konferencjaRepository,
        WystepMedialnyRepository $wystepRepository,
        PaymentStatusService $paymentStatusService
    ): Response {
        $opinie = [];

        // Opinie są widoczne tylko dla osób funkcyjnych
        if ($this->isGranted('ROLE_FUNKCYJNY')) {
            $opinie = $opiniaRepository->findOpinieByCzlonek($czlonek);
        }

        // Dokumenty związane z tym członkiem
        $dokumenty = $dokumentRepository->findForMember($czlonek);
        
        // Konferencje prasowe gdzie był/jest mówcą
        $konferencje = $konferencjaRepository->findForSpeaker($czlonek);
        
        // Wystąpienia medialne gdzie był/jest mówcą
        $wystepiaMedialne = $wystepRepository->findForSpeaker($czlonek);

        // Pobierz status składek z nową logiką
        $paymentStatus = null;
        if ($czlonek->getTypUzytkownika() === 'czlonek' || $czlonek->getTypUzytkownika() === 'kandydat') {
            $paymentStatus = $paymentStatusService->getPaymentStatus($czlonek);
        }

        return $this->render('czlonek/show.html.twig', [
            'czlonek' => $czlonek,
            'opinie' => $opinie,
            'dokumenty' => $dokumenty,
            'konferencje' => $konferencje,
            'wystepiaMedialne' => $wystepiaMedialne,
            'paymentStatus' => $paymentStatus,
        ]);
    }

    #[Route('/{id}/edytuj', name: 'czlonek_edit')]
    public function edit(Request $request, User $czlonek, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CzlonekType::class, $czlonek);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Dane członka zostały zaktualizowane.');

            return $this->redirectToRoute('czlonek_show', ['id' => $czlonek->getId()]);
        }

        return $this->render('czlonek/edit.html.twig', [
            'czlonek' => $czlonek,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/zdjecie', name: 'czlonek_photo', requirements: ['id' => '\d+'])]
    public function uploadPhoto(
        Request $request,
        User $czlonek,
        EntityManagerInterface $entityManager,
        PhotoService $photoService,
    ): Response {
        $form = $this->createForm(PhotoUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                try {
                    $photoFileName = $photoService->upload($photoFile, $czlonek->getZdjecie());
                    $czlonek->setZdjecie($photoFileName);
                    $entityManager->flush();

                    $this->addFlash('success', 'Zdjęcie zostało wgrane i przycięte do rozmiaru 500x500px.');

                    return $this->redirectToRoute('czlonek_show', ['id' => $czlonek->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Wystąpił błąd podczas wgrywania zdjęcia: '.$e->getMessage());
                }
            }
        }

        return $this->render('czlonek/photo.html.twig', [
            'czlonek' => $czlonek,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/usun-zdjecie', name: 'czlonek_delete_photo', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deletePhoto(
        User $czlonek,
        EntityManagerInterface $entityManager,
        PhotoService $photoService,
    ): Response {
        if ($czlonek->getZdjecie()) {
            $photoService->deletePhoto($czlonek->getZdjecie());
            $czlonek->setZdjecie(null);
            $entityManager->flush();

            $this->addFlash('success', 'Zdjęcie zostało usunięte.');
        }

        return $this->redirectToRoute('czlonek_show', ['id' => $czlonek->getId()]);
    }

    #[Route('/{id}/edit-field', name: 'czlonek_edit_field', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editField(
        Request $request,
        User $czlonek,
        EntityManagerInterface $entityManager,
    ): Response {
        // Check if request is AJAX
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;

        if (!$field) {
            return $this->json(['success' => false, 'message' => 'Field not specified'], 400);
        }

        try {
            switch ($field) {
                case 'name':
                    $parts = explode(' ', $value, 2);
                    $czlonek->setImie($parts[0]);
                    $czlonek->setNazwisko($parts[1] ?? '');
                    break;
                case 'secondName':
                    $czlonek->setDrugieImie($value);
                    break;
                case 'pesel':
                    $czlonek->setPesel($value);
                    break;
                case 'birthDate':
                    $czlonek->setDataUrodzenia(new \DateTime($value));
                    break;
                case 'gender':
                    $czlonek->setPlec($value);
                    break;
                case 'email':
                    $czlonek->setEmail($value);
                    break;
                case 'phone':
                    $czlonek->setTelefon($value);
                    break;
                case 'address':
                    $czlonek->setAdresZamieszkania($value);
                    break;
                case 'about':
                    $czlonek->setInformacjeOmnie($value);
                    break;
                case 'social':
                    $czlonek->setSocialMedia($value);
                    break;
                case 'employment':
                    $czlonek->setZatrudnienieSpolki($value);
                    break;
                case 'employmentMiejskie':
                    $czlonek->setZatrudnienieSpolkiMiejskie($value);
                    break;
                case 'employmentSkarbu':
                    $czlonek->setZatrudnienieSpolkiSkarbuPanstwa($value);
                    break;
                case 'employmentKomunalne':
                    $czlonek->setZatrudnienieSpolkiKomunalne($value);
                    break;
                case 'internalNote':
                    if ($this->isGranted('ROLE_FUNKCYJNY')) {
                        $czlonek->setNotatkaWewnetrzna($value);
                    } else {
                        return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
                    }
                    break;
                default:
                    return $this->json(['success' => false, 'message' => 'Unknown field'], 400);
            }

            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Field updated successfully',
                'value' => $value,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating field: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/export/csv', name: 'czlonek_export_csv')]
    public function exportCsv(
        Request $request,
        UserRepository $userRepository,
    ): Response {
        try {
            /** @var User $currentUser */
            $currentUser = $this->getUser();

            // Get the same filtered data as in index
            $queryBuilder = $userRepository->createQueryBuilderForUser($currentUser, 'czlonek');

            // Apply filters
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
            $selectedIds = $request->query->get('ids');
            if ($selectedIds) {
                $idsArray = array_map('intval', explode(',', $selectedIds));
                $queryBuilder->andWhere('u.id IN (:selectedIds)')
                    ->setParameter('selectedIds', $idsArray);
            }

            $members = $queryBuilder->getQuery()->getResult();

            // Create CSV content
            $csvContent = "\xEF\xBB\xBF"; // UTF-8 BOM

            // Add headers
            $headers = [
                'Imię i nazwisko',
                'Email',
                'Telefon',
                'Okręg',
                'Oddział',
                'Data przyjęcia',
                'Status',
                'Składka',
            ];
            $csvContent .= implode(';', $headers)."\n";

            // Add data
            foreach ($members as $member) {
                // Determine membership fee status
                $skladkaStatus = 'Nieopłacona';
                if ($member->isSkladkaOplacona()) {
                    if ($member->getDataWaznosciSkladki() && $member->getDataWaznosciSkladki() >= new \DateTime()) {
                        $skladkaStatus = 'Opłacona';
                    } else {
                        $skladkaStatus = 'Przeterminowana';
                    }
                }

                $row = [
                    $member->getFullName(),
                    $member->getEmail(),
                    $member->getTelefon() ?? '',
                    $member->getOkreg() ? $member->getOkreg()->getNazwa() : '',
                    $member->getOddzial() ? $member->getOddzial()->getNazwa() : '',
                    $member->getDataPrzyjeciaDoPartii() ? $member->getDataPrzyjeciaDoPartii()->format('d.m.Y') : '',
                    ucfirst($member->getStatus()),
                    $skladkaStatus,
                ];
                $csvContent .= implode(';', $row)."\n";
            }

            $response = new Response($csvContent);
            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');

            // Create filename with member count info
            $filename = 'czlonkowie_'.date('Y-m-d');
            if ($request->query->get('ids')) {
                $filename .= '_wybrani_'.count($members);
            }
            $filename .= '.csv';

            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

            return $response;
        } catch (\Exception $e) {
            return new Response('Error during export', 500);
        }
    }

    #[Route('/export/excel', name: 'czlonek_export_excel')]
    public function exportExcel(
        Request $request,
        UserRepository $userRepository,
    ): Response {
        try {
            /** @var User $currentUser */
            $currentUser = $this->getUser();

            // Get the same filtered data as in index
            $queryBuilder = $userRepository->createQueryBuilderForUser($currentUser, 'czlonek');

            // Apply filters
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
            $selectedIds = $request->query->get('ids');
            if ($selectedIds) {
                $idsArray = array_map('intval', explode(',', $selectedIds));
                $queryBuilder->andWhere('u.id IN (:selectedIds)')
                    ->setParameter('selectedIds', $idsArray);
            }

            $members = $queryBuilder->getQuery()->getResult();

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
            $html .= '<th>Data przyjęcia</th>';
            $html .= '<th>Status</th>';
            $html .= '<th>Składka</th>';
            $html .= '</tr>';

            // Data
            foreach ($members as $member) {
                // Determine membership fee status
                $skladkaStatus = 'Nieopłacona';
                if ($member->isSkladkaOplacona()) {
                    if ($member->getDataWaznosciSkladki() && $member->getDataWaznosciSkladki() >= new \DateTime()) {
                        $skladkaStatus = 'Opłacona';
                    } else {
                        $skladkaStatus = 'Przeterminowana';
                    }
                }

                $html .= '<tr>';
                $html .= '<td>'.htmlspecialchars($member->getFullName()).'</td>';
                $html .= '<td>'.htmlspecialchars($member->getEmail()).'</td>';
                $html .= '<td>'.htmlspecialchars($member->getTelefon() ?? '').'</td>';
                $html .= '<td>'.htmlspecialchars($member->getOkreg() ? $member->getOkreg()->getNazwa() : '').'</td>';
                $html .= '<td>'.htmlspecialchars($member->getOddzial() ? $member->getOddzial()->getNazwa() : '').'</td>';
                $html .= '<td>'.($member->getDataPrzyjeciaDoPartii() ? $member->getDataPrzyjeciaDoPartii()->format('d.m.Y') : '').'</td>';
                $html .= '<td>'.ucfirst($member->getStatus()).'</td>';
                $html .= '<td>'.$skladkaStatus.'</td>';
                $html .= '</tr>';
            }

            $html .= '</table></body></html>';

            $response = new Response($html);
            $response->headers->set('Content-Type', 'application/vnd.ms-excel');

            // Create filename with member count info
            $filename = 'czlonkowie_'.date('Y-m-d');
            if ($request->query->get('ids')) {
                $filename .= '_wybrani_'.count($members);
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
