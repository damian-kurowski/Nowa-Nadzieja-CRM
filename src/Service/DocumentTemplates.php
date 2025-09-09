<?php

namespace App\Service;

class DocumentTemplates
{
    public const TEMPLATE_PRZYJECIE_CZLONKA_PELNOMOCNIK = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
OKRĘGOWEGO PEŁNOMOCNIKA DS. PRZYJMOWANIA NOWYCH CZŁONKÓW
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie przyjęcia {imie_nazwisko} w poczet członków Partii Politycznej Nowa Nadzieja

§ 1
Na podstawie § 10 ust. 1 pkt 1 Statutu Partii Politycznej Nowa Nadzieja, działając jako Okręgowy Pełnomocnik ds. przyjmowania nowych członków Okręgu {okreg}, postanawiam przyjąć:

{imie_nazwisko}
PESEL: {pesel}
zamieszkałego/ą: {adres}

w poczet członków Partii Politycznej Nowa Nadzieja.

§ 2
Uchwała wchodzi w życie z dniem {data_wejscia}.

Okręgowy Pełnomocnik ds. przyjmowania nowych członków
_________________________
{podpisujacy}
EOT;

    public const TEMPLATE_PRZYJECIE_CZLONKA_OKREG = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZARZĄDU OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie przyjęcia {imie_nazwisko} w poczet członków Partii Politycznej Nowa Nadzieja

§ 1
Na podstawie § 10 ust. 1 pkt 2 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Okręgu {okreg} postanawia przyjąć:

{imie_nazwisko}
PESEL: {pesel}
zamieszkałego/ą: {adres}

w poczet członków Partii Politycznej Nowa Nadzieja.

§ 2
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prezes Okręgu                        Członek Zarządu Okręgu
_________________________            _________________________
{prezes_okregu}                      {czlonek_zarzadu}
EOT;

    public const TEMPLATE_PRZYJECIE_CZLONKA_KRAJOWY = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZARZĄDU KRAJOWEGO
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie przyjęcia {imie_nazwisko} w poczet członków Partii Politycznej Nowa Nadzieja

§ 1
Na podstawie § 10 ust. 1 pkt 3 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Krajowy postanawia przyjąć:

{imie_nazwisko}
PESEL: {pesel}
zamieszkałego/ą: {adres}

w poczet członków Partii Politycznej Nowa Nadzieja.

§ 2
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prezes Partii                        Członek Zarządu Krajowego
_________________________            _________________________
{prezes_partii}                      {czlonek_zarzadu}
EOT;

    public const TEMPLATE_POWOLANIE_PELNOMOCNIK_STRUKTUR = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Pełnomocnika ds. Struktur Terytorialnych

§ 1
Na podstawie § 23 ust. 3 Statutu Partii Politycznej Nowa Nadzieja, powołuję:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Pełnomocnika ds. Struktur Terytorialnych Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;

    public const TEMPLATE_ODWOLANIE_PELNOMOCNIK_STRUKTUR = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Pełnomocnika ds. Struktur Terytorialnych

§ 1
Na podstawie § 23 ust. 3 Statutu Partii Politycznej Nowa Nadzieja, odwołuję:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Pełnomocnika ds. Struktur Terytorialnych Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;

    public const TEMPLATE_POWOLANIE_SEKRETARZ_PARTII = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Sekretarza Partii

§ 1
Na podstawie § 21 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, powołuję:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Sekretarza Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;

    public const TEMPLATE_ODWOLANIE_SEKRETARZ_PARTII = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Sekretarza Partii

§ 1
Na podstawie § 21 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, odwołuję:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Sekretarza Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;

    public const TEMPLATE_POWOLANIE_SKARBNIK_PARTII = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Skarbnika Partii

§ 1
Na podstawie § 21 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, powołuję:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Skarbnika Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;

    public const TEMPLATE_ODWOLANIE_SKARBNIK_PARTII = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Skarbnika Partii

§ 1
Na podstawie § 21 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, odwołuję:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Skarbnika Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;

    public const TEMPLATE_POWOLANIE_WICEPREZES_PARTII = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Wiceprezesa Partii

§ 1
Na podstawie § 21 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, powołuję:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Wiceprezesa Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;

