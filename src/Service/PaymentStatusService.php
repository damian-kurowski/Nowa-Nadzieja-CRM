<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\PlatnoscRepository;
use App\Repository\SkladkaCzlonkowskaRepository;
use Doctrine\ORM\EntityManagerInterface;

class PaymentStatusService
{
    private const MONTHLY_FEE = 20.00; // Aktualna stawka
    private const CANDIDATE_ADVANCE_MONTHS = 3; // Kandydaci muszą wpłacić za 3 miesiące z góry
    
    // Stawki składek historyczne
    private const STAWKI_SKLADEK = [
        '2022-06-06' => 20.00, // Od 6 czerwca 2022 - 20 PLN
        '1970-01-01' => 10.00  // Do 5 czerwca 2022 - 10 PLN
    ];

    public function __construct(
        private PlatnoscRepository $platnoscRepository,
        private SkladkaCzlonkowskaRepository $skladkaCzlonkowskaRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Oblicz stawkę składki dla danego okresu
     */
    public function getStawkaSkladki(\DateTime $data): float
    {
        foreach (self::STAWKI_SKLADEK as $dataZmiany => $stawka) {
            $dataZmianyObj = new \DateTime($dataZmiany);
            if ($data >= $dataZmianyObj) {
                return $stawka;
            }
        }
        
        // Domyślnie 10 PLN (najstarsza stawka)
        return 10.00;
    }

    public function getPaymentStatus(User $user): array
    {
        // Oblicz sumę wpłaconych składek
        $oplaconeSkladki = $this->skladkaCzlonkowskaRepository->findOplaconeByCzlonek($user);
        $totalPaid = 0;

        foreach ($oplaconeSkladki as $skladka) {
            $totalPaid += (float) $skladka->getKwota();
        }

        // Special logic for candidates vs members
        if ($user->getTypUzytkownika() === 'kandydat') {
            // Kandydaci: tylko sprawdzamy czy wpłacili 60 PLN (opłata kandydacka)
            return $this->getCandidatePaymentStatus($user, $totalPaid, null, null, false);
        } else {
            // Członkowie: używamy najpóźniejszej daty ważności z tabeli skladka_czlonkowska
            $najpozniejszaDataWaznosci = $this->skladkaCzlonkowskaRepository->getNajpozniejszaDataWaznosci($user);

            $currentDate = new \DateTime();
            $currentMonthPaid = $najpozniejszaDataWaznosci && $najpozniejszaDataWaznosci >= $currentDate;

            return $this->getMemberPaymentStatus($user, $totalPaid, null, $najpozniejszaDataWaznosci, $currentMonthPaid);
        }
    }

    private function getCandidatePaymentStatus(User $user, float $totalPaid, $najnowszaSkladka, $najpozniejszaDataWaznosci, bool $currentMonthPaid): array
    {
        $monthlyFee = self::MONTHLY_FEE;
        $requiredAmount = self::CANDIDATE_ADVANCE_MONTHS * $monthlyFee; // 60 PLN (3 x 20 PLN)

        // Kandydaci: opłata kandydacka 60 PLN - albo mają, albo nie (bez liczenia czasu)
        $hasFullPayment = $totalPaid >= $requiredAmount;

        if (!$hasFullPayment) {
            $missingAmount = $requiredAmount - $totalPaid;

            return [
                'status' => 'candidate_insufficient',
                'message' => "❌ Wymagana opłata kandydacka: {$requiredAmount} PLN. Brakuje: {$missingAmount} PLN",
                'color' => 'danger',
                'monthsCovered' => 0,
                'monthsSinceStart' => 0,
                'totalPaid' => $totalPaid,
                'monthlyFee' => $monthlyFee,
                'overdueMonths' => 0,
                'paidUntil' => null,
                'isCandidate' => true,
                'hasRequiredAdvance' => false,
                'missingAmount' => $missingAmount
            ];
        }

        // Kandydat wpłacił opłatę kandydacką
        return [
            'status' => 'candidate_ready',
            'message' => "✅ Opłata kandydacka wpłacona ({$requiredAmount} PLN)",
            'color' => 'success',
            'monthsCovered' => 0,
            'monthsSinceStart' => 0,
            'totalPaid' => $totalPaid,
            'monthlyFee' => $monthlyFee,
            'overdueMonths' => 0,
            'paidUntil' => null,
            'isCandidate' => true,
            'hasRequiredAdvance' => true
        ];
    }

    private function getMemberPaymentStatus(User $user, float $totalPaid, $najnowszaSkladka, $najpozniejszaDataWaznosci, bool $currentMonthPaid): array
    {
        $monthlyFee = self::MONTHLY_FEE;
        $currentDate = new \DateTime();
        
        // Get member start date (when they became a member, not when they registered)
        $startDate = $this->getMembershipStartDate($user);
        
        // Jeśli data przyjęcia jest w przyszłości, przedpłaty liczą się od daty członkostwa
        if ($startDate > $currentDate) {
            if ($najpozniejszaDataWaznosci && $totalPaid > 0) {
                // Jest przedpłata - przenieś ją na okres członkostwa
                $membershipStartMonth = clone $startDate;
                $membershipStartMonth->modify('first day of this month');
                
                // Oblicz ile miesięcy pokrywa przedpłata od daty przyjęcia do partii
                $monthsWillBeCovered = (int) floor($totalPaid / $monthlyFee);
                
                // Oblicz do kiedy będą pokryte składki od daty przyjęcia
                $paidUntilFromMembership = clone $membershipStartMonth;
                $paidUntilFromMembership->modify("+{$monthsWillBeCovered} months");
                $paidUntilFromMembership->modify('last day of this month')->setTime(23, 59, 59);
                
                $paidUntilFormatted = $paidUntilFromMembership->format('m.Y');
                
                return [
                    'status' => 'future_member_prepaid',
                    'message' => "🕐 Przyjęcie: " . $startDate->format('d.m.Y') . " | Składki pokryte do {$paidUntilFormatted} ({$monthsWillBeCovered} mies.)",
                    'color' => 'success',
                    'monthsCovered' => $monthsWillBeCovered,
                    'monthsSinceStart' => 0,
                    'totalPaid' => $totalPaid,
                    'monthlyFee' => $monthlyFee,
                    'overdueMonths' => 0,
                    'paidUntil' => $paidUntilFromMembership,
                    'isCandidate' => false,
                    'membershipStartDate' => $startDate
                ];
            } else {
                // Brak przedpłat
                return [
                    'status' => 'future_member',
                    'message' => "🕐 Przyjęcie do partii zaplanowane na " . $startDate->format('d.m.Y'),
                    'color' => 'info',
                    'monthsCovered' => 0,
                    'monthsSinceStart' => 0,
                    'totalPaid' => $totalPaid,
                    'monthlyFee' => $monthlyFee,
                    'overdueMonths' => 0,
                    'paidUntil' => null,
                    'isCandidate' => false,
                    'membershipStartDate' => $startDate
                ];
            }
        }
        
        $monthsSinceStart = $this->getMonthsDifference($startDate, $currentDate);
        
        // Calculate status based on validity date, not months count
        if (!$najpozniejszaDataWaznosci) {
            $overdueAmount = $monthsSinceStart * $monthlyFee;
            return [
                'status' => 'overdue',
                'message' => "❌ Brak opłaconych składek od daty przyjęcia ({$overdueAmount} PLN zaległości)",
                'color' => 'danger',
                'monthsCovered' => 0,
                'monthsSinceStart' => $monthsSinceStart,
                'totalPaid' => $totalPaid,
                'monthlyFee' => $monthlyFee,
                'overdueMonths' => $monthsSinceStart,
                'paidUntil' => null,
                'isCandidate' => false,
                'membershipStartDate' => $startDate
            ];
        }
        
        // Sprawdź czy składki pokrywają wymagany okres od daty przyjęcia do partii
        // Składki należy płacić od początku miesiąca przyjęcia do partii do końca bieżącego miesiąca
        $membershipStartMonth = clone $startDate;
        $membershipStartMonth->modify('first day of this month');
        $currentEndOfMonth = clone $currentDate;
        $currentEndOfMonth->modify('last day of this month')->setTime(23, 59, 59);
        
        if ($najpozniejszaDataWaznosci >= $currentEndOfMonth) {
            // Składki są aktualne i pokrywają wymagany okres
            $paidUntilFormatted = $najpozniejszaDataWaznosci->format('m.Y');
            
            if ($najpozniejszaDataWaznosci > $currentDate) {
                return [
                    'status' => 'paid',
                    'message' => "✅ Składki opłacone do {$paidUntilFormatted}",
                    'color' => 'success',
                    'monthsCovered' => $this->getMonthsDifference($startDate, $najpozniejszaDataWaznosci),
                    'monthsSinceStart' => $monthsSinceStart,
                    'totalPaid' => $totalPaid,
                    'monthlyFee' => $monthlyFee,
                    'overdueMonths' => 0,
                    'paidUntil' => $najpozniejszaDataWaznosci,
                    'isCandidate' => false,
                    'membershipStartDate' => $startDate
                ];
            } else {
                return [
                    'status' => 'current',
                    'message' => "✅ Składki aktualne",
                    'color' => 'success',
                    'monthsCovered' => $this->getMonthsDifference($startDate, $najpozniejszaDataWaznosci),
                    'monthsSinceStart' => $monthsSinceStart,
                    'totalPaid' => $totalPaid,
                    'monthlyFee' => $monthlyFee,
                    'overdueMonths' => 0,
                    'paidUntil' => $najpozniejszaDataWaznosci,
                    'isCandidate' => false,
                    'membershipStartDate' => $startDate
                ];
            }
        } else {
            // Składki nie pokrywają wymaganego okresu do końca bieżącego miesiąca
            $monthsOverdue = $this->getMonthsDifference($najpozniejszaDataWaznosci, $currentEndOfMonth);
            $overdueAmount = $monthsOverdue * $monthlyFee;
            
            return [
                'status' => 'overdue',
                'message' => "❌ Zaległość od daty przyjęcia: {$monthsOverdue} miesięcy ({$overdueAmount} PLN)",
                'color' => 'danger',
                'monthsCovered' => $this->getMonthsDifference($startDate, $najpozniejszaDataWaznosci),
                'monthsSinceStart' => $monthsSinceStart,
                'totalPaid' => $totalPaid,
                'monthlyFee' => $monthlyFee,
                'overdueMonths' => $monthsOverdue,
                'paidUntil' => $najpozniejszaDataWaznosci,
                'isCandidate' => false,
                'membershipStartDate' => $startDate
            ];
        }
    }

    public function getTotalPaidAmount(User $user): float
    {
        $oplaconeSkladki = $this->skladkaCzlonkowskaRepository->findOplaconeByCzlonek($user);
        $total = 0;
        
        foreach ($oplaconeSkladki as $skladka) {
            $total += (float) $skladka->getKwota();
        }
        
        return $total;
    }

    public function getPaymentHistory(User $user): array
    {
        return $this->skladkaCzlonkowskaRepository->findByCzlonek($user, ['rok' => 'DESC', 'miesiac' => 'DESC']);
    }

    private function getMembershipStartDate(User $user): \DateTime
    {
        // For members, use the date they became a member (not registration date)
        $membershipDate = $user->getDataPrzyjeciaDoPartii();

        if ($membershipDate) {
            return $membershipDate;
        }

        // Fallback to registration date if no membership date set
        return $user->getDataRejestracji() ?: new \DateTime('2025-01-01');
    }

    private function getMonthsDifference(\DateTime $startDate, \DateTime $endDate): int
    {
        $start = clone $startDate;
        $start->modify('first day of this month');
        
        $end = clone $endDate;
        $end->modify('first day of this month');
        
        $interval = $start->diff($end);
        return ($interval->y * 12) + $interval->m + ($interval->d > 0 ? 1 : 0);
    }

    private function calculateStatus(int $monthsCovered, int $monthsSinceStart): array
    {
        $overdue = $monthsSinceStart - $monthsCovered;
        
        if ($overdue <= 0) {
            $paidUntilMonth = date('m.Y', strtotime("+{$monthsCovered} months"));
            return [
                'status' => 'paid',
                'message' => "✅ Składki opłacone do {$paidUntilMonth}",
                'color' => 'success'
            ];
        } elseif ($overdue == 1) {
            return [
                'status' => 'warning',
                'message' => "⚠ Zaległość: 1 miesiąc",
                'color' => 'warning'
            ];
        } else {
            return [
                'status' => 'overdue',
                'message' => "❌ Zaległość: {$overdue} miesięcy",
                'color' => 'danger'
            ];
        }
    }

    private function calculatePaidUntilDate(\DateTime $startDate, int $monthsCovered): ?\DateTime
    {
        if ($monthsCovered <= 0) {
            return null;
        }
        
        $paidUntil = clone $startDate;
        $paidUntil->modify("+{$monthsCovered} months");
        return $paidUntil;
    }

    public function getOverdueUsers(): array
    {
        // This would need to be implemented with a more efficient query
        // For now, returning empty array - would need repository method
        return [];
    }

    /**
     * Update user's payment status after successful payment import
     * Uses the same logic as getPaymentStatus for consistency
     */
    public function updateUserPaymentStatus(User $user): void
    {
        // Pobierz wszystkie opłacone składki
        $oplaconeSkladki = $this->skladkaCzlonkowskaRepository->findOplaconeByCzlonek($user);
        
        if (empty($oplaconeSkladki)) {
            // Brak składek - ustaw na nieopłacone
            $user->setSkladkaOplacona(false);
            $user->setDataWaznosciSkladki(null);
            $user->setDataOplaceniaSkladki(null);
            $user->setKwotaSkladki(null);
        } else {
            $totalPaid = 0;
            foreach ($oplaconeSkladki as $skladka) {
                $totalPaid += (float) $skladka->getKwota();
            }

            if ($user->getTypUzytkownika() === 'kandydat') {
                // Kandydaci: opłata kandydacka 60 PLN - albo mają, albo nie
                $requiredAmount = self::CANDIDATE_ADVANCE_MONTHS * self::MONTHLY_FEE; // 60 PLN
                $isValid = $totalPaid >= $requiredAmount;

                $user->setSkladkaOplacona($isValid);
                $user->setDataWaznosciSkladki(null); // Kandydaci nie mają daty ważności
            } else {
                // Członkowie: używamy najpóźniejszej daty ważności z tabeli skladka_czlonkowska
                $najpozniejszaDataWaznosci = $this->skladkaCzlonkowskaRepository->getNajpozniejszaDataWaznosci($user);

                $currentDate = new \DateTime();
                $currentEndOfMonth = clone $currentDate;
                $currentEndOfMonth->modify('last day of this month')->setTime(23, 59, 59);

                // Składka jest opłacona gdy pokrywa cały bieżący miesiąc (do końca miesiąca)
                $isValid = $najpozniejszaDataWaznosci && $najpozniejszaDataWaznosci >= $currentEndOfMonth;

                $user->setSkladkaOplacona($isValid);
                $user->setDataWaznosciSkladki($najpozniejszaDataWaznosci);
            }

            // Ustaw dane ostatniej wpłaty
            $ostatniaWplata = end($oplaconeSkladki); // Ostatnia składka (sortowana w repository)
            if ($ostatniaWplata) {
                $user->setDataOplaceniaSkladki($ostatniaWplata->getDataPlatnosci());
                $user->setKwotaSkladki($ostatniaWplata->getKwota());
            }
        }
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }


    /**
     * Handle candidate becoming a member - recalculate payment validity dates
     */
    public function handleCandidatePromotion(User $user, \DateTime $membershipDate): void
    {
        if ($user->getTypUzytkownika() !== 'czlonek') {
            throw new \InvalidArgumentException('User must be a member to handle promotion');
        }
        
        $user->setDataPrzyjeciaDoPartii($membershipDate);
        
        // Update payment status based on new membership status
        $this->updateUserPaymentStatus($user);
    }

    public function generateStatusReport(): array
    {
        // Summary statistics for admin dashboard
        return [
            'totalUsers' => 0, // Would need user count
            'paidUsers' => 0,
            'warningUsers' => 0,
            'overdueUsers' => 0,
            'totalCollected' => 0,
            'expectedMonthly' => 0
        ];
    }
}