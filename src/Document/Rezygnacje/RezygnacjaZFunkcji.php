<?php

namespace App\Document\Rezygnacje;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class RezygnacjaZFunkcji extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_REZYGNACJA_Z_FUNKCJI;
    }
    
    public function getTitle(): string
    {
        return 'Rezygnacja z funkcji';
    }
    
    public function getCategory(): string
    {
        return 'Rezygnacje';
    }
    
    public function getDescription(): string
    {
        return 'Oświadczenie o rezygnacji z pełnionej funkcji w Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'funkcja', 'powod_rezygnacji', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'rezygnujacy' => true,  // Osoba rezygnująca
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/rezygnacje/rezygnacja_z_funkcji.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}