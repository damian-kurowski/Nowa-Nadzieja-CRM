<?php

namespace App\Document\Struktura;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class UtworzenieOddzialu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_UTWORZENIE_ODDZIALU;
    }
    
    public function getTitle(): string
    {
        return 'Utworzenie Oddziału';
    }
    
    public function getCategory(): string
    {
        return 'Struktura';
    }
    
    public function getDescription(): string
    {
        return 'Dokument tworzący nowy oddział w okręgu przez Zarząd Okręgu';
    }
    
    public function getRequiredFields(): array
    {
        return [
            'nazwa_oddzialu',
            'siedziba_oddzialu',
            'gminy',
            'liczba_czlonkow',
            'czlonkowie_zalozyciele',
            'koordynator',
            'data_wejscia_w_zycie',
            'drugi_podpisujacy'
        ];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Okręgu (twórca dokumentu)
            'district_board_member' => true,  // Członek Zarządu Okręgu (Sekretarz/Wiceprezes/Skarbnik)
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/struktura/utworzenie_oddzialu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZARZĄDU OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie utworzenia Oddziału {nazwa_oddzialu}

Na podstawie § 15 ust. 1 Statutu Partii Politycznej Nowa Nadzieja oraz w celu zapewnienia 
lepszej organizacji działalności Partii na poziomie lokalnym, Zarząd Okręgu {okreg} 
po przeprowadzeniu analizy potrzeb organizacyjnych postanawia:

§ 1
Utworzyć w strukturach Partii Politycznej Nowa Nadzieja:

ODDZIAŁ {nazwa_oddzialu}

z siedzibą w {siedziba_oddzialu}.

§ 2
Oddział obejmuje swoim działaniem teren następujących gmin:
{gminy}

§ 3
Stwierdza się, że utworzenie Oddziału jest uzasadnione:
1. Liczbą członków Partii na danym terenie (minimum {liczba_czlonkow} członków)
2. Potrzebą lepszej organizacji działalności lokalnej
3. Zapewnieniem członkom możliwości aktywnego uczestnictwa w życiu Partii
4. Realizacją celów statutowych na poziomie lokalnym

§ 4
Członkami założycielami Oddziału są:
{czlonkowie_zalozyciele}

§ 5
Do czasu wyboru władz Oddziału przez Zebranie Członków:
1. Koordynatorem Oddziału zostaje wyznaczony: {koordynator}
2. Pierwsze Zebranie Członków zostanie zwołane w terminie 30 dni
3. Zarząd Okręgu zapewnia wsparcie organizacyjne i merytoryczne
4. Koszty organizacji pierwszego zebrania pokrywa Okręg

§ 6
Zobowiązuje się Sekretarza Okręgu do:
1. Niezwłocznego powiadomienia Zarządu Krajowego o utworzeniu Oddziału
2. Aktualizacji ewidencji struktur Partii
3. Poinformowania wszystkich członków Partii z terenu działania Oddziału
4. Zapewnienia obsługi administracyjno-prawnej Oddziału
5. Przekazania kopii niniejszej uchwały do archiwum Partii

§ 7
Oddział uzyskuje pełną zdolność do działania z dniem wejścia w życie niniejszej uchwały.

§ 8
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 9
Uchwała została podjęta jednomyślnie w głosowaniu jawnym.

Prezes Okręgu                        Sekretarz Okręgu
_________________________            _________________________
{prezes_okregu}                      {sekretarz_okregu}
EOT;
    }
}