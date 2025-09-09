<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolaniePelnomocnikStruktur extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Pełnomocnika ds. Struktur';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Pełnomocnika ds. Struktur przez Prezesa Partii';
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
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Pełnomocnika ds. Struktur Terytorialnych

Na podstawie § 23 ust. 3 Statutu Partii Politycznej Nowa Nadzieja, postanawiam:

§ 1
Odwołać ze stanowiska Pełnomocnika ds. Struktur Terytorialnych 
Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


§ 2
Podstawa odwołania:
{powod_odwolania}

§ 3
Zobowiązać odwołanego Pełnomocnika do:
1. Przekazania wszystkich dokumentów związanych z pełnioną funkcją
2. Zdania sprawy ze stanu struktur terenowych
3. Przekazania bieżących spraw i projektów
4. Rozliczenia powierzonego mienia i środków

Termin wykonania: 14 dni od dnia doręczenia decyzji.

§ 4
Z dniem wejścia w życie niniejszej decyzji wygasają wszystkie upoważnienia 
i pełnomocnictwa udzielone w związku z pełnieniem funkcji.

§ 5
Decyzja wchodzi w życie z dniem {data_wejscia}.

§ 6
Od niniejszej decyzji przysługuje odwołanie do Zarządu Krajowego w terminie 14 dni.

Prezes Partii
_________________________
{prezes_partii}
EOT;
    }
}