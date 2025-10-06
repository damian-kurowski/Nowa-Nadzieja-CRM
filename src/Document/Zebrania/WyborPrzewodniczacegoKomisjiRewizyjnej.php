<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborPrzewodniczacegoKomisjiRewizyjnej extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_PRZEWODNICZACY_KOMISJI_REWIZYJNEJ;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Przewodniczącego Komisji Rewizyjnej';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyborczy Przewodniczącego Komisji Rewizyjnej przez członków Komisji';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'prowadzacy_zebranie' => true,  // Prowadzący zebranie Komisji
            'sekretarz_zebrania' => true,  // Sekretarz zebrania Komisji
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/zebrania/wybor_przewodniczacego_komisji_rewizyjnej.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
KOMISJI REWIZYJNEJ
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Przewodniczącego Komisji Rewizyjnej

Na podstawie § 40 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, członkowie 
Komisji Rewizyjnej w wyniku przeprowadzonego demokratycznego głosowania postanawia:

§ 1
Wybrać na stanowisko Przewodniczącego Komisji Rewizyjnej Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



Kwalifikacje zawodowe: {kwalifikacje_zawodowe}

Członek Komisji Rewizyjnej od: {data_czlonkostwa_komisji}

§ 2
Podstawą wyboru kandydata są:
1. Wysokie kwalifikacje w zakresie księgowości, audytu lub kontroli finansowej
2. Doświadczenie w przeprowadzaniu kontroli organizacji
3. Znajomość przepisów o finansowaniu partii politycznych
4. Niezależność i obiektywizm w ocenach
5. Nieposzlakowana opinia i bezstronność

§ 3
Przebieg głosowania:
- Data i miejsce: {data} w {miejsce_zebrania}
- Liczba członków Komisji Rewizyjnej: 7
- Liczba członków obecnych: {liczba_obecnych}
- Wynik głosowania: {wynik_glosowania}

§ 4
Do kompetencji Przewodniczącego Komisji Rewizyjnej należy:
1. Kierowanie pracami Komisji Rewizyjnej
2. Reprezentowanie Komisji wobec innych organów Partii
3. Organizacja i koordynacja kontroli finansowych
4. Przygotowywanie planów kontroli i sprawozdań
5. Zwołuje posiedzenia Komisji i przewodniczy im
6. Nadzór nad realizacją zaleceń pokontrolnych

§ 5
Przewodniczący Komisji Rewizyjnej może być odwołany przez członków Komisji 
zgodnie z § 40 ust. 3 Statutu.

§ 6
Kadencja trwa do końca kadencji Komisji Rewizyjnej.

§ 7
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 8
Uchwała została podjęta jednomyślnie w głosowaniu tajnym.

Prowadzący zebranie Komisji           Sekretarz zebrania Komisji
_________________________             _________________________
{prowadzacy_zebranie}                 {sekretarz_zebrania}
EOT;
    }
}