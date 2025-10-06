# API Kandydatów - Dokumentacja Techniczna

## Informacje podstawowe

**Base URL:** `https://test.system.nowa-nadzieja.org.pl/api/`  
**Metoda:** `POST`  
**Content-Type:** `application/json`  
**Kodowanie:** UTF-8  

## Autoryzacja

Wszystkie endpointy wymagają tego samego klucza autoryzacyjnego:

```json
{
  "api_key": "cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0"
}
```

**UWAGA:** Klucz API musi być przechowywany bezpiecznie i nie może być ujawniony publicznie.

---

# 1. API Kandydata na Członka Partii

## Endpoint
```
POST https://test.system.nowa-nadzieja.org.pl/api/kandydat/create
```

## Przykładowe żądania

### cURL - Kandydat partii
```bash
curl -X POST https://test.system.nowa-nadzieja.org.pl/api/kandydat/create \
  -H "Content-Type: application/json" \
  -d '{"api_key":"cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0","imie":"Jan","nazwisko":"Kowalski","pesel":"90010112345","email":"jan.kowalski@example.com","telefon":"48123456789","ulicaZamieszkania":"Marszałkowska","nrDomuZamieszkania":"123","nrLokaliZamieszkania":"45","kodPocztowyZamieszkania":"00-001","miastoZamieszkania":"Warszawa","pocztaZamieszkania":"Warszawa","regionNazwa":"mazowiecki","okregNazwa":"Warszawa","oddzialNazwa":"Wołomiński","zgodaRodo":true}'
```

## Struktura żądania JSON

```json
{
  "api_key": "cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0",
  "imie": "Jan",
  "drugieImie": "Kowalski",
  "nazwisko": "Nowak",
  "pesel": "90010112345",
  "email": "jan.nowak@example.com",
  "telefon": "48123456789",
  
  "ulicaZamieszkania": "Marszałkowska",
  "nrDomuZamieszkania": "123",
  "nrLokaliZamieszkania": "45",
  "kodPocztowyZamieszkania": "00-001",
  "miastoZamieszkania": "Warszawa",
  "pocztaZamieszkania": "Warszawa",
  
  "ulicaKorespondencyjny": "Krakowskie Przedmieście",
  "nrDomuKorespondencyjny": "26",
  "nrLokaliKorespondencyjny": "8",
  "kodPocztowyKorespondencyjny": "00-927",
  "miastoKorespondencyjne": "Warszawa",
  "pocztaKorespondencyjna": "Warszawa",
  
  "regionNazwa": "mazowiecki",
  "okregNazwa": "Warszawa",
  "oddzialNazwa": "Wołomiński",
  
  "przynaleznosc": "Opis przynależności do innych organizacji",
  "funkcjePubliczne": "Opis pełnionych funkcji publicznych",
  "historiaWyborow": "Historia kandydowania w wyborach",
  
  "zgodaRodo": true
}
```

## Opis pól

### Wymagane pola podstawowe
- `api_key` (string) - Klucz autoryzacyjny
- `imie` (string, 2-255 znaków) - Imię kandydata
- `nazwisko` (string, 2-255 znaków) - Nazwisko kandydata
- `pesel` (string, 11 cyfr) - Numer PESEL
- `email` (string, format email, max 180 znaków) - Adres email
- `telefon` (string, format: +48xxxxxxxxx lub 48xxxxxxxxx lub xxxxxxxxx) - Numer telefonu

### Wymagane pola adresu zamieszkania
- `ulicaZamieszkania` (string, max 255) - Nazwa ulicy
- `nrDomuZamieszkania` (string, max 20) - Numer domu
- `nrLokaliZamieszkania` (string, max 20) - Numer lokalu
- `kodPocztowyZamieszkania` (string, format XX-XXX) - Kod pocztowy
- `miastoZamieszkania` (string, max 255) - Nazwa miasta
- `pocztaZamieszkania` (string, max 255) - Poczta

