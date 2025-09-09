<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolaniePelnomocnikStruktur extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Pełnomocnika ds. Struktur';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Pełnomocnika ds. Struktur przez Prezesa Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
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
w sprawie powołania Pełnomocnika ds. Struktur Terytorialnych

Na podstawie § 23 ust. 3 Statutu Partii Politycznej Nowa Nadzieja oraz w celu zapewnienia 
sprawnego rozwoju struktur terenowych Partii, niniejszym postanawiam:

§ 1
Powołać na stanowisko Pełnomocnika ds. Struktur Terytorialnych Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Do zadań Pełnomocnika ds. Struktur należy:
1. Koordynacja tworzenia nowych struktur terenowych
2. Nadzór nad przestrzeganiem procedur statutowych przy tworzeniu oddziałów
3. Wsparcie merytoryczne dla zarządów okręgów w zakresie rozwoju struktur
4. Raportowanie do Prezesa Partii o stanie struktur terenowych
5. Współpraca z Sekretarzem Partii w zakresie ewidencji członków

§ 3
Pełnomocnik ds. Struktur podlega bezpośrednio Prezesowi Partii.

§ 4
Powołanie następuje na czas nieokreślony.

§ 5
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;
    }
}