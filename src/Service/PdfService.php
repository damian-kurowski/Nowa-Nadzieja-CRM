<?php

namespace App\Service;

use App\Entity\Dokument;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private Environment $twig,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function generateDocumentPdf(Dokument $dokument): ?string
    {
        // Configure Dompdf with Polish character support
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $pdfOptions->set('isRemoteEnabled', false);
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('debugKeepTemp', false);
        $pdfOptions->set('debugCss', false);
        $pdfOptions->set('debugLayout', false);
        $pdfOptions->set('debugLayoutLines', false);
        $pdfOptions->set('debugLayoutBlocks', false);
        $pdfOptions->set('debugLayoutInline', false);
        $pdfOptions->set('debugLayoutPaddingBox', false);

        // Enable unicode support
        $pdfOptions->set('isUnicode', true);
        $pdfOptions->set('defaultMediaType', 'print');
        $pdfOptions->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        // Load logo and convert to base64
        /** @var string $projectDir */
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $logoPath = $projectDir.'/nn_logo.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            if ($logoData !== false && !empty($logoData)) {
                $logoBase64 = base64_encode($logoData);
            }
        }

        // Generate document content with signatures
        $documentContent = $this->generateDocumentContentWithSignatures($dokument);
        
        // Render HTML content with proper encoding
        $html = $this->twig->render('dokument/pdf_template.html.twig', [
            'dokument' => $dokument,
            'document_content_with_signatures' => $documentContent,
            'isIntegrityValid' => $dokument->verifyHash(),
            'logo_base64' => $logoBase64,
        ]);

        // Clean up any problematic HTML that might cause table issues
        $html = (string) preg_replace('/display:\s*table[^;]*;?/i', '', (string) $html);
        $html = preg_replace('/display:\s*table-cell[^;]*;?/i', '', $html);

        // Ensure UTF-8 encoding
        $html = mb_convert_encoding((string) $html, 'UTF-8', 'UTF-8');

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function getDocumentFilename(Dokument $dokument): string
    {
        // Create readable filename with Polish characters support
        $baseFilename = sprintf(
            'Dokument_%s_%s',
            $dokument->getNumerDokumentu(),
            $dokument->getDataUtworzenia()->format('Y-m-d')
        );

        // Clean filename for filesystem compatibility
        $cleanFilename = $this->sanitizeFilename($baseFilename);

        return $cleanFilename.'.pdf';
    }

    private function sanitizeFilename(string $filename): string
    {
        // Replace Polish characters with ASCII equivalents
        $polishChars = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E', 'Ł' => 'L', 'Ń' => 'N',
            'Ó' => 'O', 'Ś' => 'S', 'Ź' => 'Z', 'Ż' => 'Z',
        ];

        $sanitized = strtr($filename, $polishChars);

        // Remove or replace problematic characters
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sanitized);

        // Remove multiple underscores
        $sanitized = (string) preg_replace('/_{2,}/', '_', (string) $sanitized);

        // Trim underscores from start and end
        return trim((string) $sanitized, '_');
    }

    /**
     * Generate PDF from HTML content and return HTTP Response
     */
    public function generatePdfResponse(string $html, string $filename): Response
    {
        // Configure Dompdf with Polish character support
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $pdfOptions->set('isRemoteEnabled', false);
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('debugKeepTemp', false);
        $pdfOptions->set('debugCss', false);
        $pdfOptions->set('debugLayout', false);
        $pdfOptions->set('debugLayoutLines', false);
        $pdfOptions->set('debugLayoutBlocks', false);
        $pdfOptions->set('debugLayoutInline', false);
        $pdfOptions->set('debugLayoutPaddingBox', false);

        // Enable unicode support
        $pdfOptions->set('isUnicode', true);
        $pdfOptions->set('defaultMediaType', 'print');
        $pdfOptions->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        // Clean up any problematic HTML that might cause table issues
        $html = (string) preg_replace('/display:\s*table[^;]*;?/i', '', (string) $html);
        $html = preg_replace('/display:\s*table-cell[^;]*;?/i', '', $html);

        // Ensure UTF-8 encoding
        $html = mb_convert_encoding((string) $html, 'UTF-8', 'UTF-8');

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output();

        // Clean filename for browser compatibility
        $cleanFilename = $this->sanitizeFilename($filename);

        // Create HTTP response
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="' . $cleanFilename . '"');
        $response->headers->set('Content-Length', (string) strlen($pdfContent));

        return $response;
    }
    
    /**
     * Generuje treść dokumentu z podpisami elektronicznymi
     */
    private function generateDocumentContentWithSignatures(Dokument $dokument): string
    {
        // Sprawdź czy dokument ma szablon Twig
        $documentType = $dokument->getTyp();

        try {
            // Spróbuj pobrać klasę dokumentu i jego szablon
            $documentClass = \App\Document\DocumentFactory::create($documentType);
            $templateName = $documentClass->getTemplateName();

            // Przygotuj dane do szablonu
            $templateData = $documentClass->prepareTemplateData($dokument, $dokument->getDaneDodatkowe() ?? []);

            // Dodaj podpisy do danych
            $templateData['podpisy'] = $dokument->getPodpisy();
            $templateData['dokument'] = $dokument;

            // Renderuj szablon Twig
            $content = $this->twig->render($templateName, $templateData);

            return $content;
        } catch (\Exception $e) {
            // Fallback do starej metody jeśli szablon nie istnieje
            // Ten fallback będzie używany tylko dla starych dokumentów
        }

        // Stara metoda - dodawanie podpisów na końcu (fallback dla starych dokumentów)
        $content = $dokument->getFormattedContent();

        // Jeśli dokument ma podpisy, dodaj sekcję z podpisami
        if (!$dokument->getPodpisy()->isEmpty()) {
            $content .= '<div style="margin-top: 50px; border-top: 2px solid #000; padding-top: 30px; page-break-inside: avoid;">';
            $content .= '<h3 style="text-align: center; margin-bottom: 30px; font-size: 12pt;">Podpisy elektroniczne</h3>';

            foreach ($dokument->getPodpisy() as $podpis) {
                // Kontener dla podpisu
                $content .= '<div style="margin-bottom: 30px; page-break-inside: avoid;">';

                // Informacje o podpisującym
                $content .= '<div style="font-weight: bold; margin-bottom: 5px; font-size: 11pt;">';
                $content .= htmlspecialchars($podpis->getPodpisujacyFullInfo());
                $content .= '</div>';

                // Status podpisu
                if ($podpis->isSigned()) {
                    $content .= '<div style="color: #28a745; margin-bottom: 10px; font-size: 10pt;">✓ Podpisany';
                    if ($podpis->getDataPodpisania()) {
                        $content .= ' – ' . $podpis->getDataPodpisania()->format('d.m.Y H:i');
                    }
                    $content .= '</div>';

                    // Wizualny podpis elektroniczny
                    if ($podpis->getPodpisElektroniczny()) {
                        $content .= '<div style="border: 1px solid #ccc; padding: 10px; background: #fff; min-height: 60px; margin-bottom: 10px;">';
                        $content .= '<img src="' . htmlspecialchars($podpis->getPodpisElektroniczny()) . '" ';
                        $content .= 'alt="Podpis elektroniczny" style="max-width: 300px; max-height: 80px; display: block;" />';
                        $content .= '</div>';
                    }

                    if ($podpis->getKomentarz()) {
                        $content .= '<div style="font-size: 9pt; color: #666; margin-top: 5px; font-style: italic;">Komentarz: ' .
                                   htmlspecialchars($podpis->getKomentarz()) . '</div>';
                    }

                    // Hash podpisu (skrócony)
                    if ($podpis->getHashPodpisu()) {
                        $content .= '<div style="font-size: 8pt; color: #999; margin-top: 5px; font-family: monospace;">';
                        $content .= 'Hash weryfikacyjny: ' . substr($podpis->getHashPodpisu(), 0, 32) . '...';
                        $content .= '</div>';
                    }
                } elseif ($podpis->isRejected()) {
                    $content .= '<div style="color: #dc3545; margin-bottom: 5px; font-size: 10pt;">✗ Odrzucony';
                    if ($podpis->getDataPodpisania()) {
                        $content .= ' – ' . $podpis->getDataPodpisania()->format('d.m.Y H:i');
                    }
                    $content .= '</div>';

                    if ($podpis->getKomentarz()) {
                        $content .= '<div style="font-size: 9pt; color: #666; margin-top: 5px; background: #f8d7da; padding: 8px; border-left: 3px solid #dc3545;">Powód odrzucenia: ' .
                                   htmlspecialchars($podpis->getKomentarz()) . '</div>';
                    }
                } else {
                    $content .= '<div style="color: #ffc107; font-size: 10pt;">⏳ Oczekuje na podpis</div>';
                }

                $content .= '</div>'; // Koniec kontenera podpisu
            }

            $content .= '</div>';
        }

        return $content;
    }
    
    /**
     * Formatuje treść dokumentu dla PDF
     */
    private function formatContentForPdf(string $content): string
    {
        // Formatowanie nagłówków
        $content = (string) preg_replace('/^(UCHWAŁA.*|DECYZJA.*)/m', '<div style="font-weight: bold; text-align: center; margin: 20px 0 15px 0; font-size: 13pt;">$1</div>', $content);
        $content = (string) preg_replace('/^(ZARZĄDU OKRĘGU.*|ZARZĄDU KRAJOWEGO.*|OKRĘGOWEGO PEŁNOMOCNIKA.*|PREZESA PARTII.*|RADY KRAJOWEJ.*)/m', '<div style="font-weight: bold; text-align: center; margin: 5px 0; font-size: 12pt;">$1</div>', $content);
        $content = (string) preg_replace('/^(PARTII POLITYCZNEJ NOWA NADZIEJA.*)/m', '<div style="font-weight: bold; text-align: center; margin: 5px 0; font-size: 12pt;">$1</div>', $content);
        $content = (string) preg_replace('/^(z dnia.*)/m', '<div style="text-align: center; margin: 10px 0; font-style: italic;">$1</div>', $content);
        $content = (string) preg_replace('/^(w sprawie.*)/m', '<div style="text-align: center; margin: 10px 0 20px 0; font-weight: bold;">$1</div>', $content);
        
        // Formatowanie paragrafów
        $content = (string) preg_replace('/^§ (\d+)$/m', '<div style="font-weight: bold; margin: 20px 0 10px 0; font-size: 12pt; border-left: 3px solid #000; padding-left: 10px; background: #f9f9f9;">§ $1</div>', $content);
        
        // Formatowanie list
        $content = (string) preg_replace('/^(\d+\.\s.+)$/m', '<div style="margin: 5px 0 5px 30px; padding: 3px 8px; background: #f8f9fa; border-left: 3px solid #666;">$1</div>', $content);
        
        // Zastąp nowe linie na <br>
        $content = nl2br($content);
        
        return $content;
    }
}
