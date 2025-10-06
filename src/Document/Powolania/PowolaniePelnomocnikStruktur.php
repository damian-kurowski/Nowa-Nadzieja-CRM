<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolaniePelnomocnikStruktur extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Pełnomocnika ds. Struktur';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Pełnomocnika ds. Struktur przez Prezesa Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/powolania/powolanie_pelnomocnik_struktur.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}