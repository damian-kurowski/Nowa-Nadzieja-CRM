<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolaniePrzewodniczacegoOddzialu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Przewodniczącego Oddziału';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Przewodniczącego Oddziału przez Zebranie Członków';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'oddzial', 'data_wejscia_w_zycie', 'powod_odwolania'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'prowadzacy' => true,  // Prowadzący zebranie
            'protokolant' => true,  // Protokolant zebrania
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Przewodniczącego Oddziału

Na podstawie § 42 ust. 1 pkt 1 Statutu Partii Politycznej Nowa Nadzieja, 
zebranie członków oddziału {oddzial} po przeprowadzeniu demokratycznego głosowania 
postanawia:

§ 1
Odwołać ze stanowiska Przewodniczącego Oddziału {oddzial} 
Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


§ 2
Podstawa odwołania:
{powod_odwolania}

§ 3
Wynik głosowania:
- Liczba obecnych członków: {liczba_obecnych}
- Głosy "za": {glosy_za}
- Głosy "przeciw": {glosy_przeciw}
- Wstrzymujące się: {glosy_wstrzymujace}

§ 4
Zobowiązać odwołanego Przewodniczącego do:
1. Przekazania dokumentacji oddziału
2. Zdania sprawy z działalności
3. Rozliczenia powierzonego mienia
4. Przekazania bieżących spraw

§ 5
Do czasu wyboru nowego Przewodniczącego obowiązki przejmuje 
najstarszy stażem członek oddziału.

§ 6
Zebranie zobowiązuje się do wyboru nowego Przewodniczącego 
w terminie 30 dni.

§ 7
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;
    }
}