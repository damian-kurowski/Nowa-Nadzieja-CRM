<?php

namespace App\Controller;

use App\Entity\UmowaZlecenia;
use App\Entity\User;
use App\Form\UmowaZleceniaType;
use App\Repository\UmowaZleceniaRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use App\Service\PdfService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/umowy-zlecenia')]
#[IsGranted('ROLE_USER')]
class UmowaZleceniaController extends AbstractController
{
    public function __construct(
        private UmowaZleceniaRepository $umowaRepository,
        private UserRepository $userRepository,
        private ActivityLogService $activityLogService,
        private PdfService $pdfService,
        private SluggerInterface $slugger
    ) {}

    #[Route('', name: 'umowa_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userRoles = $user->getRoles();
        
        $isSkarbnikPartii = in_array('ROLE_SKARBNIK_PARTII', $userRoles);
        
        // Sprawdź czy użytkownik ma dostęp do umów
        // Skarbnik partii może zarządzać wszystkimi umowami
        // Inni użytkownicy mogą widzieć tylko swoje umowy jako zleceniobiorcy
        if (!$isSkarbnikPartii) {
            // Sprawdź czy użytkownik ma jakieś umowy jako zleceniobiorca
            $hasContracts = $this->umowaRepository->count(['zleceniobiorca' => $user]) > 0;
            if (!$hasContracts) {
                $this->addFlash('info', 'Nie masz jeszcze żadnych umów zlecenia.');
            }
        }

        // Pobierz filtry z zapytania
        $filters = [
            'status' => $request->query->get('status'),
            'zakres' => $request->query->get('zakres'),
            'tworca_id' => $request->query->get('tworca_id'),
            'zleceniobiorca_id' => $request->query->get('zleceniobiorca_id'),
            'data_od' => $request->query->get('data_od') ? new \DateTime($request->query->get('data_od')) : null,
            'data_do' => $request->query->get('data_do') ? new \DateTime($request->query->get('data_do')) : null,
            'okres_od' => $request->query->get('okres_od') ? new \DateTime($request->query->get('okres_od')) : null,
            'okres_do' => $request->query->get('okres_do') ? new \DateTime($request->query->get('okres_do')) : null,
            'wynagrodzenie_od' => $request->query->get('wynagrodzenie_od'),
            'wynagrodzenie_do' => $request->query->get('wynagrodzenie_do'),
            'numer_umowy' => $request->query->get('numer_umowy'),
            'sortuj_po' => $request->query->get('sortuj_po', 'dataUtworzenia'),
            'kierunek' => $request->query->get('kierunek', 'desc'),
        ];

        // Pobierz umowy z filtrami
        if ($isSkarbnikPartii) {
            $query = $this->umowaRepository->findAllWithFilters($filters);
        } else {
            // Dla zleceniobiorców - tylko ich umowy
            $filters['zleceniobiorca_id'] = $user->getId();
            $query = $this->umowaRepository->findAllWithFilters($filters);
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        // Pobierz statystyki
        $statistics = $this->umowaRepository->getStatistics($isSkarbnikPartii ? null : $user);
        
        // Pobierz dodatkowe informacje dla dashboardu
        $expiringSoon = $this->umowaRepository->findExpiringSoon($isSkarbnikPartii ? null : $user);
        $recentlySigned = $this->umowaRepository->findRecentlySigned($isSkarbnikPartii ? null : $user);
        $requireAttention = $this->umowaRepository->findRequiringAttention($isSkarbnikPartii ? null : $user);

        return $this->render('umowa_zlecenia/index.html.twig', [
            'pagination' => $pagination,
            'statistics' => $statistics,
            'expiringSoon' => $expiringSoon,
            'recentlySigned' => $recentlySigned,
            'requireAttention' => $requireAttention,
            'currentFilters' => $filters,
            'isSkarbnikPartii' => $isSkarbnikPartii,
            'statusChoices' => UmowaZlecenia::getStatusChoices(),
            'zakresChoices' => UmowaZlecenia::getZakresChoices(),
        ]);
    }

    #[Route('/nowa', name: 'umowa_new')]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $umowa = new UmowaZlecenia();
        $umowa->setTworca($user);

