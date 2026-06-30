<?php
// proxy.php — liegt auf www.joystream-fm.de im selben Ordner wie index.html
// Leitet alle API-Anfragen an panel.joystream-fm.de/api.php weiter.
// Kein CORS-Problem mehr, weil index.html und proxy.php auf derselben Domain sind.

// Ziel-API auf dem Panel-Server
define('API_TARGET', 'https://panel.joystream-fm.de/api.php');

// Nur GET und POST erlauben
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST', 'OPTIONS'], true)) {
    http_response_code(405);
    exit;
}

// OPTIONS direkt beantworten (wird von manchen Browsern gesendet)
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Query-String weiterleiten (z.B. ?action=login)
$qs = $_SERVER['QUERY_STRING'] ?? '';
$url = API_TARGET . ($qs ? '?' . $qs : '');

// Headers die weitergeleitet werden
$forwardHeaders = [];

// Authorization-Header weiterleiten (für eingeloggte Requests)
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $forwardHeaders[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}

// POST-Body weiterleiten
$body = '';
if ($method === 'POST') {
    $body = file_get_contents('php://input');
    $forwardHeaders[] = 'Content-Type: application/json';
}

// cURL-Request an die echte API
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_HTTPHEADER     => $forwardHeaders,
    CURLOPT_SSL_VERIFYPEER => true,
]);
if ($method === 'POST' && $body !== '') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

$response = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Fehler behandeln
if ($response === false) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Proxy-Fehler: ' . $curlError]);
    exit;
}

// Antwort 1:1 zurückgeben
http_response_code($httpCode ?: 200);
header('Content-Type: application/json; charset=utf-8');
echo $response;
