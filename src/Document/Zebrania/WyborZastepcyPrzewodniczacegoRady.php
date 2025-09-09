<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborZastepcyPrzewodniczacegoRady extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_ZASTEPCA_PRZEWODNICZACY_RADY;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Zastępcy Przewodniczącego Rady Krajowej';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyborczy Zastępcy Przewodniczącego Rady Krajowej przez członków Rady';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'przewodniczacy_rady' => true,  // Przewodniczący Rady Krajowej
            'sekretarz_zebrania' => true,  // Sekretarz zebrania
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
RADY KRAJOWEJ
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Zastępcy Przewodniczącego Rady Krajowej

Na podstawie § 30 ust. 3 Statutu Partii Politycznej Nowa Nadzieja, Rada Krajowa 
w wyniku przeprowadzonego demokratycznego głosowania tajnego postanawia:

§ 1
Wybrać na stanowisko Zastępcy Przewodniczącego Rady Krajowej:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

§ 2
Kandydat został zgłoszony przez: {kto_zglosil}

§ 3
Podstawą wyboru kandydata są:
1. Doświadczenie w pracy w strukturach Partii
2. Znajomość procedur i regulaminów wewnętrznych
3. Umiejętności organizacyjne i koordynacyjne
4. Zdolność do zastępowania Przewodniczącego
5. Kompetencje w zakresie zarządzania projektami

§ 4
Wynik głosowania:
- Głosy "ZA": {glosy_za} ({procent_za}%)
- Głosy "PRZECIW": {glosy_przeciw} ({procent_przeciw}%)
- Wstrzymujące się: {glosy_wstrzymujace} ({procent_wstrzymujace}%)

§ 5
Do zadań Zastępcy Przewodniczącego Rady Krajowej należy:
1. Wspieranie Przewodniczącego w organizacji prac Rady
2. Zastępowanie Przewodniczącego podczas jego nieobecności
3. Koordynacja prac komisji i zespołów roboczych
4. Przygotowywanie projektów uchwał i stanowisk
5. Nadzór nad realizacją uchwał Rady Krajowej

§ 6
Zastępca może pełnić obowiązki Przewodniczącego z pełnymi uprawnieniami 
w przypadku jego nieobecności lub niemożności działania.

§ 7
Kadencja trwa do końca kadencji Rady Krajowej.

§ 8
Uchwała wchodzi w życie z dniem {data_wejscia}.

Przewodniczący Rady Krajowej           Sekretarz zebrania
_________________________              _________________________
{przewodniczacy_rady}                  {sekretarz_zebrania}
EOT;
    }
}