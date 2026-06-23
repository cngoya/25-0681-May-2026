<?php
/**
 * Database configuration.
 *
 * Defaults match a stock XAMPP install on macOS:
 *   user "root", empty password, MySQL on 127.0.0.1:3306.
 * Secrets (like DB_PASS) come from backend/.env via env.php, which is
 * gitignored — never commit real credentials.
 */

require __DIR__ . '/env.php';

return [
    'host'    => getenv('DB_HOST') ?: '127.0.0.1',
    'port'    => getenv('DB_PORT') ?: '3306',
    'name'    => getenv('DB_NAME') ?: 'bloom_petal',
    'user'    => getenv('DB_USER') ?: 'root',
    'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'charset' => 'utf8mb4',
];
