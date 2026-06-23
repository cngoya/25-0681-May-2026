<?php
/**
 * POST /backend/signup.php
 *
 * Accepts a sign-up (form-encoded or JSON), validates it on the server,
 * and stores it in the `users` table. Responds with JSON:
 *   201 { ok: true,  message }
 *   400 { ok: false, errors: { field: message } }   — validation failed
 *   409 { ok: false, message }                       — email already exists
 *   405 / 500                                         — method / server error
 */

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed. Use POST.']);
}

$in = read_input();

$name   = trim((string) ($in['name']   ?? ''));
$email  = trim((string) ($in['email']  ?? ''));
$phone  = trim((string) ($in['phone']  ?? ''));
$gender = trim((string) ($in['gender'] ?? ''));

// ─── Validate every field; collect all errors so the form can show them at once ───
$errors = [];
if (!valid_name($name))     $errors['name']   = 'Please enter a valid name (letters only, at least 2 characters).';
if (!valid_email($email))   $errors['email']  = 'Please enter a valid email (e.g. cleon@gmail.com).';
if (!valid_phone($phone))   $errors['phone']  = 'Please enter a valid phone number (10-13 digits).';
if (!valid_gender($gender)) $errors['gender'] = 'Please select your gender.';

if ($errors) {
    json_response(400, ['ok' => false, 'errors' => $errors]);
}

// ─── Store it (prepared statement = safe against SQL injection) ───
try {
    $stmt = db()->prepare(
        'INSERT INTO users (full_name, email, phone, gender)
         VALUES (:name, :email, :phone, :gender)'
    );
    $stmt->execute([
        ':name'   => $name,
        ':email'  => $email,
        ':phone'  => $phone,
        ':gender' => $gender,
    ]);
} catch (PDOException $e) {
    // 23000 = integrity constraint violation → duplicate email (UNIQUE key).
    if ($e->getCode() === '23000') {
        json_response(409, ['ok' => false, 'message' => 'That email is already registered.']);
    }

    error_log('Sign-up failed: ' . $e->getMessage());
    json_response(500, ['ok' => false, 'message' => 'Something went wrong. Please try again later.']);
}

json_response(201, [
    'ok'      => true,
    'message' => 'Account created successfully.',
    'name'    => $name,
    'email'   => $email,
]);
