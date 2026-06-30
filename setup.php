<?php
// setup.php — EINMALIG aufrufen (https://panel.joystream-fm.de/setup.php), um den ersten
// Admin-Account korrekt mit gehashtem Passwort anzulegen. Danach diese Datei VOM SERVER LÖSCHEN!

require __DIR__ . '/db.php';

$username = 'admin';
$password = 'joystream2025'; // <- danach im Admin-Panel sofort ändern!

$stmt = $pdo->prepare("SELECT id FROM jsfm_admin WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    die("Admin-Account '$username' existiert bereits. Nichts zu tun. Bitte diese Datei jetzt löschen.");
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO jsfm_admin (username, password_hash) VALUES (?, ?)");
$stmt->execute([$username, $hash]);

echo "Admin-Account angelegt: Benutzername '$username', Passwort '$password'.\n";
echo "WICHTIG: Bitte das Passwort jetzt im Admin-Panel ändern und diese Datei (setup.php) vom Server löschen!";
