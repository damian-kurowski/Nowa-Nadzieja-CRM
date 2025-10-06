<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class UchwalaSkresleniaCzlonka extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_UCHWALA_SKRESLENIA_CZLONKA;
    }
    
    public function getTitle(): string
    {
        return 'Uchwała o skreśleniu członka';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Uchwała Zarządu Okręgu o skreśleniu członka z listy członków Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'podstawa_skreslenia', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'prezes_okregu' => true,  // Prezes Okręgu
            'sekretarz_okregu' => true,  // Sekretarz Okręgu
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/czlonkostwo/uchwala_skreslenia_czlonka.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}