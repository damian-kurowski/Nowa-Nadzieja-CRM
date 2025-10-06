<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieWiceprezesPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_WICEPREZES_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Wiceprezesa Partii przez Kongres';
    }
    
    public function getCategory(): string
    {
        return 'Wybory Kongresowe';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyborczy Wiceprezesa Partii przez Kongres';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'przewodniczacy_kongresu' => true,  // Przewodniczący Kongresu
            'sekretarz_kongresu' => true,  // Sekretarz Kongresu
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/powolania/powolanie_wiceprezes_partii.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}