        $form = $this->createForm(UmowaZleceniaType::class, $umowa);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->addFlash('error', 'Formularz zawiera błędy walidacji');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle automatic account number retrieval
            if ($umowa->isPobranieKontaZSkladek() && $umowa->getZleceniobiorca()) {
                $accountNumber = $umowa->getZleceniobiorca()->getNumerKontaBankowego();
                if ($accountNumber) {
                    $umowa->setNumerKonta($accountNumber);
                } else {
                    $this->addFlash('warning', 'Zleceniobiorca nie ma przypisanego numeru konta w systemie składkowym. Musisz wprowadzić numer ręcznie.');
                    // Don't save if no account number available and checkbox was checked
                    return $this->render('umowa_zlecenia/new.html.twig', [
                        'form' => $form->createView(),
                        'umowa' => $umowa,
                    ]);
                }
            }

            try {
                $this->umowaRepository->save($umowa, true);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd podczas zapisywania umowy: ' . $e->getMessage());
                return $this->render('umowa_zlecenia/new.html.twig', [
                    'form' => $form->createView(),
                    'umowa' => $umowa,
                ]);
            }

            // Log activity
            $this->activityLogService->log(
                'umowa_create',
                sprintf('Utworzono umowę zlecenia %s na kwotę %s PLN', 
                    $umowa->getNumerUmowy(), 
                    $umowa->getFormattedWynagrodzenie()
                ),
                'UmowaZlecenia',
                $umowa->getId(),
                [
                    'numer_umowy' => $umowa->getNumerUmowy(),
                    'wynagrodzenie' => $umowa->getWynagrodzenie(),
                    'zleceniobiorca' => $umowa->getZleceniobiorca()->getImie() . ' ' . $umowa->getZleceniobiorca()->getNazwisko()
                ],
                $user
            );

