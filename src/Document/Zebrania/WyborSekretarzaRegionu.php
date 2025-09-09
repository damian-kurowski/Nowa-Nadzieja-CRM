<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborSekretarzaRegionu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_SEKRETARZ_REGIONU;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Sekretarza Regionu';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyborczy Sekretarza Regionu przez Zarząd Regionu';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'region', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'prezes_regionu' => true,  // Prezes Regionu
            'drugi_podpisujacy' => true,  // Członek Zarządu Regionu
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZARZĄDU REGIONU {region}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Sekretarza Regionu {region}

Na podstawie § 23 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Regionu {region} 
w wyniku przeprowadzonego demokratycznego głosowania postanawia:

§ 1
Wybrać na stanowisko Sekretarza Regionu {region} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}




Dotychczasowe funkcje w Partii: {dotychczasowe_funkcje}

§ 2
Podstawą wyboru kandydata są:
1. Wysokie kwalifikacje organizacyjne i administracyjne
2. Doświadczenie w pracy w strukturach Partii
3. Znajomość specyfiki regionu i problemów lokalnych
4. Umiejętności w zakresie zarządzania dokumentacją
5. Nieposzlakowana opinia w środowisku partyjnym

§ 3
Przebieg głosowania:
- Data i miejsce: {data} w {miejsce_zebrania}
- Liczba członków Zarządu Regionu: {liczba_czlonkow_zarzadu}
- Liczba członków obecnych: {liczba_obecnych}
- Liczba głosów oddanych na kandydata: {glosy_za}
- Kandydat został wybrany większością głosów

§ 4
Do zakresu zadań Sekretarza Regionu należy:
1. Prowadzenie dokumentacji kancelaryjnej Regionu
2. Obsługa administracyjna Zarządu Regionu
3. Sporządzanie protokołów z posiedzeń Zarządu Regionu
4. Koordynacja działań administracyjnych między Okręgami
5. Prowadzenie korespondencji regionalnej
6. Archiwizowanie dokumentów regionalnych

§ 5
Sekretarz Regionu składa sprawozdania Zarządowi Regionu co najmniej raz na kwartał.

§ 6
Wybór następuje na czas kadencji Zarządu Regionu.

§ 7
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 8
Uchwała została podjęta w głosowaniu jawnym.

Prezes Regionu                         Członek Zarządu Regionu
_________________________              _________________________
{prezes_regionu}                       {czlonek_zarzadu_regionu}
EOT;
    }
}