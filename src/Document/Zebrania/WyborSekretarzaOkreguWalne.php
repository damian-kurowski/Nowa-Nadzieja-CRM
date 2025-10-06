<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborSekretarzaOkreguWalne extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_SEKRETARZA_OKREGU_WALNE;
    }

    public function getTitle(): string
    {
        return 'Wybór Sekretarza Okręgu przez Walne Zgromadzenie';
    }

    public function getCategory(): string
    {
        return 'Zebrania';
    }

    public function getDescription(): string
    {
        return 'Dokument wyboru Sekretarza Okręgu przez Walne Zgromadzenie Członków';
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
        return 'dokumenty/zebrania/wybor_sekretarza_okregu_walne.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Sekretarza Okręgu {okreg}

Na podstawie § 20 Statutu Partii Politycznej Nowa Nadzieja,
Zebranie Członków Okręgu {okreg} zebrane w dniu {data}
w {miejsce_zebrania}, po przeprowadzeniu demokratycznego głosowania
postanawia:

§ 1
Wybrać na stanowisko Sekretarza Okręgu {okreg} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

§ 2
Zakres uprawnień i obowiązków Sekretarza Okręgu:
1. Prowadzi dokumentację Zarządu Okręgu
2. Przygotowuje porządek obrad posiedzeń Zarządu
3. Sporządza protokoły z posiedzeń
4. Prowadzi korespondencję Zarządu Okręgu
5. Nadzoruje archiwum dokumentów okręgu
6. Wspomaga Prezesa w działaniach organizacyjnych
7. Zastępuje Prezesa w razie jego nieobecności (wraz z Wiceprezesem)

§ 3
Kadencja Sekretarza Okręgu:
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
