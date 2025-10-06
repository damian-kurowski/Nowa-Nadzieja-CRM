<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolanieSkarbnikPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_SKARBNIK_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Skarbnika Partii';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Skarbnika Partii przez Prezesa Partii';
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
        return 'dokumenty/odwolania/odwolanie_skarbnik_partii.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Skarbnika Partii

Na podstawie § 21 ust. 9a Statutu Partii Politycznej Nowa Nadzieja, postanawiam:

§ 1
Odwołać ze stanowiska Skarbnika Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


§ 2
Podstawa odwołania:
{powod_odwolania}

§ 3
Zobowiązać odwołanego Skarbnika do:
1. Przekazania całej dokumentacji księgowej i finansowej
2. Zdania wszystkich środków finansowych i mienia Partii
3. Sporządzenia bilansu zamknięcia na dzień odwołania
4. Przekazania dostępów do kont bankowych i systemów finansowych
5. Rozliczenia wszystkich bieżących operacji finansowych

Termin wykonania: 7 dni od dnia doręczenia decyzji.

§ 4
Do czasu powołania nowego Skarbnika obowiązki przejmuje Prezes Partii.

§ 5
Z dniem wejścia w życie niniejszej decyzji wygasają wszystkie upoważnienia 
i pełnomocnictwa finansowe.

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