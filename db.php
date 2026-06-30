<?php
// db.php — Datenbankverbindung. Trage hier deine echten MySQL-Zugangsdaten ein.
// Diese Datei NICHT öffentlich zugänglich machen / nicht in ein öffentliches Git-Repo legen.

$DB_HOST = 'localhost';
$DB_NAME = 'joystream_fm';      // <- anpassen
$DB_USER = 'DJ_Lukas';      // <- anpassen
$DB_PASS = 'd290711RR.';  // <- anpassen

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['ok' => false, 'error' => 'Datenbankverbindung fehlgeschlagen']));
}
