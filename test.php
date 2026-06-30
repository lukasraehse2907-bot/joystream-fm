// test.php – einmalig aufrufen um Probleme zu diagnostizieren
// URL: https://www.joystream-fm.de/test.php
// DANACH SOFORT LÖSCHEN!

echo "<pre>";

// Ziel-URL testen und Redirect verfolgen
$testUrl = 'https://panel.joystream-fm.de/api.php?action=get_all';

$ch = curl_init($testUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_FOLLOWLOCATION => true,   // Redirects folgen
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_HEADER         => false,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$err  = curl_error($ch);
curl_close($ch);

echo "Ursprungs-URL: $testUrl\n";
echo "Finale URL nach Redirects: $finalUrl\n";
echo "HTTP-Code: $code\n";
echo "cURL-Fehler: " . ($err ?: "keiner") . "\n";
echo "Antwort (erste 400 Zeichen):\n" . htmlspecialchars(substr($res, 0, 400)) . "\n";
echo "</pre>";
