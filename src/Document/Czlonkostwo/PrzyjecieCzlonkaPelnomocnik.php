<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PrzyjecieCzlonkaPelnomocnik extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK;
    }
    
    public function getTitle(): string
    {
        return 'Przyjęcie członka przez Pełnomocnika';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Dokument przyjmujący kandydata do partii przez Okręgowego Pełnomocnika ds. przyjmowania nowych członków';
    }
    
    public function getRequiredFields(): array
    {
        return ['kandydat', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Pełnomocnik przyjmowania
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
OKRĘGOWEGO PEŁNOMOCNIKA DS. PRZYJMOWANIA NOWYCH CZŁONKÓW
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie przyjęcia {imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii} w poczet członków Partii Politycznej Nowa Nadzieja

Na podstawie § 8 ust. 5 Statutu Partii Politycznej Nowa Nadzieja, działając jako Okręgowy 
Pełnomocnik ds. przyjmowania nowych członków Okręgu {okreg}, po rozpatrzeniu wniosku 
o przyjęcie w poczet członków Partii, postanawiam:

§ 1
Przyjąć w poczet członków Partii Politycznej Nowa Nadzieja kandydata:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


Zamieszkały/a: {adres}
Email: {email}
Telefon: {telefon}

§ 2
Stwierdzam, że kandydat spełnia wszystkie wymagania statutowe:
1. Jest obywatelem polskim
2. Ukończył 18 lat (wiek: {wiek} lat)
3. Posiada pełną zdolność do czynności prawnych
4. Złożył wniosek o przyjęcie wraz z deklaracją członkowską
5. Zapoznał się ze Statutem i Programem Partii
6. Wyraził zgodę na przetwarzanie danych osobowych
7. Nie pozostaje w konflikcie z celami i wartościami Partii

§ 3
Kandydat zostaje przydzielony do Okręgu {okreg}.

§ 4
Nowy członek zobowiązuje się do:
1. Przestrzegania postanowień Statutu i uchwał władz Partii
2. Aktywnego uczestnictwa w działalności Partii
3. Regularnego opłacania składek członkowskich
4. Godnego reprezentowania wartości i celów Partii
5. Uczestnictwa w życiu organizacyjnym odpowiedniego Oddziału

§ 5
Zobowiązuję Sekretarza Okręgu do wpisania nowego członka do ewidencji 
oraz poinformowania go o prawach i obowiązkach członkowskich.

§ 6
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 7
Uchwała podlega przekazaniu do Zarządu Okręgu w terminie 7 dni.

Okręgowy Pełnomocnik ds. przyjmowania nowych członków
_________________________
{podpisujacy}
EOT;
    }
}