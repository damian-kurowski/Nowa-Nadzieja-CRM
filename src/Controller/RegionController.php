<?php

namespace App\Controller;

use App\Entity\Region;
use App\Entity\User;
use App\Repository\RegionRepository;
use App\Repository\UserRepository;
use App\Repository\OkregRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/region')]
#[IsGranted('ROLE_ADMIN')]
class RegionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RegionRepository $regionRepository,
        private UserRepository $userRepository,
        private OkregRepository $okregRepository
    ) {
    }

    #[Route('/', name: 'app_region_index', methods: ['GET'])]
    public function index(): Response
    {
        $regions = $this->regionRepository->findAllOrdered();

        return $this->render('region/index.html.twig', [
            'regions' => $regions,
        ]);
    }

    #[Route('/new', name: 'app_region_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $nazwa = $request->request->get('nazwa');
            $wojewodztwo = $request->request->get('wojewodztwo');

            if ($nazwa && $wojewodztwo) {
                $region = new Region();
                $region->setNazwa($nazwa);
                $region->setWojewodztwo($wojewodztwo);

                $this->entityManager->persist($region);
                $this->entityManager->flush();

                $this->addFlash('success', 'Region został utworzony pomyślnie.');
                return $this->redirectToRoute('app_region_index');
            }

            $this->addFlash('error', 'Wszystkie pola są wymagane.');
        }

        $wojewodztwa = [
            'dolnośląskie', 'kujawsko-pomorskie', 'lubelskie', 'lubuskie',
            'łódzkie', 'małopolskie', 'mazowieckie', 'opolskie',
            'podkarpackie', 'podlaskie', 'pomorskie', 'śląskie',
            'świętokrzyskie', 'warmińsko-mazurskie', 'wielkopolskie', 'zachodniopomorskie'
        ];

        return $this->render('region/new.html.twig', [
            'wojewodztwa' => $wojewodztwa,
        ]);
    }

    #[Route('/{id}', name: 'app_region_show', methods: ['GET'])]
    public function show(Region $region): Response
    {
        $okregi = $region->getOkregi();
        $prezesi = $this->userRepository->findBy([
            'region' => $region,
        ]);

        return $this->render('region/show.html.twig', [
            'region' => $region,
            'okregi' => $okregi,
            'prezesi' => $prezesi,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_region_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Region $region): Response
    {
        if ($request->isMethod('POST')) {
            $nazwa = $request->request->get('nazwa');
            $wojewodztwo = $request->request->get('wojewodztwo');

            if ($nazwa && $wojewodztwo) {
                $region->setNazwa($nazwa);
                $region->setWojewodztwo($wojewodztwo);

                $this->entityManager->flush();

                $this->addFlash('success', 'Region został zaktualizowany pomyślnie.');
                return $this->redirectToRoute('app_region_show', ['id' => $region->getId()]);
            }

            $this->addFlash('error', 'Wszystkie pola są wymagane.');
        }

        $wojewodztwa = [
            'dolnośląskie', 'kujawsko-pomorskie', 'lubelskie', 'lubuskie',
            'łódzkie', 'małopolskie', 'mazowieckie', 'opolskie',
            'podkarpackie', 'podlaskie', 'pomorskie', 'śląskie',
            'świętokrzyskie', 'warmińsko-mazurskie', 'wielkopolskie', 'zachodniopomorskie'
        ];

        return $this->render('region/edit.html.twig', [
            'region' => $region,
            'wojewodztwa' => $wojewodztwa,
        ]);
    }

    #[Route('/{id}/assign-okreg', name: 'app_region_assign_okreg', methods: ['POST'])]
    public function assignOkreg(Request $request, Region $region): Response
    {
        $okregId = $request->request->get('okreg_id');
        
        if ($okregId) {
            $okreg = $this->okregRepository->find($okregId);
            if ($okreg) {
                $okreg->setRegion($region);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Okręg został przypisany do regionu.');
            } else {
                $this->addFlash('error', 'Nie znaleziono okręgu.');
            }
        }

        return $this->redirectToRoute('app_region_show', ['id' => $region->getId()]);
    }

    #[Route('/{id}/assign-prezes', name: 'app_region_assign_prezes', methods: ['POST'])]
    public function assignPrezes(Request $request, Region $region): Response
    {
        $userId = $request->request->get('user_id');
        
        if ($userId) {
            $user = $this->userRepository->find($userId);
            if ($user) {
                // Sprawdź czy użytkownik ma rolę prezesa regionu
                if (!$user->hasRole('ROLE_PREZES_REGIONU')) {
                    $roles = $user->getRoles();
                    $roles[] = 'ROLE_PREZES_REGIONU';
                    $user->setRoles($roles);
                }
                
                $user->setRegion($region);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Prezes regionu został przypisany.');
            } else {
                $this->addFlash('error', 'Nie znaleziono użytkownika.');
            }
        }

        return $this->redirectToRoute('app_region_show', ['id' => $region->getId()]);
    }

    #[Route('/{id}/remove-okreg/{okresId}', name: 'app_region_remove_okreg', methods: ['POST'])]
    public function removeOkreg(Region $region, int $okresId): Response
    {
        $okreg = $this->okregRepository->find($okresId);
        if ($okreg && $okreg->getRegion() === $region) {
            $okreg->setRegion(null);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Okręg został usunięty z regionu.');
        }

        return $this->redirectToRoute('app_region_show', ['id' => $region->getId()]);
    }

    #[Route('/{id}/remove-prezes/{prezesId}', name: 'app_region_remove_prezes', methods: ['POST'])]
    public function removePrezes(Region $region, int $prezesId): Response
    {
        $user = $this->userRepository->find($prezesId);
        if ($user && $user->getRegion() === $region) {
            $user->setRegion(null);
            
            // Usuń rolę prezesa regionu
            $roles = array_filter($user->getRoles(), fn($role) => $role !== 'ROLE_PREZES_REGIONU');
            $user->setRoles($roles);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Prezes regionu został usunięty.');
        }

        return $this->redirectToRoute('app_region_show', ['id' => $region->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_region_delete', methods: ['POST'])]
    public function delete(Region $region): Response
    {
        // Sprawdź czy region nie ma przypisanych okręgów ani prezesów
        if ($region->getOkregi()->count() > 0) {
            $this->addFlash('error', 'Nie można usunąć regionu, który ma przypisane okręgi.');
            return $this->redirectToRoute('app_region_show', ['id' => $region->getId()]);
        }

        if ($region->getPrezesi()->count() > 0) {
            $this->addFlash('error', 'Nie można usunąć regionu, który ma przypisanych prezesów.');
            return $this->redirectToRoute('app_region_show', ['id' => $region->getId()]);
        }

        $this->entityManager->remove($region);
        $this->entityManager->flush();

        $this->addFlash('success', 'Region został usunięty.');
        return $this->redirectToRoute('app_region_index');
    }
}