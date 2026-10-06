-- Legislative Viewer database schema (MariaDB / MySQL).
-- install.php runs this file; it can also be imported in phpMyAdmin.

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login    DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per Act; versions of the same Act share its act_number.
CREATE TABLE IF NOT EXISTS acts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    act_number  VARCHAR(50)  NOT NULL UNIQUE,
    title       VARCHAR(255) NOT NULL,
    created_by  INT UNSIGNED NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_acts_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per uploaded XML file (a version of an Act). The file itself is stored on disk.
CREATE TABLE IF NOT EXISTS act_versions (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    act_id         INT UNSIGNED NOT NULL,
    file_name      VARCHAR(255) NOT NULL,
    stored_name    VARCHAR(255) NOT NULL,
    version_kind   ENUM('Original', 'Revised', 'Current') NOT NULL,
    version_year   SMALLINT UNSIGNED NULL,
    title          VARCHAR(255) NOT NULL,
    has_english    TINYINT(1) NOT NULL DEFAULT 0,
    has_malay      TINYINT(1) NOT NULL DEFAULT 0,
    section_count  INT UNSIGNED NOT NULL DEFAULT 0,
    file_size      INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by    INT UNSIGNED NULL,
    uploaded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_act_file (act_id, file_name),
    CONSTRAINT fk_versions_act  FOREIGN KEY (act_id) REFERENCES acts(id) ON DELETE CASCADE,
    CONSTRAINT fk_versions_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit trail: logins, uploads, views, comparisons, user changes.
CREATE TABLE IF NOT EXISTS activity_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(30)  NOT NULL,
    act_id      INT UNSIGNED NULL,
    details     VARCHAR(255) NOT NULL DEFAULT '',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_activity_time (created_at),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_activity_act  FOREIGN KEY (act_id)  REFERENCES acts(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
