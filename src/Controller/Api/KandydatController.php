<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\PostepKandydata;
use App\Form\KandydatApiType;
use App\Repository\RegionRepository;
use App\Repository\OddzialRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/kandydat', name: 'api_kandydat_')]
class KandydatController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        RegionRepository $regionRepository,
        OddzialRepository $oddzialRepository,
        UserRepository $userRepository,
        \App\Repository\OkregRepository $okregRepository
    ): JsonResponse {
        $startTime = microtime(true);
        $content = $request->getContent();
        
        // Upewnij się, że content jest w UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }
        
        
        try {
            $requestData = json_decode($content, true, 512, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            $this->logger->error('JSON decode error', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], 400);
        }
        
        // Sprawdź API key (obsługa wielu kluczy po przecinku)
        $providedKey = $requestData['api_key'] ?? null;
        $expectedKeys = $this->getParameter('struktura_api_key') ?? 'default_api_key_123';

        // Rozdziel klucze po przecinku i usuń białe znaki
        $validKeys = array_map('trim', explode(',', $expectedKeys));

        $isAuthorized = false;
        if ($providedKey) {
            foreach ($validKeys as $validKey) {
                if (hash_equals($validKey, $providedKey)) {
                    $isAuthorized = true;
                    break;
                }
            }
        }

        if (!$isAuthorized) {
            $this->logger->warning('Nieautoryzowany dostęp do API kandydata', [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'provided_key' => $providedKey ?? 'missing'
            ]);

            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        if (!$requestData) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }

        // Usuń api_key z danych formularza
        unset($requestData['api_key']);

        // Sprawdź czy użytkownik z takim emailem już istnieje
        if (isset($requestData['email'])) {
            $existingUser = $userRepository->findOneBy(['email' => $requestData['email']]);
            if ($existingUser) {
                return new JsonResponse([
                    'error' => 'User with this email already exists',
                    'message' => 'Użytkownik z tym adresem email już istnieje'
                ], 409);
            }
        }

        // Stwórz nowego kandydata
        $kandydat = new User();
        
        // Stwórz formularz i przetwórz dane
        $form = $this->createForm(KandydatApiType::class, $kandydat);
        
        // Przygotuj dane formularza
        $form->submit($requestData);

        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = [
                    'field' => $error->getOrigin() ? $error->getOrigin()->getName() : 'general',
                    'message' => $error->getMessage()
                ];
            }
            
            return new JsonResponse([
                'error' => 'Validation failed',
                'errors' => $errors
            ], 400);
        }

        try {
            // Ustaw podstawowe właściwości kandydata
            $kandydat->setTypUzytkownika('kandydat');
            $kandydat->setStatus('aktywny');
            $kandydat->setRoles(['ROLE_KANDYDAT_PARTII']);
            
            // Wygeneruj hasło tymczasowe
            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = $passwordHasher->hashPassword($kandydat, $tempPassword);
            $kandydat->setPassword($hashedPassword);
            
            // Ustaw daty
            $now = new \DateTime();
            $kandydat->setDataRejestracji($now);
            $kandydat->setDataZlozeniaDeklaracji($now);
            
            if ($kandydat->getZgodaRodo()) {
                $kandydat->setDataZgodyRodo($now);
            }
            
            // Połącz adres zamieszkania w jeden string
            $adresZamieszkania = $this->buildAddress($requestData, 'Zamieszkania');
            if ($adresZamieszkania) {
                $kandydat->setAdresZamieszkania($adresZamieszkania);
            }
            
            // Połącz adres korespondencyjny w jeden string
            $adresKorespondencyjny = $this->buildAddress($requestData, 'Korespondencyjny');
            if ($adresKorespondencyjny) {
                $kandydat->setAdresKorespondencyjny($adresKorespondencyjny);
            }

            // Znajdź i przypisz region, okręg i oddział na podstawie nazw
            if (!empty($requestData['regionNazwa'])) {
                $region = $regionRepository->findOneBy(['nazwa' => $requestData['regionNazwa']]);
                if ($region) {
                    $kandydat->setRegion($region);
                }
            }

            if (!empty($requestData['okregNazwa'])) {
                $okreg = $okregRepository->findOneBy(['nazwa' => $requestData['okregNazwa']]);
                if ($okreg) {
                    $kandydat->setOkreg($okreg);
                }
            }
            
            if (!empty($requestData['oddzialNazwa'])) {
                $oddzial = $oddzialRepository->findOneBy(['nazwa' => $requestData['oddzialNazwa']]);
                if ($oddzial) {
                    $kandydat->setOddzial($oddzial);
                    // Jeśli nie podano okręgu, ustaw na podstawie oddziału
                    if (empty($requestData['okregNazwa']) && $oddzial->getOkreg()) {
                        $kandydat->setOkreg($oddzial->getOkreg());
                    }
                }
            }

            // Zapisz kandydata
            $this->entityManager->persist($kandydat);

            // Stwórz encję postępu kandydata
            $postepKandydata = new PostepKandydata();
            $postepKandydata->setKandydat($kandydat);
            $postepKandydata->setDataRozpoczecia($now);
            
            $this->entityManager->persist($postepKandydata);
            $this->entityManager->flush();

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Loguj pomyślne utworzenie kandydata
            $this->logger->info('Nowy kandydat utworzony przez API', [
                'kandydat_id' => $kandydat->getId(),
                'email' => $kandydat->getEmail(),
                'imie' => $kandydat->getImie(),
                'nazwisko' => $kandydat->getNazwisko(),
                'region' => $kandydat->getRegion()?->getNazwa(),
                'oddzial' => $kandydat->getOddzial()?->getNazwa(),
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'execution_time_ms' => $executionTime
            ]);

            // Loguj aktywność w systemie
            $this->activityLogService->log(
                'kandydat_utworzony_api',
                'Utworzono nowego kandydata przez API',
                'User',
                $kandydat->getId(),
                [
                    'kandydat_id' => $kandydat->getId(),
                    'email' => $kandydat->getEmail(),
                    'imie_nazwisko' => $kandydat->getFullName()
                ],
                null // brak użytkownika bo to API
            );

            $response = new JsonResponse([
                'success' => true,
                'message' => 'Kandydat utworzony pomyślnie',
                'data' => [
                    'id' => $kandydat->getId(),
                    'email' => $kandydat->getEmail(),
                    'full_name' => $kandydat->getFullName(),
                    'temporary_password' => $tempPassword,
                    'status' => $kandydat->getStatus(),
                    'type' => $kandydat->getTypUzytkownika(),
                    'region' => $kandydat->getRegion()?->getNazwa(),
                    'oddzial' => $kandydat->getOddzial()?->getNazwa(),
                    'okregNazwa' => $kandydat->getOkreg()?->getNazwa(),
                    'created_at' => $kandydat->getDataRejestracji()->format('Y-m-d H:i:s')
                ]
            ], 201);
            
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas tworzenia kandydata przez API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $requestData,
                'ip' => $request->getClientIp()
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'Wystąpił błąd podczas tworzenia kandydata'
            ], 500);
        }
    }
    
    /**
     * Buduje string adresu z poszczególnych pól
     * @param string $type 'Zamieszkania' lub 'Korespondencyjny'
     */
    private function buildAddress(array $requestData, string $type): ?string
    {
        $ulica = $requestData['ulica' . $type] ?? '';
        $nrDomu = $requestData['nrDomu' . $type] ?? '';
        $nrLokalu = $requestData['nrLokali' . $type] ?? '';
        // Specjalna obsługa dla miasta korespondencyjnego (różna nazwa pola)
        $miasto = $type === 'Korespondencyjny' 
            ? ($requestData['miastoKorespondencyjne'] ?? '') 
            : ($requestData['miastoZamieszkania'] ?? '');
        $kodPocztowy = $requestData['kodPocztowy' . $type] ?? '';
        
        // Dla adresu korespondencyjnego - jeśli którekolwiek pole jest puste, zwróć null
        // (adres korespondencyjny jest opcjonalny)
        if ($type === 'Korespondencyjny' && (empty($ulica) || empty($nrDomu) || empty($miasto) || empty($kodPocztowy))) {
            return null;
        }
        
        // Dla adresu zamieszkania - pola są wymagane, ale sprawdzamy czy istnieją
        if (empty($ulica) || empty($nrDomu) || empty($miasto) || empty($kodPocztowy)) {
            return null;
        }
        
        // Buduj pierwszą część adresu: ulica nrDomu lub ulica nrDomu/nrLokalu
        $adresPart1 = $ulica . ' ' . $nrDomu;
        if (!empty($nrLokalu) && $nrLokalu !== '') {
            $adresPart1 .= '/' . $nrLokalu;
        }
        
        // Buduj drugą część: miasto kodPocztowy
        $adresPart2 = $miasto . ' ' . $kodPocztowy;
        
        // Połącz części przecinkiem
        return $adresPart1 . ', ' . $adresPart2;
    }
}