<?php

namespace App\Document\Rezygnacje;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class RezygnacjaZFunkcji extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_REZYGNACJA_Z_FUNKCJI;
    }
    
    public function getTitle(): string
    {
        return 'Rezygnacja z funkcji';
    }
    
    public function getCategory(): string
    {
        return 'Rezygnacje';
    }
    
    public function getDescription(): string
    {
        return 'Oświadczenie o rezygnacji z pełnionej funkcji w Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'funkcja', 'powod_rezygnacji', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'rezygnujacy' => true,  // Osoba rezygnująca
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
OŚWIADCZENIE O REZYGNACJI Z FUNKCJI

Do
{adresat_rezygnacji}
Partii Politycznej Nowa Nadzieja

Ja, niżej podpisany/a:

{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

Zamieszkały/a: {adres}
Telefon: {telefon}
Email: {email}


oświadczam o swojej rezygnacji z pełnionej funkcji:

FUNKCJA: {funkcja}
POWOŁANY/A NA PODSTAWIE: {podstawa_powolania}
PEŁNIĘ FUNKCJĘ OD: {data_objecia_funkcji}

POWÓD REZYGNACJI:
{powod_rezygnacji}

SZCZEGÓŁY:
1. Data złożenia rezygnacji: {data}
2. Data skuteczności rezygnacji: {data_wejscia}
3. Okres wypowiedzenia: {okres_wypowiedzenia}
4. Tryb rezygnacji: {tryb_rezygnacji}

SPRAWY DO PRZEKAZANIA:
1. Bieżące projekty i zadania:
   {biezace_projekty}

2. Dokumenty i materiały do przekazania:
   {dokumenty_do_przekazania}

3. Dostępy i uprawnienia do przekazania/anulowania:
   {dostepy_do_przekazania}

4. Kontakty służbowe i korespondencja:
   {kontakty_sluzebowe}

5. Mienie partyjne do przekazania:
   {mienie_do_przekazania}

OSOBA PRZEJMUJĄCA OBOWIĄZKI:
(tymczasowo lub wskazana następca)
{osoba_przejmujaca}
Kontakt: {kontakt_przejmujacej}

ZOBOWIĄZANIA:
1. Zobowiązuję się do uporządkowanego przekazania spraw w terminie {termin_przekazania}
2. Zobowiązuję się do współpracy przy wdrożeniu następcy
3. Zobowiązuję się do zachowania poufności spraw partyjnych
4. Zobowiązuję się do zwrotu powierzonego mienia partyjnego
5. Zobowiązuję się do rozliczenia powierzonych środków

OŚWIADCZENIA:
1. Rezygnacja jest dobrowolna i przemyślana
2. Nie posiadam zobowiązań finansowych związanych z pełnioną funkcją
3. Wszystkie projekty są w stanie umożliwiającym przekazanie
4. Nie pozostają nierozliczone sprawy wymagające kontynuacji

PODZIĘKOWANIA:
{podziekowania}

Proszę o przyjęcie mojej rezygnacji oraz podjęcie niezbędnych działań 
organizacyjnych związanych z zakończeniem pełnienia przeze mnie funkcji.

Data: {data}

Podpis rezygnującego
_________________________
{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

---
POTWIERDZENIE PRZYJĘCIA REZYGNACJI:

Data otrzymania: _______________
Podpis odbierającego: _______________
Stanowisko: _______________

Uwagi dotyczące przekazania spraw:
_________________________________
_________________________________

AKCEPTACJA REZYGNACJI:

Data akceptacji: _______________
Podpis akceptującego: _______________
Stanowisko: _______________
EOT;
    }
}