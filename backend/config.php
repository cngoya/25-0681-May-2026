<?php
/**
 * Database configuration.
 *
 * Defaults match a stock XAMPP install on macOS:
 *   user "root", empty password, MySQL on 127.0.0.1:3306.
 * Secrets (like DB_PASS) come from backend/.env via env.php, which is
 * gitignored — never commit real credentials.
 */

require_once __DIR__ . '/env.php';

return [
    'host'    => env_get('DB_HOST', '127.0.0.1'),
    'port'    => env_get('DB_PORT', '3306'),
    'name'    => env_get('DB_NAME', 'bloom_petal'),
    'user'    => env_get('DB_USER', 'root'),
    'pass'    => env_get('DB_PASS', ''),
    'charset' => 'utf8mb4',
];
