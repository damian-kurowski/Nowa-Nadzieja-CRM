<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CzlonekType;
use App\Form\PhotoUploadType;
use App\Repository\OddzialRepository;
use App\Repository\OkregRepository;
use App\Repository\OpiniaCzlonkaRepository;
use App\Repository\UserRepository;
use App\Service\PhotoService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mlodziezowka')]
class MlodziezowkaController extends AbstractController
{
    #[Route('/', name: 'mlodziezowka_index')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        OkregRepository $okregRepository,
        OddzialRepository $oddzialRepository,
        PaginatorInterface $paginator,
    ): Response {
        // Sprawdź uprawnienia - ROLE_FUNKCYJNY lub ROLE_PELNOMOCNIK_STRUKTUR
        if (!$this->isGranted('ROLE_FUNKCYJNY') && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            throw $this->createAccessDeniedException('Brak uprawnień do przeglądania członków młodzieżówki');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Filtrowanie na podstawie uprawnień - tylko członkowie młodzieżówki
        $queryBuilder = $userRepository->createQueryBuilderForUser($currentUser, 'mlodziezowka');

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

        // Sortowanie
        $sort = $request->query->get('sort', 'nazwisko');
        $direction = $request->query->get('direction', 'asc');
        $allowedSorts = ['imie', 'nazwisko', 'email', 'dataRejestracji', 'status'];
        
        if (in_array($sort, $allowedSorts)) {
            $queryBuilder->orderBy('u.'.$sort, $direction);
        } else {
            $queryBuilder->orderBy('u.nazwisko', 'ASC')->addOrderBy('u.imie', 'ASC');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        // Statystyki członków młodzieżówki - zaawansowane
        $totalQuery = $userRepository->createQueryBuilderForUser($currentUser, 'mlodziezowka')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
            
        $activeQuery = $userRepository->createQueryBuilderForUser($currentUser, 'mlodziezowka')
            ->select('COUNT(u.id)')
            ->andWhere('u.status = :status')
            ->setParameter('status', 'aktywny')
            ->getQuery()
            ->getSingleScalarResult();
            
        $recentQuery = $userRepository->createQueryBuilderForUser($currentUser, 'mlodziezowka')
            ->select('COUNT(u.id)')
            ->andWhere('u.dataRejestracji >= :recentDate')
            ->setParameter('recentDate', new \DateTime('-30 days'))
            ->getQuery()
            ->getSingleScalarResult();
            
        // Calculate average age using a simpler approach
        $membersWithBirthdate = $userRepository->createQueryBuilderForUser($currentUser, 'mlodziezowka')
            ->andWhere('u.dataUrodzenia IS NOT NULL')
            ->getQuery()
            ->getResult();
        
        $totalAge = 0;
        $count = 0;
        $currentDate = new \DateTime();
        
        foreach ($membersWithBirthdate as $member) {
            if ($member->getDataUrodzenia()) {
                $age = $currentDate->diff($member->getDataUrodzenia())->y;
                $totalAge += $age;
                $count++;
            }
        }
        
        $avgAge = $count > 0 ? round($totalAge / $count, 1) : 0;
        
        $stats = [
            'total' => $totalQuery,
            'active' => $activeQuery,
            'recent' => $recentQuery,
            'avgAge' => $avgAge,
        ];

        // Pobierz listę okręgów i oddziałów do filtrów (tylko te, do których user ma dostęp)
        $okregi = [];
        $oddzialy = [];
        
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            $okregi = $okregRepository->findBy([], ['nazwa' => 'ASC']);
            $oddzialy = $oddzialRepository->findBy([], ['nazwa' => 'ASC']);
        } elseif ($currentUser->getOkreg()) {
            $okregi = [$currentUser->getOkreg()];
            $oddzialy = $currentUser->getOkreg()->getOddzialy()->toArray();
        }

        return $this->render('mlodziezowka/index.html.twig', [
            'pagination' => $pagination,
            'stats' => $stats,
            'okregi' => $okregi,
            'oddzialy' => $oddzialy,
        ]);
    }


    #[Route('/{id}', name: 'mlodziezowka_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        User $user,
        OpiniaCzlonkaRepository $opiniaRepository
    ): Response {
        // Sprawdź czy to rzeczywiście członek młodzieżówki
        if ($user->getTypUzytkownika() !== 'mlodziezowka') {
            throw $this->createNotFoundException('Nie znaleziono członka młodzieżówki.');
        }
        
        if (!$this->isGranted('ROLE_FUNKCYJNY') && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            throw $this->createAccessDeniedException('Brak uprawnień do przeglądania członków młodzieżówki');
        }

        // Sprawdź dostęp na podstawie struktury
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            // Sprawdź czy użytkownik może zobaczyć tego członka
            if ($currentUser->getOkreg() !== $user->getOkreg()) {
                throw $this->createAccessDeniedException('Brak dostępu do tego członka młodzieżówki.');
            }
        }

        $opinie = [];
        // Opinie są widoczne tylko dla osób funkcyjnych
        if ($this->isGranted('ROLE_FUNKCYJNY')) {
            $opinie = $opiniaRepository->findOpinieByCzlonek($user);
        }

        return $this->render('mlodziezowka/show.html.twig', [
            'user' => $user,
            'opinie' => $opinie,
        ]);
    }

    #[Route('/{id}/edytuj', name: 'mlodziezowka_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        OkregRepository $okregRepository,
        OddzialRepository $oddzialRepository,
    ): Response {
        // Sprawdź czy to rzeczywiście członek młodzieżówki
        if ($user->getTypUzytkownika() !== 'mlodziezowka') {
            throw $this->createNotFoundException('Nie znaleziono członka młodzieżówki.');
        }
        
        if (!$this->isGranted('ROLE_FUNKCYJNY') && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            throw $this->createAccessDeniedException('Brak uprawnień do edycji członków młodzieżówki');
        }

        // Sprawdź dostęp na podstawie struktury
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            if ($currentUser->getOkreg() !== $user->getOkreg()) {
                throw $this->createAccessDeniedException('Brak dostępu do edycji tego członka młodzieżówki.');
            }
        }

        $form = $this->createForm(CzlonekType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Upewnij się że typ pozostaje jako młodzieżówka
            $user->setTypUzytkownika('mlodziezowka');
            
            // Nie pobieraj składek dla młodzieżówki
            $user->setSkladkaOplacona(null);
            $user->setKwotaSkladki(null);
            $user->setDataOplaceniaSkladki(null);
            $user->setDataWaznosciSkladki(null);
            
            $entityManager->flush();

            $this->addFlash('success', 'Dane członka młodzieżówki zostały zaktualizowane.');

            return $this->redirectToRoute('mlodziezowka_show', ['id' => $user->getId()]);
        }

        // Pobierz listę okręgów i oddziałów
        $okregi = [];
        $oddzialy = [];
        
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            $okregi = $okregRepository->findBy([], ['nazwa' => 'ASC']);
            $oddzialy = $oddzialRepository->findBy([], ['nazwa' => 'ASC']);
        } elseif ($currentUser->getOkreg()) {
            $okregi = [$currentUser->getOkreg()];
            $oddzialy = $currentUser->getOkreg()->getOddzialy()->toArray();
        }

        return $this->render('mlodziezowka/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'okregi' => $okregi,
            'oddzialy' => $oddzialy,
        ]);
    }

    #[Route('/{id}/zmien-zdjecie', name: 'mlodziezowka_change_photo', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function changePhoto(
        Request $request,
        User $user,
        PhotoService $photoService,
        EntityManagerInterface $entityManager,
    ): Response {
        // Sprawdź czy to rzeczywiście członek młodzieżówki
        if ($user->getTypUzytkownika() !== 'mlodziezowka') {
            throw $this->createNotFoundException('Nie znaleziono członka młodzieżówki.');
        }
        
        if (!$this->isGranted('ROLE_FUNKCYJNY') && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')) {
            throw $this->createAccessDeniedException('Brak uprawnień do zmiany zdjęcia');
        }

        $form = $this->createForm(PhotoUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            
            if ($photoFile) {
                $filename = $photoService->uploadPhoto($photoFile, $user);
                
                if ($filename) {
                    // Usuń stare zdjęcie jeśli istnieje
                    if ($user->getZdjecie()) {
                        $photoService->removePhoto($user->getZdjecie());
                    }
                    
                    $user->setZdjecie($filename);
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Zdjęcie zostało zaktualizowane.');
                } else {
                    $this->addFlash('error', 'Wystąpił błąd podczas przesyłania zdjęcia.');
                }
            }
            
            return $this->redirectToRoute('mlodziezowka_show', ['id' => $user->getId()]);
        }

        return $this->render('czlonek/change_photo.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'return_route' => 'mlodziezowka_show',
        ]);
    }

    #[Route('/{id}/usun', name: 'mlodziezowka_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
    ): Response {
        // Sprawdź czy to rzeczywiście członek młodzieżówki
        if ($user->getTypUzytkownika() !== 'mlodziezowka') {
            throw $this->createNotFoundException('Nie znaleziono członka młodzieżówki.');
        }
        
        // Tylko admin może usuwać członków
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Tylko administrator może usuwać członków młodzieżówki');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Członek młodzieżówki został usunięty.');
        }

        return $this->redirectToRoute('mlodziezowka_index');
    }
}