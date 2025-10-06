<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolaniePrezesRegionu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_PREZES_REGIONU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Prezesa Regionu';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Prezesa Regionu przez Prezesa Partii po zaopiniowaniu przez Zarząd Krajowy';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'region', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/powolania/powolanie_prezes_regionu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}