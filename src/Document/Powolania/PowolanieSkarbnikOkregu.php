<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieSkarbnikOkregu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_SKARBNIK_OKREGU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Skarbnika Okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Skarbnika Okręgu przez Prezesa Okręgu';
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
        return 'dokumenty/powolania/powolanie_skarbnik_okregu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}