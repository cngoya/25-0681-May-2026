<?php
/**
 * Shared admin page chrome: render_header() and render_footer().
 * Pages call render_header('Title', 'active_nav_key') then echo content.
 */

function render_header(string $title, string $active = ''): void
{
    $nav = [
        'dashboard' => ['index.php',    '📊 Dashboard'],
        'users'     => ['users.php',    '🌷 Sign-ups'],
        'orders'    => ['orders.php',   '🛒 Orders'],
        'messages'  => ['messages.php', '✉️ Messages'],
        'products'  => ['products.php', '🌸 Products'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> · Bloom &amp; Petal Admin</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <header class="admin-header">
        <h1>🌸 Bloom &amp; Petal — Admin</h1>
        <a class="logout" href="logout.php">Log out →</a>
    </header>
    <nav class="admin-nav">
        <?php foreach ($nav as $key => [$href, $label]): ?>
            <a href="<?= $href ?>" class="<?= $key === $active ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
    <main class="admin-main">
        <h2><?= h($title) ?></h2>
<?php
}

function render_footer(): void
{
    ?>
    </main>
    <footer class="admin-footer">
        <p>Bloom &amp; Petal Haven · Admin Dashboard</p>
    </footer>
</body>
</html>
<?php
}
