<?php

namespace App\Controller;

use App\Entity\OpiniaCzlonka;
use App\Entity\User;
use App\Form\OpiniaCzlonkaType;
use App\Repository\OpiniaCzlonkaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/opinie-czlonkow')]
#[IsGranted('ROLE_FUNKCYJNY')]
class OpiniaCzlonkaController extends AbstractController
{
    #[Route('/dodaj/{id}', name: 'opinia_czlonka_new', requirements: ['id' => '\d+'])]
    public function new(
        User $czlonek,
        Request $request,
        EntityManagerInterface $entityManager,
        OpiniaCzlonkaRepository $opiniaRepository,
    ): Response {
        $currentUser = $this->getUser();

        // Nie można dodać opinii o sobie
        if ($currentUser === $czlonek) {
            $this->addFlash('error', 'Nie możesz dodać opinii o sobie.');

            return $this->redirectToRoute('czlonek_show', ['id' => $czlonek->getId()]);
        }

        $opinia = new OpiniaCzlonka();
        $opinia->setCzlonek($czlonek);
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Brak uprawnień');
        }

        $opinia->setAutor($currentUser);

        // Ustaw funkcję autora na podstawie jego ról
        $funkcjaAutora = $this->determineFunkcjaFromRoles($currentUser->getRoles());
        $opinia->setFunkcjaAutora($funkcjaAutora);

        $form = $this->createForm(OpiniaCzlonkaType::class, $opinia);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($opinia);
            $entityManager->flush();

            $this->addFlash('success', 'Opinia została dodana pomyślnie.');

            return $this->redirectToRoute('czlonek_show', ['id' => $czlonek->getId()]);
        }

        return $this->render('opinia_czlonka/new.html.twig', [
            'form' => $form->createView(),
            'czlonek' => $czlonek,
        ]);
    }

    #[Route('/lista/{id}', name: 'opinia_czlonka_list', requirements: ['id' => '\d+'])]
    public function list(
        User $czlonek,
        OpiniaCzlonkaRepository $opiniaRepository,
    ): Response {
        $opinie = $opiniaRepository->findOpinieByCzlonek($czlonek);

        return $this->render('opinia_czlonka/list.html.twig', [
            'czlonek' => $czlonek,
            'opinie' => $opinie,
        ]);
    }

    /**
     * @param array<string> $roles
     */
    private function determineFunkcjaFromRoles(array $roles): string
    {
        // Role administracyjne
        if (in_array('ROLE_SUPER_ADMIN', $roles)) {
            return 'Super Administrator';
        }
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'Administrator';
        }
        
        // Zarząd Krajowy
        if (in_array('ROLE_ZARZAD_KRAJOWY', $roles)) {
            return 'Członek Zarządu Krajowego';
        }
        
        // Role regionalne
        if (in_array('ROLE_PREZES_REGIONU', $roles)) {
            return 'Prezes Regionu';
        }
        
        // Role okręgowe
        if (in_array('ROLE_PREZES_OKREGU', $roles)) {
            return 'Prezes Okręgu';
        }
        if (in_array('ROLE_WICEPREZES_OKREGU', $roles)) {
            return 'Wiceprezes Okręgu';
        }
        if (in_array('ROLE_SEKRETARZ_OKREGU', $roles)) {
            return 'Sekretarz Okręgu';
        }
        if (in_array('ROLE_SKARBNIK_OKREGU', $roles)) {
            return 'Skarbnik Okręgu';
        }
        
        // Role oddziałowe
        if (in_array('ROLE_PRZEWODNICZACY_ODDZIALU', $roles)) {
            return 'Przewodniczący Oddziału';
        }
        if (in_array('ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU', $roles)) {
            return 'Zastępca Przewodniczącego Oddziału';
        }
        if (in_array('ROLE_SEKRETARZ_ODDZIALU', $roles)) {
            return 'Sekretarz Oddziału';
        }
        
        // Role związane z zebraniami
        if (in_array('ROLE_OBSERWATOR_ZEBRANIA', $roles)) {
            return 'Obserwator Zebrania';
        }
        if (in_array('ROLE_PROTOKOLANT_ZEBRANIA', $roles)) {
            return 'Protokolant Zebrania';
        }
        if (in_array('ROLE_PROWADZACY_ZEBRANIA', $roles)) {
            return 'Prowadzący Zebranie';
        }
        
        // Role podstawowe
        if (in_array('ROLE_CZLONEK_PARTII', $roles)) {
            return 'Członek Partii';
        }
        if (in_array('ROLE_KANDYDAT', $roles)) {
            return 'Kandydat';
        }
        if (in_array('ROLE_SYMPATYK', $roles)) {
            return 'Sympatyk';
        }
        if (in_array('ROLE_MLODZIEZOWKA', $roles)) {
            return 'Członek Młodzieżówki';
        }
        
        // Role funkcyjne (ogólne)
        if (in_array('ROLE_FUNKCYJNY', $roles)) {
            return 'Osoba funkcyjna';
        }

        return 'Brak funkcji';
    }
}
