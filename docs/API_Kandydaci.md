# API Kandydatów - Dokumentacja

## Przegląd

API umożliwia tworzenie nowych kandydatów w systemie CRM. Wszystkie żądania wymagają autoryzacji za pomocą klucza API.

## Endpoint

### POST /api/kandydat/create

Tworzy nowego kandydata w systemie.

**URL:** `https://domena.com/api/kandydat/create`  
**Metoda:** `POST`  
**Content-Type:** `application/json; charset=utf-8`

## Autoryzacja

Wszystkie żądania wymagają klucza API przekazanego w body żądania:

```json
{
  "api_key": "twój_klucz_api"
}
```

## Parametry żądania

| Pole | Typ | Wymagane | Opis | Walidacja |
|------|-----|----------|------|-----------|
| `api_key` | string | ✅ | Klucz autoryzacji API | - |
| `imie` | string | ✅ | Imię kandydata | Min 2 znaki, max 255, tylko litery, spacje i myślniki |
| `drugieImie` | string | ❌ | Drugie imię kandydata | Max 255 znaków, tylko litery, spacje i myślniki |
| `nazwisko` | string | ✅ | Nazwisko kandydata | Min 2 znaki, max 255, tylko litery, spacje i myślniki |
| `pesel` | string | ✅ | Numer PESEL | Dokładnie 11 cyfr |
| `ulicaZamieszkania` | string | ✅ | Ulica zamieszkania | Max 255 znaków |
| `nrDomuZamieszkania` | string | ✅ | Numer domu zamieszkania | Max 20 znaków |
| `nrLokaliZamieszkania` | string | ✅ | Numer lokalu zamieszkania | Max 20 znaków |
| `kodPocztowyZamieszkania` | string | ✅ | Kod pocztowy zamieszkania | Format: XX-XXX |
| `miastoZamieszkania` | string | ✅ | Miasto zamieszkania | Max 255 znaków |
| `pocztaZamieszkania` | string | ✅ | Poczta zamieszkania | Max 255 znaków |
| `ulicaKorespondencyjny` | string | ❌ | Ulica korespondencyjna | Max 255 znaków |
| `nrDomuKorespondencyjny` | string | ❌ | Numer domu korespondencyjny | Max 20 znaków |
| `nrLokaliKorespondencyjny` | string | ❌ | Numer lokalu korespondencyjny | Max 20 znaków |
| `kodPocztowyKorespondencyjny` | string | ❌ | Kod pocztowy korespondencyjny | Format: XX-XXX |
| `miastoKorespondencyjne` | string | ❌ | Miasto korespondencyjne | Max 255 znaków |
| `pocztaKorespondencyjna` | string | ❌ | Poczta korespondencyjna | Max 255 znaków |
| `email` | string | ✅ | Adres email | Prawidłowy format email, max 180 znaków, unikalny |
| `telefon` | string | ✅ | Numer telefonu | Format polski: +48XXXXXXXXX lub XXXXXXXXX |
| `przynaleznosc` | string | ❌ | Przynależność do organizacji | Max 2000 znaków |
| `regionNazwa` | string | ✅ | Nazwa regionu | Max 255 znaków |
| `oddzialNazwa` | string | ❌ | Nazwa oddziału | Max 255 znaków |
| `okregNazwa` | string | ✅ | Nazwa okręgu | Max 255 znaków |
| `funkcjePubliczne` | string | ❌ | Pełnione funkcje publiczne | Max 2000 znaków |
| `historiaWyborow` | string | ❌ | Historia startów w wyborach | Max 2000 znaków |
| `zgodaRodo` | boolean | ✅ | Zgoda na przetwarzanie danych RODO | Musi być true |

## Przykład żądania

```bash
curl -X POST https://domena.com/api/kandydat/create \
  -H "Content-Type: application/json; charset=utf-8" \
  -d '{
    "api_key": "twój_klucz_api",
    "imie": "Michał",
    "nazwisko": "Kowalski",
    "pesel": "90011512345",
    "ulicaZamieszkania": "Królewska",
    "nrDomuZamieszkania": "15A",
    "nrLokaliZamieszkania": "6",
    "kodPocztowyZamieszkania": "00-065",
    "miastoZamieszkania": "Warszawa",
    "pocztaZamieszkania": "Warszawa",
    "ulicaKorespondencyjny": "Marszałkowska",
    "nrDomuKorespondencyjny": "100",
    "nrLokaliKorespondencyjny": "25",
    "kodPocztowyKorespondencyjny": "00-001",
    "miastoKorespondencyjne": "Warszawa",
    "pocztaKorespondencyjna": "Warszawa",
    "email": "michal.kowalski@example.com",
    "telefon": "123456789",
    "regionNazwa": "mazowiecki",
    "okregNazwa": "Warszawa",
    "przynaleznosc": "Związek Studentów",
    "funkcjePubliczne": "Radny gminy",
    "historiaWyborow": "Kandydował w wyborach samorządowych 2018",
    "zgodaRodo": true
  }'
```

## Odpowiedzi

### Sukces (201)

```json
{
  "success": true,
  "message": "Kandydat utworzony pomyślnie",
  "data": {
    "id": 6210,
    "email": "michal.kowalski@example.com",
    "full_name": "Michał Kowalski",
    "temporary_password": "a1b2c3d4e5f6g7h8",
    "status": "aktywny",
    "type": "kandydat",
    "region": "mazowiecki",
    "oddzial": "Warszawa",
    "okregNazwa": "Warszawa",
    "created_at": "2025-09-09 12:00:00"
  }
}
```

