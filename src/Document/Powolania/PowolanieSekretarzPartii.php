<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieSekretarzPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_SEKRETARZ_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Sekretarza Partii';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Sekretarza Partii przez Prezesa Partii';
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
        return 'dokumenty/powolania/powolanie_sekretarz_partii.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}