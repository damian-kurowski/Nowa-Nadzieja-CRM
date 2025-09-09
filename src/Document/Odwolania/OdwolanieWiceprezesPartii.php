<?php

namespace App\Document\Odwolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OdwolanieWiceprezesPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_ODWOLANIE_WICEPREZES_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Odwołanie Wiceprezesa Partii';
    }
    
    public function getCategory(): string
    {
        return 'Odwołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument odwołujący Wiceprezesa Partii przez Prezesa Partii';
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
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Wiceprezesa Partii

Na podstawie § 21 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, postanawiam:

§ 1
Odwołać ze stanowiska Wiceprezesa Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


§ 2
Podstawa odwołania:
{powod_odwolania}

§ 3
Zobowiązać odwołanego Wiceprezesa do:
1. Przekazania wszystkich spraw związanych z pełnioną funkcją
2. Zdania dokumentacji i materiałów roboczych
3. Zakończenia prowadzonych projektów lub przekazania ich następcy
4. Rozliczenia podległych komórek organizacyjnych

Termin wykonania: 14 dni od dnia doręczenia decyzji.

§ 4
Z dniem wejścia w życie niniejszej decyzji odwołany przestaje być członkiem 
Zarządu Krajowego, a jego obowiązki przejmuje Prezes Partii.

§ 5
Wygasają wszystkie upoważnienia i pełnomocnictwa udzielone w związku 
z pełnieniem funkcji Wiceprezesa.

§ 6
Decyzja wchodzi w życie z dniem {data_wejscia}.

§ 7
Od niniejszej decyzji przysługuje odwołanie do Zarządu Krajowego w terminie 14 dni.

Prezes Partii
_________________________
{prezes_partii}
EOT;
    }
}