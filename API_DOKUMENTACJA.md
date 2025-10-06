# API Struktury Organizacyjnej - Dokumentacja Techniczna

## Informacje podstawowe

**URL Endpoint:** `https://test.system.nowa-nadzieja.org.pl/api/struktura`  
**Metoda:** `POST`  
**Content-Type:** `application/json`  
**Kodowanie:** UTF-8  

## Autoryzacja

API wymaga klucza autoryzacyjnego przesłanego w ciele żądania:

```json
{
  "api_key": "cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0"
}
```

**UWAGA:** Klucz API musi być przechowywany bezpiecznie i nie może być ujawniony publicznie.

## Przykład żądania

### cURL
```bash
curl -X POST "https://test.system.nowa-nadzieja.org.pl/api/struktura" \
  -H "Content-Type: application/json" \
  -d '{"api_key":"cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0"}'
```

### JavaScript (fetch)
```javascript
const response = await fetch('https://test.system.nowa-nadzieja.org.pl/api/struktura', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    api_key: 'cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0'
  })
});

const data = await response.json();
```

### PHP
```php
$data = json_encode(['api_key' => 'cb829c65c62077123ed42e98519ca06a28b3b8b6340820e31c59c8c330217de0']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://test.system.nowa-nadzieja.org.pl/api/struktura');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
```

## Struktura odpowiedzi

API zwraca hierarchiczną strukturę organizacyjną w formacie JSON:

```json
{
  "regions": [
    {
      "id": 1,
      "name": "pomorski",
      "roles": [
        {
          "role": "Prezes Regionu",
          "person": {
            "first_name": "Adam",
            "last_name": "Zychowicz",
            "phone": "48605126918",
            "email": "adam.zychowicz@wolnosc.pl",
            "photo_url": null
          }
        }
      ],
      "okregi": [
        {
          "id": 25,
          "name": "Gdańsk",
          "roles": [
            {
              "role": "Prezes Okręgu",
              "person": {
                "first_name": "Natalia",
                "last_name": "Kołc",
                "phone": "48727531648",
                "email": "natalia.grazyna.kolc@wolnosc.pl",
                "photo_url": null
              }
            },
            {
              "role": "Wiceprezes Okręgu",
              "persons": [
                {
                  "first_name": "Agnieszka",
                  "last_name": "Grzegorzewska",
                  "phone": "48607285786",
                  "email": "agnieszka.grzegorzewska@wolnosc.pl",
                  "photo_url": null
                }
              ]
            }
          ],
          "oddzialy": [
            {
              "id": 100,
              "name": "Pruszcz Gdański",
              "roles": [
                {
                  "role": "Przewodniczący Oddziału",
                  "person": {
                    "first_name": "Kamil",
                    "last_name": "Kraiński",
                    "phone": "48573917808",
                    "email": "kamilkrainski@hotmail.com",
                    "photo_url": null
                  }
                },
                {
                  "role": "Zastępca Przewodniczącego Oddziału",
                  "persons": [
                    {
                      "first_name": "Michał",
                      "last_name": "Bieniasz-Krzywiec",
                      "phone": "48518325814",
                      "email": "biuro@amw-work.pl",
                      "photo_url": null
                    }
                  ]
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

## Opis pól

### Region
- `id` (integer) - Unikalny identyfikator regionu
- `name` (string) - Nazwa regionu
- `roles` (array) - Tablica ról regionalnych
- `okregi` (array) - Tablica okręgów w regionie

### Okręg
- `id` (integer) - Unikalny identyfikator okręgu
- `name` (string) - Nazwa okręgu
- `roles` (array) - Tablica ról okręgowych
- `oddzialy` (array) - Tablica oddziałów w okręgu

### Oddział
- `id` (integer) - Unikalny identyfikator oddziału
- `name` (string) - Nazwa oddziału
- `roles` (array) - Tablica ról oddziałowych

### Rola
- `role` (string) - Nazwa roli
- `person` (object|null) - Osoba pełniąca rolę (dla ról jednoosobowych)
- `persons` (array) - Tablica osób pełniących rolę (dla ról wieloosobowych)

### Osoba
- `first_name` (string) - Imię
- `last_name` (string) - Nazwisko
- `phone` (string|null) - Numer telefonu
- `email` (string) - Adres email
- `photo_url` (string|null) - URL do zdjęcia (względny, np. "/uploads/photos/imie-nazwisko.webp")

## Dostępne role

### Role regionalne (jednoosobowe)
- `Prezes Regionu`

### Role okręgowe
- `Prezes Okręgu` (jednoosobowa)
- `Wiceprezes Okręgu` (wieloosobowa)
- `Sekretarz Okręgu` (jednoosobowa)
- `Skarbnik Okręgu` (jednoosobowa)

### Role oddziałowe
- `Przewodniczący Oddziału` (jednoosobowa)
- `Zastępca Przewodniczącego Oddziału` (wieloosobowa)
- `Sekretarz Oddziału` (jednoosobowa)

## Obsługa zdjęć

- Zdjęcia są dostępne pod adresem: `https://test.system.nowa-nadzieja.org.pl{photo_url}`
- Przykład pełnego URL: `https://test.system.nowa-nadzieja.org.pl/uploads/photos/Dawid-Ratajczak.webp`
- Format zdjęć: WebP
- Jeśli `photo_url` jest `null`, oznacza to brak zdjęcia

