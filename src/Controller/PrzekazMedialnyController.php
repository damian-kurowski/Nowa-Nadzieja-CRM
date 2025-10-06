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
            $tytul = $request->request->get('tytul');
            $tresc = $request->request->get('tresc');

            if (empty($tytul) || empty($tresc)) {
                $this->addFlash('error', 'Tytuł i treść są wymagane');
                return $this->redirectToRoute('przekaz_medialny_new');
            }

            if (strlen($tresc) > 4096) {
                $this->addFlash('error', 'Treść przekazu nie może przekraczać 4096 znaków (limit Telegram)');
                return $this->redirectToRoute('przekaz_medialny_new');
            }

            /** @var User $user */
            $user = $this->getUser();

            $przekaz = new PrzekazMedialny();
            $przekaz->setTytul($tytul);
            $przekaz->setTresc($tresc);
            $przekaz->setAutor($user);
            $przekaz->setStatus('draft');

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
    public function send(PrzekazMedialny $przekaz): Response
    {
        if ($przekaz->getStatus() !== 'draft') {
            $this->addFlash('error', 'Ten przekaz został już wysłany');
            return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
        }

        // Znajdź wszystkich użytkowników z połączonym Telegramem
        $users = $this->userRepository->findBy(['isTelegramConnected' => true]);

        if (empty($users)) {
            $this->addFlash('error', 'Brak użytkowników z połączonym Telegramem');
            return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
        }

        $przekaz->setStatus('sending');
        $przekaz->setDataWyslania(new \DateTime());
        $przekaz->setLiczbaOdbiorcow(count($users));
        $this->entityManager->flush();

        // Wyślij przekaz do wszystkich użytkowników
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $odbiorca = new PrzekazOdbiorca();
            $odbiorca->setPrzekazMedialny($przekaz);
            $odbiorca->setOdbiorca($user);
            $odbiorca->setStatus('pending');

            try {
                $message = "📢 <b>{$przekaz->getTytul()}</b>\n\n";
                $message .= $przekaz->getTresc() . "\n\n";
                $message .= "Jeśli udostępniłeś ten przekaz, wyślij nam link do swojego posta!";

                $result = $this->telegramService->sendMessageToUser(
                    $user->getTelegramChatId(),
                    $message,
                    $przekaz->getId()
                );

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

            // Flush co 50 użytkowników żeby nie przeciążać pamięci
            if (($sent + $failed) % 50 === 0) {
                $this->entityManager->flush();
            }
        }

        $przekaz->setStatus('sent');
        $this->entityManager->flush();

        $this->addFlash('success', "Przekaz wysłany pomyślnie do {$sent} użytkowników. Błędy: {$failed}");

        return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
    }

    #[Route('/{id}/usun', name: 'przekaz_medialny_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(PrzekazMedialny $przekaz): Response
    {
        if ($przekaz->getStatus() !== 'draft') {
            $this->addFlash('error', 'Można usunąć tylko szkice przekazów');
            return $this->redirectToRoute('przekaz_medialny_show', ['id' => $przekaz->getId()]);
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
