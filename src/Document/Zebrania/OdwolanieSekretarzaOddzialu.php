<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolanieSekretarzaOddzialu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Sekretarza Oddziału';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Sekretarza Oddziału przez Zebranie Członków';
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
w sprawie odwołania Sekretarza Oddziału

Na podstawie § 42 ust. 1 pkt 3 Statutu Partii Politycznej Nowa Nadzieja, 
zebranie członków oddziału {oddzial} po przeprowadzeniu demokratycznego głosowania 
postanawia:

§ 1
Odwołać ze stanowiska Sekretarza Oddziału {oddzial} 
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
Zobowiązać odwołanego Sekretarza do:
1. Przekazania całej dokumentacji oddziału
2. Zdania ewidencji członków
3. Przekazania korespondencji i materiałów roboczych
4. Rozliczenia powierzonego mienia

§ 5
Do czasu wyboru nowego Sekretarza obowiązki przejmuje 
Przewodniczący Oddziału.

§ 6
Zebranie zobowiązuje się do wyboru nowego Sekretarza 
w terminie 30 dni.

§ 7
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;
    }
}