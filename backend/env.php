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

if (!function_exists('load_env')):
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

        // putenv() is disabled on some shared hosts, so we always populate
        // $_ENV / $_SERVER too and read via env_get() below.
        @putenv("$key=$value");
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
    }
}
endif;

if (!function_exists('env_get')):
/**
 * Read a configuration value from any place it might live, in order:
 * a real environment variable, then $_ENV / $_SERVER (populated from .env).
 * Robust to hosts where putenv()/getenv() are restricted.
 */
function env_get(string $key, $default = null)
{
    $val = getenv($key);
    if ($val !== false) {
        return $val;
    }
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }
    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }
    return $default;
}
endif;

load_env(__DIR__ . '/.env');
