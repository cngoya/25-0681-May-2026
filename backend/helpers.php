<?php
/**
 * Small shared helpers for the JSON API:
 *  - reading the request body (form-encoded OR JSON)
 *  - sending a JSON response
 *  - server-side validators that mirror the front-end rules in script.js
 */

/** Send a JSON response with the given HTTP status code and stop. */
function json_response(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

/**
 * Read incoming request data whether the client sent a normal HTML form
 * (application/x-www-form-urlencoded) or a JSON body via fetch().
 */
function read_input(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// ─── Validators (kept in sync with script.js) ───

function valid_name(string $name): bool
{
    return (bool) preg_match('/^[A-Za-z\s]{2,}$/', trim($name));
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function valid_phone(string $phone): bool
{
    $phone = preg_replace('/\s+/', '', $phone);
    return (bool) preg_match('/^\+?[0-9]{10,13}$/', $phone);
}

function valid_gender(string $gender): bool
{
    return in_array($gender, ['female', 'male', 'other', 'prefer-not'], true);
}
