<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieSekretarzaOddzialu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_SEKRETARZA_ODDZIALU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Sekretarza Oddziału';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Sekretarza Oddziału przez Zebranie Członków';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'oddzial', 'data_wejscia_w_zycie'];
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
w sprawie powołania Sekretarza Oddziału

Na podstawie § 42 ust. 1 pkt 3 Statutu Partii Politycznej Nowa Nadzieja, 
zebranie członków oddziału {oddzial} po przeprowadzeniu demokratycznego głosowania 
postanawia:

§ 1
Powołać na stanowisko Sekretarza Oddziału {oddzial} 
Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Do zadań Sekretarza Oddziału należy:
1. Prowadzenie dokumentacji oddziału
2. Organizowanie posiedzeń i zebrań
3. Sporządzanie protokołów z zebrań
4. Prowadzenie korespondencji
5. Współpraca z Sekretarzem Okręgu
6. Prowadzenie ewidencji członków oddziału

§ 3
Sekretarz wybierany jest na okres 4 lat.

§ 4
Wynik głosowania:
- Liczba obecnych członków: {liczba_obecnych}
- Głosy "za": {glosy_za}
- Głosy "przeciw": {glosy_przeciw}
- Wstrzymujące się: {glosy_wstrzymujace}

§ 5
Sekretarz Oddziału podlega Przewodniczącemu Oddziału i składa mu 
regularne sprawozdania z działalności.

§ 6
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;
    }
}