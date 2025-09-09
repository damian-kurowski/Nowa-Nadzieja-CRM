<?php

namespace App\Document\Czlonkostwo;

use App\Document\AbstractDocument;
use App\Entity\Dokument;

class WniosekZawieszeniaGzlonkostwa extends AbstractDocument
{
    public function getType(): string
    {
        return Dokument::TYP_WNIOSEK_ZAWIESZENIA_CZLONKOSTWA;
    }
    
    public function getTitle(): string
    {
        return 'Wniosek o zawieszenie członkostwa';
    }
    
    public function getCategory(): string
    {
        return 'Członkostwo';
    }
    
    public function getDescription(): string
    {
        return 'Wniosek członka o zawieszenie swojego członkostwa w Partii';
    }
    
    public function getRequiredFields(): array
    {
        return ['czlonek', 'przyczyna_zawieszenia', 'okres_zawieszenia', 'data_wejscia_w_zycie'];
    }
    
    public function getSignersConfig(): array
    {
        return [
            'czlonek' => true,  // Członek wnioskujący
        ];
    }
    
    public function generateContent(array $data): string
    {
        return <<<'EOT'
WNIOSEK O ZAWIESZENIE CZŁONKOSTWA

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

Pełnione funkcje: {pelnione_funkcje}

Na podstawie § 10 ust. 3 Statutu Partii Politycznej Nowa Nadzieja 
składam wniosek o zawieszenie mojego członkostwa w Partii.

SZCZEGÓŁY WNIOSKU:

1. PRZYCZYNA ZAWIESZENIA CZŁONKOSTWA:
{przyczyna_zawieszenia}

2. OKRES ZAWIESZENIA:
- Od dnia: {data_rozpoczecia_zawieszenia}
- Do dnia: {data_zakonczenia_zawieszenia}
- Łączny okres: {okres_zawieszenia} dni
  (nie krótszy niż 7 dni, nie dłuższy niż 90 dni - zgodnie z § 10 ust. 3b)

3. PEŁNIONE FUNKCJE W PARTII:
{szczegoly_funkcji}

4. OSOBA ZASTĘPUJĄCA W PEŁNIONYCH FUNKCJACH:
(dotyczy funkcji we władzach terenowych lub krajowych, z wyjątkiem Zarządu Krajowego)
{imie_nazwisko_zastepcy}
PESEL zastępcy: {pesel_zastepcy}
Funkcja zastępcy: {funkcja_zastepcy}

OŚWIADCZENIA:
1. Oświadczam, że przyczyna zawieszenia jest ważna i uzasadniona
2. Oświadczam, że w okresie zawieszenia będę nadal opłacał składki członkowskie
3. Oświadczam znajomość konsekwencji zawieszenia członkostwa
4. Zobowiązuję się do przekazania bieżących spraw osobie zastępującej
5. Zachowuję prawo do wniosku o odwieszenie członkostwa

KONSEKWENCJE ZAWIESZENIA (zgodnie z § 10 ust. 3c):
- Zawieszenie praw członkowskich
- Zawieszenie obowiązków członkowskich (z wyjątkiem składek)
- Zachowanie prawa do odwieszenia członkostwa
- Zachowanie prawa do wystąpienia z Partii

DODATKOWE INFORMACJE:
{dodatkowe_informacje}

Proszę o rozpatrzenie mojego wniosku i wydanie decyzji w sprawie 
zawieszenia członkostwa na wskazany okres.

Data złożenia wniosku: {data}

Podpis wnioskodawcy
_________________________
{imie_nazwisko}
ID: {user_id}
Numer w partii: {numer_w_partii}

---
ZAŁĄCZNIKI:
□ Dokumenty potwierdzające przyczynę zawieszenia
□ Oświadczenie osoby zastępującej (jeśli dotyczy)
□ {inne_zalaczniki}

---
POTWIERDZENIE OTRZYMANIA (wypełnia Prezes Okręgu):

Data otrzymania: _______________
Podpis Prezesa Okręgu: _______________

Informacja o przekazaniu kopii do Sekretarza Partii (jeśli dotyczy): _______________
EOT;
    }
}