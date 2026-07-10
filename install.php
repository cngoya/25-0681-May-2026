<?php
/**
 * One-time database installer for shared hosting.
 *
 * Visit  http://your-site/install.php?run=bloom  ONCE in your browser to
 * create the tables and load the catalogue, then DELETE the file.
 *
 * It runs on the host, so it can reach the host's MySQL (which blocks
 * outside connections). Safe to re-run: tables use IF NOT EXISTS and the
 * seed uses INSERT IGNORE.
 */

require __DIR__ . '/backend/db.php';

header('Content-Type: text/plain; charset=utf-8');

if (($_GET['run'] ?? '') !== 'bloom') {
    echo "To install the database, add ?run=bloom to this URL.\n";
    exit;
}

$sqlFile = __DIR__ . '/sql/deploy_hosted.sql';
if (!is_readable($sqlFile)) {
    http_response_code(500);
    echo "Cannot read $sqlFile\n";
    exit;
}

// Strip SQL comment lines, then split into individual statements.
$sql        = preg_replace('/^\s*--.*$/m', '', file_get_contents($sqlFile));
$statements = array_filter(array_map('trim', explode(';', $sql)), static fn($s) => $s !== '');

try {
    $pdo = db();
    $count = 0;
    foreach ($statements as $stmt) {
        $pdo->exec($stmt);
        $count++;
    }
    echo "✓ Database ready — executed {$count} statements.\n";
    echo "Products in catalogue: " . $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() . "\n";
    echo "Tables: " . implode(', ', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)) . "\n";
    echo "\nALL DONE. Please DELETE install.php from the server now.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Check backend/.env DB credentials.\n";
}
