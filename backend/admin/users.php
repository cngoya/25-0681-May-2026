<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/layout.php';

$users = db()->query(
    'SELECT id, full_name, email, phone, gender, created_at
     FROM users ORDER BY id DESC'
)->fetchAll();

render_header('Sign-ups', 'users');
?>
<p class="count"><?= count($users) ?> registered customer<?= count($users) === 1 ? '' : 's' ?>.</p>
<?php if (!$users): ?>
    <p class="empty">No sign-ups yet.</p>
<?php else: ?>
    <table class="admin-table">
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int) $u['id'] ?></td>
                <td><?= h($u['full_name']) ?></td>
                <td><?= h($u['email']) ?></td>
                <td><?= h($u['phone']) ?></td>
                <td><?= h($u['gender']) ?></td>
                <td><?= h($u['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php
render_footer();
