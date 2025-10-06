<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborSkarbnikaOkreguWalne extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_SKARBNIKA_OKREGU_WALNE;
    }

    public function getTitle(): string
    {
        return 'Wybór Skarbnika Okręgu przez Walne Zgromadzenie';
    }

    public function getCategory(): string
    {
        return 'Zebrania';
    }

    public function getDescription(): string
    {
        return 'Dokument wyboru Skarbnika Okręgu przez Walne Zgromadzenie Członków';
    }

    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
    }

    public function getSignersConfig(): array
    {
        return [
            'prowadzacy' => true,   // Przewodniczący Walnego Zgromadzenia
            'protokolant' => true,  // Sekretarz Walnego Zgromadzenia
            'obserwator' => true,   // Obserwator zebrania
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/zebrania/wybor_skarbnika_okregu_walne.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Skarbnika Okręgu {okreg}

Na podstawie § 20 Statutu Partii Politycznej Nowa Nadzieja,
Zebranie Członków Okręgu {okreg} zebrane w dniu {data}
w {miejsce_zebrania}, po przeprowadzeniu demokratycznego głosowania
postanawia:

§ 1
Wybrać na stanowisko Skarbnika Okręgu {okreg} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

§ 2
Zakres uprawnień i obowiązków Skarbnika Okręgu:
1. Prowadzi księgowość i gospodarkę finansową okręgu
2. Sporządza budżet okręgu i plany finansowe
3. Przygotowuje sprawozdania finansowe
4. Nadzoruje wpłaty składek członkowskich
5. Odpowiada za prawidłowe gospodarowanie środkami finansowymi
6. Składa sprawozdania finansowe Zarządowi i Walnemu Zgromadzeniu
7. Współpracuje ze Skarbnikiem Krajowym

§ 3
Kadencja Skarbnika Okręgu:
1. Trwa 4 lata, licząc od dnia wejścia w życie niniejszej uchwały
2. Kończy się z dniem wyboru następcy lub wcześniejszego odwołania

§ 4
Uchwała wchodzi w życie z dniem {data_wejscia}.

Przewodniczący Walnego Zgromadzenia    Protokolant Walnego Zgromadzenia
_________________________               _________________________
{przewodniczacy_walnego}                {protokolant}
EOT;
    }
}
