<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OswiadczenieWystapienia extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_OSWIADCZENIE_WYSTAPIENIA;
    }
    
    public function getTitle(): string
    {
        return 'Oświadczenie o wystąpieniu z Partii';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wystąpienia członka z Partii Politycznej Nowa Nadzieja';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'powod_wystapienia', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'czlonek' => true,  // Członek występujący
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/czlonkostwo/oswiadczenie_wystapienia.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}