    public const TEMPLATE_ODWOLANIE_WICEPREZES_PARTII = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Wiceprezesa Partii

§ 1
Na podstawie § 21 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, odwołuję:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Wiceprezesa Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;

    public const TEMPLATE_ODWOLANIE_PREZES_OKREGU = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Prezesa Okręgu {okreg}

§ 1
Na podstawie § 31 ust. 4 Statutu Partii Politycznej Nowa Nadzieja, odwołuję:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Prezesa Okręgu {okreg} Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;

    public const TEMPLATE_POWOLANIE_PO_PREZES_OKREGU = <<<'EOT'
DECYZJA NR {numer_dokumentu}
ZARZĄDU KRAJOWEGO
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Pełniącego Obowiązki Prezesa Okręgu {okreg}

§ 1
Na podstawie § 31 ust. 5 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Krajowy powołuje:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Pełniącego Obowiązki Prezesa Okręgu {okreg} Partii Politycznej Nowa Nadzieja.

§ 2
Pełnienie obowiązków trwa do czasu wyboru nowego Prezesa Okręgu przez Walne Zgromadzenie Członków Okręgu.

§ 3
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii                        Sekretarz Partii
_________________________            _________________________
{prezes_partii}                      {sekretarz_partii}
EOT;

    public const TEMPLATE_ODWOLANIE_PO_PREZES_OKREGU = <<<'EOT'
DECYZJA NR {numer_dokumentu}
ZARZĄDU KRAJOWEGO
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Pełniącego Obowiązki Prezesa Okręgu {okreg}

§ 1
Na podstawie § 31 ust. 5 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Krajowy odwołuje:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Pełniącego Obowiązki Prezesa Okręgu {okreg} Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii                        Sekretarz Partii
_________________________            _________________________
{prezes_partii}                      {sekretarz_partii}
EOT;

    public const TEMPLATE_POWOLANIE_SEKRETARZ_OKREGU = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Sekretarza Okręgu

§ 1
Na podstawie § 33 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, powołuję:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Sekretarza Okręgu {okreg} Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Okręgu
_________________________
{prezes_okregu}
EOT;

    public const TEMPLATE_ODWOLANIE_SEKRETARZ_OKREGU = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Sekretarza Okręgu

§ 1
Na podstawie § 33 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, odwołuję:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Sekretarza Okręgu {okreg} Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Okręgu
_________________________
{prezes_okregu}
EOT;

    public const TEMPLATE_POWOLANIE_SKARBNIK_OKREGU = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Skarbnika Okręgu

§ 1
Na podstawie § 33 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, powołuję:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Skarbnika Okręgu {okreg} Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Okręgu
_________________________
{prezes_okregu}
EOT;

    public const TEMPLATE_ODWOLANIE_SKARBNIK_OKREGU = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Skarbnika Okręgu

§ 1
Na podstawie § 33 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, odwołuję:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Skarbnika Okręgu {okreg} Partii Politycznej Nowa Nadzieja.

§ 2
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Okręgu
_________________________
{prezes_okregu}
EOT;

    public const TEMPLATE_UTWORZENIE_ODDZIALU = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZARZĄDU OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie utworzenia Oddziału {oddzial}

§ 1
Na podstawie § 37 ust. 1 Statutu Partii Politycznej Nowa Nadzieja, Zarząd Okręgu {okreg} postanawia utworzyć Oddział {oddzial} Partii Politycznej Nowa Nadzieja.

§ 2
Oddział obejmuje swoim działaniem następujące gminy:
{gminy}

§ 3
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prezes Okręgu                        Sekretarz Okręgu
_________________________            _________________________
{prezes_okregu}                      {sekretarz_okregu}
EOT;

    public const TEMPLATE_WYZNACZENIE_OBSERWATORA = <<<'EOT'
DECYZJA NR {numer_dokumentu}
SEKRETARZA OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyznaczenia obserwatora zebrania członków oddziału {oddzial}

§ 1
Na podstawie § 40 ust. 2 Statutu Partii Politycznej Nowa Nadzieja, wyznaczam:

{imie_nazwisko}
PESEL: {pesel}

na obserwatora zebrania członków oddziału {oddzial} Partii Politycznej Nowa Nadzieja, które odbędzie się dnia {data_zebrania}.

§ 2
Obserwator ma prawo uczestniczyć w zebraniu z głosem doradczym oraz weryfikować zgodność przebiegu zebrania ze Statutem Partii.

§ 3
Decyzja wchodzi w życie z dniem {data_wejscia}.

Sekretarz Okręgu
_________________________
{sekretarz_okregu}
EOT;

