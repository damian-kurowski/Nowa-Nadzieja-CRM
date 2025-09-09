<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PrzyjecieCzlonkaKrajowy extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_PRZYJECIE_CZLONKA_KRAJOWY;
    }
    
    public function getTitle(): string
    {
        return 'Przyjęcie członka przez zarząd krajowy';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Dokument przyjmujący kandydata do partii przez zarząd krajowy';
    }
    
    public function getRequiredFields(): array
    {
        return ['kandydat', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii lub Sekretarz Partii
            'drugi_podpisujacy' => true,  // Członek zarządu krajowego
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZARZĄDU KRAJOWEGO
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie przyjęcia {imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii} w poczet członków Partii Politycznej Nowa Nadzieja

Na podstawie § 8 ust. 1 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Krajowy 
po rozpatrzeniu wniosku o przyjęcie w poczet członków Partii postanawia:

§ 1
Przyjąć do Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Zamieszkały/a: {adres}
Email: {email}

§ 2
Stwierdzić, że kandydat:
1. Spełnia wszystkie wymagania statutowe
2. Posiada nieposzlakowaną opinię
3. Deklaruje aktywne zaangażowanie w działalność Partii
4. Akceptuje Program i wartości Partii

§ 3
Przydzielić nowego członka do Okręgu {okreg}.

§ 4
Zobowiązać nowego członka do:
1. Przestrzegania postanowień Statutu i uchwał władz Partii
2. Aktywnego uczestnictwa w działalności Partii
3. Regularnego opłacania składek członkowskich
4. Godnego reprezentowania Partii

§ 5
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 6
Uchwała została podjęta w głosowaniu jawnym.

Prezes Partii                        Członek Zarządu Krajowego
_________________________            _________________________
{prezes_partii}                      {czlonek_zarzadu}
EOT;
    }
}