<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/zarzad-oddzialu')]
#[IsGranted('ROLE_CZLONEK_PARTII')]
class ZarzadOddzialuController extends AbstractController
{
    #[Route('/', name: 'zarzad_oddzialu_index')]
    public function index(UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Sprawdź czy użytkownik ma przypisany oddział
        if (!$user->getOddzial()) {
            $this->addFlash('warning', 'Nie masz przypisanego oddziału.');

            return $this->redirectToRoute('dashboard');
        }

        // Pobierz członków zarządu oddziału - używamy natywnego zapytania dla PostgreSQL JSON
        $conn = $entityManager->getConnection();
        $sql = "
            SELECT u.* FROM \"user\" u
            WHERE u.oddzial_id = :oddzial
            AND (
                u.roles::jsonb @> '[\"ROLE_PRZEWODNICZACY_ODDZIALU\"]'::jsonb
                OR u.roles::jsonb @> '[\"ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU\"]'::jsonb
                OR u.roles::jsonb @> '[\"ROLE_SEKRETARZ_ODDZIALU\"]'::jsonb
            )
            ORDER BY u.nazwisko ASC
        ";

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['oddzial' => $user->getOddzial()->getId()]);
        $usersData = $resultSet->fetchAllAssociative();

        // Konwertuj wyniki na encje User
        $zarzadOddzialu = [];
        foreach ($usersData as $userData) {
            $zarzadOddzialu[] = $userRepository->find($userData['id']);
        }

        return $this->render('zarzad_oddzialu/index.html.twig', [
            'oddział' => $user->getOddzial(),
            'zarzad' => $zarzadOddzialu,
        ]);
    }
}
