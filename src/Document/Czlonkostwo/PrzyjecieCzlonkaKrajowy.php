<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PrzyjecieCzlonkaKrajowy extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_PRZYJECIE_CZLONKA_KRAJOWY;
    }
    
    public function getTitle(): string
    {
        return 'Przyjęcie członka przez zarząd krajowy';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Dokument przyjmujący kandydata do partii przez zarząd krajowy';
    }
    
    public function getRequiredFields(): array
    {
        return ['kandydat', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii lub Sekretarz Partii
            'drugi_podpisujacy' => true,  // Członek zarządu krajowego
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/czlonkostwo/przyjecie_czlonka_krajowy.html.twig';
    }
    
    public function generateContent(array $data): string
    {
        return '';
    }
}