<?php
// test.php – einmalig aufrufen um Probleme zu diagnostizieren
// URL: https://www.joystream-fm.de/test.php
// DANACH SOFORT LÖSCHEN!

echo "<pre>";

// 1. Ist cURL verfügbar?
echo "cURL verfügbar: " . (function_exists('curl_init') ? "JA ✓" : "NEIN ✗") . "\n";

// 2. Kann der Server panel.joystream-fm.de erreichen?
if (function_exists('curl_init')) {
    $ch = curl_init('https://panel.joystream-fm.de/api.php?action=get_all');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    echo "HTTP-Code von api.php: " . $code . "\n";
    echo "cURL-Fehler: " . ($err ?: "keiner") . "\n";
    echo "Antwort (erste 300 Zeichen): " . substr($res, 0, 300) . "\n";
} else {
    // cURL nicht da – file_get_contents versuchen
    echo "Versuche file_get_contents...\n";
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $res = @file_get_contents('https://panel.joystream-fm.de/api.php?action=get_all', false, $ctx);
    echo "Antwort: " . ($res === false ? "FEHLER – Server nicht erreichbar" : substr($res, 0, 300)) . "\n";
}

// 3. PHP-Version
echo "\nPHP-Version: " . PHP_VERSION . "\n";

echo "</pre>";
