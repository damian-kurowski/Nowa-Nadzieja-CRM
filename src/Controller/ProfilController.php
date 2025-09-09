<?php

namespace App\Controller;

use App\Form\PhotoUploadType;
use App\Form\ProfilType;
use App\Service\PhotoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfilController extends AbstractController
{
    #[Route('/', name: 'profil_index')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('profil/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edytuj', name: 'profil_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password change if provided
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Handle CV file upload
            $cvFile = $form->get('cvFile')->getData();
            if ($cvFile) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir').'/public/uploads/cv';
                if (!is_dir($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0755, true);
                }
                
                $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $fileName = $safeFilename.'-'.uniqid().'.'.$cvFile->guessExtension();
                
                try {
                    $cvFile->move($uploadsDirectory, $fileName);
                    
                    // Delete old CV file if exists
                    if ($user->getCv() && file_exists($uploadsDirectory.'/'.$user->getCv())) {
                        unlink($uploadsDirectory.'/'.$user->getCv());
                    }
                    
                    $user->setCv($fileName);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Wystąpił błąd podczas wgrywania CV: '.$e->getMessage());
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Profil został zaktualizowany.');

            return $this->redirectToRoute('profil_index');
        }

        return $this->render('profil/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/zdjecie', name: 'profil_photo')]
    public function uploadPhoto(
        Request $request,
        EntityManagerInterface $entityManager,
        PhotoService $photoService,
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(PhotoUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                try {
                    /** @var \App\Entity\User $user */
                    $photoFileName = $photoService->upload($photoFile, $user->getZdjecie());
                    $user->setZdjecie($photoFileName);
                    $entityManager->flush();

                    $this->addFlash('success', 'Zdjęcie zostało wgrane i przycięte do rozmiaru 500x500px.');

                    return $this->redirectToRoute('profil_index');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Wystąpił błąd podczas wgrywania zdjęcia: '.$e->getMessage());
                }
            }
        }

        return $this->render('profil/photo.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/usun-zdjecie', name: 'profil_delete_photo', methods: ['POST'])]
    public function deletePhoto(
        EntityManagerInterface $entityManager,
        PhotoService $photoService,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->getZdjecie()) {
            $photoService->deletePhoto($user->getZdjecie());
            $user->setZdjecie(null);
            $entityManager->flush();

            $this->addFlash('success', 'Zdjęcie zostało usunięte.');
        }

        return $this->redirectToRoute('profil_index');
    }
}
