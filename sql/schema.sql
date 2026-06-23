-- ═══════════════════════════════════════════
--  BLOOM & PETAL HAVEN — Database schema
--  MySQL / MariaDB (XAMPP)
--
--  Run once to create the database and tables:
--    mysql -u root < sql/schema.sql
--  …or paste it into phpMyAdmin → SQL tab.
-- ═══════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS bloom_petal
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bloom_petal;

-- ─── Sign-ups / customer accounts ───
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(120)  NOT NULL,
    email       VARCHAR(190)  NOT NULL,
    phone       VARCHAR(20)   NOT NULL,
    gender      ENUM('female', 'male', 'other', 'prefer-not') NOT NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
