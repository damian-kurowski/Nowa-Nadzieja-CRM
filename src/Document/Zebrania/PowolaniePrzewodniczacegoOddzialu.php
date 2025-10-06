<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolaniePrzewodniczacegoOddzialu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Przewodniczącego Oddziału';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Przewodniczącego Oddziału przez Zebranie Członków';
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

    public function getTemplateName(): string
    {
        return 'dokumenty/zebrania/powolanie_przewodniczacego_oddzialu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Przewodniczącego Oddziału

Na podstawie § 15 ust. 5 Statutu Partii Politycznej Nowa Nadzieja, 
zebranie członków oddziału {oddzial} po przeprowadzeniu demokratycznego głosowania 
postanawia:

§ 1
Powołać na stanowisko Przewodniczącego Oddziału {oddzial} 
Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Do zadań Przewodniczącego Oddziału należy:
1. Kierowanie pracami oddziału
2. Reprezentowanie oddziału na zewnątrz
3. Organizowanie działalności programowej i organizacyjnej
4. Współpraca z Zarządem Okręgu
5. Nadzór nad realizacją uchwał zebrania członków
6. Koordynowanie pracy komisji i zespołów roboczych

§ 3
Przewodniczący wybierany jest na okres 4 lat.

§ 4
Wynik głosowania:
- Liczba obecnych członków: {liczba_obecnych}
- Głosy "za": {glosy_za}
- Głosy "przeciw": {glosy_przeciw}
- Wstrzymujące się: {glosy_wstrzymujace}

§ 5
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;
    }
}