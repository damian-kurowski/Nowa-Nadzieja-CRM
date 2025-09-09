<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieSekretarzPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_SEKRETARZ_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Sekretarza Partii';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Sekretarza Partii przez Prezesa Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
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
w sprawie powołania Sekretarza Partii

Na podstawie § 21 ust. 9a Statutu Partii Politycznej Nowa Nadzieja oraz mając na względzie 
konieczność zapewnienia sprawnego funkcjonowania administracji Partii i profesjonalnej 
obsługi organizacyjnej władz Partii, postanawiam:

§ 1
Powołać na stanowisko Sekretarza Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

§ 2
Podstawą powołania są:
1. Wysokie kwalifikacje organizacyjne i administracyjne kandydata
2. Dotychczasowe zaangażowanie w działalność Partii
3. Znajomość procedur statutowych i regulaminowych
4. Umiejętność zarządzania zespołem i projektami
5. Nieposzlakowana opinia w środowisku partyjnym

§ 3
Do zakresu zadań Sekretarza Partii należy:
1. Prowadzenie dokumentacji kancelaryjnej Partii zgodnie z przepisami prawa
2. Kompleksowa obsługa administracyjna Zarządu Krajowego
3. Nadzór nad systemem obiegu dokumentów w strukturach Partii
4. Prowadzenie centralnej ewidencji członków Partii
5. Koordynacja pracy biura Partii i nadzór nad personelem administracyjnym
6. Przygotowywanie projektów uchwał, zarządzeń i innych aktów wewnętrznych
7. Sporządzanie protokołów z posiedzeń władz Partii
8. Organizacja konferencji, zjazdów i innych wydarzeń partyjnych
9. Współpraca z organami państwowymi w sprawach rejestracyjnych
10. Nadzór nad archiwum Partii i ochroną danych osobowych

§ 4
Sekretarz Partii:
1. Wchodzi w skład Zarządu Krajowego z głosem doradczym
2. Uczestniczy we wszystkich posiedzeniach władz krajowych
3. Ma prawo inicjatywy w sprawach organizacyjnych i proceduralnych
4. Przedkłada kwartalne sprawozdania z działalności

§ 5
Sekretarz Partii podlega bezpośrednio Prezesowi Partii i wykonuje jego polecenia 
w zakresie organizacji i administracji.

§ 6
Powołanie następuje na czas kadencji Prezesa Partii, z możliwością wcześniejszego 
odwołania w przypadku niewywiązywania się z obowiązków.

§ 7
Sekretarz otrzymuje stosowne umocowania do reprezentowania Partii w sprawach 
administracyjnych i organizacyjnych.

§ 8
Decyzja wchodzi w życie z dniem {data_wejscia}.

§ 9
Decyzję należy przekazać do wiadomości Zarządu Krajowego oraz opublikować 
na stronie internetowej Partii.

Prezes Partii
_________________________
{prezes_partii}
EOT;
    }
}