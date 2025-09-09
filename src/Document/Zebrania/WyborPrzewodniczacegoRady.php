<?php

namespace App\Document\Zebrania;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WyborPrzewodniczacegoRady extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WYBOR_PRZEWODNICZACY_RADY;
    }
    
    public function getTitle(): string
    {
        return 'Wybór Przewodniczącego Rady Krajowej';
    }
    
    public function getCategory(): string
    {
        return 'Zebrania';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wyborczy Przewodniczącego Rady Krajowej przez członków Rady';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'data_wejscia_w_zycie', 'drugi_podpisujacy'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'prowadzacy_zebranie' => true,  // Prowadzący zebranie
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
w sprawie wyboru Przewodniczącego Rady Krajowej

Na podstawie § 30 ust. 3 Statutu Partii Politycznej Nowa Nadzieja, Rada Krajowa 
w wyniku przeprowadzonego demokratycznego głosowania tajnego postanawia:

§ 1
Wybrać na stanowisko Przewodniczącego Rady Krajowej Partii Politycznej Nowa Nadzieja:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}




Członek Rady Krajowej od: {data_czlonkostwa_rady}

§ 2
Kandydat został zgłoszony przez: {kto_zglosil}

§ 3
Podstawą wyboru kandydata są:
1. Wysokie kwalifikacje w zakresie kierowania pracami kolegialnych organów
2. Doświadczenie w działalności politycznej i samorządowej
3. Znajomość procedur parlamentarnych i statutowych
4. Umiejętności mediacyjne i budowania konsensusu
5. Autorytet w środowisku partyjnym i społecznym

§ 4
Przebieg głosowania:
- Data i miejsce: {data} w {miejsce_zebrania}
- Liczba członków Rady Krajowej: {liczba_czlonkow_rady}
- Liczba członków obecnych: {liczba_obecnych}
- Liczba oddanych kart do głosowania: {liczba_kart}
- Liczba głosów ważnych: {glosy_wazne}
- Liczba głosów nieważnych: {glosy_niewazne}

§ 5
Wynik głosowania na kandydata {imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}:
- Głosy "ZA": {glosy_za} ({procent_za}%)
- Głosy "PRZECIW": {glosy_przeciw} ({procent_przeciw}%)
- Wstrzymujące się: {glosy_wstrzymujace} ({procent_wstrzymujace}%)

Kandydat został wybrany większością głosów.

§ 6
Do zakresu kompetencji Przewodniczącego Rady Krajowej należy:
1. Zwołuje posiedzenia Rady Krajowej nie rzadziej niż raz na 6 miesięcy
2. Przewodniczy obradom Rady Krajowej
3. Reprezentuje Radę Krajową na zewnątrz
4. Koordynuje prace komisji i zespołów roboczych Rady
5. Współpracuje z Prezesem Partii w sprawach programowych

§ 7
Kadencja Przewodniczącego trwa do końca kadencji Rady Krajowej.

§ 8
Uchwała wchodzi w życie z dniem {data_wejscia}.

§ 9
Uchwała została podjęta przy kworum {kworum} osób.

Prowadzący zebranie Rady Krajowej      Sekretarz zebrania Rady Krajowej
_________________________              _________________________
{prowadzacy_zebranie}                  {sekretarz_zebrania}
EOT;
    }
}