    public const TEMPLATE_WYZNACZENIE_PROTOKOLANTA = <<<'EOT'
DECYZJA NR {numer_dokumentu}
PRZEWODNICZĄCEGO ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyznaczenia protokolanta zebrania

§ 1
Na podstawie § 40 ust. 3 Statutu Partii Politycznej Nowa Nadzieja, wyznaczam:

{imie_nazwisko}
PESEL: {pesel}

na protokolanta zebrania członków oddziału {oddzial} Partii Politycznej Nowa Nadzieja.

§ 2
Protokolant jest odpowiedzialny za sporządzenie protokołu z przebiegu zebrania.

§ 3
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie
_________________________
{prowadzacy}
EOT;

    public const TEMPLATE_WYZNACZENIE_PROWADZACEGO = <<<'EOT'
DECYZJA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyznaczenia prowadzącego zebranie

§ 1
Na podstawie § 40 ust. 1 Statutu Partii Politycznej Nowa Nadzieja, zebranie członków oddziału {oddzial} wyznacza:

{imie_nazwisko}
PESEL: {pesel}

na prowadzącego zebranie członków oddziału {oddzial} Partii Politycznej Nowa Nadzieja.

§ 2
Prowadzący jest odpowiedzialny za prowadzenie obrad zgodnie z porządkiem obrad i Statutem Partii.

§ 3
Decyzja wchodzi w życie z dniem {data_wejscia}.

Za zebranie:
Protokolant                          Członek zebrania
_________________________            _________________________
{protokolant}                        {czlonek_zebrania}
EOT;

    public const TEMPLATE_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Przewodniczącego Oddziału

§ 1
Na podstawie § 42 ust. 1 pkt 1 Statutu Partii Politycznej Nowa Nadzieja, zebranie członków oddziału {oddzial} powołuje:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Przewodniczącego Oddziału {oddzial} Partii Politycznej Nowa Nadzieja.

§ 2
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;

    public const TEMPLATE_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Przewodniczącego Oddziału

§ 1
Na podstawie § 42 ust. 1 pkt 1 Statutu Partii Politycznej Nowa Nadzieja, zebranie członków oddziału {oddzial} odwołuje:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Przewodniczącego Oddziału {oddzial} Partii Politycznej Nowa Nadzieja.

§ 2
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;

    public const TEMPLATE_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Zastępcy Przewodniczącego Oddziału

§ 1
Na podstawie § 42 ust. 1 pkt 2 Statutu Partii Politycznej Nowa Nadzieja, zebranie członków oddziału {oddzial} powołuje:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Zastępcy Przewodniczącego Oddziału {oddzial} Partii Politycznej Nowa Nadzieja.

§ 2
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;

    public const TEMPLATE_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Zastępcy Przewodniczącego Oddziału

§ 1
Na podstawie § 42 ust. 1 pkt 2 Statutu Partii Politycznej Nowa Nadzieja, zebranie członków oddziału {oddzial} odwołuje:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Zastępcy Przewodniczącego Oddziału {oddzial} Partii Politycznej Nowa Nadzieja.

§ 2
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;

    public const TEMPLATE_POWOLANIE_SEKRETARZA_ODDZIALU = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Sekretarza Oddziału

§ 1
Na podstawie § 42 ust. 1 pkt 3 Statutu Partii Politycznej Nowa Nadzieja, zebranie członków oddziału {oddzial} powołuje:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Sekretarza Oddziału {oddzial} Partii Politycznej Nowa Nadzieja.

§ 2
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;

    public const TEMPLATE_ODWOLANIE_SEKRETARZA_ODDZIALU = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
ZEBRANIA CZŁONKÓW ODDZIAŁU {oddzial}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie odwołania Sekretarza Oddziału

§ 1
Na podstawie § 42 ust. 1 pkt 3 Statutu Partii Politycznej Nowa Nadzieja, zebranie członków oddziału {oddzial} odwołuje:

{imie_nazwisko}
PESEL: {pesel}

ze stanowiska Sekretarza Oddziału {oddzial} Partii Politycznej Nowa Nadzieja.

§ 2
Uchwała wchodzi w życie z dniem {data_wejscia}.

Prowadzący zebranie                  Protokolant
_________________________            _________________________
{prowadzacy}                         {protokolant}
EOT;

