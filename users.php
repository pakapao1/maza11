<?php
require_once __DIR__ . '/includes/layout.php';
$admin = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['user_id'] ?? 0);

    if ($action === 'add') {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'viewer';
        $exists = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
        $exists->execute([$username]);

        if (!preg_match('/^[a-z0-9_.]{3,50}$/', $username)) {
            flash('error', 'Username must be 3 to 50 characters: letters, numbers, dots or underscores.');
        } elseif ($fullName === '') {
            flash('error', 'Enter the full name.');
        } elseif (strlen($password) < 8) {
            flash('error', 'The password must be at least 8 characters.');
        } elseif ($exists->fetchColumn()) {
            flash('error', "The username \"$username\" is already taken.");
        } else {
            $pdo->prepare('INSERT INTO users (username, full_name, password_hash, role) VALUES (?, ?, ?, ?)')
                ->execute([$username, mb_substr($fullName, 0, 100), password_hash($password, PASSWORD_DEFAULT), $role]);
            log_activity('user_add', "$username ($role)");
            flash('success', "Added $username as $role.");
        }
    } elseif ($action === 'role' && $id !== (int) $admin['id']) {
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'viewer';
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        log_activity('user_update', "user #$id is now $role");
        flash('success', "Role updated to $role.");
    } elseif ($action === 'reset') {
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 8) {
            flash('error', 'The new password must be at least 8 characters.');
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            log_activity('user_update', "reset password for user #$id");
            flash('success', 'Password reset.');
        }
    } elseif ($action === 'delete' && $id !== (int) $admin['id']) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        log_activity('user_delete', "user #$id");
        flash('success', 'User deleted. Their uploads and history are kept.');
    } else {
        flash('error', 'You cannot change your own role or delete your own account here.');
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query('SELECT u.id, u.username, u.full_name, u.role, u.created_at, u.last_login,
        (SELECT COUNT(*) FROM act_versions v WHERE v.uploaded_by = u.id) AS uploads
    FROM users u ORDER BY u.role, u.username')->fetchAll();

page_start('Users', 'users'); ?>

<div class="page-header">
    <h1>Users</h1>
    <p>Manage who can sign in. <b>Admins</b> can upload and delete Acts and manage users; <b>viewers</b> can read and compare.</p>
</div>

<section class="card">
    <h2>Add a user</h2>
    <form method="post" class="form-grid" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <label class="field">Username <input name="username" required pattern="[a-zA-Z0-9_.]{3,50}"></label>
        <label class="field">Full name <input name="full_name" required maxlength="100"></label>
        <label class="field">Password <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label class="field">Role
            <select name="role"><option value="viewer">Viewer</option><option value="admin">Admin</option></select>
        </label>
        <div><button class="btn" type="submit">Add user</button></div>
    </form>
</section>

<section class="card">
    <h2>All users (<?= count($users) ?>)</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>User</th><th>Role</th><th>Uploads</th><th>Last sign-in</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): $self = (int) $u['id'] === (int) $admin['id']; ?>
                <tr>
                    <td><b><?= e($u['full_name']) ?></b><?= $self ? ' <span class="muted">(you)</span>' : '' ?><div class="muted"><?= e($u['username']) ?></div></td>
                    <td><span class="pill <?= $u['role'] === 'admin' ? 'admin' : '' ?>"><?= e($u['role']) ?></span></td>
                    <td><?= (int) $u['uploads'] ?></td>
                    <td class="muted"><?= e(format_date($u['last_login'])) ?></td>
                    <td class="muted"><?= e(format_date($u['created_at'])) ?></td>
                    <td>
                        <?php if ($self): ?>
                            <a href="profile.php" class="muted">Edit in My profile</a>
                        <?php else: ?>
                            <div class="actions">
                                <form method="post" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="role">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'viewer' : 'admin' ?>">
                                    <button class="btn small secondary" type="submit">Make <?= $u['role'] === 'admin' ? 'viewer' : 'admin' ?></button>
                                </form>
                                <form method="post" class="inline-form search-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reset">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <input type="password" name="password" class="search-input" style="min-width:150px;padding:4px 8px;font-size:12px" placeholder="New password" minlength="8" required autocomplete="new-password" aria-label="New password for <?= e($u['username']) ?>">
                                    <button class="btn small secondary" type="submit">Reset</button>
                                </form>
                                <form method="post" class="inline-form" data-confirm="Delete <?= e($u['username']) ?>? Their uploads stay in the library.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <button class="link-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php page_end();
