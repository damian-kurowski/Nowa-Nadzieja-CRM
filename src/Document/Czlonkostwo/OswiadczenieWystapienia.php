<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class OswiadczenieWystapienia extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_OSWIADCZENIE_WYSTAPIENIA;
    }
    
    public function getTitle(): string
    {
        return 'Oświadczenie o wystąpieniu z Partii';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Dokument wystąpienia członka z Partii Politycznej Nowa Nadzieja';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'powod_wystapienia', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'czlonek' => true,  // Członek występujący
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
OŚWIADCZENIE O WYSTĄPIENIU Z PARTII

Do
Prezesa Okręgu {okreg}
Partii Politycznej Nowa Nadzieja

Ja, niżej podpisany/a:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Zamieszkały/a: {adres}
Telefon: {telefon}
Email: {email}


Na podstawie § 9 ust. 1 pkt 1 Statutu Partii Politycznej Nowa Nadzieja 
oświadczam o moim wystąpieniu z Partii Politycznej Nowa Nadzieja.

POWÓD WYSTĄPIENIA:
{powod_wystapienia}

DODATKOWE INFORMACJE:
1. Pełnione funkcje w Partii: {pelnione_funkcje}
2. Zobowiązuję się do przekazania wszystkich dokumentów i materiałów 
   partyjnych w terminie 14 dni
3. Zobowiązuję się do rozliczenia powierzonego mienia partyjnego
4. Zobowiązuję się do zachowania poufności informacji uzyskanych 
   w trakcie działalności partyjnej

OŚWIADCZENIA:
1. Oświadczam, że nie posiadam zobowiązań finansowych wobec Partii
2. Oświadczam, że zwracam wszelkie dokumenty tożsamości partyjnej
3. Oświadczam, że nie będę używał nazwy Partii w celach prywatnych
4. Oświadczam, że wystąpienie jest dobrowolne i ostateczne

SKUTKI PRAWNE WYSTĄPIENIA:
Zgodnie ze Statutem wystąpienie z Partii skutkuje z dniem złożenia 
niniejszego oświadczenia:
- Utratą członkostwa w Partii
- Utratą wszystkich pełnionych funkcji partyjnych  
- Utratą praw członkowskich
- Zwolnieniem z obowiązków członkowskich

Proszę o skreślenie mnie z listy członków Partii oraz potwierdzenie 
przyjęcia niniejszego oświadczenia.

Data wystąpienia: {data_wejscia}

Podpis
_________________________
{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

---
POTWIERDZENIE OTRZYMANIA (wypełnia Prezes Okręgu):

Data otrzymania: _______________
Podpis Prezesa Okręgu: _______________

Uwagi:
_________________________________
_________________________________
EOT;
    }
}