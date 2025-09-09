<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieSekretarzOkregu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_SEKRETARZ_OKREGU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Sekretarza Okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Sekretarza Okręgu przez Prezesa Okręgu';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'creator' => true,  // Prezes Okręgu
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
DECYZJA NR {numer_dokumentu}
PREZESA OKRĘGU {okreg}
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Sekretarza Okręgu

Na podstawie § 19 ust. 1 pkt 4 Statutu Partii Politycznej Nowa Nadzieja, postanawiam:

§ 1
Powołać na stanowisko Sekretarza Okręgu {okreg} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Do zadań Sekretarza Okręgu należy w szczególności:
1. Prowadzenie dokumentacji Okręgu
2. Obsługa administracyjna Zarządu Okręgu
3. Prowadzenie ewidencji członków w okręgu
4. Koordynacja pracy z oddziałami
5. Organizacja posiedzeń i zebrań
6. Sporządzanie protokołów i sprawozdań
7. Współpraca z Sekretarzem Partii

§ 3
Sekretarz Okręgu wchodzi w skład Zarządu Okręgu z głosem stanowiącym.

§ 4
Sekretarz Okręgu podlega bezpośrednio Prezesowi Okręgu.

§ 5
Powołanie następuje na czas kadencji Prezesa Okręgu.

§ 6
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Okręgu
_________________________
{prezes_okregu}
EOT;
    }
}