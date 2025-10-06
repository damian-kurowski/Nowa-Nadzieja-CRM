<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolaniePrezesRegionu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_PREZES_REGIONU;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Prezesa Regionu';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Prezesa Regionu przez Prezesa Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'region', 'data_wejscia_w_zycie', 'powod_odwolania'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/odwolania/odwolanie_prezes_regionu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Prezesa Regionu {region}

Na podstawie § 24 Statutu Partii Politycznej Nowa Nadzieja oraz mając na względzie 
interes Partii, postanawiam:

§ 1
Odwołać ze stanowiska Prezesa Regionu {region} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Pełniący funkcję od dnia: {data_powolania}

§ 2
Podstawą odwołania jest:
{powod_odwolania}

§ 3
Dodatkowe okoliczności uzasadniające odwołanie:
1. {okolicznosc_1}
2. {okolicznosc_2}
3. {okolicznosc_3}

§ 4
Zobowiązać odwołanego Prezesa Regionu do wykonania w terminie 14 dni:
1. Przekazania całej dokumentacji regionalnej wraz z inwentarzem
2. Zdania protokolarnego wszystkich prowadzonych spraw
3. Przekazania dostępów do systemów informatycznych regionu
4. Rozliczenia powierzonego mienia Partii na poziomie regionu
5. Sporządzenia raportu końcowego z pełnionej funkcji

§ 5
Do czasu powołania nowego Prezesa Regionu obowiązki przejmuje Zarząd Krajowy 
lub wyznaczony przez niego pełnomocnik.

§ 6
Z dniem wejścia w życie niniejszej decyzji wygasają:
1. Wszystkie upoważnienia udzielone w związku z funkcją Prezesa Regionu
2. Prawo reprezentowania Partii w sprawach regionalnych
3. Członkostwo w regionalnych organach współpracy

§ 7
Decyzja wchodzi w życie z dniem {data_wejscia}.

§ 8
Decyzję należy przekazać do:
1. Zarządu Krajowego
2. Wszystkich Zarządów Okręgów w Regionie {region}
3. Archiwum Partii

Prezes Partii
_________________________
{prezes_partii}
EOT;
    }
}