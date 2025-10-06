<?php

namespace App\Document\Dyscyplinarne;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PostanowienieSaduPartyjnego extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POSTANOWIENIE_SADU_PARTYJNEGO;
    }
    
    public function getTitle(): string
    {
        return 'Postanowienie Sądu Partyjnego';
    }
    
    public function getCategory(): string
    {
        return 'Dyscyplinarne';
    }
    
    public function getDescription(): string
    {
        return 'Postanowienie Sądu Partyjnego w sprawie wymierzenia kary dyscyplinarnej';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'rodzaj_kary', 'zarzuty', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'przewodniczacy_sadu' => true,  // Przewodniczący Sądu Partyjnego
            'sedziowie' => true,  // Sędziowie
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/dyscyplinarne/postanowienie_sadu_partyjnego.html.twig';
    }

    public function generateContent(array $data): string
    {
        return '';
    }
}