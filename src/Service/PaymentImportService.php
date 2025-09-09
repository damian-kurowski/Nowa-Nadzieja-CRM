<?php

namespace App\Service;

use App\Entity\ImportPlatnosci;
use App\Entity\Platnosc;
use App\Entity\SkladkaCzlonkowska;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PaymentStatusService;
use App\Service\SkladkaCzlonkowskaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PaymentImportService
{
    private ?PaymentStatusService $paymentStatusService = null;
    private ?SkladkaCzlonkowskaService $skladkaCzlonkowskaService = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        PaymentStatusService $paymentStatusService = null
    ) {
        if ($paymentStatusService) {
            $this->paymentStatusService = $paymentStatusService;
        }
    }

    public function setPaymentStatusService(PaymentStatusService $paymentStatusService): void
    {
        $this->paymentStatusService = $paymentStatusService;
    }

    public function setSkladkaCzlonkowskaService(SkladkaCzlonkowskaService $skladkaCzlonkowskaService): void
    {
        $this->skladkaCzlonkowskaService = $skladkaCzlonkowskaService;
    }

    public function processCSVImport(UploadedFile $file, User $importedBy, int $kwotaColumn, int $tytulColumn, ?int $kontoColumn = null): ImportPlatnosci
    {
        $import = new ImportPlatnosci();
        $import->setNazwaPliku($file->getClientOriginalName());
        $import->setImportowanyPrzez($importedBy);

        $dopasowane = 0;
        $bledne = 0;
        $wiersze = 0;
        $bledy = [];

        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            throw new \Exception('Nie można otworzyć pliku CSV');
        }

        // Skip header row
        $header = fgetcsv($handle, 0, ';');
        
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            $wiersze++;
            
            try {
                $result = $this->processPaymentRow($data, $kwotaColumn, $tytulColumn, $import, $kontoColumn);
                
                if ($result['matched']) {
                    $dopasowane++;
                    // Add warning if exists
                    if (!empty($result['warning'])) {
                        $bledy[] = "Wiersz {$wiersze} (ostrzeżenie): " . $result['warning'];
                    }
                } else {
                    $bledne++;
                    $bledy[] = "Wiersz {$wiersze}: " . $result['error'];
                }
            } catch (\Exception $e) {
                $bledne++;
                $bledy[] = "Wiersz {$wiersze}: " . $e->getMessage();
            }
        }

        fclose($handle);

        $import->setLiczbaWierszy($wiersze);
        $import->setLiczbaDopasowanych($dopasowane);
        $import->setLiczbaBlednych($bledne);
        
        if (!empty($bledy)) {
            $import->setRaportBledow(implode("\n", $bledy));
        }

        $this->entityManager->persist($import);
        $this->entityManager->flush();

        // Update payment status for all users who received payments in this import
        if (isset($this->paymentStatusService)) {
            $this->updateUsersPaymentStatus($import);
        }

        return $import;
    }

    private function processPaymentRow(array $data, int $kwotaColumn, int $tytulColumn, ImportPlatnosci $import, ?int $kontoColumn = null): array
    {
        // Expected CSV structure based on the example:
        // 0: Adres kontrahenta, 1: Adres właściciela, 2: Bank kontrahenta, 3: Bank prowadzący rachunek,
        // 4: Data efektywna, 5: Data księgowania, 6: Data obciążenia, 7: Kod operacji, 8: Kwota,
        // 9: Nazwa kontrahenta, 10: Nazwa właściciela, 11: Numer sekwencyjny, 12: Opis kodu operacji,
        // 13: Rachunek kontrahenta, 14: Rachunek właściciela, 15: Referencje banku, 16: Saldo po operacji,
        // 17: Sygnatura, 18: Typ operacji, 19: Tytuł operacji, 20: Waluta

        if (!isset($data[$kwotaColumn]) || !isset($data[$tytulColumn])) {
            return ['matched' => false, 'error' => 'Brak danych w wymaganych kolumnach'];
        }

        // Parse amount (replace comma with dot for decimal)
        $kwotaStr = str_replace(',', '.', $data[$kwotaColumn]);
        $kwota = floatval($kwotaStr);
        
        if ($kwota <= 0) {
            return ['matched' => false, 'error' => 'Nieprawidłowa kwota: ' . $data[$kwotaColumn]];
        }

        // Extract PESEL from title
        $tytul = $data[$tytulColumn];
        $pesel = $this->extractPeselFromTitle($tytul);
        
        if (!$pesel) {
            return ['matched' => false, 'error' => 'Nie znaleziono PESEL w tytule: ' . $tytul];
        }

        // Find user by PESEL
        $user = $this->userRepository->findOneBy(['pesel' => $pesel]);
        if (!$user) {
            return ['matched' => false, 'error' => 'Nie znaleziono użytkownika z PESEL: ' . $pesel];
        }

        // Parse booking date
        $dataKsiegowania = null;
        if (isset($data[5]) && !empty($data[5])) {
            try {
                $dataKsiegowania = new \DateTime($data[5]);
            } catch (\Exception $e) {
                return ['matched' => false, 'error' => 'Nieprawidłowa data księgowania: ' . $data[5]];
            }
        }

        // Extract account number if column is specified
        $numerKonta = null;
        $kontoWarning = null;
        if ($kontoColumn !== null && isset($data[$kontoColumn]) && !empty($data[$kontoColumn])) {
            $numerKonta = $this->normalizeAccountNumber(trim($data[$kontoColumn]));
            
            // Check for account number conflicts
            if ($numerKonta && $user->getNumerKontaBankowego()) {
                if ($user->getNumerKontaBankowego() !== $numerKonta) {
                    $kontoWarning = "Konflikt numeru konta dla {$user->getImie()} {$user->getNazwisko()} (PESEL: {$pesel}). " .
                                   "Istniejący: {$user->getNumerKontaBankowego()}, nowy: {$numerKonta}. Zachowano istniejący.";
                    // DON'T update the account number if there's a conflict
                }
            } elseif ($numerKonta && !$user->getNumerKontaBankowego()) {
                // Only set if user doesn't have an account number yet
                $user->setNumerKontaBankowego($numerKonta);
                $this->entityManager->persist($user);
            }
        }

        // Determine period from payment date
        $dataWplaty = $dataKsiegowania ?: new \DateTime();
        $rok = (int) $dataWplaty->format('Y');
        $miesiac = (int) $dataWplaty->format('n');

        // Determine expected amount based on date
        $dataZmianyStawki = new \DateTime('2022-06-06');
        $oczekiwanaKwota = $dataWplaty < $dataZmianyStawki ? 10.00 : 20.00;
        
        // Calculate how many months this payment covers
        $iloscMiesiecy = max(1, (int)floor($kwota / $oczekiwanaKwota));
        
        // Check if amount is below minimum required
        $kwotaWarning = '';
        if ($kwota < ($oczekiwanaKwota - 0.01)) {
            $kwotaWarning = " Kwota $kwota PLN jest poniżej wymaganej stawki $oczekiwanaKwota PLN.";
        } elseif ($iloscMiesiecy > 1) {
            $kwotaWarning = " Nadpłata - pokrywa $iloscMiesiecy miesięcy.";
        }

        // Create membership fee record
        $skladka = new SkladkaCzlonkowska();
        $skladka->setCzlonek($user);
        $skladka->setRok($rok);
        $skladka->setMiesiac($miesiac);
        $skladka->setKwota((string)$kwota);
        $skladka->setStatus('oplacona');
        $skladka->setDataPlatnosci($dataWplaty);
        $skladka->setImportPlatnosci($import);
        $skladka->setUwagi("Import CSV - " . $tytul . $kwotaWarning);
        
        // Set validity using historical rates logic
        if ($this->paymentStatusService) {
            $dataWaznosci = $this->paymentStatusService->calculateValidityWithHistoricalRates($dataWplaty, $kwota);
        } else {
            // Fallback - set validity based on how many months the payment covers
            $dataWaznosci = clone $dataWplaty;
            $dataWaznosci->setDate($rok, $miesiac, 1);
            $dataWaznosci->setTime(23, 59, 59);
            $dataWaznosci->modify('last day of this month');
            
            // If overpaid, extend validity by additional months
            if ($iloscMiesiecy > 1) {
                $dataWaznosci->modify('+' . ($iloscMiesiecy - 1) . ' months');
                $dataWaznosci->modify('last day of this month');
            }
        }
        
        $skladka->setDataWaznosciSkladki($dataWaznosci);

        $this->entityManager->persist($skladka);

        // Combine warnings
        $allWarnings = array_filter([$kontoWarning, $kwotaWarning]);
        $finalWarning = !empty($allWarnings) ? implode(' ', $allWarnings) : null;

        return ['matched' => true, 'error' => null, 'warning' => $finalWarning];
    }

    private function extractPeselFromTitle(string $title): ?string
    {
        // Remove common non-digit characters and normalize
        $normalized = preg_replace('/[^\d\s]/', ' ', $title);
        
        // Find all 11-digit sequences (PESEL candidates)
        if (preg_match_all('/(\d{11})/', $normalized, $matches)) {
            foreach ($matches[1] as $candidate) {
                // Basic PESEL validation - check date part
                if ($this->isValidPeselFormat($candidate)) {
                    return $candidate;
                }
            }
        }
        
        // Fallback: try to find 11 consecutive digits even if attached to letters
        if (preg_match('/(\d{11})/', $title, $matches)) {
            $candidate = $matches[1];
            if ($this->isValidPeselFormat($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
    
    private function isValidPeselFormat(string $pesel): bool
    {
        if (strlen($pesel) !== 11) {
            return false;
        }
        
        // Basic validation - check if it could be a valid date
        $year = (int) substr($pesel, 0, 2);
        $month = (int) substr($pesel, 2, 2);
        $day = (int) substr($pesel, 4, 2);
        
        // PESEL month encoding: 01-12 (1900-1999), 21-32 (2000-2099), 81-92 (1800-1899)
        if ($month >= 1 && $month <= 12) {
            // 1900-1999
            $actualMonth = $month;
        } elseif ($month >= 21 && $month <= 32) {
            // 2000-2099
            $actualMonth = $month - 20;
        } elseif ($month >= 81 && $month <= 92) {
            // 1800-1899
            $actualMonth = $month - 80;
        } else {
            return false;
        }
        
        // Basic day validation
        if ($day < 1 || $day > 31) {
            return false;
        }
        
        // Basic month validation
        if ($actualMonth < 1 || $actualMonth > 12) {
            return false;
        }
        
        return true;
    }

    public function validateCSVStructure(UploadedFile $file): array
    {
        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            return ['valid' => false, 'error' => 'Nie można otworzyć pliku'];
        }

        $header = fgetcsv($handle, 0, ';');
        if ($header === false) {
            fclose($handle);
            return ['valid' => false, 'error' => 'Plik jest pusty lub ma nieprawidłowy format'];
        }

        // Read first few data rows for preview
        $preview = [];
        $rowCount = 0;
        while (($data = fgetcsv($handle, 0, ';')) !== false && $rowCount < 5) {
            $preview[] = $data;
            $rowCount++;
        }

        fclose($handle);

        return [
            'valid' => true,
            'header' => $header,
            'preview' => $preview,
            'columnCount' => count($header)
        ];
    }

    /**
     * Update payment status for all users who received membership fees in this import
     */
    private function updateUsersPaymentStatus(ImportPlatnosci $import): void
    {
        // Get all membership fees from this import
        $skladki = $this->entityManager
            ->getRepository(SkladkaCzlonkowska::class)
            ->findBy(['importPlatnosci' => $import]);

        $processedUsers = [];

        foreach ($skladki as $skladka) {
            $user = $skladka->getCzlonek();
            if ($user && !in_array($user->getId(), $processedUsers)) {
                try {
                    $this->paymentStatusService->updateUserPaymentStatus($user);
                    $processedUsers[] = $user->getId();
                } catch (\Exception $e) {
                    // Log error but don't fail the import
                }
            }
        }
    }

    /**
     * Normalize account number by removing spaces and non-digit characters
     */
    private function normalizeAccountNumber(string $accountNumber): ?string
    {
        // Remove all non-digit characters
        $normalized = preg_replace('/[^\d]/', '', $accountNumber);
        
        // Check if it looks like a valid Polish account number (26 digits for IBAN or 20+ for national)
        if (strlen($normalized) >= 20 && strlen($normalized) <= 34) {
            return $normalized;
        }
        
        // If it's shorter but has some digits, still save it (might be partial)
        if (strlen($normalized) >= 10) {
            return $normalized;
        }
        
        return null;
    }
}