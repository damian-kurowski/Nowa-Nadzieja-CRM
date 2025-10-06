<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieZastepcy extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Zastępcy Przewodniczącego Oddziału';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Zastępcy Przewodniczącego Oddziału przez Zebranie Członków';
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
        return 'dokumenty/zebrania/powolanie_zastepcy.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Zastępcy Przewodniczącego Oddziału

Na podstawie § 42 ust. 1 pkt 2 Statutu Partii Politycznej Nowa Nadzieja, 
zebranie członków oddziału {oddzial} po przeprowadzeniu demokratycznego głosowania 
postanawia:

§ 1
Powołać na stanowisko Zastępcy Przewodniczącego Oddziału {oddzial} 
Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Do zadań Zastępcy Przewodniczącego należy:
1. Wspieranie Przewodniczącego w kierowaniu oddziałem
2. Zastępowanie Przewodniczącego w przypadku jego nieobecności
3. Koordynowanie wyznaczonych obszarów działalności
4. Współpraca z członkami oddziału
5. Realizacja zadań zleconych przez Przewodniczącego

§ 3
Zastępca wybierany jest na okres 4 lat.

§ 4
Wynik głosowania:
- Liczba obecnych członków: {liczba_obecnych}
- Głosy "za": {glosy_za}
- Głosy "przeciw": {glosy_przeciw}
- Wstrzymujące się: {glosy_wstrzymujace}

§ 5
W przypadku niemożności pełnienia funkcji przez Przewodniczącego, 
Zastępca przejmuje jego obowiązki.

§ 6
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;
    }
}