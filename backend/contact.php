<?php
/**
 * POST /backend/contact.php
 *
 * Saves a contact / enquiry message into `contact_messages`.
 *
 * Request (form-encoded or JSON): name, email, message (required);
 *                                 phone, department (optional).
 *
 * Responses:
 *   201 { ok:true, message }
 *   400 { ok:false, errors:{ field: message } }
 *   405 / 500
 */

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed. Use POST.']);
}

$in = read_input();

$name       = trim((string) ($in['name']       ?? ''));
$email      = trim((string) ($in['email']      ?? ''));
$phone      = trim((string) ($in['phone']      ?? ''));
$department = trim((string) ($in['department'] ?? ''));
$message    = trim((string) ($in['message']    ?? ''));

// ─── Validate ───
$errors = [];
if (!valid_name($name))                 $errors['name']    = 'Please enter a valid name (letters only, at least 2 characters).';
if (!valid_email($email))               $errors['email']   = 'Please enter a valid email (e.g. cleon@gmail.com).';
if ($phone !== '' && !valid_phone($phone)) $errors['phone'] = 'Please enter a valid phone number, or leave it blank.';
if (mb_strlen($message) < 10)           $errors['message'] = 'Please enter a message of at least 10 characters.';

if ($errors) {
    json_response(400, ['ok' => false, 'errors' => $errors]);
}

// ─── Store it (prepared statement) ───
try {
    $stmt = db()->prepare(
        'INSERT INTO contact_messages (name, email, phone, department, message)
         VALUES (:name, :email, :phone, :department, :message)'
    );
    $stmt->execute([
        ':name'       => $name,
        ':email'      => $email,
        ':phone'      => $phone !== '' ? $phone : null,
        ':department' => $department !== '' ? $department : null,
        ':message'    => $message,
    ]);
} catch (PDOException $e) {
    error_log('Contact message failed: ' . $e->getMessage());
    json_response(500, ['ok' => false, 'message' => 'Something went wrong. Please try again later.']);
}

json_response(201, [
    'ok'      => true,
    'message' => 'Thank you! Your message has been received.',
    'name'    => $name,
]);
