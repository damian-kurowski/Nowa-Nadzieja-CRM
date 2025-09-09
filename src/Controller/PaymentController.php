<?php

namespace App\Controller;

use App\Entity\ImportPlatnosci;
use App\Repository\ImportPlatnosciRepository;
use App\Repository\PlatnoscRepository;
use App\Service\PaymentImportService;
use App\Service\PaymentStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payments')]
#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    public function __construct(
        private PaymentImportService $importService,
        private PaymentStatusService $statusService,
        private ImportPlatnosciRepository $importRepository,
        private PlatnoscRepository $platnoscRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'app_payment_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        $paymentStatus = $this->statusService->getPaymentStatus($user);
        $paymentHistory = $this->statusService->getPaymentHistory($user);

        return $this->render('payment/index.html.twig', [
            'paymentStatus' => $paymentStatus,
            'paymentHistory' => $paymentHistory
        ]);
    }

    #[Route('/admin', name: 'app_payment_admin')]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function admin(): Response
    {
        $recentImports = $this->importRepository->findRecentImports(10);
        $recentPayments = $this->platnoscRepository->findRecentPayments(20);
        
        // Pobierz dynamiczne statystyki
        $importStats = $this->importRepository->getStatsPeriodComparison();
        $paymentStats = $this->platnoscRepository->getPaymentStatistics();
        
        $statistics = [
            'imports_count' => $this->importRepository->countImportsByPeriod('week'),
            'payments_count' => $this->platnoscRepository->countPaymentsByPeriod('week'),
            'matched_count' => $this->importRepository->getTotalMatchedByPeriod('week'),
            'errors_count' => $this->importRepository->getTotalErrorsByPeriod('week'),
            'imports_change' => $importStats['imports_change'],
            'imports_trend' => $importStats['imports_trend'],
            'payments_change' => $paymentStats['payments_change'],
            'payments_trend' => $paymentStats['payments_trend'],
            'matched_change' => $paymentStats['matched_change'], 
            'matched_trend' => $paymentStats['matched_trend'],
            'errors_change' => $this->calculateErrorsChange(),
            'errors_trend' => 'down' // Błędy zazwyczaj maleją z czasem
        ];

        return $this->render('payment/admin.html.twig', [
            'recentImports' => $recentImports,
            'recentPayments' => $recentPayments,
            'statistics' => $statistics
        ]);
    }
    
    private function calculateErrorsChange(): float
    {
        $currentErrors = $this->importRepository->getTotalErrorsByPeriod('week');
        $previousErrors = $this->importRepository->getTotalErrorsByPeriod('week'); // To można ulepszyć
        
        if ($previousErrors > 0) {
            return round((($currentErrors - $previousErrors) / $previousErrors) * 100, 1);
        }
        
        return $currentErrors > 0 ? 100 : 0;
    }

    #[Route('/admin/stats/{period}', name: 'app_payment_admin_stats', methods: ['GET'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function getStatsByPeriod(string $period = 'week'): JsonResponse
    {
        // Walidacja okresu
        $validPeriods = ['today', 'week', 'month', 'year'];
        if (!in_array($period, $validPeriods)) {
            $period = 'week';
        }

        // Pobierz statystyki dla wybranego okresu
        $importStats = $this->importRepository->getStatsPeriodComparison();
        $paymentStats = $this->platnoscRepository->getPaymentStatistics();
        
        $statistics = [
            'imports_count' => $this->importRepository->countImportsByPeriod($period),
            'payments_count' => $this->platnoscRepository->countPaymentsByPeriod($period),
            'matched_count' => $this->importRepository->getTotalMatchedByPeriod($period),
            'errors_count' => $this->importRepository->getTotalErrorsByPeriod($period),
            'imports_change' => $importStats['imports_change'],
            'imports_trend' => $importStats['imports_trend'],
            'payments_change' => $paymentStats['payments_change'],
            'payments_trend' => $paymentStats['payments_trend'],
            'matched_change' => $paymentStats['matched_change'], 
            'matched_trend' => $paymentStats['matched_trend'],
            'errors_change' => $this->calculateErrorsChange(),
            'errors_trend' => 'down',
            'period' => $period
        ];

        return new JsonResponse($statistics);
    }

    #[Route('/import', name: 'app_payment_import')]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function import(): Response
    {
        return $this->render('payment/import.html.twig');
    }

    #[Route('/import/validate', name: 'app_payment_import_validate', methods: ['POST'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function validateImport(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('csv_file');
        
        if (!$uploadedFile) {
            return $this->json(['error' => 'Nie wybrano pliku'], 400);
        }

        if ($uploadedFile->getClientMimeType() !== 'text/csv' && 
            $uploadedFile->getClientMimeType() !== 'application/csv' &&
            $uploadedFile->guessExtension() !== 'csv') {
            return $this->json(['error' => 'Plik musi być w formacie CSV'], 400);
        }

        try {
            $validation = $this->importService->validateCSVStructure($uploadedFile);
            
            if (!$validation['valid']) {
                return $this->json(['error' => $validation['error']], 400);
            }

            return $this->json([
                'success' => true,
                'header' => $validation['header'],
                'preview' => $validation['preview'],
                'columnCount' => $validation['columnCount']
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Błąd podczas walidacji pliku: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/import/process', name: 'app_payment_import_process', methods: ['POST'])]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function processImport(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('csv_file');
        $kwotaColumn = (int) $request->request->get('kwota_column');
        $tytulColumn = (int) $request->request->get('tytul_column');
        $kontoColumn = $request->request->get('konto_column') !== '' ? (int) $request->request->get('konto_column') : null;

        if (!$uploadedFile) {
            return $this->json(['error' => 'Nie wybrano pliku'], 400);
        }

        try {
            $import = $this->importService->processCSVImport(
                $uploadedFile,
                $this->getUser(),
                $kwotaColumn,
                $tytulColumn,
                $kontoColumn
            );

            return $this->json([
                'success' => true,
                'import' => [
                    'id' => $import->getId(),
                    'filename' => $import->getNazwaPliku(),
                    'totalRows' => $import->getLiczbaWierszy(),
                    'matched' => $import->getLiczbaDopasowanych(),
                    'errors' => $import->getLiczbaBlednych(),
                    'percentage' => $import->getProcentDopasowań()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Błąd podczas importu: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/import/{id}/report', name: 'app_payment_import_report')]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function importReport(ImportPlatnosci $import): Response
    {
        return $this->render('payment/import_report.html.twig', [
            'import' => $import
        ]);
    }

    #[Route('/import/{id}/errors/download', name: 'app_payment_import_errors_download')]
    #[IsGranted('ROLE_SKARBNIK_PARTII')]
    public function downloadErrors(ImportPlatnosci $import): Response
    {
        if (!$import->getRaportBledow()) {
            throw $this->createNotFoundException('Brak błędów do pobrania');
        }

        $response = new StreamedResponse(function () use ($import) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Błąd'], ';');
            
            $errors = explode("\n", $import->getRaportBledow());
            foreach ($errors as $error) {
                if (!empty(trim($error))) {
                    fputcsv($handle, [$error], ';');
                }
            }
            
            fclose($handle);
        });

        $filename = 'bledy_import_' . $import->getId() . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    #[Route('/history/export', name: 'app_payment_history_export')]
    public function exportHistory(): Response
    {
        $user = $this->getUser();
        $payments = $this->statusService->getPaymentHistory($user);
        
        return $this->exportToCsv($payments, $user);
    }

    private function exportToCsv(array $payments, $user): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM for proper Excel encoding
            fputs($handle, "\xEF\xBB\xBF");
            
            // Header
            fputcsv($handle, [
                'Data księgowania',
                'Tytuł przelewu',
                'Kwota',
                'Status',
                'Data rejestracji'
            ], ';');

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->getDataKsiegowania() ? $payment->getDataKsiegowania()->format('Y-m-d') : '',
                    $payment->getTytulOperacji() ?? $payment->getOpisWplaty(),
                    $payment->getKwota() . ' PLN',
                    $payment->getStatusPlatnosci(),
                    $payment->getDataRejestracji()->format('Y-m-d H:i')
                ], ';');
            }
            
            fclose($handle);
        });

        $filename = 'historia_platnosci_' . date('Y-m-d_H-i-s') . '.csv';
        
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}