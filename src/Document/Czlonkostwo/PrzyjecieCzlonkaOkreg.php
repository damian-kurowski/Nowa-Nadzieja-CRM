<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PrzyjecieCzlonkaOkreg extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_PRZYJECIE_CZLONKA_OKREG;
    }
    
    public function getTitle(): string
    {
        return 'Przyjęcie członka przez zarząd okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Dokument przyjmujący kandydata do partii przez zarząd okręgu';
    }
    
    public function getRequiredFields(): array
    {
        return ['kandydat', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Okręgu
            'drugi_podpisujacy' => true,  // Członek zarządu okręgu
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZARZĄDU OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie przyjęcia {imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii} w poczet członków Partii Politycznej Nowa Nadzieja

Na podstawie § 8 ust. 1 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Okręgu {okreg} 
po rozpatrzeniu wniosku o przyjęcie w poczet członków Partii postanawia:

§ 1
Przyjąć do Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Zamieszkały/a: {adres}
Email: {email}

§ 2
Stwierdzić, że kandydat spełnia wymagania statutowe:
1. Jest obywatelem polskim
2. Ukończył 18 lat
3. Złożył deklarację członkowską wraz z wymaganymi dokumentami
4. Zapoznał się ze Statutem i Programem Partii
5. Wyraził zgodę na przetwarzanie danych osobowych

§ 3
Zobowiązać nowego członka do:
1. Przestrzegania postanowień Statutu i uchwał władz Partii
2. Aktywnego uczestnictwa w działalności Partii
3. Regularnego opłacania składek członkowskich

§ 4
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 5
Uchwała została podjęta jednomyślnie/większością głosów w głosowaniu jawnym.

Prezes Okręgu                        Członek Zarządu Okręgu
_________________________            _________________________
{prezes_okregu}                      {czlonek_zarzadu}
EOT;
    }
}