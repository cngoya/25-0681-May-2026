<?php
require_once __DIR__ . '/auth.php';

// Already logged in? Go to dashboard.
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $entered = (string) ($_POST['password'] ?? '');
    $actual  = admin_password();

    if ($actual === '') {
        $error = 'No admin password is configured. Set ADMIN_PASSWORD in backend/.env.';
    } elseif (hash_equals($actual, $entered)) {
        session_regenerate_id(true);
        $_SESSION['admin_authed'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Incorrect password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login · Bloom &amp; Petal</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body class="login-body">
    <form class="login-box" method="post" action="login.php">
        <h1>🌸 Admin Login</h1>
        <p>Bloom &amp; Petal Haven dashboard</p>
        <?php if ($error): ?>
            <div class="login-error"><?= h($error) ?></div>
        <?php endif; ?>
        <input type="password" name="password" placeholder="Admin password" autofocus required>
        <button type="submit">Log in</button>
    </form>
</body>
</html>
