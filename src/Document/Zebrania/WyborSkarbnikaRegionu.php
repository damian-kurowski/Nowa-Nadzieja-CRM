<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborSkarbnikaRegionu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_SKARBNIK_REGIONU;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Skarbnika Regionu';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyborczy Skarbnika Regionu przez Zarząd Regionu';
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
w sprawie wyboru Skarbnika Regionu {region}

Na podstawie § 23 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Regionu {region} 
w wyniku przeprowadzonego demokratycznego głosowania postanawia:

§ 1
Wybrać na stanowisko Skarbnika Regionu {region} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}




Doświadczenie finansowe: {doswiadczenie_finansowe}

§ 2
Podstawą wyboru kandydata są:
1. Kwalifikacje w zakresie księgowości i finansów
2. Doświadczenie w zarządzaniu budżetem organizacji
3. Znajomość przepisów o finansowaniu partii politycznych
4. Umiejętności analityczne i planistyczne
5. Nieposzlakowana opinia i uczciwość

§ 3
Przebieg głosowania:
- Data i miejsce: {data} w {miejsce_zebrania}
- Liczba członków Zarządu Regionu: {liczba_czlonkow_zarzadu}
- Liczba członków obecnych: {liczba_obecnych}
- Liczba głosów oddanych na kandydata: {glosy_za}
- Kandydat został wybrany większością głosów

§ 4
Do zakresu zadań Skarbnika Regionu należy:
1. Zarządzanie finansami Regionu zgodnie z budżetem
2. Prowadzenie rozliczeń finansowych z Okręgami
3. Przygotowywanie sprawozdań finansowych Regionu
4. Nadzór nad gospodarowaniem środkami regionalnymi
5. Współpraca ze Skarbnikiem Partii w sprawach finansowych
6. Zabezpieczanie majątku Partii na poziomie regionalnym

§ 5
Skarbnik Regionu składa sprawozdania finansowe Zarządowi Regionu co najmniej raz na kwartał.

§ 6
Wybór następuje na czas kadencji Zarządu Regionu.

§ 7
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 8
Uchwała została podjęta w głosowaniu tajnym.

Prezes Regionu                         Członek Zarządu Regionu
_________________________              _________________________
{prezes_regionu}                       {czlonek_zarzadu_regionu}
EOT;
    }
}