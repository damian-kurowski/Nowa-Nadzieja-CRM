<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/zarzad-okregu')]
#[IsGranted('ROLE_CZLONEK_PARTII')]
class ZarzadOkreguController extends AbstractController
{
    #[Route('/', name: 'zarzad_okregu_index')]
    public function index(UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Sprawdź czy użytkownik ma przypisany okręg
        if (!$user->getOkreg()) {
            $this->addFlash('warning', 'Nie masz przypisanego okręgu.');

            return $this->redirectToRoute('dashboard');
        }

        // Pobierz członków zarządu okręgu - używamy natywnego zapytania dla PostgreSQL JSON
        $conn = $entityManager->getConnection();
        $sql = "
            SELECT u.* FROM \"user\" u
            WHERE u.okreg_id = :okreg
            AND (
                u.roles::jsonb @> '[\"ROLE_PREZES_OKREGU\"]'::jsonb
                OR u.roles::jsonb @> '[\"ROLE_WICEPREZES_OKREGU\"]'::jsonb
                OR u.roles::jsonb @> '[\"ROLE_SEKRETARZ_OKREGU\"]'::jsonb
                OR u.roles::jsonb @> '[\"ROLE_SKARBNIK_OKREGU\"]'::jsonb
                OR u.roles::jsonb @> '[\"ROLE_PELNOMOCNIK_PRZYJMOWANIA\"]'::jsonb
            )
            ORDER BY u.nazwisko ASC
        ";

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['okreg' => $user->getOkreg()->getId()]);
        $usersData = $resultSet->fetchAllAssociative();

        // Konwertuj wyniki na encje User
        $zarzadOkregu = [];
        foreach ($usersData as $userData) {
            $zarzadOkregu[] = $userRepository->find($userData['id']);
        }

        return $this->render('zarzad_okregu/index.html.twig', [
            'okreg' => $user->getOkreg(),
            'zarzad' => $zarzadOkregu,
        ]);
    }
}