### Wymagane pola struktury organizacyjnej
- `regionNazwa` (string, max 255) - Nazwa regionu
- `okregNazwa` (string, max 255) - Nazwa okręgu

### Wymagane zgody
- `zgodaRodo` (boolean) - Zgoda na przetwarzanie danych osobowych (musi być `true`)

### Opcjonalne pola
- `drugieImie` (string, max 255) - Drugie imię
- `ulicaKorespondencyjny` (string, max 255) - Ulica adresu korespondencyjnego
- `nrDomuKorespondencyjny` (string, max 20) - Numer domu korespondencyjnego
- `nrLokaliKorespondencyjny` (string, max 20) - Numer lokalu korespondencyjnego
- `kodPocztowyKorespondencyjny` (string, format XX-XXX) - Kod pocztowy korespondencyjny
- `miastoKorespondencyjne` (string, max 255) - Miasto korespondencyjne
- `pocztaKorespondencyjna` (string, max 255) - Poczta korespondencyjna
- `oddzialNazwa` (string, max 255) - Nazwa oddziału
- `przynaleznosc` (string, max 2000) - Przynależność do innych organizacji
- `funkcjePubliczne` (string, max 2000) - Pełnione funkcje publiczne
- `historiaWyborow` (string, max 2000) - Historia kandydowania w wyborach

## Przykład odpowiedzi sukces (201)

```json
{
  "success": true,
  "message": "Kandydat utworzony pomyślnie",
  "data": {
    "id": 6235,
    "email": "jan.nowak@example.com",
    "full_name": "Jan Nowak",
    "temporary_password": "a1b2c3d4e5f6g7h8",
    "status": "aktywny",
    "type": "kandydat",
    "region": "mazowiecki",
    "oddzial": "Wołomiński",
    "okregNazwa": "Warszawa",
    "created_at": "2025-09-09 15:30:25"
  }
}
```

## Przykład odpowiedzi błąd walidacji (400)

```json
{
  "error": "Validation failed",
  "errors": [
    {
      "field": "email",
      "message": "Nieprawidłowy format email"
    },
    {
      "field": "pesel",
      "message": "PESEL musi składać się z 11 cyfr"
    }
  ]
}
```

## Przykład odpowiedzi konflikt (409)

```json
{
  "error": "User with this email already exists",
  "message": "Użytkownik z tym adresem email już istnieje"
}
```

---

# 2. API Kandydata Młodzieżówki

## Endpoint
```
POST https://test.system.nowa-nadzieja.org.pl/api/mlodziezowka/create
```

## Przykładowe żądania

### cURL - Kandydat młodzieżówki
```bash
curl -X POST https://test.system.nowa-nadzieja.org.pl/api/mlodziezowka/create \
  -H "Content-Type: application/json" \
  -d '{"api_key":"cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0","imie":"Anna","nazwisko":"Nowak","pesel":"02230154321","email":"anna.nowak@example.com","telefon":"48987654321","ulicaZamieszkania":"Nowy Świat","nrDomuZamieszkania":"15","nrLokaliZamieszkania":"2","kodPocztowyZamieszkania":"00-373","miastoZamieszkania":"Warszawa","regionNazwa":"mazowiecki","okregNazwa":"Warszawa"}'
```

## Struktura żądania JSON

```json
{
  "api_key": "cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0",
  "imie": "Anna",
  "drugieImie": "Maria",
  "nazwisko": "Kowalska",
  "pesel": "02230154321",
  "email": "anna.kowalska@example.com",
  "telefon": "48987654321",
  
  "ulicaZamieszkania": "Nowy Świat",
  "nrDomuZamieszkania": "15",
  "nrLokaliZamieszkania": "2",
  "kodPocztowyZamieszkania": "00-373",
  "miastoZamieszkania": "Warszawa",
  
  "regionNazwa": "mazowiecki",
  "okregNazwa": "Warszawa"
}
```

## Opis pól młodzieżówki

