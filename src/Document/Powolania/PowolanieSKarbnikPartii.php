<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieSKarbnikPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_SKARBNIK_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Skarbnika Partii';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Skarbnika Partii przez Prezesa Partii';
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
        return 'dokumenty/powolania/powolanie_skarbnik_partii.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}