## Kody odpowiedzi HTTP

- `200 OK` - Żądanie wykonane pomyślnie
- `401 Unauthorized` - Nieprawidłowy lub brakujący klucz API
- `405 Method Not Allowed` - Nieprawidłowa metoda HTTP (tylko POST jest dozwolone)
- `500 Internal Server Error` - Błąd serwera

## Przykład obsługi błędów

```javascript
const response = await fetch('https://test.system.nowa-nadzieja.org.pl/api/struktura', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    api_key: 'your-api-key'
  })
});

if (!response.ok) {
  if (response.status === 401) {
    console.error('Błąd autoryzacji: Nieprawidłowy klucz API');
  } else {
    console.error('Błąd HTTP:', response.status);
  }
  return;
}

const data = await response.json();
```

## Wydajność i limity

- API nie ma limitów liczby żądań
- Średni czas odpowiedzi: ~100-300ms
- Rozmiar odpowiedzi: ~50-150KB (w zależności od liczby danych)
- API loguje wszystkie żądania w celach bezpieczeństwa i monitorowania

## Bezpieczeństwo

- Zawsze używaj HTTPS
- Nie ujawniaj klucza API w kodzie frontend/JavaScript
- Klucz API powinien być przechowywany w zmiennych środowiskowych
- API loguje nieautoryzowane próby dostępu

## Uwagi implementacyjne

1. **Cache:** Dane organizacyjne zmieniają się rzadko - zaleca się cache'owanie odpowiedzi na 15-30 minut
2. **Kodowanie:** Wszystkie polskie znaki są prawidłowo kodowane w UTF-8
3. **Null values:** Sprawdzaj czy pola nie są null przed wyświetleniem
4. **Role wieloosobowe:** Zwracaj uwagę na różnicę między `person` (obiekt) a `persons` (tablica)

## Przykład parsowania w JavaScript

```javascript
function displayStructure(data) {
  data.regions.forEach(region => {
    console.log(`Region: ${region.name}`);
    
    region.roles.forEach(role => {
      if (role.person) {
        console.log(`  ${role.role}: ${role.person.first_name} ${role.person.last_name}`);
      }
    });
    
    region.okregi.forEach(okreg => {
      console.log(`  Okręg: ${okreg.name}`);
      
      okreg.roles.forEach(role => {
        if (role.person) {
          console.log(`    ${role.role}: ${role.person.first_name} ${role.person.last_name}`);
        } else if (role.persons && role.persons.length > 0) {
          console.log(`    ${role.role}:`);
          role.persons.forEach(person => {
            console.log(`      - ${person.first_name} ${person.last_name}`);
          });
        }
      });
      
      okreg.oddzialy.forEach(oddzial => {
        console.log(`    Oddział: ${oddzial.name}`);
        // Similar logic for oddzial roles...
      });
    });
  });
}
```

## Kontakt

W przypadku problemów z API lub pytań technicznych, skontaktuj się z administratorem systemu.

**Ostatnia aktualizacja:** 9 września 2025