### Wymagane pola podstawowe
- `api_key` (string) - Klucz autoryzacyjny
- `imie` (string, 2-255 znaków) - Imię kandydata
- `nazwisko` (string, 2-255 znaków) - Nazwisko kandydata
- `pesel` (string, 11 cyfr) - Numer PESEL
- `email` (string, format email, max 180 znaków) - Adres email
- `telefon` (string, format: +48xxxxxxxxx lub 48xxxxxxxxx lub xxxxxxxxx) - Numer telefonu

### Wymagane pola adresu zamieszkania
- `ulicaZamieszkania` (string, max 255) - Nazwa ulicy
- `nrDomuZamieszkania` (string, max 20) - Numer domu
- `kodPocztowyZamieszkania` (string, format XX-XXX) - Kod pocztowy
- `miastoZamieszkania` (string, max 255) - Nazwa miasta

### Wymagane pola struktury organizacyjnej
- `regionNazwa` (string, max 255) - Nazwa regionu
- `okregNazwa` (string, max 255) - Nazwa okręgu

### Opcjonalne pola
- `drugieImie` (string, max 255) - Drugie imię
- `nrLokaliZamieszkania` (string, max 20) - Numer lokalu

## Przykład odpowiedzi sukces młodzieżówka (201)

```json
{
  "success": true,
  "message": "Kandydat młodzieżówki utworzony pomyślnie",
  "data": {
    "id": 145,
    "email": "anna.kowalska@example.com",
    "full_name": "Anna Maria Kowalska",
    "pesel": "02230154321",
    "adres_zamieszkania": "Nowy Świat 15/2, Warszawa 00-373",
    "telefon": "48987654321",
    "region": "mazowiecki",
    "okreg": "Warszawa",
    "data_zlozenia_deklaracji": "2025-09-09",
    "created_at": "2025-09-09 15:35:42"
  }
}
```

---

# Wspólne informacje

## Kody odpowiedzi HTTP

- `201 Created` - Kandydat utworzony pomyślnie
- `400 Bad Request` - Błędne dane wejściowe lub błąd walidacji
- `401 Unauthorized` - Nieprawidłowy lub brakujący klucz API
- `409 Conflict` - Kandydat z podanym emailem lub PESEL już istnieje
- `500 Internal Server Error` - Błąd serwera

## Obsługa błędów

### JavaScript
```javascript
const createKandydat = async (kandydatData) => {
  try {
    const response = await fetch('https://test.system.nowa-nadzieja.org.pl/api/kandydat/create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        api_key: 'cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0',
        ...kandydatData
      })
    });

    if (!response.ok) {
      const errorData = await response.json();
      if (response.status === 400 && errorData.errors) {
        console.error('Błędy walidacji:', errorData.errors);
      } else if (response.status === 409) {
        console.error('Konflikt:', errorData.message);
      } else {
        console.error('Błąd HTTP:', response.status);
      }
      return;
    }

    const result = await response.json();
    console.log('Kandydat utworzony:', result.data);
    return result;
  } catch (error) {
    console.error('Błąd sieci:', error);
  }
};
```

### PHP
```php
function createKandydat($kandydatData) {
    $data = array_merge([
        'api_key' => 'cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0'
    ], $kandydatData);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://test.system.nowa-nadzieja.org.pl/api/kandydat/create');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode === 201) {
        return $result['data'];
    } else {
        throw new Exception($result['message'] ?? 'Błąd API');
    }
}
```

## Dostępne regiony i okręgi

Aktualna lista dostępna przez API struktury. Najczęściej używane:

### Regiony
- `pomorski`
- `mazowiecki` 
- `kujawsko-pomorski`
- `podlasko-warmińsko-mazurski`

### Przykładowe okręgi
- `Warszawa` (mazowiecki)
- `Gdańsk` (pomorski)
- `Toruń` (kujawsko-pomorski)
- `Białystok` (podlasko-warmińsko-mazurski)
