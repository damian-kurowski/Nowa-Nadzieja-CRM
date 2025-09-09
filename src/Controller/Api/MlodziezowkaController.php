<?php

namespace App\Controller\Api;

use App\Entity\MdwKandydat;
use App\Form\MdwKandydatApiType;
use App\Repository\RegionRepository;
use App\Repository\OkregRepository;
use App\Repository\MdwKandydatRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/mlodziezowka', name: 'api_mlodziezowka_')]
class MlodziezowkaController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/test', name: 'test', methods: ['GET'])]
    public function test(): JsonResponse {
        return new JsonResponse([
            'success' => true,
            'message' => 'Test endpoint działa'
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        RegionRepository $regionRepository,
        OkregRepository $okregRepository,
        MdwKandydatRepository $mdwKandydatRepository
    ): JsonResponse {
        $content = $request->getContent();
        
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }
        
        try {
            $requestData = json_decode($content, true, 512, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            $this->logger->error('JSON decode error', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], 400);
        }
        
        $providedKey = $requestData['api_key'] ?? null;
        $expectedKey = $this->getParameter('struktura_api_key') ?? 'default_api_key_123';
        
        if (!$providedKey || !hash_equals($expectedKey, $providedKey)) {
            $this->logger->warning('Nieautoryzowany dostęp do API młodzieżówki', [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'provided_key' => $providedKey ?? 'missing'
            ]);
            
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        if (!$requestData) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }

        unset($requestData['api_key']);
        if (isset($requestData['email'])) {
            $existingKandydat = $mdwKandydatRepository->findOneBy(['email' => $requestData['email']]);
            if ($existingKandydat) {
                return new JsonResponse([
                    'error' => 'User with this email already exists',
                    'message' => 'Kandydat z tym adresem email już istnieje'
                ], 409);
            }
        }
        if (isset($requestData['pesel'])) {
            $existingKandydat = $mdwKandydatRepository->findOneBy(['pesel' => $requestData['pesel']]);
            if ($existingKandydat) {
                return new JsonResponse([
                    'error' => 'User with this PESEL already exists',
                    'message' => 'Kandydat z tym numerem PESEL już istnieje'
                ], 409);
            }
        }

        $kandydat = new MdwKandydat();
        $form = $this->createForm(MdwKandydatApiType::class, $kandydat);
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
            $kandydat->setDataZlozeniaDeklaracji(new \DateTime());
            $adresZamieszkania = $this->buildAddress($requestData);
            if ($adresZamieszkania) {
                $kandydat->setAdresZamieszkania($adresZamieszkania);
            }
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

            $this->entityManager->persist($kandydat);
            $this->entityManager->flush();

            $this->logger->info('Nowy kandydat młodzieżówki utworzony przez API', [
                'kandydat_id' => $kandydat->getId(),
                'email' => $kandydat->getEmail(),
                'imie_nazwisko' => $kandydat->getFullName()
            ]);
            $this->activityLogService->log(
                'mdw_kandydat_utworzony_api',
                'Utworzono nowego kandydata młodzieżówki przez API',
                'MdwKandydat',
                $kandydat->getId(),
                [
                    'kandydat_id' => $kandydat->getId(),
                    'email' => $kandydat->getEmail(),
                    'imie_nazwisko' => $kandydat->getFullName()
                ],
                null
            );

            $response = new JsonResponse([
                'success' => true,
                'message' => 'Kandydat młodzieżówki utworzony pomyślnie',
                'data' => [
                    'id' => $kandydat->getId(),
                    'email' => $kandydat->getEmail(),
                    'full_name' => $kandydat->getFullName(),
                    'pesel' => $kandydat->getPesel(),
                    'adres_zamieszkania' => $kandydat->getAdresZamieszkania(),
                    'telefon' => $kandydat->getTelefon(),
                    'region' => $kandydat->getRegion()?->getNazwa(),
                    'okreg' => $kandydat->getOkreg()?->getNazwa(),
                    'data_zlozenia_deklaracji' => $kandydat->getDataZlozeniaDeklaracji()->format('Y-m-d'),
                    'created_at' => $kandydat->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ], 201);
            
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas tworzenia kandydata młodzieżówki przez API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $requestData,
                'ip' => $request->getClientIp()
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'Wystąpił błąd podczas tworzenia kandydata młodzieżówki'
            ], 500);
        }
    }
    
    private function buildAddress(array $requestData): ?string
    {
        $ulica = $requestData['ulicaZamieszkania'] ?? '';
        $nrDomu = $requestData['nrDomuZamieszkania'] ?? '';
        $nrLokalu = $requestData['nrLokaliZamieszkania'] ?? '';
        $miasto = $requestData['miastoZamieszkania'] ?? '';
        $kodPocztowy = $requestData['kodPocztowyZamieszkania'] ?? '';
        
        if (empty($ulica) || empty($nrDomu) || empty($miasto) || empty($kodPocztowy)) {
            return null;
        }
        
        $adresPart1 = $ulica . ' ' . $nrDomu;
        if (!empty($nrLokalu) && $nrLokalu !== '') {
            $adresPart1 .= '/' . $nrLokalu;
        }
        
        $adresPart2 = $miasto . ' ' . $kodPocztowy;
        return $adresPart1 . ', ' . $adresPart2;
    }
}