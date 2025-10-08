<?php

namespace App\Controller;

use App\Entity\PrzekazMedialny;
use App\Entity\PrzekazOdbiorca;
use App\Entity\PrzekazOdpowiedz;
use App\Entity\User;
use App\Repository\PrzekazMedialnyRepository;
use App\Repository\PrzekazOdpowiedzRepository;
use App\Repository\UserRepository;
use App\Service\TelegramBroadcastService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/przekaz-medialny')]
#[IsGranted('ROLE_PRZEKAZ')]
class PrzekazMedialnyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PrzekazMedialnyRepository $przekazRepository,
        private UserRepository $userRepository,
        private PrzekazOdpowiedzRepository $odpowiedzRepository,
        private TelegramBroadcastService $telegramService,
    ) {
    }

    #[Route('/', name: 'przekaz_medialny_index')]
    public function index(): Response
    {
        $przekazy = $this->przekazRepository->findRecent(50);
        $statystyki = $this->przekazRepository->getStatystyki();

        return $this->render('przekaz_medialny/index.html.twig', [
            'przekazy' => $przekazy,
            'statystyki' => $statystyki,
        ]);
    }

    #[Route('/nowy', name: 'przekaz_medialny_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Validate CSRF token
            if (!$this->isCsrfTokenValid('przekaz_new', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token');
            }

            $tytul = $request->request->get('tytul');
            $tresc = $request->request->get('tresc');

            if (empty($tytul) || empty($tresc)) {
                $this->addFlash('error', 'Tytuł i treść są wymagane');
                return $this->redirectToRoute('przekaz_medialny_new');
            }

            /** @var User $user */
            $user = $this->getUser();

            // Create temporary przekaz to calculate caption length
            $tempPrzekaz = new PrzekazMedialny();
            $tempPrzekaz->setTytul($tytul);
            $tempPrzekaz->setTresc($tresc);

            // Check if caption will fit
            if ($tempPrzekaz->getRemainingCaptionLength() < 0) {
                $footer = "Jeśli udostępniłeś ten przekaz, wyślij nam link do swojego posta!";
                $prefix = "📢 ";
                $htmlTags = 10;
                $maxAllowed = PrzekazMedialny::MAX_CAPTION_LENGTH - strlen($tytul) - strlen($footer) - strlen($prefix) - $htmlTags;
                $this->addFlash('error', "Treść przekazu jest za długa. Dla tego tytułu maksymalnie {$maxAllowed} znaków.");
                return $this->redirectToRoute('przekaz_medialny_new');
            }

            $przekaz = new PrzekazMedialny();
            $przekaz->setTytul($tytul);
            $przekaz->setTresc($tresc);
            $przekaz->setAutor($user);
            $przekaz->setStatus(PrzekazMedialny::STATUS_DRAFT);

            // Handle file upload
            $mediaFile = $request->files->get('media_file');
            if ($mediaFile) {
                // Validate file was uploaded
                if (!$mediaFile->isValid()) {
                    $this->addFlash('error', 'Błąd podczas przesyłania pliku: ' . $mediaFile->getErrorMessage());
                    return $this->redirectToRoute('przekaz_medialny_new');
                }

                // Get real MIME type using finfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMimeType = finfo_file($finfo, $mediaFile->getPathname());
                finfo_close($finfo);

                $allowedMimeTypes = PrzekazMedialny::getAllowedMimeTypes();

                // Validate real MIME type
                if (!in_array($realMimeType, $allowedMimeTypes)) {
                    $this->addFlash('error', 'Nieprawidłowy format pliku. Dozwolone: JPG, PNG, GIF, MP4, MPEG, MOV');
                    return $this->redirectToRoute('przekaz_medialny_new');
                }

                // Additional validation for images
                if (in_array($realMimeType, PrzekazMedialny::ALLOWED_IMAGE_TYPES)) {
                    $imageInfo = @getimagesize($mediaFile->getPathname());
                    if ($imageInfo === false) {
                        $this->addFlash('error', 'Plik nie jest prawidłowym obrazem');
                        return $this->redirectToRoute('przekaz_medialny_new');
                    }
                }

                // Check file size
                $maxSize = in_array($realMimeType, PrzekazMedialny::ALLOWED_IMAGE_TYPES)
                    ? PrzekazMedialny::MAX_PHOTO_SIZE
                    : PrzekazMedialny::MAX_VIDEO_SIZE;

                if ($mediaFile->getSize() > $maxSize) {
                    $maxSizeMB = $maxSize / (1024 * 1024);
                    $this->addFlash('error', "Plik jest za duży. Maksymalny rozmiar: {$maxSizeMB}MB");
                    return $this->redirectToRoute('przekaz_medialny_new');
                }

                // Determine media type
                $mediaType = in_array($realMimeType, PrzekazMedialny::ALLOWED_IMAGE_TYPES)
                    ? PrzekazMedialny::MEDIA_TYPE_PHOTO
                    : PrzekazMedialny::MEDIA_TYPE_VIDEO;

                // Generate secure filename
                $extension = $mediaFile->guessExtension() ?? 'bin';
                // Use random_bytes for cryptographically secure random name
                $fileName = bin2hex(random_bytes(16)) . '.' . preg_replace('/[^a-z0-9]/i', '', $extension);
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/przekazy';

                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true); // 0755 instead of 0777 for better security
                }

                // Move the file
                try {
                    $mediaFile->move($uploadDir, $fileName);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Błąd podczas zapisywania pliku: ' . $e->getMessage());
                    return $this->redirectToRoute('przekaz_medialny_new');
                }

                $przekaz->setMediaFilePath('uploads/przekazy/' . $fileName);
                $przekaz->setMediaType($mediaType);
            }

            $this->entityManager->persist($przekaz);
            $this->entityManager->flush();

            $this->addFlash('success', 'Przekaz medialny został utworzony jako szkic');

            return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
        }

        return $this->render('przekaz_medialny/new.html.twig');
    }

    #[Route('/{id}', name: 'przekaz_medialny_show', requirements: ['id' => '\d+'])]
    public function show(PrzekazMedialny $przekaz): Response
    {
        return $this->render('przekaz_medialny/show.html.twig', [
            'przekaz' => $przekaz,
        ]);
    }

    #[Route('/{id}/wyslij', name: 'przekaz_medialny_send', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function send(int $id): Response
    {
        // Increase execution time for large broadcasts
        set_time_limit(300); // 5 minutes
        ini_set('max_execution_time', '300');

        // Use pessimistic locking to prevent race condition
        $przekaz = $this->entityManager->find(
            PrzekazMedialny::class,
            $id,
            \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE
        );

        if (!$przekaz) {
            throw $this->createNotFoundException('Przekaz nie został znaleziony');
        }

        if ($przekaz->getStatus() !== PrzekazMedialny::STATUS_DRAFT) {
            $this->addFlash('error', 'Ten przekaz został już wysłany');
            return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
        }

        // Znajdź wszystkich użytkowników z połączonym Telegramem
        $users = $this->userRepository->findBy(['isTelegramConnected' => true]);

        if (empty($users)) {
            $this->addFlash('error', 'Brak użytkowników z połączonym Telegramem');
            return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
        }

        $przekaz->setStatus(PrzekazMedialny::STATUS_SENDING);
        $przekaz->setDataWyslania(new \DateTime());
        $przekaz->setLiczbaOdbiorcow(count($users));
        $this->entityManager->flush();

        // Check if media file exists
        if ($przekaz->hasMedia()) {
            $mediaPath = $this->getParameter('kernel.project_dir') . '/public/' . $przekaz->getMediaFilePath();
            if (!file_exists($mediaPath)) {
                $przekaz->setStatus(PrzekazMedialny::STATUS_FAILED);
                $this->entityManager->flush();
                $this->addFlash('error', 'Plik multimedialny nie został znaleziony');
                return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
            }
        }

        // Wyślij przekaz do wszystkich użytkowników
        $sent = 0;
        $failed = 0;
        $totalUsers = count($users);

        $this->logger->info("Starting broadcast to {$totalUsers} users", [
            'przekaz_id' => $przekaz->getId(),
            'total_users' => $totalUsers,
        ]);

        foreach ($users as $user) {
            $odbiorca = new PrzekazOdbiorca();
            $odbiorca->setPrzekazMedialny($przekaz);
            $odbiorca->setOdbiorca($user);
            $odbiorca->setStatus('pending');

            try {
                $caption = "📢 <b>{$przekaz->getTytul()}</b>\n\n";
                $caption .= $przekaz->getTresc() . "\n\n";
                $caption .= "Jeśli udostępniłeś ten przekaz, wyślij nam link do swojego posta!";

                // Send based on media type
                if ($przekaz->hasMedia()) {
                    $mediaPath = $this->getParameter('kernel.project_dir') . '/public/' . $przekaz->getMediaFilePath();

                    if ($przekaz->getMediaType() === PrzekazMedialny::MEDIA_TYPE_PHOTO) {
                        $result = $this->telegramService->sendPhotoToUser(
                            $user->getTelegramChatId(),
                            $mediaPath,
                            $caption
                        );
                    } else {
                        $result = $this->telegramService->sendVideoToUser(
                            $user->getTelegramChatId(),
                            $mediaPath,
                            $caption
                        );
                    }
                } else {
                    $result = $this->telegramService->sendMessageToUser(
                        $user->getTelegramChatId(),
                        $caption,
                        $przekaz->getId()
                    );
                }

                if ($result['ok']) {
                    $odbiorca->setStatus('sent');
                    $odbiorca->setDataWyslania(new \DateTime());
                    $odbiorca->setTelegramMessageId((string)$result['result']['message_id']);
                    $sent++;
                } else {
                    $odbiorca->setStatus('failed');
                    $odbiorca->setBlad($result['description'] ?? 'Unknown error');
                    $failed++;
                }
            } catch (\Exception $e) {
                $odbiorca->setStatus('failed');
                $odbiorca->setBlad($e->getMessage());
                $failed++;
            }

            $this->entityManager->persist($odbiorca);

            // Flush co N użytkowników żeby nie przeciążać pamięci
            if (($sent + $failed) % PrzekazMedialny::FLUSH_BATCH_SIZE === 0) {
                $this->entityManager->flush();
                $progress = round((($sent + $failed) / $totalUsers) * 100, 1);
                $this->logger->info("Broadcast progress: {$progress}%", [
                    'sent' => $sent,
                    'failed' => $failed,
                    'total' => $totalUsers,
                ]);
            }
        }

        $przekaz->setStatus(PrzekazMedialny::STATUS_SENT);
        $this->entityManager->flush();

        $this->logger->info("Broadcast completed", [
            'przekaz_id' => $przekaz->getId(),
            'sent' => $sent,
            'failed' => $failed,
            'total' => $totalUsers,
        ]);

        $this->addFlash('success', "Przekaz wysłany pomyślnie do {$sent} użytkowników. Błędy: {$failed}");

        return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
    }

    #[Route('/{id}/usun', name: 'przekaz_medialny_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(PrzekazMedialny $przekaz): Response
    {
        if ($przekaz->getStatus() !== PrzekazMedialny::STATUS_DRAFT) {
            $this->addFlash('error', 'Można usunąć tylko szkice przekazów');
            return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
        }

        // Delete media file if exists
        if ($przekaz->hasMedia()) {
            $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $przekaz->getMediaFilePath();
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $this->entityManager->remove($przekaz);
        $this->entityManager->flush();

        $this->addFlash('success', 'Przekaz został usunięty');

        return $this->redirectToRoute('przekaz_medialny_index');
    }

    #[Route('/odpowiedz/{id}/weryfikuj', name: 'przekaz_medialny_weryfikuj_odpowiedz', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function weryfikujOdpowiedz(PrzekazOdpowiedz $odpowiedz): Response
    {
        $odpowiedz->setZweryfikowany(!$odpowiedz->isZweryfikowany());
        $this->entityManager->flush();

        $status = $odpowiedz->isZweryfikowany() ? 'zweryfikowana' : 'odweryfikowana';
        $this->addFlash('success', "Odpowiedź została {$status}");

        return $this->redirectToRoute('przekaz_medialny_show', [
            'id' => $odpowiedz->getPrzekazMedialny()->getId()
        ]);
    }
}
