<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Factory\AppFactory;
use Laminas\Diactoros\Response; // Dodano za korištenje odgovora

// Postavljanje aplikacije (Slim Framework)
$app = AppFactory::create();

// API ključ za JWT enkripciju (čuvaj ovaj ključ sigurnim i generiraj jak ključ)
$jwt_secret = 'tvoj_jak_i_skriveni_kljuc'; // Čuvaj u .env datoteci

// Konekcija na bazu
$host = 'localhost';
$dbname = 'fiskalizacija_hr'; // Ime tvoje baze
$username = 'root'; // Zamijeni s tvojim korisničkim imenom
$password = ''; // Zamijeni s tvojom lozinkom

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit; // Zaustavi izvršavanje ako veza ne uspije
}

// Funkcija za kreiranje JWT tokena
function kreirajToken($user) {
    global $jwt_secret;

    $payload = [
        'iss' => "tvoj_domen.com", // Izdavatelj tokena
        'iat' => time(), // Vrijeme generiranja tokena
        'exp' => time() + 3600, // Vrijeme isteka (1 sat)
        'user_id' => $user['id'],
        'oib' => $user['oib']
    ];

    return JWT::encode($payload, $jwt_secret, 'HS256');
}

// Middleware za provjeru JWT tokena
$authenticate = function ($request, $handler) {
    $authHeader = $request->getHeader('Authorization');

    if (!$authHeader || count($authHeader) === 0) {
        $response = new Response(); // Koristimo Laminas odgovor
        $response->getBody()->write(json_encode(['error' => 'Authorization token missing']));
        return $response->withStatus(401);
    }

    $jwt = str_replace('Bearer ', '', $authHeader[0]);
    global $jwt_secret;

    try {
        $decoded = JWT::decode($jwt, new Key($jwt_secret, 'HS256'));
        // Token je valjan, nastavljamo dalje
        $request = $request->withAttribute('user_id', $decoded->user_id);
        return $handler->handle($request);
    } catch (Exception $e) {
        $response = new Response(); // Koristimo Laminas odgovor
        $response->getBody()->write(json_encode(['error' => 'Invalid token']));
        return $response->withStatus(401);
    }
};

// Ruta za registraciju korisnika
$app->post('/register', function ($request, $response, $args) {
    $params = (array)$request->getParsedBody();
    $korisnicko_ime = $params['korisnicko_ime'] ?? '';
    $lozinka = $params['lozinka'] ?? '';
    $oib = $params['oib'] ?? '';

    // Hashiranje lozinke
    $hashedPassword = password_hash($lozinka, PASSWORD_BCRYPT);

    global $pdo;

    // Provjera postoji li korisnik s tim korisničkim imenom
    $stmt = $pdo->prepare("SELECT * FROM korisnici WHERE korisnicko_ime = ?");
    $stmt->execute([$korisnicko_ime]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        $response->getBody()->write(json_encode(['error' => 'Korisničko ime već postoji']));
        return $response->withStatus(409); // Conflict
    }

    // Dodavanje novog korisnika u bazu
    $stmt = $pdo->prepare("INSERT INTO korisnici (korisnicko_ime, lozinka, oib) VALUES (?, ?, ?)");
    $stmt->execute([$korisnicko_ime, $hashedPassword, $oib]);

    $response->getBody()->write(json_encode(['status' => 'Korisnik uspješno registriran']));
    return $response->withStatus(201); // Created
});

// Ruta za prijavu (login) koja generira JWT token
$app->post('/login', function ($request, $response, $args) {
    $params = (array)$request->getParsedBody();
    $korisnicko_ime = $params['korisnicko_ime'] ?? '';
    $lozinka = $params['lozinka'] ?? '';

    // Provjera korisnika u bazi (ovdje se koristi hashirana lozinka)
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM korisnici WHERE korisnicko_ime = ?");
    $stmt->execute([$korisnicko_ime]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($lozinka, $user['lozinka'])) {
        // Kreiramo JWT token
        $token = kreirajToken($user);
        $response->getBody()->write(json_encode(['token' => $token]));
        return $response->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
        return $response->withStatus(401);
    }
});

// Zaštićeni endpoint za izdavanje računa (potreban valjani JWT token)
$app->post('/racun', function ($request, $response, $args) {
    // Dohvat korisničkog ID-a iz JWT tokena
    $userId = $request->getAttribute('user_id');

    $params = (array)$request->getParsedBody();
    $broj_racuna = $params['broj_racuna'] ?? '';
    $ukupni_iznos = $params['ukupni_iznos'] ?? 0;

    // Dodavanje novog računa u bazu
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO racuni (korisnik_id, broj_racuna, ukupni_iznos) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $broj_racuna, $ukupni_iznos]);

    $response->getBody()->write(json_encode(['status' => 'Račun uspješno dodan']));
    return $response->withStatus(201);
})->add($authenticate);

// Pokretanje aplikacije
$app->run();
