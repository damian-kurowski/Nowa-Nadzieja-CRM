<?php

namespace App\Controller;

use App\Entity\BylyCzlonek;
use App\Repository\BylyCzlonekRepository;
use App\Repository\OddzialRepository;
use App\Repository\OkregRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/byli-czlonkowie')]
class BylyCzlonekController extends AbstractController
{
    #[Route('/', name: 'bylo_czlonek_index')]
    public function index(
        BylyCzlonekRepository $bylyCzlonekRepository,
        OkregRepository $okregRepository,
        OddzialRepository $oddzialRepository,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        // Sprawdź uprawnienia - zarząd i admin może przeglądać byłych członków
        if (!$this->isGranted('ROLE_ADMIN') &&
            !$this->isGranted('ROLE_ZARZAD_ODDZIALU') && 
            !$this->isGranted('ROLE_ZARZAD_OKREGU') && 
            !$this->isGranted('ROLE_ZARZAD_KRAJOWY') && 
            !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            throw $this->createAccessDeniedException('Brak uprawnień do przeglądania byłych członków');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Pobierz tylko byłych członków
        $queryBuilder = $bylyCzlonekRepository->createQueryBuilder('b')
            ->leftJoin('b.okreg', 'o')
            ->leftJoin('b.oddzial', 'd')
            ->orderBy('b.dataZakonczeniaCzlonkostwa', 'DESC');

        // Filtrowanie na podstawie uprawnień
        if (in_array('ROLE_ADMIN', $user->getRoles())
            || in_array('ROLE_ZARZAD_KRAJOWY', $user->getRoles())
            || in_array('ROLE_PELNOMOCNIK_STRUKTUR', $user->getRoles())) {
            // Admin, Zarząd krajowy i Pełnomocnik ds. Struktur widzą wszystkich byłych członków w kraju
            // Nie dodajemy dodatkowych filtrów
        } elseif (in_array('ROLE_ZARZAD_OKREGU', $user->getRoles()) && $user->getOkreg()) {
            // Zarząd okręgu widzi byłych członków ze swojego okręgu
            $queryBuilder->andWhere('b.okreg = :user_okreg')
                ->setParameter('user_okreg', $user->getOkreg());
        } elseif (in_array('ROLE_ZARZAD_ODDZIALU', $user->getRoles()) && $user->getOddzial()) {
            // Zarząd oddziału widzi byłych członków ze swojego oddziału
            $queryBuilder->andWhere('b.oddzial = :user_oddzial')
                ->setParameter('user_oddzial', $user->getOddzial());
        } else {
            // Pozostali użytkownicy nie widzą nic
            $queryBuilder->andWhere('1 = 0');
        }

        // Filtry
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder
                ->andWhere('b.imie LIKE :search OR b.nazwisko LIKE :search OR b.email LIKE :search OR b.telefon LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        $imie = $request->query->get('imie');
        if ($imie) {
            $queryBuilder
                ->andWhere('b.imie LIKE :imie')
                ->setParameter('imie', '%'.$imie.'%');
        }

        $nazwisko = $request->query->get('nazwisko');
        if ($nazwisko) {
            $queryBuilder
                ->andWhere('b.nazwisko LIKE :nazwisko')
                ->setParameter('nazwisko', '%'.$nazwisko.'%');
        }

        $email = $request->query->get('email');
        if ($email) {
            $queryBuilder
                ->andWhere('b.email LIKE :email')
                ->setParameter('email', '%'.$email.'%');
        }

        $telefon = $request->query->get('telefon');
        if ($telefon) {
            $queryBuilder
                ->andWhere('b.telefon LIKE :telefon')
                ->setParameter('telefon', '%'.$telefon.'%');
        }

        $okreg = $request->query->get('okreg');
        if ($okreg) {
            $queryBuilder
                ->andWhere('b.okreg = :okreg')
                ->setParameter('okreg', $okreg);
        }

        $oddzial = $request->query->get('oddzial');
        if ($oddzial) {
            $queryBuilder
                ->andWhere('b.oddzial = :oddzial')
                ->setParameter('oddzial', $oddzial);
        }

        $powodZakonczenia = $request->query->get('powod_zakonczenia');
        if ($powodZakonczenia) {
            $queryBuilder
                ->andWhere('b.powodZakonczeniaCzlonkostwa LIKE :powod')
                ->setParameter('powod', '%'.$powodZakonczenia.'%');
        }

        // Removed skladka filter - field doesn't exist for former members

        $dataZakonczeniaOd = $request->query->get('data_zakonczenia_od');
        if ($dataZakonczeniaOd) {
            $queryBuilder
                ->andWhere('b.dataZakonczeniaCzlonkostwa >= :dataZakonczeniaOd')
                ->setParameter('dataZakonczeniaOd', new \DateTime($dataZakonczeniaOd));
        }

        $dataZakonczeniaDo = $request->query->get('data_zakonczenia_do');
        if ($dataZakonczeniaDo) {
            $queryBuilder
                ->andWhere('b.dataZakonczeniaCzlonkostwa <= :dataZakonczeniaDo')
                ->setParameter('dataZakonczeniaDo', new \DateTime($dataZakonczeniaDo));
        }

        $dataPrzyjeciaOd = $request->query->get('data_przyjecia_od');
        if ($dataPrzyjeciaOd) {
            $queryBuilder
                ->andWhere('b.dataPrzyjecia >= :dataPrzyjeciaOd')
                ->setParameter('dataPrzyjeciaOd', new \DateTime($dataPrzyjeciaOd));
        }

        $dataPrzejeciaDo = $request->query->get('data_przyjecia_do');
        if ($dataPrzejeciaDo) {
            $queryBuilder
                ->andWhere('b.dataPrzyjecia <= :dataPrzejeciaDo')
                ->setParameter('dataPrzejeciaDo', new \DateTime($dataPrzejeciaDo));
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20,
            [
                'sortFieldWhitelist' => [], // Disable all sorting
                'defaultSortFieldName' => null,
                'defaultSortDirection' => null,
            ]
        );

        // Zaawansowane statystyki byłych członków
        $totalQuery = $bylyCzlonekRepository->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Byłych członków w ostatnich 12 miesiącach
        $recentQuery = $bylyCzlonekRepository->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.dataZakonczeniaCzlonkostwa >= :recentDate')
            ->setParameter('recentDate', new \DateTime('-12 months'))
            ->getQuery()
            ->getSingleScalarResult();

        // Najczęstszy powód zakończenia
        $topReasonResult = $bylyCzlonekRepository->createQueryBuilder('b')
            ->select('b.powodZakonczeniaCzlonkostwa, COUNT(b.id) as cnt')
            ->where('b.powodZakonczeniaCzlonkostwa IS NOT NULL')
            ->groupBy('b.powodZakonczeniaCzlonkostwa')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Średni okres członkostwa
        $membersWithDates = $bylyCzlonekRepository->createQueryBuilder('b')
            ->where('b.dataPrzyjecia IS NOT NULL')
            ->andWhere('b.dataZakonczeniaCzlonkostwa IS NOT NULL')
            ->getQuery()
            ->getResult();

        $totalDays = 0;
        $count = 0;
        foreach ($membersWithDates as $member) {
            if ($member->getDataPrzyjecia() && $member->getDataZakonczeniaCzlonkostwa()) {
                $diff = $member->getDataPrzyjecia()->diff($member->getDataZakonczeniaCzlonkostwa());
                $totalDays += $diff->days;
                $count++;
            }
        }
        
        $avgMembershipMonths = $count > 0 ? round($totalDays / $count / 30.44, 1) : 0;

        $stats = [
            'total' => $totalQuery,
            'recent' => $recentQuery,
            'topReason' => $topReasonResult ? $topReasonResult['powodZakonczeniaCzlonkostwa'] : 'Brak danych',
            'avgMembership' => $avgMembershipMonths,
        ];

        return $this->render('bylo_czlonek/index.html.twig', [
            'pagination' => $pagination,
            'okregi' => $okregRepository->findAll(),
            'oddzialy' => $oddzialRepository->findAll(),
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}', name: 'bylo_czlonek_show', requirements: ['id' => '\d+'])]
    public function show(BylyCzlonek $czlonek): Response
    {
        return $this->render('bylo_czlonek/show.html.twig', [
            'czlonek' => $czlonek,
        ]);
    }

    #[Route('/export/csv', name: 'bylo_czlonek_export_csv')]
    public function exportCsv(
        Request $request,
        BylyCzlonekRepository $bylyCzlonekRepository,
    ): Response {
        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            // Get the same filtered data as in index
            $queryBuilder = $bylyCzlonekRepository->createQueryBuilder('b')
                ->leftJoin('b.okreg', 'o')
                ->leftJoin('b.oddzial', 'd')
                ->orderBy('b.dataZakonczeniaCzlonkostwa', 'DESC');

            // Apply same permissions as index
            if (in_array('ROLE_ZARZAD_KRAJOWY', $user->getRoles())) {
                // National board sees all
            } elseif (in_array('ROLE_ZARZAD_OKREGU', $user->getRoles()) && $user->getOkreg()) {
                $queryBuilder->andWhere('b.okreg = :user_okreg')
                    ->setParameter('user_okreg', $user->getOkreg());
            } elseif (in_array('ROLE_ZARZAD_ODDZIALU', $user->getRoles()) && $user->getOddzial()) {
                $queryBuilder->andWhere('b.oddzial = :user_oddzial')
                    ->setParameter('user_oddzial', $user->getOddzial());
            } else {
                $queryBuilder->andWhere('1 = 0');
            }

            // Apply filters (same as in index method)
            if ($search = $request->query->get('search')) {
                $queryBuilder->andWhere('b.imie LIKE :search OR b.nazwisko LIKE :search OR b.email LIKE :search OR b.telefon LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
            }

            if ($okreg = $request->query->get('okreg')) {
                $queryBuilder->andWhere('b.okreg = :okreg')
                    ->setParameter('okreg', $okreg);
            }

            if ($oddzial = $request->query->get('oddzial')) {
                $queryBuilder->andWhere('b.oddzial = :oddzial')
                    ->setParameter('oddzial', $oddzial);
            }

            // Filter by selected IDs if provided
            $selectedIds = $request->query->get('ids');
            if ($selectedIds) {
                $idsArray = array_map('intval', explode(',', $selectedIds));
                $queryBuilder->andWhere('b.id IN (:selectedIds)')
                    ->setParameter('selectedIds', $idsArray);
            }

            $byliCzlonkowie = $queryBuilder->getQuery()->getResult();

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
                'Data zakończenia',
                'Powód zakończenia',
                'Okres członkostwa',
            ];
            $csvContent .= implode(';', $headers)."\n";

            // Add data
            foreach ($byliCzlonkowie as $czlonek) {
                // Calculate membership period
                $okresCzlonkostwa = '';
                if ($czlonek->getDataPrzyjecia() && $czlonek->getDataZakonczeniaCzlonkostwa()) {
                    $diff = $czlonek->getDataPrzyjecia()->diff($czlonek->getDataZakonczeniaCzlonkostwa());
                    $lata = $diff->y;
                    $miesiace = $diff->m;
                    $dni = $diff->d;

                    $okresCzlonkostwa = '';
                    if ($lata > 0) {
                        $okresCzlonkostwa .= $lata.' lat ';
                    }
                    if ($miesiace > 0) {
                        $okresCzlonkostwa .= $miesiace.' mies. ';
                    }
                    if ($dni > 0) {
                        $okresCzlonkostwa .= $dni.' dni';
                    }
                    $okresCzlonkostwa = trim($okresCzlonkostwa);
                }

                $row = [
                    $czlonek->getImie().' '.$czlonek->getNazwisko(),
                    $czlonek->getEmail() ?? '',
                    $czlonek->getTelefon() ?? '',
                    $czlonek->getOkreg() ? $czlonek->getOkreg()->getNazwa() : '',
                    $czlonek->getOddzial() ? $czlonek->getOddzial()->getNazwa() : '',
                    $czlonek->getDataPrzyjecia() ? $czlonek->getDataPrzyjecia()->format('d.m.Y') : '',
                    $czlonek->getDataZakonczeniaCzlonkostwa() ? $czlonek->getDataZakonczeniaCzlonkostwa()->format('d.m.Y') : '',
                    $czlonek->getPowodZakonczeniaCzlonkostwa() ?? '',
                    $okresCzlonkostwa,
                ];
                $csvContent .= implode(';', $row)."\n";
            }

            $response = new Response($csvContent);
            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');

            // Create filename with member count info
            $filename = 'byli_czlonkowie_'.date('Y-m-d');
            if ($request->query->get('ids')) {
                $filename .= '_wybrani_'.count($byliCzlonkowie);
            }
            $filename .= '.csv';

            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

            return $response;
        } catch (\Exception $e) {
            return new Response('Error: '.$e->getMessage(), 500);
        }
    }

    #[Route('/export/excel', name: 'bylo_czlonek_export_excel')]
    public function exportExcel(
        Request $request,
        BylyCzlonekRepository $bylyCzlonekRepository,
    ): Response {
        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            // Get the same filtered data as in index
            $queryBuilder = $bylyCzlonekRepository->createQueryBuilder('b')
                ->leftJoin('b.okreg', 'o')
                ->leftJoin('b.oddzial', 'd')
                ->orderBy('b.dataZakonczeniaCzlonkostwa', 'DESC');

            // Apply same permissions as index
            if (in_array('ROLE_ZARZAD_KRAJOWY', $user->getRoles())) {
                // National board sees all
            } elseif (in_array('ROLE_ZARZAD_OKREGU', $user->getRoles()) && $user->getOkreg()) {
                $queryBuilder->andWhere('b.okreg = :user_okreg')
                    ->setParameter('user_okreg', $user->getOkreg());
            } elseif (in_array('ROLE_ZARZAD_ODDZIALU', $user->getRoles()) && $user->getOddzial()) {
                $queryBuilder->andWhere('b.oddzial = :user_oddzial')
                    ->setParameter('user_oddzial', $user->getOddzial());
            } else {
                $queryBuilder->andWhere('1 = 0');
            }

            // Apply filters (same as in index method)
            if ($search = $request->query->get('search')) {
                $queryBuilder->andWhere('b.imie LIKE :search OR b.nazwisko LIKE :search OR b.email LIKE :search OR b.telefon LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
            }

            if ($okreg = $request->query->get('okreg')) {
                $queryBuilder->andWhere('b.okreg = :okreg')
                    ->setParameter('okreg', $okreg);
            }

            if ($oddzial = $request->query->get('oddzial')) {
                $queryBuilder->andWhere('b.oddzial = :oddzial')
                    ->setParameter('oddzial', $oddzial);
            }

            // Filter by selected IDs if provided
            $selectedIds = $request->query->get('ids');
            if ($selectedIds) {
                $idsArray = array_map('intval', explode(',', $selectedIds));
                $queryBuilder->andWhere('b.id IN (:selectedIds)')
                    ->setParameter('selectedIds', $idsArray);
            }

            $byliCzlonkowie = $queryBuilder->getQuery()->getResult();

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
            $html .= '<th>Data zakończenia</th>';
            $html .= '<th>Powód zakończenia</th>';
            $html .= '<th>Okres członkostwa</th>';
            $html .= '</tr>';

            // Data
            foreach ($byliCzlonkowie as $czlonek) {
                // Calculate membership period
                $okresCzlonkostwa = '';
                if ($czlonek->getDataPrzyjecia() && $czlonek->getDataZakonczeniaCzlonkostwa()) {
                    $diff = $czlonek->getDataPrzyjecia()->diff($czlonek->getDataZakonczeniaCzlonkostwa());
                    $lata = $diff->y;
                    $miesiace = $diff->m;
                    $dni = $diff->d;

                    $okresCzlonkostwa = '';
                    if ($lata > 0) {
                        $okresCzlonkostwa .= $lata.' lat ';
                    }
                    if ($miesiace > 0) {
                        $okresCzlonkostwa .= $miesiace.' mies. ';
                    }
                    if ($dni > 0) {
                        $okresCzlonkostwa .= $dni.' dni';
                    }
                    $okresCzlonkostwa = trim($okresCzlonkostwa);
                }

                $html .= '<tr>';
                $html .= '<td>'.htmlspecialchars($czlonek->getImie().' '.$czlonek->getNazwisko()).'</td>';
                $html .= '<td>'.htmlspecialchars($czlonek->getEmail() ?? '').'</td>';
                $html .= '<td>'.htmlspecialchars($czlonek->getTelefon() ?? '').'</td>';
                $html .= '<td>'.htmlspecialchars($czlonek->getOkreg() ? $czlonek->getOkreg()->getNazwa() : '').'</td>';
                $html .= '<td>'.htmlspecialchars($czlonek->getOddzial() ? $czlonek->getOddzial()->getNazwa() : '').'</td>';
                $html .= '<td>'.($czlonek->getDataPrzyjecia() ? $czlonek->getDataPrzyjecia()->format('d.m.Y') : '').'</td>';
                $html .= '<td>'.($czlonek->getDataZakonczeniaCzlonkostwa() ? $czlonek->getDataZakonczeniaCzlonkostwa()->format('d.m.Y') : '').'</td>';
                $html .= '<td>'.htmlspecialchars($czlonek->getPowodZakonczeniaCzlonkostwa() ?? '').'</td>';
                $html .= '<td>'.htmlspecialchars($okresCzlonkostwa).'</td>';
                $html .= '</tr>';
            }

            $html .= '</table></body></html>';

            $response = new Response($html);
            $response->headers->set('Content-Type', 'application/vnd.ms-excel');

            // Create filename with member count info
            $filename = 'byli_czlonkowie_'.date('Y-m-d');
            if ($request->query->get('ids')) {
                $filename .= '_wybrani_'.count($byliCzlonkowie);
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
