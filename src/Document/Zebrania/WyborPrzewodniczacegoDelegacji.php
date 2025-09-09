<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborPrzewodniczacegoDelegacji extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_PRZEWODNICZACY_DELEGACJI;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Przewodniczącego Delegacji Partii w PE';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyborczy Przewodniczącego Delegacji Partii w Parlamencie Europejskim';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'prowadzacy_zebranie' => true,  // Prowadzący zebranie Delegacji
            'sekretarz_delegacji' => true,  // Sekretarz Delegacji
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
UCHWAŁA NR {numer_dokumentu}
DELEGACJI PARTII W PARLAMENCIE EUROPEJSKIM
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}
w sprawie wyboru Przewodniczącego Delegacji Partii

Na podstawie § 5 ust. 1 Statutu Partii Politycznej Nowa Nadzieja, 
na wniosek Prezesa Partii, Delegacja Partii w Parlamencie Europejskim postanawia:

§ 1
Wybrać na stanowisko Przewodniczącego Delegacji Partii w Parlamencie Europejskim:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Poseł do Parlamentu Europejskiego
Okręg wyborczy: {okreg_wyborczy}


§ 2
Podstawą wyboru są:
1. Status członka Partii (zgodnie z § 5 ust. 2)
2. Doświadczenie w pracy europarlamentarnej
3. Znajomość procedur Parlamentu Europejskiego
4. Umiejętności reprezentowania interesów Polski
5. Kompetencje językowe i międzynarodowe

§ 3
Do zadań Przewodniczącego Delegacji należy:
1. Kierowanie pracami Delegacji Partii w PE
2. Reprezentowanie stanowiska Partii w sprawach europejskich
3. Koordynacja działań posłów Partii w PE
4. Współpraca z władzami krajowymi Partii
5. Promocja polskich interesów na forum europejskim

§ 4
Delegacja działa na podstawie regulaminu uzgodnionego z Zarządem Krajowym 
zgodnie z § 5 ust. 3 Statutu.

§ 5
Wybór następuje na okres kadencji Parlamentu Europejskiego.

§ 6
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 7
Uchwała została podjęta jednomyślnie.

Prowadzący zebranie Delegacji        Sekretarz Delegacji
_________________________            _________________________
{prowadzacy_zebranie}                {sekretarz_delegacji}
EOT;
    }
}