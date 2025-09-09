<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborPrezesaOkreguWalne extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_PREZESA_OKREGU_WALNE;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Prezesa Okręgu przez Walne Zgromadzenie';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyboru Prezesa Okręgu przez Walne Zgromadzenie Członków';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'prowadzacy' => true,  // Przewodniczący Walnego Zgromadzenia
            'protokolant' => true,  // Sekretarz Walnego Zgromadzenia
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Prezesa Okręgu {okreg}

Na podstawie § 17 Statutu Partii Politycznej Nowa Nadzieja, 
Zebranie Członków Okręgu {okreg} zebrane w dniu {data} 
w {miejsce_zebrania}, po przeprowadzeniu demokratycznego głosowania tajnego 
postanawia:

§ 1
Wybrać na stanowisko Prezesa Okręgu {okreg} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



Zawód: {zawod}

Dotychczasowe funkcje w Partii: {dotychczasowe_funkcje}

§ 2
Podstawą wyboru kandydata są:
1. Wysokie kwalifikacje osobiste i zawodowe
2. Doświadczenie w działalności społecznej i politycznej
3. Znajomość problemów lokalnych i regionalnych
4. Umiejętności przywódcze i organizacyjne
5. Akceptacja społeczna w środowisku partyjnym
6. Zobowiązanie do realizacji programu Partii

§ 3
Przebieg głosowania:
- Data i miejsce: {data} w {miejsce_zebrania}
- Liczba członków uprawnionych do głosowania: {liczba_uprawnionych}
- Liczba członków obecnych na zebraniu: {liczba_obecnych}
- Liczba oddanych kart do głosowania: {liczba_kart}
- Liczba głosów ważnych: {glosy_wazne}
- Liczba głosów nieważnych: {glosy_niewazne}

§ 4
Wynik głosowania na kandydata {imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}:
- Głosy "ZA": {glosy_za} ({procent_za}%)
- Głosy "PRZECIW": {glosy_przeciw} ({procent_przeciw}%)
- Wstrzymujące się: {glosy_wstrzymujace} ({procent_wstrzymujace}%)

Kandydat został wybrany większością {typ_wiekszosci}.

§ 5
Kadencja Prezesa Okręgu:
1. Trwa 4 lata, licząc od dnia wejścia w życie niniejszej uchwały
2. Kończy się z dniem wyboru następcy lub wcześniejszego odwołania
3. Może być przedłużona w przypadku braku następcy (maksymalnie o 3 miesiące)

§ 6
Zakres uprawnień i obowiązków Prezesa Okręgu:
1. Kieruje pracami Zarządu Okręgu i zwołuje jego posiedzenia
2. Reprezentuje Okręg na zewnątrz we wszystkich sprawach statutowych
3. Realizuje program Partii na poziomie okręgu
4. Koordynuje działalność wszystkich oddziałów w okręgu
5. Współpracuje z Zarządem Krajowym i innymi okręgami
6. Nadzoruje gospodarkę finansową okręgu
7. Składa sprawozdania z działalności Walnemu Zgromadzeniu
8. Powołuje i odwołuje Sekretarza i Skarbnika Okręgu

§ 7
Prezes Okręgu składa ślubowanie następującej treści:
"Ślubuję uroczyście, że powierzone mi stanowisko Prezesa Okręgu będę pełnić 
zgodnie ze Statutem Partii Politycznej Nowa Nadzieja, w interesie członków 
i celów Partii, z poszanowaniem demokratycznych zasad i wartości."

§ 8
Kontrola i odpowiedzialność:
1. Prezes składa sprawozdania na żądanie Walnego Zgromadzenia
2. Może być odwołany przez Walne Zgromadzenie większością 2/3 głosów
3. Może być odwołany przez Zarząd Krajowy zgodnie ze Statutem
4. Odpowiada przed Walnym Zgromadzeniem za działalność okręgu

§ 9
Zobowiązuje się nowego Prezesa do:
1. Objęcia stanowiska w terminie 7 dni
2. Złożenia sprawozdania z działalności w ciągu pierwszych 100 dni
3. Opracowania planu działania na kadencję w terminie 30 dni

§ 10
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 11
Uchwała została podjęta przy kworum {kworum} osób, co stanowi {procent_kworum}% 
członków okręgu uprawnionych do głosowania.

Przewodniczący Walnego Zgromadzenia    Sekretarz Walnego Zgromadzenia
_________________________               _________________________
{przewodniczacy_walnego}                {sekretarz_walnego}
EOT;
    }
}