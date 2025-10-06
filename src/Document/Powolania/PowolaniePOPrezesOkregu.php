<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolaniePOPrezesOkregu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_PO_PREZES_OKREGU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie p.o. Prezesa Okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Pełniącego Obowiązki Prezesa Okręgu';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii lub Sekretarz Partii
            'drugi_podpisujacy' => true,  // Drugi członek zarządu krajowego
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/powolania/powolanie_p_o_prezes_okregu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}