<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/acts.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!is_admin()) {
        flash('error', 'Only administrators can change the library.');
    } elseif (($_POST['action'] ?? '') === 'upload') {
        $files = uploaded_files('xml_files');
        if (!$files) {
            // An empty $_FILES on a POST usually means the upload was bigger than post_max_size.
            flash('error', empty($_FILES) ? 'The upload was too large for the server. Try fewer files at once.' : 'Choose at least one XML file.');
        }
        $done = [];
        $failed = [];
        foreach ($files as $f) {
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $failed[] = $f['name'] . ': upload failed (error ' . $f['error'] . ').';
                continue;
            }
            try {
                $done[] = import_act_file($f['tmp'], $f['name'], (int) $user['id']);
            } catch (Throwable $ex) {
                $failed[] = $ex->getMessage();
            }
        }
        if ($done) flash('success', implode(' ', $done));
        foreach ($failed as $msg) flash('error', $msg);
    } elseif (($_POST['action'] ?? '') === 'delete_act') {
        $act = delete_act((int) ($_POST['act_id'] ?? 0));
        flash($act ? 'success' : 'error', $act ? "Deleted {$act['act_number']} and all its versions." : 'That Act no longer exists.');
    }
    header('Location: library.php');
    exit;
}

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT a.id, a.act_number, a.title, a.updated_at, COUNT(v.id) AS versions,
        MIN(v.version_year) AS first_year, MAX(v.version_year) AS last_year,
        MAX(v.has_english) AS has_english, MAX(v.has_malay) AS has_malay
    FROM acts a LEFT JOIN act_versions v ON v.act_id = a.id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE a.act_number LIKE ? OR a.title LIKE ?';
    $params = ["%$q%", "%$q%"];
}
$stmt = db()->prepare($sql . ' GROUP BY a.id ORDER BY a.act_number');
$stmt->execute($params);
$acts = $stmt->fetchAll();

page_start('Act Library', 'library'); ?>

<div class="page-header page-header-row">
    <div>
        <h1>Act Library</h1>
        <p>Every Act stored in the system, with all of its uploaded versions.</p>
    </div>
    <form class="search-form" method="get" role="search">
        <input class="search-input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search by Act number or title" aria-label="Search Acts">
        <button class="btn" type="submit">Search</button>
        <?php if ($q !== ''): ?><a class="btn secondary" href="library.php">Clear</a><?php endif; ?>
    </form>
</div>

<?php if (is_admin()): ?>
<section class="card" id="upload">
    <h2>Upload versions</h2>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">
        <label class="drop-zone" id="dropZone">
            <strong>&#128193; Choose XML files or drag them here</strong>
            <span class="muted">Each file is filed under its Act using the &lt;NUMBER&gt; element. Upload a file with the same name again to replace it.</span>
            <input type="file" name="xml_files[]" id="xmlFiles" accept=".xml" multiple required>
        </label>
        <div class="btn-row" style="margin-top:12px">
            <button class="btn" type="submit">Upload to library</button>
            <span class="muted" style="align-self:center">Name files <code>*_R.xml</code> (original), <code>*_R2020.xml</code> (revised in 2020) or anything else (current).</span>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="card">
    <div class="card-head">
        <h2><?= $q !== '' ? count($acts) . ' result' . (count($acts) === 1 ? '' : 's') . ' for "' . e($q) . '"' : 'All Acts (' . count($acts) . ')' ?></h2>
    </div>
    <?php if (!$acts): ?>
        <div class="empty"><?= $q !== '' ? 'No Acts match your search.' : 'The library is empty.' . (is_admin() ? ' Upload XML files above to add an Act.' : ' Ask an administrator to upload Acts.') ?></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Act</th><th>Title</th><th>Versions</th><th>Years</th><th>Languages</th><th>Updated</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($acts as $act):
                    $langs = array_filter([$act['has_english'] ? 'English' : '', $act['has_malay'] ? 'Malay' : '']);
                    $years = $act['first_year'] ? ($act['first_year'] == $act['last_year'] ? $act['first_year'] : "{$act['first_year']}–{$act['last_year']}") : '—'; ?>
                    <tr>
                        <td><a href="act.php?id=<?= (int) $act['id'] ?>"><b><?= e($act['act_number']) ?></b></a></td>
                        <td><?= e($act['title']) ?></td>
                        <td><?= (int) $act['versions'] ?></td>
                        <td><?= e($years) ?></td>
                        <td><?= e(implode(', ', $langs) ?: '—') ?></td>
                        <td class="muted"><?= e(format_date($act['updated_at'])) ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn small" href="viewer.php?act=<?= (int) $act['id'] ?>">View</a>
                                <?php if ($act['versions'] > 1): ?><a class="btn small secondary" href="viewer.php?act=<?= (int) $act['id'] ?>&amp;compare=1">Compare</a><?php endif; ?>
                                <a class="btn small secondary" href="act.php?id=<?= (int) $act['id'] ?>">Details</a>
                                <?php if (is_admin()): ?>
                                    <form method="post" class="inline-form" data-confirm="Delete <?= e($act['act_number']) ?> and all <?= (int) $act['versions'] ?> of its versions? This cannot be undone.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_act">
                                        <input type="hidden" name="act_id" value="<?= (int) $act['id'] ?>">
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
    <?php endif; ?>
</section>

<?php if (is_admin()): ?>
<script>
    // Drag and drop onto the upload box fills the file input.
    const dropZone = document.getElementById('dropZone');
    const xmlFiles = document.getElementById('xmlFiles');
    ['dragenter', 'dragover'].forEach(ev => dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('dragover'); }));
    ['dragleave', 'drop'].forEach(ev => dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.remove('dragover'); }));
    dropZone.addEventListener('drop', e => { xmlFiles.files = e.dataTransfer.files; });
</script>
<?php endif;
page_end();
