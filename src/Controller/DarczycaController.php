<?php

namespace App\Controller;

use App\Entity\Darczyca;
use App\Repository\DarczycaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/darczyncy')]
#[IsGranted('ROLE_USER')]
class DarczycaController extends AbstractController
{
    #[Route('/', name: 'darczyca_index')]
    public function index(
        Request $request,
        DarczycaRepository $darczycaRepository,
        PaginatorInterface $paginator,
    ): Response {
        $queryBuilder = $darczycaRepository->createQueryBuilder('d');

        // Filtry
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('d.imie LIKE :search OR d.nazwisko LIKE :search OR d.email LIKE :search OR d.firma LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($forma = $request->query->get('forma_wsparcia')) {
            $queryBuilder->andWhere('d.forma_wsparcia = :forma')
                ->setParameter('forma', $forma);
        }

        $queryBuilder->orderBy('d.created_at', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        // Oblicz realne statystyki z nowych pól profesjonalnych
        $stats = [
            'total' => $darczycaRepository->count([]),
            'activeCount' => $darczycaRepository->count(['status_darczyny' => 'aktywny']),
            'vipCount' => $darczycaRepository->count(['status_darczyny' => 'vip']),
            'totalAmount' => $darczycaRepository->createQueryBuilder('d')
                ->select('SUM(d.laczna_kwota_dotacji)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0,
            'totalDonations' => $darczycaRepository->createQueryBuilder('d')
                ->select('SUM(d.liczba_dotacji)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0,
            'avgDonation' => 0
        ];
        
        // Oblicz średnią dotację
        if ($stats['totalDonations'] > 0) {
            $stats['avgDonation'] = $stats['totalAmount'] / $stats['totalDonations'];
        }

        return $this->render('darczyca/index.html.twig', [
            'pagination' => $pagination,
            'stats' => $stats,
        ]);
    }

    #[Route('/nowy', name: 'darczyca_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $darczyca = new Darczyca();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $darczyca->setImie($data['imie'] ?? '');
            $darczyca->setNazwisko($data['nazwisko'] ?? '');
            $darczyca->setFirma($data['firma'] ?? null);
            $darczyca->setEmail($data['email'] ?? '');
            $darczyca->setTelefon($data['telefon'] ?? null);
            $darczyca->setAdres($data['adres'] ?? null);
            $darczyca->setKwotaWsparcia($data['kwota_wsparcia'] ?? null);
            $darczyca->setFormaWsparcia($data['forma_wsparcia'] ?? null);
            $darczyca->setNotatka($data['notatka'] ?? null);
            $darczyca->setZgodaNaKontakt(isset($data['zgoda_na_kontakt']));
            
            if (!empty($data['data_pierwszej_wplaty'])) {
                $darczyca->setDataPierwszejWplaty(new \DateTime($data['data_pierwszej_wplaty']));
            }
            if (!empty($data['data_ostatniej_wplaty'])) {
                $darczyca->setDataOstatniejWplaty(new \DateTime($data['data_ostatniej_wplaty']));
            }

            $entityManager->persist($darczyca);
            $entityManager->flush();

            $this->addFlash('success', 'Darczyńca został dodany pomyślnie.');

            return $this->redirectToRoute('darczyca_index');
        }

        return $this->render('darczyca/new.html.twig', [
            'darczyca' => $darczyca,
        ]);
    }

    #[Route('/{id}', name: 'darczyca_show')]
    public function show(Darczyca $darczyca): Response
    {
        return $this->render('darczyca/show.html.twig', [
            'darczyca' => $darczyca,
        ]);
    }

    #[Route('/{id}/edytuj', name: 'darczyca_edit')]
    public function edit(Request $request, Darczyca $darczyca, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $darczyca->setImie($data['imie'] ?? $darczyca->getImie());
            $darczyca->setNazwisko($data['nazwisko'] ?? $darczyca->getNazwisko());
            $darczyca->setFirma($data['firma'] ?? null);
            $darczyca->setEmail($data['email'] ?? $darczyca->getEmail());
            $darczyca->setTelefon($data['telefon'] ?? null);
            $darczyca->setAdres($data['adres'] ?? null);
            $darczyca->setKwotaWsparcia($data['kwota_wsparcia'] ?? null);
            $darczyca->setFormaWsparcia($data['forma_wsparcia'] ?? null);
            $darczyca->setNotatka($data['notatka'] ?? null);
            $darczyca->setZgodaNaKontakt(isset($data['zgoda_na_kontakt']));
            
            if (!empty($data['data_pierwszej_wplaty'])) {
                $darczyca->setDataPierwszejWplaty(new \DateTime($data['data_pierwszej_wplaty']));
            }
            if (!empty($data['data_ostatniej_wplaty'])) {
                $darczyca->setDataOstatniejWplaty(new \DateTime($data['data_ostatniej_wplaty']));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Dane darczyńcy zostały zaktualizowane.');

            return $this->redirectToRoute('darczyca_show', ['id' => $darczyca->getId()]);
        }

        return $this->render('darczyca/edit.html.twig', [
            'darczyca' => $darczyca,
        ]);
    }

}
