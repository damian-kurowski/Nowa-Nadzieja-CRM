<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolanieWiceprezesPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_WICEPREZES_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Wiceprezesa Partii';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Wiceprezesa Partii przez Prezesa Partii';
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
        return 'dokumenty/odwolania/odwolanie_wiceprezes_partii.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}