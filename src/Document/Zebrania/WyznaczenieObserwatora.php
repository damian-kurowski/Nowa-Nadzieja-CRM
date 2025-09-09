<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyznaczenieObserwatora extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYZNACZENIE_OBSERWATORA;
    }
    
    public function getTitle(): string
    {
        return 'Wyznaczenie Obserwatora Zebrania';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyznaczający obserwatora zebrania członków oddziału';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'oddzial', 'data_zebrania', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Sekretarz Okręgu
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
SEKRETARZA OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyznaczenia obserwatora zebrania członków oddziału {oddzial}

Na podstawie § 40 ust. 2 Statutu Partii Politycznej Nowa Nadzieja oraz w celu zapewnienia 
prawidłowego przebiegu zebrania członków oddziału, postanawiam:

§ 1
Wyznaczyć na obserwatora zebrania członków oddziału {oddzial} 
Partii Politycznej Nowa Nadzieja, które odbędzie się dnia {data_zebrania}:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Funkcja w Partii: {funkcja}

§ 2
Do zadań obserwatora należy:
1. Uczestnictwo w zebraniu z głosem doradczym
2. Weryfikacja zgodności przebiegu zebrania ze Statutem Partii
3. Czuwanie nad przestrzeganiem procedur demokratycznych
4. Udzielanie wyjaśnień w sprawach statutowych
5. Sporządzenie pisemnego sprawozdania z przebiegu zebrania

§ 3
Obserwator ma prawo do:
1. Zabierania głosu w każdym punkcie obrad
2. Zgłaszania uwag do protokołu
3. Wnioskowania o przerwę w obradach
4. Odmowy podpisania protokołu w przypadku stwierdzenia nieprawidłowości

§ 4
Obserwator zobowiązany jest do:
1. Zachowania bezstronności
2. Niewpływania na decyzje podejmowane przez zebranie
3. Złożenia sprawozdania w terminie 7 dni od zakończenia zebrania

§ 5
Decyzja wchodzi w życie z dniem {data_wejscia}.

Sekretarz Okręgu
_________________________
{sekretarz_okregu}
EOT;
    }
}