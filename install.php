<?php
// One-time setup: creates the database, its tables and the first admin account.
// Safe to run again: tables are only created if missing and the admin is only
// added when there are no users yet.
require_once __DIR__ . '/includes/layout.php';

$steps = [];
$ok = true;
try {
    $server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT), DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $steps[] = 'Database "' . DB_NAME . '" is ready.';

    $pdo = db();
    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
    $schema = preg_replace('/^\s*--.*$/m', '', $schema);
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $sql) {
        $pdo->exec($sql);
    }
    $steps[] = 'Tables users, acts, act_versions and activity_log are ready.';

    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO users (username, full_name, password_hash, role) VALUES (?, ?, ?, ?)')
            ->execute(['admin', 'Administrator', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
        $steps[] = 'Created the first account: username <b>admin</b>, password <b>admin123</b>. Change this password after you sign in.';
    } else {
        $steps[] = 'Users already exist, so no account was added.';
    }

    if (!is_dir(STORAGE_DIR) && !mkdir(STORAGE_DIR, 0775, true)) {
        throw new RuntimeException('Could not create the storage folder ' . STORAGE_DIR);
    }
    $steps[] = 'Storage folder for XML files is ready.';
} catch (Throwable $ex) {
    $ok = false;
    $steps[] = 'Setup failed: ' . e($ex->getMessage());
}

page_head('Install'); ?>
<body>
<main class="login-screen">
    <div class="login-card" style="max-width:560px">
        <div class="login-head"><div><h1>Install <?= e(APP_NAME) ?></h1><p>Database setup</p></div></div>
        <div class="login-form">
            <div class="flash <?= $ok ? 'flash-success' : 'flash-error' ?>"><?= $ok ? 'Setup complete.' : 'Setup did not finish.' ?></div>
            <ul>
                <?php foreach ($steps as $step): ?><li><?= $step ?></li><?php endforeach; ?>
            </ul>
            <?php if ($ok): ?>
                <a class="btn" href="login.php">Go to sign in</a>
            <?php else: ?>
                <p class="muted">Start MySQL in the XAMPP Control Panel and check the settings in <code>config.php</code>, then reload this page.</p>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
