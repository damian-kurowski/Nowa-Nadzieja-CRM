<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolaniePelnomocnikStruktur extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Pełnomocnika ds. Struktur';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Pełnomocnika ds. Struktur przez Prezesa Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie', 'powod_odwolania'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/odwolania/odwolanie_pelnomocnik_struktur.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}