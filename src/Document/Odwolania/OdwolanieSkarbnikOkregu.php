<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolanieSkarbnikOkregu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_SKARBNIK_OKREGU;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Skarbnika Okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Skarbnika Okręgu przez Prezesa Okręgu';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie', 'powod_odwolania'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Okręgu
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/odwolania/odwolanie_skarbnik_okregu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Skarbnika Okręgu

Na podstawie § 19 ust. 1 pkt 4 Statutu Partii Politycznej Nowa Nadzieja, postanawiam:

§ 1
Odwołać ze stanowiska Skarbnika Okręgu {okreg} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


§ 2
Podstawa odwołania:
{powod_odwolania}

§ 3
Zobowiązać odwołanego Skarbnika do:
1. Przekazania całej dokumentacji finansowej okręgu
2. Zdania wszystkich środków finansowych
3. Sporządzenia bilansu zamknięcia
4. Przekazania dostępów do kont bankowych
5. Rozliczenia wszystkich operacji finansowych

Termin wykonania: 7 dni od dnia doręczenia decyzji.

§ 4
Do czasu powołania nowego Skarbnika obowiązki przejmuje Prezes Okręgu.

§ 5
Z dniem wejścia w życie niniejszej decyzji wygasają wszystkie upoważnienia 
finansowe związane z pełnioną funkcją.

§ 6
Decyzja wchodzi w życie z dniem {data_wejscia}.

§ 7
Od niniejszej decyzji przysługuje odwołanie do Zarządu Krajowego w terminie 14 dni.

Prezes Okręgu
_________________________
{prezes_okregu}
EOT;
    }
}