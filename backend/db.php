<?php
/**
 * Returns a shared PDO connection to the MySQL database.
 *
 * Uses prepared-statement-friendly settings: real exceptions on error,
 * associative-array fetches, and no emulated prepares (so the driver
 * sends typed parameters to MySQL — the safe default against injection).
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/config.php';

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['name'],
        $cfg['charset']
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