            $this->addFlash('success', 'Umowa zlecenia została utworzona pomyślnie.');

            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }

        return $this->render('umowa_zlecenia/new.html.twig', [
            'form' => $form->createView(),
            'umowa' => $umowa,
        ]);
    }

    #[Route('/{id}', name: 'umowa_show', requirements: ['id' => '\d+'])]
    public function show(UmowaZlecenia $umowa): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Sprawdź dostęp do umowy
        if (!$this->canAccessUmowa($umowa, $user)) {
            throw $this->createAccessDeniedException('Nie masz dostępu do tej umowy.');
        }

        $canEdit = $this->canEditUmowa($umowa, $user);
        $canManageStatus = $this->canManageStatus($user);

        return $this->render('umowa_zlecenia/show.html.twig', [
            'umowa' => $umowa,
            'canEdit' => $canEdit,
            'canManageStatus' => $canManageStatus,
        ]);
    }

    #[Route('/{id}/edytuj', name: 'umowa_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, UmowaZlecenia $umowa): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->canEditUmowa($umowa, $user)) {
            throw $this->createAccessDeniedException('Nie możesz edytować tej umowy.');
        }

        $form = $this->createForm(UmowaZleceniaType::class, $umowa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle automatic account number retrieval for edit
            if ($umowa->isPobranieKontaZSkladek() && $umowa->getZleceniobiorca()) {
                $accountNumber = $umowa->getZleceniobiorca()->getNumerKontaBankowego();
                if ($accountNumber) {
                    $umowa->setNumerKonta($accountNumber);
                } else {
                    $this->addFlash('warning', 'Zleceniobiorca nie ma przypisanego numeru konta w systemie składkowym. Musisz wprowadzić numer ręcznie.');
                    return $this->render('umowa_zlecenia/edit.html.twig', [
                        'umowa' => $umowa,
                        'form' => $form->createView(),
                    ]);
                }
            }

            $this->umowaRepository->save($umowa, true);

            // Log activity
            $this->activityLogService->log(
                'umowa_edit',
                sprintf('Edytowano umowę zlecenia %s', $umowa->getNumerUmowy()),
                'UmowaZlecenia',
                $umowa->getId(),
                ['numer_umowy' => $umowa->getNumerUmowy()],
                $user
            );

            $this->addFlash('success', 'Umowa zlecenia została zaktualizowana.');

            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }

        return $this->render('umowa_zlecenia/edit.html.twig', [
            'umowa' => $umowa,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/kopiuj', name: 'umowa_copy', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function copy(Request $request, UmowaZlecenia $originalUmowa): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->canAccessUmowa($originalUmowa, $user)) {
            throw $this->createAccessDeniedException('Nie masz dostępu do tej umowy.');
        }

        // Utwórz kopię umowy
        $newUmowa = new UmowaZlecenia();
        $newUmowa->setTworca($user);
        $newUmowa->setZleceniobiorca($originalUmowa->getZleceniobiorca());
        $newUmowa->setSekretarzPartii($originalUmowa->getSekretarzPartii());
        $newUmowa->setZakresUmowy($originalUmowa->getZakresUmowy());
        $newUmowa->setOpisZakresu($originalUmowa->getOpisZakresu());
        $newUmowa->setTypOkresu($originalUmowa->getTypOkresu());
        $newUmowa->setWynagrodzenie($originalUmowa->getWynagrodzenie());
        $newUmowa->setNumerKonta($originalUmowa->getNumerKonta());
        $newUmowa->setPobranieKontaZSkladek($originalUmowa->isPobranieKontaZSkladek());
        $newUmowa->setCzyStudent($originalUmowa->isCzyStudent());
        
        // Ustaw domyślną datę rozpoczęcia na dzisiaj
        $newUmowa->setDataOd(new \DateTime());

        $form = $this->createForm(UmowaZleceniaType::class, $newUmowa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle automatic account number retrieval for copy
            if ($newUmowa->isPobranieKontaZSkladek() && $newUmowa->getZleceniobiorca()) {
                $accountNumber = $newUmowa->getZleceniobiorca()->getNumerKontaBankowego();
                if ($accountNumber) {
                    $newUmowa->setNumerKonta($accountNumber);
                } else {
                    $this->addFlash('warning', 'Zleceniobiorca nie ma przypisanego numeru konta w systemie składkowym. Musisz wprowadzić numer ręcznie.');
                    return $this->render('umowa_zlecenia/copy.html.twig', [
                        'form' => $form->createView(),
                        'originalUmowa' => $originalUmowa,
                        'newUmowa' => $newUmowa,
                    ]);
                }
            }

            $this->umowaRepository->save($newUmowa, true);

            // Log activity
            $this->activityLogService->log(
                'umowa_copy',
                sprintf('Skopiowano umowę %s jako nową umowę %s', 
                    $originalUmowa->getNumerUmowy(), 
                    $newUmowa->getNumerUmowy()
                ),
                'UmowaZlecenia',
                $newUmowa->getId(),
                [
                    'original_id' => $originalUmowa->getId(),
                    'original_numer' => $originalUmowa->getNumerUmowy(),
                    'new_numer' => $newUmowa->getNumerUmowy()
                ],
                $user
            );

            $this->addFlash('success', 'Umowa została skopiowana pomyślnie.');

            return $this->redirectToRoute('umowa_show', ['id' => $newUmowa->getId()]);
        }

        return $this->render('umowa_zlecenia/copy.html.twig', [
            'form' => $form->createView(),
            'originalUmowa' => $originalUmowa,
            'newUmowa' => $newUmowa,
        ]);
    }

    #[Route('/{id}/podpisz', name: 'umowa_sign', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function sign(Request $request, UmowaZlecenia $umowa): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isCsrfTokenValid('sign' . $umowa->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }
        
        if (!$umowa->isProjekt()) {
            $this->addFlash('error', 'Można podpisać tylko umowy o statusie "Projekt".');
            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }

        $umowa->setStatus(UmowaZlecenia::STATUS_PODPISANA);
        $umowa->setDataPodpisania(new \DateTime());

        $this->umowaRepository->save($umowa, true);

        // Log activity
        $this->activityLogService->log(
            'umowa_sign',
            sprintf('Podpisano umowę zlecenia %s', $umowa->getNumerUmowy()),
            'UmowaZlecenia',
            $umowa->getId(),
            ['numer_umowy' => $umowa->getNumerUmowy()],
            $user
        );

        $this->addFlash('success', 'Umowa została podpisana.');

        return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
    }

    #[Route('/{id}/anuluj', name: 'umowa_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function cancel(Request $request, UmowaZlecenia $umowa): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isCsrfTokenValid('cancel' . $umowa->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }

        if ($umowa->isZakonczona()) {
            $this->addFlash('error', 'Nie można anulować zakończonej umowy.');
            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }

        $umowa->setStatus(UmowaZlecenia::STATUS_ANULOWANA);

        $this->umowaRepository->save($umowa, true);

        // Log activity
        $this->activityLogService->log(
            'umowa_cancel',
            sprintf('Anulowano umowę zlecenia %s', $umowa->getNumerUmowy()),
            'UmowaZlecenia',
            $umowa->getId(),
            ['numer_umowy' => $umowa->getNumerUmowy()],
            $user
        );

        $this->addFlash('warning', 'Umowa została anulowana.');

        return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
    }

    #[Route('/{id}/zakoncz', name: 'umowa_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function complete(Request $request, UmowaZlecenia $umowa): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isCsrfTokenValid('complete' . $umowa->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }
        
        if (!$umowa->isPodpisana()) {
            $this->addFlash('error', 'Można zakończyć tylko podpisane umowy.');
            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }

        $umowa->setStatus(UmowaZlecenia::STATUS_ZAKONCZONA);
        $umowa->setDataZakonczenia(new \DateTime());

        $this->umowaRepository->save($umowa, true);

        // Log activity
        $this->activityLogService->log(
            'umowa_complete',
            sprintf('Zakończono umowę zlecenia %s', $umowa->getNumerUmowy()),
            'UmowaZlecenia',
            $umowa->getId(),
            ['numer_umowy' => $umowa->getNumerUmowy()],
            $user
        );

        $this->addFlash('success', 'Umowa została zakończona.');

        return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
    }

    #[Route('/{id}/pdf', name: 'umowa_pdf', requirements: ['id' => '\d+'])]
    public function generatePdf(UmowaZlecenia $umowa): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->canAccessUmowa($umowa, $user)) {
            throw $this->createAccessDeniedException('Nie masz dostępu do tej umowy.');
        }

        // Get secretary data automatically if not set
        $sekretarz = $umowa->getSekretarzPartii();
        if (!$sekretarz) {
            // Find secretary by searching through all users
            $sekretarz = null;
            $allUsers = $this->userRepository->findAll();
            foreach ($allUsers as $user) {
                if (in_array('ROLE_SEKRETARZ', $user->getRoles()) || in_array('ROLE_SEKRETARZ_PARTII', $user->getRoles())) {
                    $sekretarz = $user;
                    break;
                }
            }
        }

        $html = $this->renderView('umowa_zlecenia/pdf_template.html.twig', [
            'umowa' => $umowa,
            'sekretarz' => $sekretarz,
            'dataGenerowania' => new \DateTime(),
        ]);

        $filename = sprintf('umowa_%s_%s.pdf', 
            $umowa->getNumerUmowy(), 
            $umowa->getDataUtworzenia()->format('Y-m-d')
        );

        return $this->pdfService->generatePdfResponse($html, $filename);
    }

    #[Route('/{id}/usun', name: 'umowa_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function delete(Request $request, UmowaZlecenia $umowa): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$this->isCsrfTokenValid('delete' . $umowa->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }

        if ($umowa->isPodpisana() || $umowa->isZakonczona()) {
            $this->addFlash('error', 'Nie można usunąć podpisanej lub zakończonej umowy.');
            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }

        $numerUmowy = $umowa->getNumerUmowy();

        // Log activity before deletion
        $this->activityLogService->log(
            'umowa_delete',
            sprintf('Usunięto umowę zlecenia %s', $numerUmowy),
            'UmowaZlecenia',
            $umowa->getId(),
            ['numer_umowy' => $numerUmowy],
            $user
        );

        $this->umowaRepository->remove($umowa, true);

        $this->addFlash('success', sprintf('Umowa %s została usunięta.', $numerUmowy));

        return $this->redirectToRoute('umowa_index');
    }

    #[Route('/api/user/{id}/account-number', name: 'umowa_get_user_account', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function getUserAccountNumber(User $user): Response
    {
        $accountNumber = $user->getNumerKontaBankowego();
        
        return $this->json([
            'success' => true,
            'accountNumber' => $accountNumber,
            'hasAccountNumber' => !empty($accountNumber),
            'message' => $accountNumber 
                ? 'Numer konta pobrano z danych składkowych'
                : 'Użytkownik nie ma przypisanego numeru konta w systemie składkowym'
        ]);
    }

    #[Route('/{id}/upload-skan', name: 'umowa_upload_scan', requirements: ['id' => '\d+'])]
    public function uploadScan(Request $request, UmowaZlecenia $umowa): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Sprawdź czy użytkownik może przesłać skan
        if (!$this->canUploadScan($umowa, $user)) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do przesłania skanu tej umowy.');
        }

        if ($request->isMethod('POST')) {
            // Sprawdź CSRF token
            if (!$this->isCsrfTokenValid('upload' . $umowa->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
                return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
            }
            
            /** @var UploadedFile|null $skanFile */
            $skanFile = $request->files->get('skan');
            
            if ($skanFile) {
                // Sprawdź czy plik jest valid
                if (!$skanFile->isValid()) {
                    $this->addFlash('error', 'Przesłany plik jest nieprawidłowy: ' . $skanFile->getErrorMessage());
                    return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
                }
                
                $originalFilename = pathinfo($skanFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$skanFile->guessExtension();

                try {
                    // Użyj ścieżki kompatybilnej z Windows
                    $uploadDirectory = str_replace('/', DIRECTORY_SEPARATOR, $this->getParameter('kernel.project_dir').'/public/uploads/scans');
                    if (!is_dir($uploadDirectory)) {
                        if (!mkdir($uploadDirectory, 0755, true)) {
                            throw new \Exception('Nie można utworzyć katalogu: ' . $uploadDirectory);
                        }
                    }
                    
                    $skanFile->move($uploadDirectory, $newFilename);
                    
                    // Usuń stary skan jeśli istnieje
                    if ($umowa->getSkanPodpisanejUmowy()) {
                        $oldFile = $uploadDirectory . DIRECTORY_SEPARATOR . $umowa->getSkanPodpisanejUmowy();
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                    
                    $umowa->setSkanPodpisanejUmowy($newFilename);
                    
                    // Zmień status umowy na podpisana tylko jeśli był projekt
                    if ($umowa->isProjekt()) {
                        $umowa->setStatus(UmowaZlecenia::STATUS_PODPISANA);
                        $umowa->setDataPodpisania(new \DateTime());
                    }
                    
                    $this->umowaRepository->save($umowa, true);

                    // Log activity
                    $this->activityLogService->log(
                        'umowa_scan_uploaded',
                        sprintf('Przesłano skan podpisanej umowy %s', $umowa->getNumerUmowy()),
                        'UmowaZlecenia',
                        $umowa->getId(),
                        ['numer_umowy' => $umowa->getNumerUmowy(), 'filename' => $newFilename],
                        $user
                    );

                    $this->addFlash('success', 'Skan podpisanej umowy został przesłany pomyślnie. Umowa została oznaczona jako podpisana.');
                    
                } catch (FileException $e) {
                    $this->addFlash('error', 'Błąd podczas przesyłania pliku: ' . $e->getMessage());
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Błąd: ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Nie wybrano pliku do przesłania. Upewnij się, że wybrałeś plik przed wysłaniem formularza.');
            }
            
            return $this->redirectToRoute('umowa_show', ['id' => $umowa->getId()]);
        }

        return $this->render('umowa_zlecenia/upload_scan.html.twig', [
            'umowa' => $umowa,
        ]);
    }

    private function canAccessUmowa(UmowaZlecenia $umowa, User $user): bool
    {
        $userRoles = $user->getRoles();
        
        // Skarbnik partii może wszystko
        if (in_array('ROLE_SKARBNIK_PARTII', $userRoles)) {
            return true;
        }
        
        // Twórca może dostęp do swoich umów
        return $umowa->getTworca() === $user || $umowa->getZleceniobiorca() === $user;
    }

    private function canEditUmowa(UmowaZlecenia $umowa, User $user): bool
    {
        // Można edytować tylko projekty
        if (!$umowa->isProjekt()) {
            return false;
        }

        return $this->canAccessUmowa($umowa, $user);
    }

    private function canManageStatus(User $user): bool
    {
        return in_array('ROLE_SKARBNIK_PARTII', $user->getRoles());
    }

    private function canUploadScan(UmowaZlecenia $umowa, User $user): bool
    {
        // Tylko zleceniobiorca może przesłać skan swojej umowy
        // I tylko jeśli umowa jest w statusie projekt
        return $umowa->getZleceniobiorca() === $user && $umowa->isProjekt();
    }
}