<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/acts.php';
$user = require_login();
$actId = (int) ($_GET['id'] ?? $_POST['act_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!is_admin()) {
        flash('error', 'Only administrators can delete versions.');
    } elseif (($_POST['action'] ?? '') === 'delete_version') {
        $version = delete_version((int) ($_POST['version_id'] ?? 0));
        flash($version ? 'success' : 'error', $version ? "Deleted {$version['file_name']}." : 'That version no longer exists.');
    }
    $exists = db()->prepare('SELECT 1 FROM acts WHERE id = ?');
    $exists->execute([$actId]);
    header('Location: ' . ($exists->fetchColumn() ? "act.php?id=$actId" : 'library.php'));
    exit;
}

$stmt = db()->prepare('SELECT a.*, u.full_name AS created_by_name FROM acts a LEFT JOIN users u ON u.id = a.created_by WHERE a.id = ?');
$stmt->execute([$actId]);
$act = $stmt->fetch();
if (!$act) {
    flash('error', 'That Act was not found.');
    header('Location: library.php');
    exit;
}

$stmt = db()->prepare('SELECT v.*, u.full_name AS uploader FROM act_versions v LEFT JOIN users u ON u.id = v.uploaded_by
    WHERE v.act_id = ? ORDER BY ' . VERSION_ORDER_SQL);
$stmt->execute([$actId]);
$versions = $stmt->fetchAll();

$stmt = db()->prepare('SELECT l.action, l.details, l.created_at, u.full_name FROM activity_log l LEFT JOIN users u ON u.id = l.user_id
    WHERE l.act_id = ? ORDER BY l.id DESC LIMIT 10');
$stmt->execute([$actId]);
$history = $stmt->fetchAll();

page_start($act['act_number'], 'library'); ?>

<div class="page-header page-header-row">
    <div>
        <p><a href="library.php">&larr; Act Library</a></p>
        <h1><?= e($act['act_number']) ?></h1>
        <p><?= e($act['title']) ?></p>
    </div>
    <div class="btn-row">
        <a class="btn" href="viewer.php?act=<?= $actId ?>">Open in Viewer</a>
        <?php if (count($versions) > 1): ?><a class="btn secondary" href="viewer.php?act=<?= $actId ?>&amp;compare=1">Compare versions</a><?php endif; ?>
    </div>
</div>

<div class="stats">
    <div class="stat"><div class="value"><?= count($versions) ?></div><div class="label">Versions</div></div>
    <div class="stat"><div class="value"><?= (int) (end($versions)['section_count'] ?? 0) ?></div><div class="label">Sections in latest version</div></div>
    <div class="stat"><div class="value" style="font-size:16px;padding-top:8px"><?= e(format_date($act['created_at'])) ?></div><div class="label">Added by <?= e($act['created_by_name'] ?? 'a deleted user') ?></div></div>
</div>

<section class="card">
    <h2>Versions</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>#</th><th>Version</th><th>File</th><th>Sections</th><th>Languages</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($versions as $i => $v): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><span class="pill"><?= e($v['version_kind']) ?></span> <?= e($v['version_year'] ?? '') ?></td>
                    <td><?= e($v['file_name']) ?><div class="muted"><?= e($v['title']) ?></div></td>
                    <td><?= (int) $v['section_count'] ?></td>
                    <td><?= e(implode(', ', array_filter([$v['has_english'] ? 'English' : '', $v['has_malay'] ? 'Malay' : ''])) ?: '—') ?></td>
                    <td class="muted"><?= e(format_bytes((int) $v['file_size'])) ?></td>
                    <td class="muted"><?= e(format_date($v['uploaded_at'])) ?><br>by <?= e($v['uploader'] ?? 'a deleted user') ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn small secondary" href="api/file.php?id=<?= (int) $v['id'] ?>&amp;download=1">Download</a>
                            <?php if (is_admin()): ?>
                                <form method="post" class="inline-form" data-confirm="Delete <?= e($v['file_name']) ?>?<?= count($versions) === 1 ? ' It is the only version, so the Act will be removed too.' : '' ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_version">
                                    <input type="hidden" name="act_id" value="<?= $actId ?>">
                                    <input type="hidden" name="version_id" value="<?= (int) $v['id'] ?>">
                                    <button class="link-danger" type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (is_admin()): ?><p class="muted" style="margin-bottom:0">To add or replace a version, upload it from the <a href="library.php#upload">Act Library</a>.</p><?php endif; ?>
</section>

<section class="card">
    <h2>History</h2>
    <?php if (!$history): ?>
        <div class="empty">No activity recorded for this Act yet.</div>
    <?php else: ?>
        <ul class="feed">
            <?php foreach ($history as $row): ?>
                <li>
                    <div><b><?= e($row['full_name'] ?? 'Deleted user') ?></b> &middot; <?= e(action_label($row['action'])) ?> <span class="muted"><?= e($row['details']) ?></span></div>
                    <span class="when"><?= e(format_date($row['created_at'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php page_end();
