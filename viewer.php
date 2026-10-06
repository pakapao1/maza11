<?php
require_once __DIR__ . '/includes/layout.php';
$user = require_login();

$acts = db()->query('SELECT a.id, a.act_number, a.title, COUNT(v.id) AS versions
    FROM acts a JOIN act_versions v ON v.act_id = a.id GROUP BY a.id ORDER BY a.act_number')->fetchAll();

$actId = (int) ($_GET['act'] ?? 0);
$current = null;
foreach ($acts as $a) if ((int) $a['id'] === $actId) $current = $a;
if ($actId && !$current) flash('error', 'That Act is not in the library.');
if ($current) log_activity('view', $current['versions'] . ' version(s)', $actId);

page_start($current ? $current['act_number'] . ' · Viewer' : 'Viewer', 'viewer'); ?>

<div class="page-header">
    <h1>Viewer</h1>
    <p>Read any version along the timeline, in English or Malay, and compare two versions section by section.</p>
</div>

<section class="card">
    <div class="source-bar">
        <form method="get" class="field" style="flex:1 1 320px">
            <label for="actPicker">Open an Act from the library</label>
            <div class="search-form">
                <select id="actPicker" name="act" class="search-input" style="flex:1" onchange="this.form.submit()">
                    <option value="">Choose an Act…</option>
                    <?php foreach ($acts as $a): ?>
                        <option value="<?= (int) $a['id'] ?>"<?= $current && $a['id'] == $current['id'] ? ' selected' : '' ?>>
                            <?= e($a['act_number']) ?> · <?= e(mb_strimwidth($a['title'], 0, 70, '…')) ?> (<?= (int) $a['versions'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="btn" type="submit">Open</button></noscript>
            </div>
        </form>
        <span class="or">or</span>
        <div class="field">
            <span>Open files from your computer</span>
            <button type="button" class="btn secondary" id="uploadArea">&#128193; Choose XML files</button>
            <input type="file" id="fileInput" hidden accept=".xml" multiple>
        </div>
        <div class="field">
            <span>&nbsp;</span>
            <button type="button" class="btn secondary" id="sampleBtn">Try a sample Act</button>
        </div>
    </div>
    <p class="muted" style="margin:10px 0 0">Files opened from your computer are only shown in your browser. <?= is_admin() ? 'To keep them, upload them in the <a href="library.php#upload">Act Library</a>.' : '' ?></p>
</section>

<div class="notice" id="notice" role="alert"></div>
<div class="act-summary" id="actSummary"></div>

<div class="timeline-container" id="timelineBox">
    <div class="timeline-bar">
        <div class="timeline-hint">Select a point on the timeline to view that version</div>
        <button type="button" class="compare-btn" id="compareBtn">Compare versions</button>
    </div>
    <div id="timelineTrack" class="timeline-track" role="list"></div>
</div>

<div id="actViewport" aria-live="polite"></div>

<?php if (!$current): ?>
    <div class="card empty" id="viewerEmpty">
        <?= $acts ? 'Choose an Act above to start reading.' : 'The library is empty. ' . (is_admin() ? '<a href="library.php#upload">Upload an Act</a> or try the sample.' : 'Try the sample Act.') ?>
    </div>
<?php endif; ?>

<script>
    window.LV = <?= json_encode([
        'actId' => $current ? (int) $current['id'] : null,
        'compare' => !empty($_GET['compare']),
        'csrf' => csrf_token(),
    ]) ?>;
</script>
<script src="assets/js/viewer.js"></script>
<?php page_end();
