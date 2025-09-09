<?php

namespace App\Document\Powolania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolanieSKarbnikPartii extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_SKARBNIK_PARTII;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Skarbnika Partii';
    }
    
    public function getCategory(): string
    {
        return 'Powołania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Skarbnika Partii przez Prezesa Partii';
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
w sprawie powołania Skarbnika Partii

Na podstawie § 21 ust. 9a Statutu Partii Politycznej Nowa Nadzieja, mając na względzie 
konieczność zapewnienia prawidłowej gospodarki finansowej Partii, postanawiam:

§ 1
Powołać na stanowisko Skarbnika Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}



§ 2
Do zadań Skarbnika Partii należy w szczególności:
1. Prowadzenie księgowości Partii zgodnie z obowiązującymi przepisami
2. Zarządzanie finansami Partii
3. Przygotowywanie projektów budżetów i sprawozdań finansowych
4. Nadzór nad poborem składek członkowskich
5. Obsługa finansowa działalności Partii
6. Współpraca z organami kontroli finansowej
7. Zabezpieczanie majątku Partii

§ 3
Skarbnik Partii wchodzi w skład Zarządu Krajowego z głosem stanowiącym.

§ 4
Skarbnik Partii podlega bezpośrednio Prezesowi Partii i składa mu regularne sprawozdania.

§ 5
Powołanie następuje na czas kadencji Prezesa Partii.

§ 6
Decyzja wchodzi w życie z dniem {data_wejscia}.

Prezes Partii
_________________________
{prezes_partii}
EOT;
    }
}