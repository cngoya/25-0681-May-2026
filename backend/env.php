<?php
/**
 * Minimal .env loader (no external dependencies).
 *
 * Reads backend/.env (if present) and exposes each KEY=value pair to
 * getenv() / $_ENV / $_SERVER. Lines starting with # and blank lines are
 * ignored. Real environment variables already set by the server are NOT
 * overwritten, so production hosts (cPanel, Docker) can override the file.
 *
 * Keep secrets in .env — it is gitignored and never committed.
 */

function load_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip surrounding quotes if present.
        if (strlen($value) >= 2 &&
            ($value[0] === '"' || $value[0] === "'") &&
            $value[strlen($value) - 1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        // Don't clobber a variable the real environment already provides.
        if (getenv($key) !== false) {
            continue;
        }

        putenv("$key=$value");
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
    }
}

load_env(__DIR__ . '/.env');
