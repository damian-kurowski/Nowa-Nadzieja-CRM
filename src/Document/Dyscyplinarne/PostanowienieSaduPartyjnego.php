<?php

namespace App\Document\Dyscyplinarne;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class PostanowienieSaduPartyjnego extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_POSTANOWIENIE_SADU_PARTYJNEGO;
    }
    
    public function getTitle(): string
    {
        return 'Postanowienie Sądu Partyjnego';
    }
    
    public function getCategory(): string
    {
        return 'Dyscyplinarne';
    }
    
    public function getDescription(): string
    {
        return 'Postanowienie Sądu Partyjnego w sprawie wymierzenia kary dyscyplinarnej';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'rodzaj_kary', 'zarzuty', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'przewodniczacy_sadu' => true,  // Przewodniczący Sądu Partyjnego
            'sedziowie' => true,  // Sędziowie
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
POSTANOWIENIE NR {numer_dokumentu}
SĄDU PARTYJNEGO
PARTII POLITYCZNEJ NOWA NADZIEJA
z dnia {data}

Sąd Partyjny Partii Politycznej Nowa Nadzieja działający na podstawie § 42 Statutu 
w składzie:

Przewodniczący: {przewodniczacy_sadu}
Sędziowie: {sedziowie_lista}

po rozpoznaniu sprawy dyscyplinarnej przeciwko:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}


Pełnione funkcje: {pelnione_funkcje}
Adres: {adres}

postanawia:

§ 1. USTALENIA FAKTYCZNE

1. Obwiniony/a dopuścił/a się następujących czynów:
{opis_czynow}

2. Czyny zostały popełnione w okresie od {data_od} do {data_do}

3. Podstawą oskarżenia są:
{podstawa_oskarzenia}

4. W toku postępowania ustalono:
{ustalenia_faktyczne}

§ 2. PODSTAWA PRAWNA

Czyny obwinionego/ej wypełniają znamiona deliktów dyscyplinarnych określonych w:
{podstawa_prawna_deliktu}

§ 3. WYMIAR KARY

Na podstawie § 42 ust. 3 i 4 Statutu Partii wymierza się karę dyscyplinarną:

{rodzaj_kary}

UZASADNIENIE WYBORU KARY:
{uzasadnienie_kary}

§ 4. KONSEKWENCJE KARY

{konsekwencje_kary}

§ 5. ŚRODEK ZAPOBIEGAWCZY
(jeśli był zastosowany)

{srodek_zapobiegawczy}

§ 6. POSTANOWIENIA WYKONAWCZE

1. Postanowienie jest prawomocne i wykonalne od dnia {data_wejscia}
2. Zobowiązać właściwe organy Partii do wykonania postanowienia
3. Wpisać postanowienie do akt osobowych obwinionego
4. Poinformować o postanowieniu Sekretarza Partii

§ 7. POUCZENIE O ŚRODKACH ODWOŁAWCZYCH

{pouczenie_odwolawcze}

§ 8. INNE POSTANOWIENIA

{inne_postanowienia}

Postanowienie zostało podjęte jednomyślnie/większością {wynik_glosowania} głosów.

Przewodniczący Sądu Partyjnego
_________________________
{przewodniczacy_sadu}

Sędziowie:
_________________________    _________________________
{sedzia_1}                   {sedzia_2}

_________________________
{sedzia_3}

---
ZAŁĄCZNIKI:
- Akta sprawy
- Protokoły z rozpraw
- Dowody w sprawie
- {inne_zalaczniki}
EOT;
    }
}