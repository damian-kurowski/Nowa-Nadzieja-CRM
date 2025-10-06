<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolaniePrezesOkregu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_PREZES_OKREGU;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Prezesa Okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Prezesa Okręgu przez Prezesa Partii';
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
        return 'dokumenty/odwolania/odwolanie_prezes_okregu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Prezesa Okręgu {okreg}

Na podstawie § 31 ust. 4 Statutu Partii Politycznej Nowa Nadzieja, postanawiam:

§ 1
Odwołać ze stanowiska Prezesa Okręgu {okreg} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


§ 2
Podstawa odwołania:
{powod_odwolania}

§ 3
Zobowiązać odwołanego Prezesa Okręgu do:
1. Przekazania całej dokumentacji okręgu
2. Zdania sprawy z działalności okręgu
3. Przekazania środków finansowych i mienia okręgu
4. Poinformowania oddziałów o zmianie kierownictwa
5. Rozliczenia bieżących spraw i projektów

Termin wykonania: 14 dni od dnia doręczenia decyzji.

§ 4
Do czasu powołania Pełniącego Obowiązki Prezesa Okręgu, funkcję przejmuje 
najstarszy stażem członek Zarządu Okręgu.

§ 5
Zarząd Krajowy w terminie 30 dni podejmie decyzję o powołaniu 
Pełniącego Obowiązki Prezesa Okręgu.

§ 6
Decyzja wchodzi w życie z dniem {data_wejscia}.

§ 7
Od niniejszej decyzji przysługuje odwołanie do Zarządu Krajowego w terminie 14 dni.

Prezes Partii
_________________________
{prezes_partii}
EOT;
    }
}