<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolanieSekretarzPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_SEKRETARZ_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Sekretarza Partii';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Sekretarza Partii przez Prezesa Partii';
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
        return 'dokumenty/odwolania/odwolanie_sekretarz_partii.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Sekretarza Partii

Na podstawie § 21 ust. 9a Statutu Partii Politycznej Nowa Nadzieja, po przeprowadzeniu 
postępowania wyjaśniającego oraz mając na względzie interes Partii, postanawiam:

§ 1
Odwołać ze stanowiska Sekretarza Partii Politycznej Nowa Nadzieja:

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
Zobowiązać odwołanego Sekretarza do wykonania w terminie 14 dni od doręczenia decyzji:
1. Przekazania całej dokumentacji kancelaryjnej Partii wraz z inwentarzem
2. Zdania protokolarnego wszystkich prowadzonych spraw
3. Przekazania dostępów do systemów informatycznych i baz danych
4. Rozliczenia powierzonego mienia ruchomego i nieruchomego
5. Przekazania pieczęci, stempli i innych atrybutów urzędowych
6. Sporządzenia raportu końcowego z pełnionej funkcji
7. Przekazania kontaktów służbowych i bieżącej korespondencji

§ 5
Do czasu powołania nowego Sekretarza Partii obowiązki przejmuje Prezes Partii 
lub wyznaczony przez niego członek Zarządu Krajowego.

§ 6
Z dniem wejścia w życie niniejszej decyzji wygasają:
1. Wszystkie upoważnienia i pełnomocnictwa udzielone w związku z funkcją
2. Prawo reprezentowania Partii w sprawach administracyjnych
3. Dostęp do informacji niejawnych i poufnych
4. Członkostwo w Zarządzie Krajowym z głosem doradczym

§ 7
Decyzja podlega niezwłocznemu przekazaniu do:
1. Zarządu Krajowego
2. Archiwum Partii
3. Organów rejestrowych (w zakresie reprezentacji)

§ 8
Decyzja wchodzi w życie z dniem {data_wejscia}.

§ 9
Od niniejszej decyzji przysługuje odwołanie do Zarządu Krajowego w terminie 14 dni 
od dnia doręczenia, za pośrednictwem Prezesa Partii.

§ 10
W przypadku wniesienia odwołania, decyzja zostaje wstrzymana do czasu 
rozpatrzenia sprawy przez Zarząd Krajowy.

Prezes Partii
_________________________
{prezes_partii}
EOT;
    }
}