<?php
// api.php — zentrales Backend für das Joystream FM Admin-Panel.

// Output-Buffer starten: verhindert dass PHP-Warnungen/Fehler die JSON-Header kaputtmachen
ob_start();

// Alle PHP-Fehler abfangen statt als HTML auszugeben
set_error_handler(function($errno, $errstr) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PHP-Fehler: ' . $errstr]);
    exit;
});

// --- CORS ZUERST – vor allem anderen ---
$allowedOrigins = [
    'https://www.joystream-fm.de',
    'https://joystream-fm.de',
    'https://panel.joystream-fm.de',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    ob_end_clean();
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';

function input() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function respond($arr) {
    ob_clean();
    echo json_encode($arr);
    exit;
}

function getBearerToken() {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/', $hdr, $m)) return $m[1];
    return null;
}

function requireAuth($pdo) {
    $token = getBearerToken();
    if (!$token) respond(['ok' => false, 'error' => 'Nicht eingeloggt']);
    $stmt = $pdo->prepare("SELECT admin_id FROM jsfm_sessions WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) respond(['ok' => false, 'error' => 'Session abgelaufen, bitte erneut einloggen']);
    return $row['admin_id'];
}

function getSetting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT setting_value FROM jsfm_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function setSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO jsfm_settings (setting_key, setting_value) VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // ---------- ÖFFENTLICH: alle Inhalte für die Startseite laden ----------
    case 'get_all': {
        $about1 = getSetting($pdo, 'about1', '');
        $about2 = getSetting($pdo, 'about2', '');
        $contactRaw = getSetting($pdo, 'contact', '{}');

        $socials = $pdo->query("SELECT id, platform, handle, url FROM jsfm_socials ORDER BY id ASC")->fetchAll();
        $partners = $pdo->query("SELECT id, name, description AS `desc`, url FROM jsfm_partners ORDER BY id ASC")->fetchAll();

        respond([
            'ok' => true,
            'about1' => $about1,
            'about2' => $about2,
            'socials' => $socials,
            'partners' => $partners,
            'contact' => json_decode($contactRaw, true) ?: [],
        ]);
    }

    // ---------- LOGIN ----------
    case 'login': {
        $body = input();
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        $stmt = $pdo->prepare("SELECT id, password_hash FROM jsfm_admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            respond(['ok' => false, 'error' => 'Benutzername oder Passwort falsch']);
        }

        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("INSERT INTO jsfm_sessions (token, admin_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
        $stmt->execute([$token, $admin['id']]);

        respond(['ok' => true, 'token' => $token]);
    }

    // ---------- LOGOUT ----------
    case 'logout': {
        $token = getBearerToken();
        if ($token) {
            $stmt = $pdo->prepare("DELETE FROM jsfm_sessions WHERE token = ?");
            $stmt->execute([$token]);
        }
        respond(['ok' => true]);
    }

    // ---------- ABOUT SPEICHERN ----------
    case 'save_about': {
        $adminId = requireAuth($pdo);
        $body = input();
        $box = (int)($body['box'] ?? 0);
        $text = trim($body['text'] ?? '');
        if (!in_array($box, [1, 2], true) || $text === '') respond(['ok' => false, 'error' => 'Ungültige Eingabe']);
        setSetting($pdo, 'about' . $box, $text);
        respond(['ok' => true]);
    }

    // ---------- SOCIAL HINZUFÜGEN / LÖSCHEN ----------
    case 'add_social': {
        $adminId = requireAuth($pdo);
        $body = input();
        $platform = trim($body['platform'] ?? 'other');
        $handle = trim($body['handle'] ?? '');
        $url = trim($body['url'] ?? '');
        if ($handle === '') respond(['ok' => false, 'error' => 'Handle erforderlich']);
        $stmt = $pdo->prepare("INSERT INTO jsfm_socials (platform, handle, url) VALUES (?, ?, ?)");
        $stmt->execute([$platform, $handle, $url]);
        respond(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }
    case 'remove_social': {
        $adminId = requireAuth($pdo);
        $body = input();
        $id = (int)($body['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM jsfm_socials WHERE id = ?");
        $stmt->execute([$id]);
        respond(['ok' => true]);
    }

    // ---------- PARTNER HINZUFÜGEN / LÖSCHEN ----------
    case 'add_partner': {
        $adminId = requireAuth($pdo);
        $body = input();
        $name = trim($body['name'] ?? '');
        $desc = trim($body['desc'] ?? '');
        $url = trim($body['url'] ?? '');
        if ($name === '') respond(['ok' => false, 'error' => 'Name erforderlich']);
        $stmt = $pdo->prepare("INSERT INTO jsfm_partners (name, description, url) VALUES (?, ?, ?)");
        $stmt->execute([$name, $desc, $url]);
        respond(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }
    case 'remove_partner': {
        $adminId = requireAuth($pdo);
        $body = input();
        $id = (int)($body['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM jsfm_partners WHERE id = ?");
        $stmt->execute([$id]);
        respond(['ok' => true]);
    }

    // ---------- KONTAKT SPEICHERN ----------
    case 'save_contact': {
        $adminId = requireAuth($pdo);
        $body = input();
        $contact = [
            'email' => trim($body['email'] ?? ''),
            'phone' => trim($body['phone'] ?? ''),
            'address' => trim($body['address'] ?? ''),
            'extra' => trim($body['extra'] ?? ''),
        ];
        setSetting($pdo, 'contact', json_encode($contact));
        respond(['ok' => true]);
    }

    // ---------- PASSWORT ÄNDERN ----------
    case 'change_password': {
        $adminId = requireAuth($pdo);
        $body = input();
        $current = $body['current_password'] ?? '';
        $new = $body['new_password'] ?? '';
        if (strlen($new) < 6) respond(['ok' => false, 'error' => 'Neues Passwort zu kurz']);

        $stmt = $pdo->prepare("SELECT password_hash FROM jsfm_admin WHERE id = ?");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            respond(['ok' => false, 'error' => 'Aktuelles Passwort ist falsch']);
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE jsfm_admin SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $adminId]);
        respond(['ok' => true]);
    }

    // ---------- BENUTZERNAME ÄNDERN ----------
    case 'change_username': {
        $adminId = requireAuth($pdo);
        $body = input();
        $newUsername = trim($body['new_username'] ?? '');
        if ($newUsername === '') respond(['ok' => false, 'error' => 'Benutzername erforderlich']);

        try {
            $stmt = $pdo->prepare("UPDATE jsfm_admin SET username = ? WHERE id = ?");
            $stmt->execute([$newUsername, $adminId]);
        } catch (PDOException $e) {
            respond(['ok' => false, 'error' => 'Benutzername bereits vergeben']);
        }
        respond(['ok' => true]);
    }

    default:
        http_response_code(400);
        respond(['ok' => false, 'error' => 'Unbekannte Aktion']);
}

ob_end_flush();
