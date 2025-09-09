<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieSkarbnikOkregu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_SKARBNIK_OKREGU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Skarbnika Okręgu';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Skarbnika Okręgu przez Prezesa Okręgu';
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
w sprawie powołania Skarbnika Okręgu

Na podstawie § 19 ust. 1 pkt 4 Statutu Partii Politycznej Nowa Nadzieja, postanawiam:

§ 1
Powołać na stanowisko Skarbnika Okręgu {okreg} Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Do zadań Skarbnika Okręgu należy w szczególności:
1. Prowadzenie księgowości Okręgu
2. Zarządzanie finansami Okręgu
3. Nadzór nad poborem składek członkowskich w okręgu
4. Sporządzanie sprawozdań finansowych
5. Obsługa finansowa działalności Okręgu
6. Zabezpieczanie majątku Okręgu
7. Współpraca ze Skarbnikiem Partii

§ 3
Skarbnik Okręgu wchodzi w skład Zarządu Okręgu z głosem stanowiącym.

§ 4
Skarbnik Okręgu podlega bezpośrednio Prezesowi Okręgu i składa mu regularne sprawozdania.

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