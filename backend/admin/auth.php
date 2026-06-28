<?php
/**
 * Admin authentication + shared helpers.
 *
 * Every admin page requires this file. It starts the session, loads the
 * environment (for ADMIN_PASSWORD), and exposes:
 *   - require_login()  → redirect to login if not authenticated
 *   - admin_password() → the configured admin password
 *   - h()              → HTML-escape a value for safe output
 */

require_once __DIR__ . '/../env.php';   // loads backend/.env into getenv()
require_once __DIR__ . '/../db.php';    // defines db()

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function admin_password(): string
{
    return (string) env_get('ADMIN_PASSWORD', '');
}

function is_logged_in(): bool
{
    return !empty($_SESSION['admin_authed']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/** HTML-escape for safe output. */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Format a number as KES currency. */
function kes($amount): string
{
    return 'KES ' . number_format((float) $amount);
}
