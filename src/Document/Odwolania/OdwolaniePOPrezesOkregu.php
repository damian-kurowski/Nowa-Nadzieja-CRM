<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolaniePOPrezesOkregu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_PO_PREZES_OKREGU;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie p.o. Prezesa Okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Pełniącego Obowiązki Prezesa Okręgu';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii lub Sekretarz Partii
            'drugi_podpisujacy' => true,  // Drugi członek zarządu krajowego
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
ZARZĄDU KRAJOWEGO
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Pełniącego Obowiązki Prezesa Okręgu {okreg}

Na podstawie § 31 ust. 5 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Krajowy 
postanawia:

§ 1
Odwołać ze stanowiska Pełniącego Obowiązki Prezesa Okręgu {okreg} 
Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


§ 2
Podstawa odwołania:
{powod_odwolania}

§ 3
Zobowiązać odwołanego do:
1. Przekazania dokumentacji okręgu
2. Zdania sprawy z okresu pełnienia obowiązków
3. Przekazania bieżących spraw
4. Rozliczenia powierzonego mienia

Termin wykonania: 7 dni od dnia doręczenia decyzji.

§ 4
Do czasu powołania nowego Pełniącego Obowiązki Prezesa Okręgu lub wyboru 
Prezesa przez Walne Zgromadzenie, obowiązki przejmuje najstarszy stażem 
członek Zarządu Okręgu.

§ 5
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii                        Sekretarz Partii
_________________________            _________________________
{prezes_partii}                      {sekretarz_partii}
EOT;
    }
}