<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/layout.php';

$messages = db()->query(
    'SELECT id, name, email, phone, department, message, created_at
     FROM contact_messages ORDER BY id DESC'
)->fetchAll();

render_header('Messages', 'messages');
?>
<p class="count"><?= count($messages) ?> enquiry message<?= count($messages) === 1 ? '' : 's' ?>.</p>
<?php if (!$messages): ?>
    <p class="empty">No messages yet. (They'll appear here once a contact form is added to the site.)</p>
<?php else: ?>
    <?php foreach ($messages as $m): ?>
        <div class="message-card">
            <div class="message-head">
                <strong><?= h($m['name']) ?></strong>
                <?php if ($m['department']): ?><span class="badge"><?= h($m['department']) ?></span><?php endif; ?>
                <span class="message-date"><?= h($m['created_at']) ?></span>
            </div>
            <div class="message-meta"><?= h($m['email']) ?><?= $m['phone'] ? ' · ' . h($m['phone']) : '' ?></div>
            <p class="message-body"><?= nl2br(h($m['message'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php
render_footer();
