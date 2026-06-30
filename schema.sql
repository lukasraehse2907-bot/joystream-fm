-- Joystream FM – Admin-Backend Datenbankschema
-- Einmalig importieren (z.B. via phpMyAdmin)

CREATE TABLE IF NOT EXISTS jsfm_settings (
  setting_key VARCHAR(64) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jsfm_socials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(32) NOT NULL,
  handle VARCHAR(190) NOT NULL,
  url VARCHAR(500) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jsfm_partners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  description VARCHAR(500) DEFAULT '',
  url VARCHAR(500) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jsfm_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jsfm_sessions (
  token VARCHAR(64) PRIMARY KEY,
  admin_id INT NOT NULL,
  expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Den ersten Admin-Account NICHT hier per SQL anlegen (Passwort-Hash müsste sonst geraten werden).
-- Stattdessen einmalig setup.php aufrufen, das legt admin/joystream2025 mit korrektem Hash an
-- und löscht sich danach selbst (siehe setup.php).
