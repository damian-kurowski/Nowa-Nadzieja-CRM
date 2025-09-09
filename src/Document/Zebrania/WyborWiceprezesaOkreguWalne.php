<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborWiceprezesaOkreguWalne extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_WICEPREZESA_OKREGU_WALNE;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Wiceprezesa Okręgu przez Walne Zgromadzenie';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyboru Wiceprezesa Okręgu przez Walne Zgromadzenie Członków';
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
w sprawie wyboru Wiceprezesa Okręgu

Na podstawie § 17 Statutu Partii Politycznej Nowa Nadzieja, 
Zebranie Członków Okręgu {okreg} w wyniku przeprowadzonego 
demokratycznego głosowania postanawia:

§ 1
Wybrać na stanowisko Wiceprezesa Okręgu {okreg} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Kadencja Wiceprezesa Okręgu trwa 4 lata, licząc od dnia wejścia w życie 
niniejszej uchwały.

§ 3
Wynik głosowania:
- Liczba uprawnionych do głosowania: {liczba_uprawnionych}
- Liczba oddanych głosów: {liczba_glosow}
- Głosy "za": {glosy_za}
- Głosy "przeciw": {glosy_przeciw}
- Wstrzymujące się: {glosy_wstrzymujace}
- Głosy nieważne: {glosy_niewazne}

§ 4
Do zadań Wiceprezesa Okręgu należy w szczególności:
1. Wspieranie Prezesa Okręgu w kierowaniu pracami
2. Zastępowanie Prezesa w przypadku jego nieobecności
3. Koordynowanie wyznaczonych obszarów działalności
4. Realizacja zadań zleconych przez Prezesa Okręgu

§ 5
W przypadku niemożności pełnienia funkcji przez Prezesa Okręgu, 
Wiceprezes przejmuje jego obowiązki.

§ 6
Wiceprezes Okręgu może być odwołany przez Walne Zgromadzenie Członków Okręgu.

§ 7
Uchwała wchodzi w życie z dniem {data_wejscia}.

Przewodniczący Walnego Zgromadzenia    Sekretarz Walnego Zgromadzenia
_________________________               _________________________
{przewodniczacy_walnego}                {sekretarz_walnego}
EOT;
    }
}