### Błąd walidacji (400)

```json
{
  "error": "Validation failed",
  "errors": [
    {
      "field": "pesel",
      "message": "PESEL jest wymagany"
    },
    {
      "field": "email",
      "message": "Nieprawidłowy format email"
    }
  ]
}
```

### Nieautoryzowany dostęp (401)

```json
{
  "error": "Unauthorized"
}
```

### Email już istnieje (409)

```json
{
  "error": "User with this email already exists",
  "message": "Użytkownik z tym adresem email już istnieje"
}
```

### Błąd serwera (500)

```json
{
  "error": "Internal server error",
  "message": "Wystąpił błąd podczas tworzenia kandydata"
}
```

## Specjalne funkcjonalności

### Łączenie adresów

System automatycznie łączy pola adresowe w jeden string:

**Adres zamieszkania:**
- Pola: `ulicaZamieszkania`, `nrDomuZamieszkania`, `nrLokaliZamieszkania`, `miastoZamieszkania`, `kodPocztowyZamieszkania`
- Format: "Królewska 15A/6, Warszawa 00-065"

**Adres korespondencyjny:**
- Pola: `ulicaKorespondencyjny`, `nrDomuKorespondencyjny`, `nrLokaliKorespondencyjny`, `miastoKorespondencyjne`, `kodPocztowyKorespondencyjny`
- Format: "Marszałkowska 100/25, Warszawa 00-001"

### Przypisywanie struktury organizacyjnej

System automatycznie przypisuje strukturę na podstawie nazw:

1. **Region** - wyszukiwany po `regionNazwa`
2. **Okręg** - wyszukiwany po `okregNazwa`
3. **Oddział** - wyszukiwany po `oddzialNazwa` (opcjonalny)

Jeśli nie podano okręgu, system automatycznie przypisze okręg na podstawie oddziału.

### Obsługa polskich znaków

API w pełni obsługuje polskie znaki w UTF-8:
- Imiona i nazwiska: "Michał", "Żółć", "Ąśćęłńóśźż"
- Adresy: "Królewska", "Kraków", "Gdańsk"
- Wszystkie pola tekstowe

## Kody błędów HTTP

| Kod | Opis |
|-----|------|
| 201 | Kandydat utworzony pomyślnie |
| 400 | Błąd walidacji danych |
| 401 | Nieautoryzowany dostęp (błędny klucz API) |
| 409 | Konflikt (email już istnieje) |
| 500 | Błąd wewnętrzny serwera |

## Uwagi techniczne

1. **Kodowanie:** Wszystkie żądania muszą być w UTF-8
2. **Content-Type:** `application/json; charset=utf-8`
3. **Hasło tymczasowe:** Generowane automatycznie i zwracane w odpowiedzi
4. **Typ użytkownika:** Automatycznie ustawiany na "kandydat"
5. **Role:** Automatycznie przypisywana rola "ROLE_KANDYDAT_PARTII"
6. **Status:** Automatycznie ustawiany na "aktywny"

## Przykłady użycia

### Kandydat z minimalnym zestawem danych

```json
{
  "api_key": "twój_klucz_api",
  "imie": "Jan",
  "nazwisko": "Nowak",
  "pesel": "90011512345",
  "ulicaZamieszkania": "Główna",
  "nrDomuZamieszkania": "1",
  "nrLokaliZamieszkania": "1",
  "kodPocztowyZamieszkania": "00-001",
  "miastoZamieszkania": "Warszawa",
  "pocztaZamieszkania": "Warszawa",
  "email": "jan.nowak@example.com",
  "telefon": "123456789",
  "regionNazwa": "mazowiecki",
  "okregNazwa": "Warszawa",
  "zgodaRodo": true
}
```

### Kandydat z pełnym zestawem danych

```json
{
  "api_key": "twój_klucz_api",
  "imie": "Anna",
  "drugieImie": "Maria",
  "nazwisko": "Kowalska",
  "pesel": "85021512345",
  "ulicaZamieszkania": "Aleje Jerozolimskie",
  "nrDomuZamieszkania": "100",
  "nrLokaliZamieszkania": "50",
  "kodPocztowyZamieszkania": "00-001",
  "miastoZamieszkania": "Warszawa",
  "pocztaZamieszkania": "Warszawa Centrum",
  "ulicaKorespondencyjny": "Nowy Świat",
  "nrDomuKorespondencyjny": "20",
  "nrLokaliKorespondencyjny": "5",
  "kodPocztowyKorespondencyjny": "00-029",
  "miastoKorespondencyjne": "Warszawa",
  "pocztaKorespondencyjna": "Warszawa Śródmieście",
  "email": "anna.kowalska@example.com",
  "telefon": "+48123456789",
  "przynaleznosc": "Związek Nauczycieli, Stowarzyszenie Młodych Demokratów",
  "regionNazwa": "mazowiecki",
  "oddzialNazwa": "Warszawa",
  "okregNazwa": "Warszawa",
  "funkcjePubliczne": "Przewodnicząca Rady Rodziców w SP nr 5",
  "historiaWyborow": "Kandydowała na radną dzielnicy w 2018 roku",
  "zgodaRodo": true
}
```