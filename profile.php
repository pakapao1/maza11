<?php
require_once __DIR__ . '/includes/layout.php';
$user = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'name') {
        $fullName = trim($_POST['full_name'] ?? '');
        if ($fullName === '') {
            flash('error', 'Enter your full name.');
        } else {
            $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?')->execute([mb_substr($fullName, 0, 100), $user['id']]);
            $_SESSION['user']['full_name'] = mb_substr($fullName, 0, 100);
            flash('success', 'Name updated.');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', (string) $stmt->fetchColumn())) {
            flash('error', 'Your current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash('error', 'The new password must be at least 8 characters.');
        } elseif ($new !== ($_POST['confirm_password'] ?? '')) {
            flash('error', 'The new passwords do not match.');
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            log_activity('password');
            flash('success', 'Password changed.');
        }
    }
    header('Location: profile.php');
    exit;
}

$stmt = $pdo->prepare('SELECT username, full_name, role, created_at, last_login FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$me = $stmt->fetch();

$stmt = $pdo->prepare('SELECT action, COUNT(*) AS n FROM activity_log WHERE user_id = ? GROUP BY action');
$stmt->execute([$user['id']]);
$counts = array_column($stmt->fetchAll(), 'n', 'action');

page_start('My profile', 'profile'); ?>

<div class="page-header">
    <h1>My profile</h1>
    <p>Signed in as <b><?= e($me['username']) ?></b> &middot; <span class="pill <?= $me['role'] === 'admin' ? 'admin' : '' ?>"><?= e($me['role']) ?></span> &middot; member since <?= e(format_date($me['created_at'])) ?></p>
</div>

<div class="stats">
    <div class="stat"><div class="value"><?= (int) ($counts['view'] ?? 0) ?></div><div class="label">Acts opened</div></div>
    <div class="stat"><div class="value"><?= (int) ($counts['compare'] ?? 0) ?></div><div class="label">Comparisons</div></div>
    <div class="stat"><div class="value"><?= (int) ($counts['upload'] ?? 0) ?></div><div class="label">Files uploaded</div></div>
    <div class="stat"><div class="value"><?= (int) ($counts['login'] ?? 0) ?></div><div class="label">Sign-ins</div></div>
</div>

<div class="grid-2">
    <section class="card">
        <h2>Details</h2>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="name">
            <label class="field">Full name <input name="full_name" value="<?= e($me['full_name']) ?>" required maxlength="100"></label>
            <div><button class="btn" type="submit">Save</button></div>
        </form>
    </section>

    <section class="card">
        <h2>Change password</h2>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <label class="field">Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
            <label class="field">New password <input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
            <label class="field">Confirm new password <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></label>
            <div><button class="btn" type="submit">Change password</button></div>
        </form>
    </section>
</div>

<?php page_end();
