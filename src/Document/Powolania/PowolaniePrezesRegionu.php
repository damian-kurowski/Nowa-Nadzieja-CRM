<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolaniePrezesRegionu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_PREZES_REGIONU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Prezesa Regionu';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Prezesa Regionu przez Prezesa Partii po zaopiniowaniu przez Zarząd Krajowy';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'region', 'data_wejscia_w_zycie'];
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
w sprawie powołania Prezesa Regionu {region}

Na podstawie § 24 Statutu Partii Politycznej Nowa Nadzieja, po wcześniejszym zaopiniowaniu 
kandydata przez Zarząd Krajowy w dniu {data_opinii_zarzadu}, postanawiam:

§ 1
Powołać na stanowisko Prezesa Regionu {region} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}





§ 2
Podstawą powołania są:
1. Pozytywna opinia Zarządu Krajowego z dnia {data_opinii_zarzadu}
2. Wysokie kwalifikacje organizacyjne i polityczne kandydata
3. Znajomość specyfiki regionu i problemów lokalnych
4. Doświadczenie w pracy w strukturach Partii
5. Zdolności przywódcze i umiejętność budowania konsensusu

§ 3
Do zakresu kompetencji Prezesa Regionu należy w szczególności:
1. Kierowanie pracami Zarządu Regionu
2. Reprezentowanie Regionu wobec władz krajowych Partii
3. Nadzór nad działalnością Okręgów w Regionie
4. Koordynacja kampanii wyborczych na poziomie wojewódzkim
5. Wypracowywanie stanowisk w sprawach wojewódzkich
6. Współpraca z władzami samorządowymi i państwowymi regionu

§ 4
Prezes Regionu:
1. Zwołuje posiedzenia Zarządu Regionu i przewodniczy im
2. Ma prawo uczestnictwa z głosem doradczym w posiedzeniach Okręgów
3. Składa sprawozdania Zarządowi Krajowemu co najmniej raz na pół roku
4. Może delegować swoje kompetencje innym członkom Zarządu Regionu

§ 5
Powołanie następuje na czas kadencji organów Partii.

§ 6
Decyzja wchodzi w życie z dniem {data_wejscia}.

§ 7
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