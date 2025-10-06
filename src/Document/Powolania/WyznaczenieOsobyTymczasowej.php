<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyznaczenieOsobyTymczasowej extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYZNACZENIE_OSOBY_TYMCZASOWEJ;
    }
    
    public function getTitle(): string
    {
        return 'Wyznaczenie osoby tymczasowo pełniącej obowiązki';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyznaczający osobę tymczasowo pełniącą obowiązki na wakującym stanowisku';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'wakujace_stanowisko', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/powolania/wyznaczenie_osoby_tymczasowej.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}