    public const TEMPLATE_WYBOR_PREZESA_OKREGU_WALNE = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
WALNEGO ZGROMADZENIA CZŁONKÓW OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Prezesa Okręgu

§ 1
Na podstawie § 29 ust. 1 pkt 1 Statutu Partii Politycznej Nowa Nadzieja, Walne Zgromadzenie Członków Okręgu {okreg} wybiera:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Prezesa Okręgu {okreg} Partii Politycznej Nowa Nadzieja.

§ 2
Kadencja Prezesa Okręgu trwa 4 lata.

§ 3
Uchwała wchodzi w życie z dniem {data_wejscia}.

Przewodniczący Walnego Zgromadzenia    Sekretarz Walnego Zgromadzenia
_________________________               _________________________
{przewodniczacy_walnego}                {sekretarz_walnego}
EOT;

    public const TEMPLATE_WYBOR_WICEPREZESA_OKREGU_WALNE = <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
WALNEGO ZGROMADZENIA CZŁONKÓW OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Wiceprezesa Okręgu

§ 1
Na podstawie § 29 ust. 1 pkt 2 Statutu Partii Politycznej Nowa Nadzieja, Walne Zgromadzenie Członków Okręgu {okreg} wybiera:

{imie_nazwisko}
PESEL: {pesel}

na stanowisko Wiceprezesa Okręgu {okreg} Partii Politycznej Nowa Nadzieja.

§ 2
Kadencja Wiceprezesa Okręgu trwa 4 lata.

§ 3
Uchwała wchodzi w życie z dniem {data_wejscia}.

Przewodniczący Walnego Zgromadzenia    Sekretarz Walnego Zgromadzenia
_________________________               _________________________
{przewodniczacy_walnego}                {sekretarz_walnego}
EOT;

