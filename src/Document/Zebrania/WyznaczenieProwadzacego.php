<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyznaczenieProwadzacego extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYZNACZENIE_PROWADZACEGO;
    }
    
    public function getTitle(): string
    {
        return 'Wyznaczenie Prowadzącego Zebranie';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyznaczający prowadzącego zebranie członków oddziału';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'oddzial', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Protokolant (za zebranie)
            'drugi_podpisujacy' => true,  // Członek zebrania
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/zebrania/wyznaczenie_prowadzacego.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyznaczenia prowadzącego zebranie

Na podstawie § 40 ust. 1 Statutu Partii Politycznej Nowa Nadzieja, 
zebranie członków oddziału {oddzial} postanawia:

§ 1
Wyznaczyć na prowadzącego zebranie członków oddziału {oddzial} 
Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Funkcja w Partii: {funkcja}

§ 2
Do zadań prowadzącego zebranie należy:
1. Kierowanie przebiegiem obrad zgodnie z porządkiem
2. Udzielanie głosu członkom zebrania
3. Przeprowadzanie głosowań
4. Czuwanie nad przestrzeganiem regulaminu
5. Podpisywanie protokołu z zebrania
6. Zapewnienie kultury dyskusji

§ 3
Prowadzący zebranie ma prawo do:
1. Udzielania upomnień za naruszenie porządku
2. Odbierania głosu za przekraczanie regulaminu
3. Zarządzania przerw w obradach
4. Wyznaczania kolejności wystąpień

§ 4
Prowadzący zobowiązany jest do:
1. Zachowania bezstronności
2. Zapewnienia równych praw wszystkim uczestnikom
3. Przestrzegania demokratycznych zasad debaty
4. Właściwego prowadzenia głosowań

§ 5
Decyzja wchodzi w życie z dniem {data_wejscia}.

Za zebranie:
Protokolant                          Członek zebrania
_________________________            _________________________
{protokolant}                        {czlonek_zebrania}
EOT;
    }
}