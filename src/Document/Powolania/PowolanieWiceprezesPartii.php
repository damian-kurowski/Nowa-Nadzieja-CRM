<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieWiceprezesPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_WICEPREZES_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Wiceprezesa Partii przez Kongres';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyborczy Wiceprezesa Partii przez Kongres';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'przewodniczacy_kongresu' => true,  // Przewodniczący Kongresu
            'sekretarz_kongresu' => true,  // Sekretarz Kongresu
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
KONGRESU
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Wiceprezesa Partii

Na podstawie § 23 ust. 1 pkt 5b Statutu Partii Politycznej Nowa Nadzieja, Kongres postanawia:

§ 1
Wybrać na stanowisko Wiceprezesa Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Do zadań Wiceprezesa Partii należy w szczególności:
1. Wspieranie Prezesa Partii w kierowaniu Partią
2. Zastępowanie Prezesa w przypadku jego nieobecności
3. Koordynacja pracy poszczególnych komórek organizacyjnych
4. Nadzór nad realizacją uchwał władz Partii
5. Reprezentowanie Partii w wyznaczonym zakresie
6. Współpraca z władzami samorządowymi i państwowymi
7. Pełnienie innych zadań zleconych przez Prezesa Partii

§ 3
Wiceprezes Partii wchodzi w skład Zarządu Krajowego z głosem stanowiącym.

§ 4
W przypadku niemożności pełnienia funkcji przez Prezesa Partii, Wiceprezes przejmuje 
jego obowiązki do czasu wyboru nowego Prezesa.

§ 5
Powołanie następuje na czas kadencji Prezesa Partii.

§ 6
Decyzja wchodzi w życie z dniem {data_wejscia}.

Przewodniczący Kongresu                 Sekretarz Kongresu
_________________________               _________________________
{przewodniczacy_kongresu}               {sekretarz_kongresu}
EOT;
    }
}