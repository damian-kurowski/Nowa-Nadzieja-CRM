<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class UchwalaSkresleniaCzlonka extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_UCHWALA_SKRESLENIA_CZLONKA;
    }
    
    public function getTitle(): string
    {
        return 'Uchwała o skreśleniu członka';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Uchwała Zarządu Okręgu o skreśleniu członka z listy członków Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'podstawa_skreslenia', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'prezes_okregu' => true,  // Prezes Okręgu
            'sekretarz_okregu' => true,  // Sekretarz Okręgu
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZARZĄDU OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie skreślenia członka z listy członków Partii

Na podstawie § 9 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, 
Zarząd Okręgu {okreg} po rozpatrzeniu sprawy postanawia:

§ 1
Skreślić z listy członków Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


Ostatnie miejsce zamieszkania: {adres}

§ 2
Podstawą skreślenia jest ustanie członkostwa na skutek:
{podstawa_skreslenia}

Zgodnie z § 9 ust. 1 Statutu:
{szczegoly_podstawy}

§ 3
Ustalenia faktyczne:
1. Data wystąpienia okoliczności: {data_okolicznosci}
2. Sposób ustalenia faktów: {sposob_ustalenia}
3. Dokumenty będące podstawą: {dokumenty_podstawa}
4. Dodatkowe okoliczności: {dodatkowe_okolicznosci}

§ 4
Konsekwencje skreślenia:
1. Utrata członkostwa w Partii z dniem {data_wejscia}
2. Utrata wszystkich pełnionych funkcji partyjnych
3. Utrata praw i obowiązków członkowskich
4. Obowiązek zwrotu dokumentów i materiałów partyjnych
5. Obowiązek rozliczenia powierzonego mienia partyjnego

§ 5
Poucza się, że od niniejszej uchwały przysługuje odwołanie do Zarządu Regionu 
{region} w terminie 14 dni od daty doręczenia uchwały o skreśleniu z listy członków.

§ 6
Odwołanie należy złożyć na piśmie do:
Zarządu Regionu {region}
Partii Politycznej Nowa Nadzieja
{adres_zarzadu_regionu}

§ 7
Zarząd Regionu rozpatruje odwołanie niezwłocznie, nie później niż w terminie 
3 miesięcy od daty doręczenia odwołania.

§ 8
Uchwała Zarządu Regionu w przedmiocie odwołania od skreślenia członka z listy 
członków Partii może zostać zaskarżona do Sądu Partyjnego w terminie 14 dni 
od daty jej doręczenia.

§ 9
Zobowiązać Sekretarza Okręgu do:
1. Doręczenia odpisu uchwały zainteresowanemu w terminie 14 dni
2. Poinformowania Sekretarza Partii o skreśleniu członka
3. Aktualizacji centralnego rejestru członków
4. Poinformowania Zarządu Regionu o podjętej uchwale

§ 10
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 11
Uchwała została podjęta w głosowaniu jawnym większością {wynik_glosowania} głosów.

Prezes Okręgu                     Sekretarz Okręgu
_________________________         _________________________
{prezes_okregu}                   {sekretarz_okregu}
EOT;
    }
}