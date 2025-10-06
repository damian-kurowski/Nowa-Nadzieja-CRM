<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieSekretarzOkregu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_SEKRETARZ_OKREGU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Sekretarza Okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Sekretarza Okręgu przez Prezesa Okręgu';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Okręgu
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/powolania/powolanie_sekretarz_okregu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}