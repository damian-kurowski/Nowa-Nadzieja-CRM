<?php

namespace App\Controller\Api;

use App\Repository\RegionRepository;
use App\Repository\OkregRepository;
use App\Repository\OddzialRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/struktura', name: 'api_struktura_')]
class StrukturController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService,
        private LoggerInterface $logger
    ) {}
    private const ROLE_MAPPING = [
        // Role regionalne
        'ROLE_PREZES_REGIONU' => 'Prezes Regionu',
        
        // Role okręgowe
        'ROLE_PREZES_OKREGU' => 'Prezes Okręgu',
        'ROLE_WICEPREZES_OKREGU' => 'Wiceprezes Okręgu',
        'ROLE_SEKRETARZ_OKREGU' => 'Sekretarz Okręgu',
        'ROLE_SKARBNIK_OKREGU' => 'Skarbnik Okręgu',
        
        // Role oddziałowe
        'ROLE_PRZEWODNICZACY_ODDZIALU' => 'Przewodniczący Oddziału',
        'ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU' => 'Zastępca Przewodniczącego Oddziału',
        'ROLE_SEKRETARZ_ODDZIALU' => 'Sekretarz Oddziału',
    ];
    
    // Role które mogą mieć wielu posiadaczy
    private const MULTIPLE_HOLDER_ROLES = [
        'ROLE_WICEPREZES_OKREGU',
        'ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU'
    ];

    #[Route('', name: 'index', methods: ['POST'])]
    public function index(
        Request $request,
        RegionRepository $regionRepository,
        OkregRepository $okregRepository,
        OddzialRepository $oddzialRepository,
        UserRepository $userRepository
    ): JsonResponse {
        // Sprawdź API key
        if (!$this->validateApiKey($request)) {
            $requestData = json_decode($request->getContent(), true);
            $this->logger->warning('Nieautoryzowany dostęp do API struktury', [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'provided_key' => $requestData['api_key'] ?? 'missing'
            ]);
            
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $startTime = microtime(true);
        $result = ['regions' => []];
        
        // Pobierz wszystkie regiony
        $regions = $regionRepository->findAll();
        
        foreach ($regions as $region) {
            $regionData = [
                'id' => $region->getId(),
                'name' => $region->getNazwa(),
                'roles' => $this->getRegionRoles($region, $userRepository),
                'okregi' => []
            ];
            
            // Pobierz okręgi tego regionu
            $okregi = $okregRepository->findBy(['region' => $region]);
            
            foreach ($okregi as $okreg) {
                $okregData = [
                    'id' => $okreg->getId(),
                    'name' => $okreg->getNazwa(),
                    'roles' => $this->getOkregRoles($okreg, $userRepository),
                    'oddzialy' => []
                ];
                
                // Pobierz oddziały tego okręgu
                $oddzialy = $oddzialRepository->findBy(['okreg' => $okreg]);
                
                foreach ($oddzialy as $oddzial) {
                    $oddzialData = [
                        'id' => $oddzial->getId(),
                        'name' => $oddzial->getNazwa(),
                        'roles' => $this->getOddzialRoles($oddzial, $userRepository)
                    ];
                    
                    $okregData['oddzialy'][] = $oddzialData;
                }
                
                $regionData['okregi'][] = $okregData;
            }
            
            $result['regions'][] = $regionData;
        }
        
        $response = new JsonResponse($result);
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Loguj pomyślne wywołanie API
        $this->logger->info('API struktury wywołane pomyślnie', [
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'execution_time_ms' => $executionTime,
            'regions_count' => count($result['regions']),
            'total_okregi' => array_sum(array_map(fn($r) => count($r['okregi']), $result['regions'])),
            'total_oddzialy' => array_sum(array_map(fn($r) => 
                array_sum(array_map(fn($o) => count($o['oddzialy']), $r['okregi'])),
                $result['regions']
            ))
        ]);
        
        return $response;
    }
    
    private function getRegionRoles($region, UserRepository $userRepository): array
    {
        $roles = [];
        $regionalRoles = ['ROLE_PREZES_REGIONU'];
        
        foreach ($regionalRoles as $roleKey) {
            if (isset(self::ROLE_MAPPING[$roleKey])) {
                $roleName = self::ROLE_MAPPING[$roleKey];
                
                // Używamy natywnego SQL dla PostgreSQL JSON
                $sql = "SELECT u.* FROM \"user\" u 
                        INNER JOIN okreg o ON u.okreg_id = o.id 
                        INNER JOIN region r ON o.region_id = r.id 
                        WHERE r.id = :region_id AND u.roles::jsonb @> :role 
                        LIMIT 1";
                        
                $conn = $userRepository->getEntityManager()->getConnection();
                $stmt = $conn->prepare($sql);
                $result = $stmt->executeQuery([
                    'region_id' => $region->getId(),
                    'role' => json_encode([$roleKey])
                ]);
                
                $userData = $result->fetchAssociative();
                $user = $userData ? $userRepository->find($userData['id']) : null;
                
                $roles[] = [
                    'role' => $roleName,
                    'person' => $user ? $this->formatPerson($user) : null
                ];
            }
        }
        
        return $roles;
    }
    
    private function getOkregRoles($okreg, UserRepository $userRepository): array
    {
        $roles = [];
        $okregRoles = ['ROLE_PREZES_OKREGU', 'ROLE_WICEPREZES_OKREGU', 'ROLE_SEKRETARZ_OKREGU', 'ROLE_SKARBNIK_OKREGU'];
        
        foreach ($okregRoles as $roleKey) {
            if (isset(self::ROLE_MAPPING[$roleKey])) {
                $roleName = self::ROLE_MAPPING[$roleKey];
                
                // Sprawdź czy ta rola może mieć wielu posiadaczy
                $canHaveMultiple = in_array($roleKey, self::MULTIPLE_HOLDER_ROLES);
                
                // Używamy natywnego SQL dla PostgreSQL JSON
                $sql = "SELECT u.* FROM \"user\" u 
                        WHERE u.okreg_id = :okreg_id AND u.roles::jsonb @> :role";
                
                if (!$canHaveMultiple) {
                    $sql .= " LIMIT 1";
                }
                        
                $conn = $userRepository->getEntityManager()->getConnection();
                $stmt = $conn->prepare($sql);
                $result = $stmt->executeQuery([
                    'okreg_id' => $okreg->getId(),
                    'role' => json_encode([$roleKey])
                ]);
                
                if ($canHaveMultiple) {
                    // Dla ról z wieloma posiadaczami zwracamy tablicę osób
                    $persons = [];
                    while ($userData = $result->fetchAssociative()) {
                        $user = $userRepository->find($userData['id']);
                        if ($user) {
                            $persons[] = $this->formatPerson($user);
                        }
                    }
                    
                    $roles[] = [
                        'role' => $roleName,
                        'persons' => $persons
                    ];
                } else {
                    // Dla ról z jednym posiadaczem zwracamy pojedynczą osobę
                    $userData = $result->fetchAssociative();
                    $user = $userData ? $userRepository->find($userData['id']) : null;
                    
                    $roles[] = [
                        'role' => $roleName,
                        'person' => $user ? $this->formatPerson($user) : null
                    ];
                }
            }
        }
        
        return $roles;
    }
    
    private function getOddzialRoles($oddzial, UserRepository $userRepository): array
    {
        $roles = [];
        $oddzialRoles = ['ROLE_PRZEWODNICZACY_ODDZIALU', 'ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU', 'ROLE_SEKRETARZ_ODDZIALU'];
        
        foreach ($oddzialRoles as $roleKey) {
            if (isset(self::ROLE_MAPPING[$roleKey])) {
                $roleName = self::ROLE_MAPPING[$roleKey];
                
                // Sprawdź czy ta rola może mieć wielu posiadaczy
                $canHaveMultiple = in_array($roleKey, self::MULTIPLE_HOLDER_ROLES);
                
                // Używamy natywnego SQL dla PostgreSQL JSON
                $sql = "SELECT u.* FROM \"user\" u 
                        WHERE u.oddzial_id = :oddzial_id AND u.roles::jsonb @> :role";
                
                if (!$canHaveMultiple) {
                    $sql .= " LIMIT 1";
                }
                        
                $conn = $userRepository->getEntityManager()->getConnection();
                $stmt = $conn->prepare($sql);
                $result = $stmt->executeQuery([
                    'oddzial_id' => $oddzial->getId(),
                    'role' => json_encode([$roleKey])
                ]);
                
                if ($canHaveMultiple) {
                    // Dla ról z wieloma posiadaczami zwracamy tablicę osób
                    $persons = [];
                    while ($userData = $result->fetchAssociative()) {
                        $user = $userRepository->find($userData['id']);
                        if ($user) {
                            $persons[] = $this->formatPerson($user);
                        }
                    }
                    
                    $roles[] = [
                        'role' => $roleName,
                        'persons' => $persons
                    ];
                } else {
                    // Dla ról z jednym posiadaczem zwracamy pojedynczą osobę
                    $userData = $result->fetchAssociative();
                    $user = $userData ? $userRepository->find($userData['id']) : null;
                    
                    $roles[] = [
                        'role' => $roleName,
                        'person' => $user ? $this->formatPerson($user) : null
                    ];
                }
            }
        }
        
        return $roles;
    }
    
    private function formatPerson($user): array
    {
        $data = [
            'first_name' => $user->getImie(),
            'last_name' => $user->getNazwisko(),
        ];

        // Dodaj email tylko jeśli użytkownik wyraził zgodę
        if ($user->isZgodaApiEmail()) {
            $data['email'] = $user->getEmail();
        }

        // Dodaj telefon tylko jeśli użytkownik wyraził zgodę
        if ($user->isZgodaApiTelefon()) {
            $data['phone'] = $user->getTelefon();
        }

        // Dodaj zdjęcie tylko jeśli użytkownik wyraził zgodę
        if ($user->isZgodaApiZdjecie() && $user->getZdjecie()) {
            $data['photo_url'] = '/uploads/photos/' . $user->getZdjecie();
        }

        return $data;
    }
    
    private function validateApiKey(Request $request): bool
    {
        // Pobierz klucz API z ciała żądania POST
        $requestData = json_decode($request->getContent(), true);
        $providedKey = $requestData['api_key'] ?? null;
        
        if (!$providedKey) {
            return false;
        }
        
        $expectedKey = $this->getParameter('struktura_api_key');
        
        return hash_equals($expectedKey, $providedKey);
    }
}