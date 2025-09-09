<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolaniePOPrezesOkregu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_PO_PREZES_OKREGU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie p.o. Prezesa Okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Pełniącego Obowiązki Prezesa Okręgu';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii lub Sekretarz Partii
            'drugi_podpisujacy' => true,  // Drugi członek zarządu krajowego
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Prezesa Okręgu {okreg}

Na podstawie § 21 ust. 13 Statutu Partii Politycznej Nowa Nadzieja, w związku z koniecznością 
zapewnienia ciągłości kierownictwa Okręgu {okreg}, postanawiam:

§ 1
Powołać na stanowisko Pełniącego Obowiązki Prezesa Okręgu {okreg} 
Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Pełniący Obowiązki Prezesa Okręgu posiada wszystkie uprawnienia i obowiązki 
Prezesa Okręgu określone w Statucie Partii, w szczególności:
1. Kieruje pracami Zarządu Okręgu
2. Reprezentuje Okręg na zewnątrz
3. Koordynuje działalność oddziałów w okręgu
4. Nadzoruje realizację programu Partii na poziomie okręgu
5. Współpracuje z Zarządem Krajowym

§ 3
Pełnienie obowiązków trwa do czasu wyboru nowego Prezesa Okręgu przez 
Walne Zgromadzenie Członków Okręgu, nie dłużej jednak niż 6 miesięcy.

§ 4
Zobowiązać Pełniącego Obowiązki do:
1. Zorganizowania Walnego Zgromadzenia Członków Okręgu w terminie 3 miesięcy
2. Zapewnienia ciągłości działania struktur okręgu
3. Składania miesięcznych sprawozdań do Zarządu Krajowego

§ 5
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii                        Sekretarz Partii
_________________________            _________________________
{prezes_partii}                      {sekretarz_partii}
EOT;
    }
}