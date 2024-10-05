# Dokumentacija za API

## Pregled
Ova aplikacija implementira RESTful API za upravljanje korisnicima i izdavanje računa. Koristi Slim Framework i JSON Web Tokens (JWT) za autentikaciju i autorizaciju. API omogućuje registraciju korisnika, prijavu i dodavanje računa u bazu podataka.

## Tehnologije
- **PHP**: Programsko okruženje za backend.
- **Slim Framework**: Mikro framework za izradu web aplikacija.
- **JWT (JSON Web Tokens)**: Standard za autentikaciju i autorizaciju.
- **MySQL**: Sistem za upravljanje bazom podataka.
- **Composer**: Alat za upravljanje PHP zavisnostima.

## Instalacija
1. **Preuzmi kod**: Preuzmi kod u lokalni direktorij.
2. **Instaliraj zavisnosti**: Pokreni `composer install` da instaliraš potrebne biblioteke.
3. **Konfiguriraj bazu**: Provjeri i postavi parametre za konekciju na bazu podataka (`$host`, `$dbname`, `$username`, `$password`).
4. **Pokreni aplikaciju**: Pokreni lokalni server koristeći PHP CLI:
   ```bash
   php -S localhost:8000
   ```

## Endpoints

### 1. Registracija korisnika
- **URL**: `/register`
- **Metoda**: `POST`
- **Podaci**: JSON tijelo zahtjeva
    ```json
    {
        "korisnicko_ime": "novi_korisnik",
        "lozinka": "tajna_lozinka",
        "oib": "12345678901"
    }
    ```
- **Odgovor**:
  - **201 Created**: Ako je korisnik uspješno registriran.
    ```json
    {
        "status": "Korisnik uspješno registriran"
    }
    ```
  - **409 Conflict**: Ako korisničko ime već postoji.
    ```json
    {
        "error": "Korisničko ime već postoji"
    }
    ```

### 2. Prijava korisnika
- **URL**: `/login`
- **Metoda**: `POST`
- **Podaci**: JSON tijelo zahtjeva
    ```json
    {
        "korisnicko_ime": "postojeci_korisnik",
        "lozinka": "tajna_lozinka"
    }
    ```
- **Odgovor**:
  - **200 OK**: Ako su podaci ispravni.
    ```json
    {
        "token": "eyJ0eXAiOiJKV1QiLCJh..."
    }
    ```
  - **401 Unauthorized**: Ako su kredencijali neispravni.
    ```json
    {
        "error": "Invalid credentials"
    }
    ```

### 3. Izdavanje računa
- **URL**: `/racun`
- **Metoda**: `POST`
- **Podaci**: JSON tijelo zahtjeva
    ```json
    {
        "broj_racuna": "RAČUN_001",
        "ukupni_iznos": 1500.00
    }
    ```
- **Zahtevi**: Potrebno je poslati JWT token u `Authorization` header-u kao `Bearer token`.
- **Odgovor**:
  - **201 Created**: Ako je račun uspješno dodan.
    ```json
    {
        "status": "Račun uspješno dodan"
    }
    ```
  - **401 Unauthorized**: Ako token nije prisutan ili je neispravan.
    ```json
    {
        "error": "Authorization token missing" // ili "Invalid token"
    }
    ```

## Middleware za autentikaciju
Middleware `authenticate` provjerava valjanost JWT tokena u `Authorization` header-u. Ako token nije prisutan ili nije važeći, vraća se 401 status kod.

## Kreiranje JWT tokena
Funkcija `kreirajToken` generira JWT token koji se koristi za autentikaciju korisnika. Token sadrži informacije o korisniku, datum generiranja i datum isteka.

## Konekcija na bazu podataka
Konekcija na MySQL bazu podataka se uspostavlja koristeći PDO. U slučaju greške, ispisuje se poruka o grešci i izvršavanje se zaustavlja.

## Zaključak
Ova aplikacija pruža osnovne funkcionalnosti za upravljanje korisnicima i izdavanje računa putem RESTful API-ja. Može se dodatno proširiti dodavanjem novih funkcionalnosti i endpointa prema potrebama.
