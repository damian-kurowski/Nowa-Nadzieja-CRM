<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PrzyjecieCzlonkaPelnomocnik extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK;
    }
    
    public function getTitle(): string
    {
        return 'Przyjęcie członka przez Pełnomocnika';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Dokument przyjmujący kandydata do partii przez Okręgowego Pełnomocnika ds. przyjmowania nowych członków';
    }
    
    public function getRequiredFields(): array
    {
        return ['kandydat', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Pełnomocnik przyjmowania
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/czlonkostwo/przyjecie_czlonka_pelnomocnik.html.twig';
    }
    
    public function generateContent(array $data): string
    {
        return '';
    }
}