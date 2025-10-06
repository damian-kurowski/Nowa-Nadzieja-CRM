<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WniosekZawieszeniaGzlonkostwa extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WNIOSEK_ZAWIESZENIA_CZLONKOSTWA;
    }
    
    public function getTitle(): string
    {
        return 'Wniosek o zawieszenie członkostwa';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Wniosek członka o zawieszenie swojego członkostwa w Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'przyczyna_zawieszenia', 'okres_zawieszenia', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'czlonek' => true,  // Członek wnioskujący
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/czlonkostwo/wniosek_zawieszenia_czlonkostwa.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}