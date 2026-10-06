<?php
require_once __DIR__ . '/includes/layout.php';

// Only follow redirects to pages on this site.
$next = $_GET['next'] ?? $_POST['next'] ?? '';
if (!preg_match('~^/[^/\\\\]~', $next)) $next = 'index.php';

if (current_user()) {
    header('Location: ' . $next);
    exit;
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, username, full_name, password_hash, role FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        unset($row['password_hash']);
        $_SESSION['user'] = $row;
        db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$row['id']]);
        log_activity('login');
        header('Location: ' . $next);
        exit;
    }
    $error = 'Incorrect username or password.';
}

page_head('Sign in'); ?>
<body>
<main class="login-screen">
    <div class="login-card">
        <div class="login-head">
            <img src="<?= FAVICON ?>" alt="" width="40" height="40">
            <div>
                <h1><?= e(APP_NAME) ?></h1>
                <p>Sign in to continue</p>
            </div>
        </div>
        <form class="login-form" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <?php if ($error): ?><div class="login-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <label>Username
                <input type="text" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
            </label>
            <label>Password
                <input type="password" name="password" id="loginPass" autocomplete="current-password" required>
            </label>
            <label class="show-pass"><input type="checkbox" id="showPass"> Show password</label>
            <button type="submit" class="login-submit">Sign in</button>
        </form>
        <div class="login-foot"><?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?> &middot; Internship Individual Project</div>
    </div>
</main>
<script>
    document.getElementById('showPass').addEventListener('change', e => {
        document.getElementById('loginPass').type = e.target.checked ? 'text' : 'password';
    });
</script>
</body>
</html>
