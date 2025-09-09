<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyznaczenieOsobyTymczasowej extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYZNACZENIE_OSOBY_TYMCZASOWEJ;
    }
    
    public function getTitle(): string
    {
        return 'Wyznaczenie osoby tymczasowo pełniącej obowiązki';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyznaczający osobę tymczasowo pełniącą obowiązki na wakującym stanowisku';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'wakujace_stanowisko', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Partii
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyznaczenia osoby tymczasowo pełniącej obowiązki

Na podstawie § 6 ust. 19 Statutu Partii Politycznej Nowa Nadzieja, w związku z wystąpieniem 
wakatu w {wakujace_stanowisko}, postanawiam:

§ 1
Wyznaczyć do tymczasowego pełnienia obowiązków {wakujace_stanowisko}:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


Dotychczasowa funkcja: {dotychczasowa_funkcja}

§ 2
Podstawą wyznaczenia są:
1. Kwalifikacje i doświadczenie kandydata
2. Znajomość specyfiki pełnionej funkcji
3. Gotowość do podjęcia obowiązków
4. Zaufanie i nieposzlakowana opinia w Partii

§ 3
Osoba wyznaczona do tymczasowego pełnienia obowiązków:
1. Posiada wszystkie uprawnienia określone w Statucie dla danej funkcji
2. Wykonuje wszystkie obowiązki przypisane do danego stanowiska
3. Uczestniczy w posiedzeniach odpowiednich organów z pełnym prawem głosu
4. Może podejmować wiążące decyzje w zakresie kompetencji stanowiska

§ 4
Tymczasowe pełnienie obowiązków trwa do czasu:
1. Uzupełnienia wakatu w trybie określonym w Statucie, lub
2. Ukończenia kadencji organu, w którym wystąpił wakat

§ 5
Zobowiązać osobę tymczasowo pełniącą obowiązki do:
1. Niezwłocznego przejęcia wszystkich spraw związanych ze stanowiskiem
2. Zapewnienia ciągłości działania odpowiednich struktur
3. Składania miesięcznych sprawozdań z pełnionej funkcji
4. Przekazania dokumentacji po uzupełnieniu wakatu

§ 6
Decyzja wchodzi w życie z dniem {data_wejscia}.

§ 7
Decyzję należy niezwłocznie przekazać do:
1. Zarządu Krajowego
2. Rady Krajowej (w przypadku wakatu w jej składzie)
3. Archiwum Partii

Prezes Partii
_________________________
{prezes_partii}
EOT;
    }
}