    /**
     * Pobiera szablon dla danego typu dokumentu
     */
    public static function getTemplate(string $type): ?string
    {
        return match ($type) {
            Dokument::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK => self::TEMPLATE_PRZYJECIE_CZLONKA_PELNOMOCNIK,
            Dokument::TYP_PRZYJECIE_CZLONKA_OKREG => self::TEMPLATE_PRZYJECIE_CZLONKA_OKREG,
            Dokument::TYP_PRZYJECIE_CZLONKA_KRAJOWY => self::TEMPLATE_PRZYJECIE_CZLONKA_KRAJOWY,
            Dokument::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR => self::TEMPLATE_POWOLANIE_PELNOMOCNIK_STRUKTUR,
            Dokument::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR => self::TEMPLATE_ODWOLANIE_PELNOMOCNIK_STRUKTUR,
            Dokument::TYP_POWOLANIE_SEKRETARZ_PARTII => self::TEMPLATE_POWOLANIE_SEKRETARZ_PARTII,
            Dokument::TYP_ODWOLANIE_SEKRETARZ_PARTII => self::TEMPLATE_ODWOLANIE_SEKRETARZ_PARTII,
            Dokument::TYP_POWOLANIE_SKARBNIK_PARTII => self::TEMPLATE_POWOLANIE_SKARBNIK_PARTII,
            Dokument::TYP_ODWOLANIE_SKARBNIK_PARTII => self::TEMPLATE_ODWOLANIE_SKARBNIK_PARTII,
            Dokument::TYP_POWOLANIE_WICEPREZES_PARTII => self::TEMPLATE_POWOLANIE_WICEPREZES_PARTII,
            Dokument::TYP_ODWOLANIE_WICEPREZES_PARTII => self::TEMPLATE_ODWOLANIE_WICEPREZES_PARTII,
            Dokument::TYP_ODWOLANIE_PREZES_OKREGU => self::TEMPLATE_ODWOLANIE_PREZES_OKREGU,
            Dokument::TYP_POWOLANIE_PO_PREZES_OKREGU => self::TEMPLATE_POWOLANIE_PO_PREZES_OKREGU,
            Dokument::TYP_ODWOLANIE_PO_PREZES_OKREGU => self::TEMPLATE_ODWOLANIE_PO_PREZES_OKREGU,
            Dokument::TYP_POWOLANIE_SEKRETARZ_OKREGU => self::TEMPLATE_POWOLANIE_SEKRETARZ_OKREGU,
            Dokument::TYP_ODWOLANIE_SEKRETARZ_OKREGU => self::TEMPLATE_ODWOLANIE_SEKRETARZ_OKREGU,
            Dokument::TYP_POWOLANIE_SKARBNIK_OKREGU => self::TEMPLATE_POWOLANIE_SKARBNIK_OKREGU,
            Dokument::TYP_ODWOLANIE_SKARBNIK_OKREGU => self::TEMPLATE_ODWOLANIE_SKARBNIK_OKREGU,
            Dokument::TYP_UTWORZENIE_ODDZIALU => self::TEMPLATE_UTWORZENIE_ODDZIALU,
            Dokument::TYP_WYZNACZENIE_OBSERWATORA => self::TEMPLATE_WYZNACZENIE_OBSERWATORA,
            Dokument::TYP_WYZNACZENIE_PROTOKOLANTA => self::TEMPLATE_WYZNACZENIE_PROTOKOLANTA,
            Dokument::TYP_WYZNACZENIE_PROWADZACEGO => self::TEMPLATE_WYZNACZENIE_PROWADZACEGO,
            Dokument::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU => self::TEMPLATE_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU,
            Dokument::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU => self::TEMPLATE_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU,
            Dokument::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => self::TEMPLATE_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO,
            Dokument::TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => self::TEMPLATE_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO,
            Dokument::TYP_POWOLANIE_SEKRETARZA_ODDZIALU => self::TEMPLATE_POWOLANIE_SEKRETARZA_ODDZIALU,
            Dokument::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU => self::TEMPLATE_ODWOLANIE_SEKRETARZA_ODDZIALU,
            Dokument::TYP_WYBOR_PREZESA_OKREGU_WALNE => self::TEMPLATE_WYBOR_PREZESA_OKREGU_WALNE,
            Dokument::TYP_WYBOR_WICEPREZESA_OKREGU_WALNE => self::TEMPLATE_WYBOR_WICEPREZESA_OKREGU_WALNE,
            default => null,
        };
    }

    /**
     * Zwraca wymagane pola dla danego typu dokumentu
     */
    public static function getRequiredFields(string $type): array
    {
        $baseFields = ['numer_dokumentu', 'data', 'data_wejscia'];
        
        return match ($type) {
            Dokument::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK => array_merge($baseFields, ['imie_nazwisko', 'pesel', 'adres', 'okreg', 'podpisujacy']),
            Dokument::TYP_PRZYJECIE_CZLONKA_OKREG => array_merge($baseFields, ['imie_nazwisko', 'pesel', 'adres', 'okreg', 'prezes_okregu', 'czlonek_zarzadu']),
            Dokument::TYP_PRZYJECIE_CZLONKA_KRAJOWY => array_merge($baseFields, ['imie_nazwisko', 'pesel', 'adres', 'prezes_partii', 'czlonek_zarzadu']),
            Dokument::TYP_UTWORZENIE_ODDZIALU => array_merge($baseFields, ['okreg', 'oddzial', 'gminy', 'prezes_okregu', 'sekretarz_okregu']),
            Dokument::TYP_WYZNACZENIE_OBSERWATORA => array_merge($baseFields, ['imie_nazwisko', 'pesel', 'okreg', 'oddzial', 'data_zebrania', 'sekretarz_okregu']),
            Dokument::TYP_WYZNACZENIE_PROTOKOLANTA => array_merge($baseFields, ['imie_nazwisko', 'pesel', 'oddzial', 'prowadzacy']),
            Dokument::TYP_WYZNACZENIE_PROWADZACEGO => array_merge($baseFields, ['imie_nazwisko', 'pesel', 'oddzial', 'protokolant', 'czlonek_zebrania']),
            default => array_merge($baseFields, ['imie_nazwisko', 'pesel']),
        };
    }

    /**
     * Wypełnia szablon danymi
     */
    public static function fillTemplate(string $template, array $data): string
    {
        $content = $template;
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }
}