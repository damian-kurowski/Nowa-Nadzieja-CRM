<?php

namespace App\Controller;

use App\Entity\PostepKandydata;
use App\Entity\User;
use App\Repository\PostepKandydataRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/postep-kandydatow')]
class PostepKandydataController extends AbstractController
{
    #[Route('/', name: 'postep_kandydata_index')]
    #[IsGranted('ROLE_USER')]
    public function index(
        UserRepository $userRepository,
        PostepKandydataRepository $postepRepository,
    ): Response {
        $user = $this->getUser();

        // Sprawdź czy użytkownik to kandydat
        if ($this->isGranted('ROLE_KANDYDAT_PARTII')) {
            // Kandydat widzi tylko swój postęp
            /** @var User $user */
            $postep = $postepRepository->findOneBy(['kandydat' => $user]);
            if (!$postep) {
                $postep = new PostepKandydata();
                $postep->setKandydat($user);
                $postep->setDataRozpoczecia(new \DateTime());
                $postep->setAktualnyEtap(1);
            }

            return $this->render('postep_kandydata/candidate_view.html.twig', [
                'postep' => $postep,
            ]);
        }

        // Sprawdź czy użytkownik ma uprawnienia do zarządzania kandydatami
        /** @var User $user */
        if (!$this->isGranted('ROLE_FUNKCYJNY')
            && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')
            && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Brak uprawnień do zarządzania kandydatami.');
        }

        // Pobierz kandydatów z odpowiedniego oddziału lub wszystkich dla admina/prezesa
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ZARZAD_KRAJOWY')) {
            // Admin i zarząd krajowy widzi wszystkich kandydatów
            $kandydaci = $userRepository->findBy(['typUzytkownika' => 'kandydat']);
        } else {
            // Inni użytkownicy widzą tylko kandydatów ze swojego oddziału
            $oddzial = $user->getOddzial();
            if (!$oddzial) {
                // Jeśli nie ma oddziału, sprawdź okręg
                $okreg = $user->getOkreg();
                if ($okreg) {
                    // Pokaż kandydatów z całego okręgu
                    $kandydaci = $userRepository->createQueryBuilder('u')
                        ->where('u.typUzytkownika = :typ')
                        ->andWhere('u.okreg = :okreg')
                        ->setParameter('typ', 'kandydat')
                        ->setParameter('okreg', $okreg)
                        ->getQuery()
                        ->getResult();
                } else {
                    throw $this->createNotFoundException('Użytkownik nie ma przypisanego oddziału ani okręgu');
                }
            } else {
                $kandydaci = $userRepository->findByTypeAndOddzial('kandydat', $oddzial);
            }
        }

        // Pobierz postępy dla tych kandydatów
        $postepy = [];
        foreach ($kandydaci as $kandydat) {
            $postep = $postepRepository->findOneBy(['kandydat' => $kandydat]);
            if (!$postep) {
                $postep = new PostepKandydata();
                $postep->setKandydat($kandydat);
                $postep->setDataRozpoczecia(new \DateTime());
                $postep->setAktualnyEtap(1);
                /* @var \App\Entity\User $user */
                $postep->setPrezesOdpowiedzialny($user);
            }
            $postepy[] = $postep;
        }

