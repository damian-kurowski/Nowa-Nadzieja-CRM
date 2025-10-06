<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PrzyjecieCzlonkaOkreg extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_PRZYJECIE_CZLONKA_OKREG;
    }
    
    public function getTitle(): string
    {
        return 'Przyjęcie członka przez zarząd okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Dokument przyjmujący kandydata do partii przez zarząd okręgu';
    }
    
    public function getRequiredFields(): array
    {
        return ['kandydat', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Okręgu
            'drugi_podpisujacy' => true,  // Członek zarządu okręgu
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/czlonkostwo/przyjecie_czlonka_okreg.html.twig';
    }
    
    public function generateContent(array $data): string
    {
        return '';
    }
}