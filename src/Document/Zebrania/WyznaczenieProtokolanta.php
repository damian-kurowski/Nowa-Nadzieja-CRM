<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyznaczenieProtokolanta extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYZNACZENIE_PROTOKOLANTA;
    }
    
    public function getTitle(): string
    {
        return 'Wyznaczenie Protokolanta Zebrania';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyznaczający protokolanta zebrania członków oddziału';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'oddzial', 'data_zebrania', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prowadzący zebranie
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PRZEWODNICZĄCEGO ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyznaczenia protokolanta zebrania

Na podstawie § 40 ust. 3 Statutu Partii Politycznej Nowa Nadzieja oraz Regulaminu 
Zebrań Członków Oddziału, postanawiam:

§ 1
Wyznaczyć na protokolanta zebrania członków oddziału {oddzial} 
Partii Politycznej Nowa Nadzieja, które odbędzie się dnia {data_zebrania}:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Funkcja w Partii: {funkcja}

§ 2
Do zadań protokolanta należy:
1. Sporządzenie protokołu z przebiegu zebrania
2. Odnotowanie wszystkich podjętych uchwał i decyzji
3. Zapisanie wyników głosowań
4. Dokumentowanie dyskusji i wniosków
5. Prowadzenie listy obecności

§ 3
Protokolant zobowiązany jest do:
1. Obiektywnego dokumentowania przebiegu zebrania
2. Dokładnego zapisywania treści uchwał
3. Odnotowania wszystkich zgłoszonych uwag i wniosków
4. Przedłożenia protokołu do podpisania prowadzącemu zebranie

§ 4
Protokół zostanie sporządzony w terminie 7 dni od zakończenia zebrania.

§ 5
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie
_________________________
{prowadzacy}
EOT;
    }
}