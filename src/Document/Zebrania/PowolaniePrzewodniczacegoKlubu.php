<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PowolaniePrzewodniczacegoKlubu extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POWOLANIE_PRZEWODNICZACY_KLUBU;
    }
    
    public function getTitle(): string
    {
        return 'Powołanie Przewodniczącego Klubu Partii';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument powołujący Przewodniczącego Klubu Partii przez członków Klubu';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'prowadzacy_zebranie' => true,  // Prowadzący zebranie Klubu
            'sekretarz_klubu' => true,  // Sekretarz Klubu
        ];
    }

    public function getTemplateName(): string
    {
        return 'dokumenty/zebrania/powolanie_przewodniczacego_klubu.html.twig';
    }

    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
KLUBU PARTII
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie powołania Przewodniczącego Klubu Partii

Na podstawie § 44 ust. 4 Statutu Partii Politycznej Nowa Nadzieja, 
na wniosek {wnioskodawca}, Klub Partii postanawia:

§ 1
Powołać na stanowisko Przewodniczącego Klubu Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Poseł/Senator: {mandat_parlamentarny}
Okręg wyborczy: {okreg_wyborczy}


§ 2
Podstawą powołania są:
1. Doświadczenie parlamentarne i polityczne kandydata
2. Umiejętności przywódcze i organizacyjne
3. Znajomość procedur parlamentarnych
4. Zdolność do reprezentowania stanowiska Partii
5. Autoritet w środowisku parlamentarnym

§ 3
Do zadań Przewodniczącego Klubu należy:
1. Kierowanie pracami Klubu Partii
2. Reprezentowanie stanowiska Partii w Sejmie/Senacie
3. Koordynacja działań posłów i senatorów Partii
4. Współpraca z władzami Partii w sprawach legislacyjnych
5. Nadzór nad dyscypliną klubową

§ 4
Przewodniczący Klubu działa zgodnie z Regulaminem Klubu Partii 
oraz wytycznymi władz krajowych Partii.

§ 5
Powołanie następuje na okres kadencji parlamentu.

§ 6
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 7
Uchwała została podjęta w głosowaniu jawnym.

Prowadzący zebranie Klubu            Sekretarz Klubu
_________________________            _________________________
{prowadzacy_zebranie}                {sekretarz_klubu}
EOT;
    }
}