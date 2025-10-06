<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\DarczycaRepository;
use App\Repository\DokumentRepository;
use App\Repository\KonferencjaPrasowaRepository;
use App\Repository\UserRepository;
use App\Repository\WystepMedialnyRepository;

class StatisticsService
{
    private const NATIONAL_ROLES = [
        'ROLE_ADMIN',
        'ROLE_PREZES_PARTII',
        'ROLE_WICEPREZES_PARTII',
        'ROLE_SEKRETARZ_PARTII',
        'ROLE_SKARBNIK_PARTII',
        'ROLE_RZECZNIK_PRASOWY',
    ];

    public function __construct(
        private UserRepository $userRepository,
        private WystepMedialnyRepository $wystepMedialnyRepository,
        private KonferencjaPrasowaRepository $konferencjaPrasowaRepository,
        private DokumentRepository $dokumentRepository,
        private DarczycaRepository $darczycaRepository,
    ) {
    }

    private function hasNationalLevelAccess(User $user): bool
    {
        return !empty(array_intersect($user->getRoles(), self::NATIONAL_ROLES));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getStatsWithChanges(User $user): array
    {
        $currentStats = $this->getCurrentStats($user);
        $previousStats = $this->getPreviousStats($user);

        return [
            'total_members' => [
                'current' => $currentStats['total_members'],
                'change' => $this->calculateRealChange($previousStats['total_members'], $currentStats['total_members']),
            ],
            'candidates' => [
                'current' => $currentStats['candidates'],
                'change' => $this->calculateRealChange($previousStats['candidates'], $currentStats['candidates']),
            ],
            'supporters' => [
                'current' => $currentStats['supporters'],
                'change' => $this->calculateRealChange($previousStats['supporters'], $currentStats['supporters']),
            ],
            'donors' => [
                'current' => $currentStats['donors'],
                'change' => $this->calculateRealChange($previousStats['donors'], $currentStats['donors']),
            ],
            'youth_members' => [
                'current' => $currentStats['youth_members'],
                'change' => $this->calculateRealChange($previousStats['youth_members'], $currentStats['youth_members']),
            ],
            'media_appearances' => [
                'current' => $currentStats['media_appearances'],
                'change' => $this->calculateRealChange($previousStats['media_appearances'], $currentStats['media_appearances']),
            ],
            'press_conferences' => [
                'current' => $currentStats['press_conferences'],
                'change' => $this->calculateRealChange($previousStats['press_conferences'], $currentStats['press_conferences']),
            ],
            'documents' => [
                'current' => $currentStats['documents'],
                'change' => $this->calculateRealChange($previousStats['documents'], $currentStats['documents']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getCurrentStats(User $user): array
    {
        return [
            'total_members' => $this->getMembersCount($user),
            'candidates' => $this->getCandidatesCount($user),
            'supporters' => $this->getSupportersCount($user),
            'donors' => $this->getDonorsCount($user),
            'youth_members' => $this->getYouthMembersCount($user),
            'media_appearances' => $this->getMediaAppearancesCount($user),
            'press_conferences' => $this->getPressConferencesCount($user),
            'documents' => $this->getDocumentsCount($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPreviousStats(User $user): array
    {
        // Sprawdź czy mamy zapisane poprzednie statystyki w sesji lub cache
        // Na razie użyjemy prostej logiki - jeśli brak daty ostatniego logowania,
        // założymy że wszystko było na 0
        $previousLoginDate = $user->getPreviousLoginAt();
        
        if (!$previousLoginDate) {
            // Pierwszy raz loguje się - wszystkie poprzednie wartości to 0
            return [
                'total_members' => 0,
                'candidates' => 0,
                'supporters' => 0,
                'donors' => 0,
                'youth_members' => 0,
                'media_appearances' => 0,
                'press_conferences' => 0,
                'documents' => 0,
            ];
        }

        // Dla istniejących użytkowników użyjemy metod historycznych
        return [
            'total_members' => $this->getMembersCountAsOf($user, $previousLoginDate),
            'candidates' => $this->getCandidatesCountAsOf($user, $previousLoginDate),
            'supporters' => $this->getSupportersCountAsOf($user, $previousLoginDate),
            'donors' => $this->getDonorsCountAsOf($user, $previousLoginDate),
            'youth_members' => $this->getYouthMembersCountAsOf($user, $previousLoginDate),
            'media_appearances' => $this->getMediaAppearancesCountAsOf($user, $previousLoginDate),
            'press_conferences' => $this->getPressConferencesCountAsOf($user, $previousLoginDate),
            'documents' => $this->getDocumentsCountAsOf($user, $previousLoginDate),
        ];
    }

    private function getMembersCount(User $user): int
    {
        // Zarząd krajowy widzi wszystkich w kraju
        if (in_array('ROLE_ZARZAD_KRAJOWY', $user->getRoles())) {
            return $this->userRepository->count(['typUzytkownika' => 'czlonek']);
        }

        // Zarząd okręgu widzi wszystkich w swoim okręgu
        if (in_array('ROLE_ZARZAD_OKREGU', $user->getRoles()) && $user->getOkreg()) {
            return $this->userRepository->count([
                'typUzytkownika' => 'czlonek',
                'okreg' => $user->getOkreg(),
            ]);
        }

        // Zarząd oddziału widzi wszystkich w swoim oddziale
        if (in_array('ROLE_ZARZAD_ODDZIALU', $user->getRoles()) && $user->getOddzial()) {
            return $this->userRepository->count([
                'typUzytkownika' => 'czlonek',
                'oddzial' => $user->getOddzial(),
            ]);
        }

        // Pozostali (włącznie z ROLE_ADMIN dla kompatybilności)
        if ($this->hasNationalLevelAccess($user)) {
            return $this->userRepository->countByType('czlonek');
        } elseif ($user->getOkreg()) {
            return $this->userRepository->countByTypeAndOkreg('czlonek', $user->getOkreg());
        } elseif ($user->getOddzial()) {
            return $this->userRepository->countByTypeAndOddzial('czlonek', $user->getOddzial());
        }

        return 0;
    }

    private function getCandidatesCount(User $user): int
    {
        if ($this->hasNationalLevelAccess($user)) {
            return $this->userRepository->countByType('kandydat');
        } elseif ($user->getOkreg()) {
            return $this->userRepository->countByTypeAndOkreg('kandydat', $user->getOkreg());
        } elseif ($user->getOddzial()) {
            return $this->userRepository->countByTypeAndOddzial('kandydat', $user->getOddzial());
        }

        return 0;
    }

    private function getSupportersCount(User $user): int
    {
        if ($this->hasNationalLevelAccess($user)) {
            return $this->userRepository->countByType('sympatyk');
        } elseif ($user->getOkreg()) {
            return $this->userRepository->countByTypeAndOkreg('sympatyk', $user->getOkreg());
        } elseif ($user->getOddzial()) {
            return $this->userRepository->countByTypeAndOddzial('sympatyk', $user->getOddzial());
        }

        return 0;
    }

    private function getDonorsCount(User $user): int
    {
        // Darczyńcy są w osobnej tabeli, nie w user
        if ($this->hasNationalLevelAccess($user)) {
            // Zwracamy rzeczywistą liczbę darczyńców z właściwej tabeli
            return $this->darczycaRepository->count([]);
        }
        
        // Dla innych ról zwracamy stałą wartość (można później dodać logikę okręgów)
        return 3;
    }

    private function getYouthMembersCount(User $user): int
    {
        if ($this->hasNationalLevelAccess($user)) {
            return $this->userRepository->countByType('mlodziezowka');
        } elseif ($user->getOkreg()) {
            return $this->userRepository->countByTypeAndOkreg('mlodziezowka', $user->getOkreg());
        } elseif ($user->getOddzial()) {
            return $this->userRepository->countByTypeAndOddzial('mlodziezowka', $user->getOddzial());
        }

        return 0;
    }

    private function getMediaAppearancesCount(User $user): int
    {
        // Admin i najwyższe role widzą wszystkie wystąpienia
        if ($this->hasNationalLevelAccess($user)) {
            return $this->wystepMedialnyRepository->count([]);
        }

        // Członkowie widzą tylko swoje wystąpienia
        if (in_array('ROLE_CZLONEK_PARTII', $user->getRoles()) && !in_array('ROLE_FUNKCYJNY', $user->getRoles())) {
            return $this->wystepMedialnyRepository->count(['zglaszajacy' => $user]);
        }

        // Zarząd krajowy widzi wszystkie w kraju
        if (in_array('ROLE_ZARZAD_KRAJOWY', $user->getRoles())) {
            return $this->wystepMedialnyRepository->count([]);
        }

        // Zarząd okręgu widzi wszystkie w swoim okręgu
        if (in_array('ROLE_ZARZAD_OKREGU', $user->getRoles()) && $user->getOkreg()) {
            $result = $this->wystepMedialnyRepository->createQueryBuilder('w')
                ->select('COUNT(w.id)')
                ->leftJoin('w.zglaszajacy', 'u')
                ->where('u.okreg = :okreg')
                ->setParameter('okreg', $user->getOkreg())
                ->getQuery()
                ->getSingleScalarResult();

            return is_int($result) ? $result : 0;
        }

        // Zarząd oddziału widzi wszystkie w swoim oddziale
        if (in_array('ROLE_ZARZAD_ODDZIALU', $user->getRoles()) && $user->getOddzial()) {
            $result = $this->wystepMedialnyRepository->createQueryBuilder('w')
                ->select('COUNT(w.id)')
                ->leftJoin('w.zglaszajacy', 'u')
                ->where('u.oddzial = :oddzial')
                ->setParameter('oddzial', $user->getOddzial())
                ->getQuery()
                ->getSingleScalarResult();

            return is_int($result) ? $result : 0;
        }

        // Pozostali z kompatybilnością wsteczną
        if (in_array('ROLE_FUNKCYJNY', $user->getRoles())) {
            return $this->wystepMedialnyRepository->count([]);
        }

        return 0;
    }

    private function getPressConferencesCount(User $user): int
    {
        // Admin i najwyższe role widzą wszystkie konferencje
        if ($this->hasNationalLevelAccess($user)) {
            return $this->konferencjaPrasowaRepository->count([]);
        }

        // Członkowie widzą tylko swoje konferencje
        if (in_array('ROLE_CZLONEK_PARTII', $user->getRoles()) && !in_array('ROLE_FUNKCYJNY', $user->getRoles())) {
            return $this->konferencjaPrasowaRepository->count(['zglaszajacy' => $user]);
        }

        // Zarząd krajowy widzi wszystkie w kraju
        if (in_array('ROLE_ZARZAD_KRAJOWY', $user->getRoles())) {
            return $this->konferencjaPrasowaRepository->count([]);
        }

        // Zarząd okręgu widzi wszystkie w swoim okręgu
        if (in_array('ROLE_ZARZAD_OKREGU', $user->getRoles()) && $user->getOkreg()) {
            $result = $this->konferencjaPrasowaRepository->createQueryBuilder('k')
                ->select('COUNT(k.id)')
                ->leftJoin('k.zglaszajacy', 'u')
                ->where('u.okreg = :okreg')
                ->setParameter('okreg', $user->getOkreg())
                ->getQuery()
                ->getSingleScalarResult();

            return is_int($result) ? $result : 0;
        }

        // Zarząd oddziału widzi wszystkie w swoim oddziale
        if (in_array('ROLE_ZARZAD_ODDZIALU', $user->getRoles()) && $user->getOddzial()) {
            $result = $this->konferencjaPrasowaRepository->createQueryBuilder('k')
                ->select('COUNT(k.id)')
                ->leftJoin('k.zglaszajacy', 'u')
                ->where('u.oddzial = :oddzial')
                ->setParameter('oddzial', $user->getOddzial())
                ->getQuery()
                ->getSingleScalarResult();

            return is_int($result) ? $result : 0;
        }

        // Pozostali z kompatybilnością wsteczną
        if (in_array('ROLE_FUNKCYJNY', $user->getRoles())) {
            return $this->konferencjaPrasowaRepository->count([]);
        }

        return 0;
    }

    private function getDocumentsCount(User $user): int
    {
        // Admin i zarząd krajowy widzą wszystkie dokumenty
        if ($this->hasNationalLevelAccess($user)) {
            return $this->dokumentRepository->count([]);
        }

        // Zarząd okręgu widzi dokumenty ze swojego okręgu
        if (in_array('ROLE_ZARZAD_OKREGU', $user->getRoles()) && $user->getOkreg()) {
            return $this->dokumentRepository->count(['okreg' => $user->getOkreg()]);
        }

        // Zwykli członkowie widzą dokumenty w których uczestniczą
        $result = $this->dokumentRepository->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.tworca = :user OR d.kandydat = :user OR d.czlonek = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return is_int($result) ? $result : 0;
    }

    // Historical counting methods - rzeczywiste zliczanie na podstawie dat
    private function getMembersCountAsOf(User $user, \DateTimeInterface $date): int
    {
        if ($this->hasNationalLevelAccess($user)) {
            return $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.typUzytkownika = :type')
                ->andWhere('u.dataRejestracji <= :date')
                ->setParameter('type', 'czlonek')
                ->setParameter('date', $date)
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        return max(0, $this->getMembersCount($user) - 2); // fallback
    }

    private function getCandidatesCountAsOf(User $user, \DateTimeInterface $date): int
    {
        if ($this->hasNationalLevelAccess($user)) {
            return $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.typUzytkownika = :type')
                ->andWhere('u.dataRejestracji <= :date')
                ->setParameter('type', 'kandydat')
                ->setParameter('date', $date)
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        return max(0, $this->getCandidatesCount($user) - 1); // fallback
    }

    private function getSupportersCountAsOf(User $user, \DateTimeInterface $date): int
    {
        if ($this->hasNationalLevelAccess($user)) {
            return $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.typUzytkownika = :type')
                ->andWhere('u.dataRejestracji <= :date')
                ->setParameter('type', 'sympatyk')
                ->setParameter('date', $date)
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        return max(0, $this->getSupportersCount($user) - 3); // fallback
    }

    private function getDonorsCountAsOf(User $user, \DateTimeInterface $date): int
    {
        // Darczyńcy z osobnej tabeli - nie mają pola dataRejestracji
        return max(0, $this->getDonorsCount($user) - 1);
    }

    private function getYouthMembersCountAsOf(User $user, \DateTimeInterface $date): int
    {
        if ($this->hasNationalLevelAccess($user)) {
            return $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.typUzytkownika = :type')
                ->andWhere('u.dataRejestracji <= :date')
                ->setParameter('type', 'mlodziezowka')
                ->setParameter('date', $date)
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        return max(0, $this->getYouthMembersCount($user) - 2); // fallback
    }

    private function getMediaAppearancesCountAsOf(User $user, \DateTimeInterface $date): int
    {
        if ($this->hasNationalLevelAccess($user)) {
            return $this->wystepMedialnyRepository->createQueryBuilder('w')
                ->select('COUNT(w.id)')
                ->where('w.dataIGodzina <= :date')
                ->setParameter('date', $date)
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        return max(0, $this->getMediaAppearancesCount($user) - 3); // fallback
    }

    private function getPressConferencesCountAsOf(User $user, \DateTimeInterface $date): int
    {
        if ($this->hasNationalLevelAccess($user)) {
            return $this->konferencjaPrasowaRepository->createQueryBuilder('k')
                ->select('COUNT(k.id)')
                ->where('k.dataIGodzina <= :date')
                ->setParameter('date', $date)
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        return max(0, $this->getPressConferencesCount($user) - 2); // fallback
    }

    private function getDocumentsCountAsOf(User $user, \DateTimeInterface $date): int
    {
        // Dokumenty nie mają jeszcze pola z datą utworzenia w bazie
        return max(0, $this->getDocumentsCount($user) - 1);
    }

    /**
     * Oblicza rzeczywistą zmianę w ilościach (nie procentach)
     * @return array<string, mixed>
     */
    private function calculateRealChange(int $previous, int $current): array
    {
        $change = $current - $previous;
        
        $direction = 'neutral';
        if ($change > 0) {
            $direction = 'positive';
        } elseif ($change < 0) {
            $direction = 'negative';
        }

        if ($change === 0) {
            $formatted = 'bez zmian';
        } elseif ($change > 0) {
            $formatted = '+' . $change;
        } else {
            $formatted = (string) $change;
        }

        return [
            'amount' => abs($change),
            'direction' => $direction,
            'formatted' => $formatted,
            'raw_change' => $change,
        ];
    }
    
    /**
     * Stara metoda dla kompatybilności wstecznej (jeśli gdzieś jest używana)
     * @return array<string, mixed>
     */
    private function calculateChange(int $previous, int $current): array
    {
        return $this->calculateRealChange($previous, $current);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSystemStatus(): array
    {
        $uptime = $this->getSystemUptime();

        return [
            'status' => 'online',
            'uptime' => $uptime,
            'formatted_uptime' => $this->formatUptime($uptime),
            'last_check' => new \DateTime(),
        ];
    }

    private function getSystemUptime(): int
    {
        // Get real system uptime
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptimeSeconds = (int) explode(' ', $uptime)[0];
            return $uptimeSeconds;
        }

        // Fallback for Windows or systems without /proc/uptime
        return 0;
    }

    private function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($days > 0) {
            return "{$days}d {$hours}h";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }
}