        return $this->render('postep_kandydata/index.html.twig', [
            'postepy' => $postepy,
        ]);
    }

    #[Route('/{id}/aktualizuj', name: 'postep_kandydata_update')]
    public function update(
        User $kandydat,
        Request $request,
        EntityManagerInterface $entityManager,
        PostepKandydataRepository $postepRepository,
    ): Response {
        // Sprawdź uprawnienia - tylko funkcyjni mogą aktualizować
        if (!$this->isGranted('ROLE_FUNKCYJNY')
            && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')
            && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Brak uprawnień do aktualizacji postępu kandydatów.');
        }
        
        $postep = $postepRepository->findOneBy(['kandydat' => $kandydat]);

        if (!$postep) {
            $postep = new PostepKandydata();
            $postep->setKandydat($kandydat);
            $postep->setDataRozpoczecia(new \DateTime());
            $postep->setAktualnyEtap(1);
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $postep->setPrezesOdpowiedzialny($currentUser);
        }

        // Aktualizuj kroki na podstawie przesłanych danych
        // Obsługa nowych 8 kroków
        $currentUser = $this->getUser();
        
        for ($i = 1; $i <= 8; $i++) {
            $krokkName = "krok{$i}";
            $krokValue = $request->request->get($krokkName);
            
            if ($krokValue !== null) {
                $isChecked = (bool) $krokValue;
                
                // Pobierz aktualny stan kroku
                $methodName = "isKrok{$i}" . $this->getKrokMethodName($i);
                $currentState = method_exists($postep, $methodName) ? $postep->$methodName() : false;
                
                if ($isChecked && !$currentState) {
                    // Krok został włączony - odznacz
                    $postep->odznaczKrok($i, $currentUser);
                } elseif (!$isChecked && $currentState) {
                    // Krok został wyłączony - cofnij
                    $postep->odznaczKrokWstecz($i);
                }
            }
        }
        
        // Obsługa uwag
        $uwagi = $request->request->get('uwagi');
        if ($uwagi !== null) {
            $postep->setUwagi((string) $uwagi);
        }

        $entityManager->persist($postep);
        $entityManager->flush();

        $this->addFlash('success', 'Postęp kandydata został zaktualizowany.');

        return $this->redirectToRoute('postep_kandydata_index');
    }
    
    private function getKrokMethodName(int $numerKroku): string
    {
        $names = [
            1 => 'OplacenieSkladki',
            2 => 'WgranieZdjecia', 
            3 => 'WgranieCv',
            4 => 'UzupelnienieProfilu',
            5 => 'RozmowaPrekwalifikacyjna',
            6 => 'OpiniaRadyOddzialu',
            7 => 'UdzialWZebraniach',
            8 => 'Decyzja',
        ];
        
        return $names[$numerKroku] ?? '';
    }
    
    #[Route('/{id}/krok/{krok}/toggle', name: 'postep_kandydata_toggle_krok', methods: ['POST'])]
    public function toggleKrok(
        User $kandydat,
        int $krok,
        Request $request,
        EntityManagerInterface $entityManager,
        PostepKandydataRepository $postepRepository
    ): Response {
        // Sprawdź uprawnienia
        if (!$this->isGranted('ROLE_FUNKCYJNY')
            && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')
            && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Brak uprawnień do aktualizacji postępu kandydatów.');
        }
        
        if ($krok < 1 || $krok > 8) {
            throw $this->createNotFoundException('Niepoprawny numer kroku.');
        }
        
        $postep = $postepRepository->findOneBy(['kandydat' => $kandydat]);
        if (!$postep) {
            $postep = new PostepKandydata();
            $postep->setKandydat($kandydat);
            $postep->setDataRozpoczecia(new \DateTime());
            $postep->setAktualnyEtap(1);
            $postep->setPrezesOdpowiedzialny($this->getUser());
        }
        
        $currentUser = $this->getUser();
        $isChecked = (bool) $request->request->get('checked');
        
        if ($isChecked) {
            $postep->odznaczKrok($krok, $currentUser);
            $message = "Krok {$krok} został oznaczony jako wykonany.";
        } else {
            $postep->odznaczKrokWstecz($krok);
            $message = "Krok {$krok} został cofnięty.";
        }
        
        $entityManager->persist($postep);
        $entityManager->flush();
        
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true, 
                'message' => $message,
                'progress' => $postep->getPostepProcentowy()
            ]);
        }
        
        $this->addFlash('success', $message);
        return $this->redirectToRoute('postep_kandydata_show', ['id' => $kandydat->getId()]);
    }

    #[Route('/{id}/szczegoly', name: 'postep_kandydata_show')]
    public function show(
        User $kandydat,
        PostepKandydataRepository $postepRepository,
    ): Response {
        // Sprawdź uprawnienia
        if (!$this->isGranted('ROLE_FUNKCYJNY')
            && !$this->isGranted('ROLE_PELNOMOCNIK_STRUKTUR')
            && !$this->isGranted('ROLE_ADMIN')) {
            // Kandydat może zobaczyć tylko swój postęp
            if ($this->isGranted('ROLE_KANDYDAT_PARTII') && $kandydat->getId() !== $this->getUser()->getId()) {
                throw $this->createAccessDeniedException('Brak uprawnień do przeglądania postępu innych kandydatów.');
            } elseif (!$this->isGranted('ROLE_KANDYDAT_PARTII')) {
                throw $this->createAccessDeniedException('Brak uprawnień do przeglądania postępu kandydatów.');
            }
        }
        
        $postep = $postepRepository->findOneBy(['kandydat' => $kandydat]);

        if (!$postep) {
            $postep = new PostepKandydata();
            $postep->setKandydat($kandydat);
            $postep->setDataRozpoczecia(new \DateTime());
            $postep->setAktualnyEtap(1);
        }

        return $this->render('postep_kandydata/show.html.twig', [
            'postep' => $postep,
            'kandydat' => $kandydat,
        ]);
    }
}
