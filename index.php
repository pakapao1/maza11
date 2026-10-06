<?php
require_once __DIR__ . '/includes/layout.php';
$user = require_login();
$pdo = db();

$stats = [
    'Acts in library'   => $pdo->query('SELECT COUNT(*) FROM acts')->fetchColumn(),
    'Versions stored'   => $pdo->query('SELECT COUNT(*) FROM act_versions')->fetchColumn(),
    'Comparisons made'  => $pdo->query("SELECT COUNT(*) FROM activity_log WHERE action = 'compare'")->fetchColumn(),
    'Registered users'  => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
];

$recentActs = $pdo->query('SELECT a.id, a.act_number, a.title, a.updated_at, COUNT(v.id) AS versions
    FROM acts a LEFT JOIN act_versions v ON v.act_id = a.id
    GROUP BY a.id ORDER BY a.updated_at DESC LIMIT 5')->fetchAll();

// Admins see everyone's activity; viewers see their own.
$sql = 'SELECT l.action, l.details, l.created_at, l.act_id, u.full_name, a.act_number
    FROM activity_log l LEFT JOIN users u ON u.id = l.user_id LEFT JOIN acts a ON a.id = l.act_id';
if (!is_admin()) $sql .= ' WHERE l.user_id = ' . (int) $user['id'];
$activity = $pdo->query($sql . ' ORDER BY l.id DESC LIMIT 8')->fetchAll();

// Feature banners. Each one links to the page where that feature lives.
$firstActId = $recentActs ? (int) $recentActs[0]['id'] : 0;
$features = [
    ['library', 'Act Version Library',
        'Store every revision of an Act in one place, uploaded from tagged XML and kept with its full history.',
        'library.php', 'Browse the library'],
    ['timeline', 'Interactive Version Timeline',
        'Move through an Act\'s revisions year by year and see which amending laws each version cites.',
        $firstActId ? "viewer.php?act=$firstActId" : 'viewer.php', 'Open the timeline'],
    ['bilingual', 'Bilingual Reading View',
        'Read any version in English or Bahasa Melayu, organised by Part, section and Schedule, with contents and highlighted search.',
        $firstActId ? "viewer.php?act=$firstActId" : 'viewer.php', 'Start reading'],
    ['compare', 'Section-by-Section Comparison',
        'Compare two versions automatically. Added, removed and modified sections are flagged, with changed wording highlighted.',
        $firstActId ? "viewer.php?act=$firstActId&compare=1" : 'viewer.php', 'Compare versions'],
    ['secure', 'Secure Role-Based Access',
        'Administrator and viewer accounts, hashed passwords, and an activity log that records uploads, views and comparisons.',
        is_admin() ? 'users.php' : 'profile.php', is_admin() ? 'Manage users' : 'My profile'],
];

// Small illustrations for the banners. Colours come from the .art-* classes in site.css.
function feature_art(string $key): string
{
    $svg = [
        'library' => '
            <rect class="art-thistle" x="18" y="62" width="84" height="16" rx="3"/>
            <rect class="art-sky" x="24" y="46" width="76" height="16" rx="3"/>
            <rect class="art-pink" x="14" y="30" width="80" height="16" rx="3"/>
            <path class="art-stroke" d="M22 38h40M32 54h36M26 70h50"/>
            <circle class="art-white art-ring" cx="92" cy="26" r="12"/>
            <path class="art-stroke" d="M101 35l9 9"/>
            <path class="art-stroke thin" d="M88 26h8M92 22v8"/>',
        'timeline' => '
            <path class="art-stroke faint" d="M10 52h100"/>
            <circle class="art-sky" cx="20" cy="52" r="7"/>
            <circle class="art-sky" cx="45" cy="52" r="7"/>
            <circle class="art-sky" cx="70" cy="52" r="7"/>
            <circle class="art-white art-ring" cx="98" cy="52" r="10"/>
            <circle class="art-pink" cx="98" cy="52" r="5"/>
            <rect class="art-thistle" x="8" y="20" width="24" height="12" rx="3"/>
            <rect class="art-thistle" x="58" y="20" width="24" height="12" rx="3"/>
            <rect class="art-pink" x="84" y="16" width="28" height="16" rx="3"/>
            <rect class="art-icy" x="30" y="72" width="30" height="10" rx="3"/>',
        'bilingual' => '
            <path class="art-white art-ring" d="M60 26c-14-8-30-8-44-2v56c14-6 30-6 44 2z"/>
            <path class="art-white art-ring" d="M60 26c14-8 30-8 44-2v56c-14-6-30-6-44 2z"/>
            <path class="art-stroke thin" d="M24 38h28M24 48h28M24 58h22M68 38h28M68 48h28M68 58h22"/>
            <rect class="art-sky" x="16" y="8" width="26" height="14" rx="4"/>
            <rect class="art-pink" x="78" y="8" width="26" height="14" rx="4"/>
            <text class="art-label" x="29" y="18.5">EN</text>
            <text class="art-label" x="91" y="18.5">BM</text>',
        'compare' => '
            <rect class="art-white art-ring" x="10" y="14" width="44" height="66" rx="5"/>
            <rect class="art-white art-ring" x="66" y="14" width="44" height="66" rx="5"/>
            <path class="art-stroke thin" d="M18 26h28M18 36h28M74 26h28M74 36h28M18 66h28M74 66h28"/>
            <rect class="art-del" x="17" y="45" width="30" height="8" rx="2"/>
            <rect class="art-ins" x="73" y="45" width="30" height="8" rx="2"/>
            <path class="art-stroke" d="M56 42l6 6-6 6"/>',
        'secure' => '
            <path class="art-thistle art-ring" d="M60 8l38 14v24c0 24-16 40-38 48-22-8-38-24-38-48V22z"/>
            <rect class="art-pink" x="44" y="46" width="32" height="26" rx="5"/>
            <path class="art-stroke" d="M50 46v-8a10 10 0 0 1 20 0v8"/>
            <circle class="art-plum" cx="60" cy="57" r="3.5"/>
            <path class="art-stroke thin" d="M60 60v6"/>',
    ][$key] ?? '';
    return '<svg class="feature-art" viewBox="0 0 120 96" aria-hidden="true" focusable="false">' . $svg . '</svg>';
}

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

page_start('Home', 'home'); ?>

<section class="hero">
    <div class="hero-fx" aria-hidden="true">
        <div class="hero-dots"></div>
        <!-- Three versions of an Act stacked over a timeline -->
        <svg class="hero-art" viewBox="0 0 340 220" focusable="false">
            <g class="hc-shadow" transform="translate(150 14) rotate(8)">
                <rect class="hc-card" width="170" height="118" rx="14"/>
                <rect class="hc-head" x="16" y="16" width="60" height="9" rx="4.5"/>
                <path class="hc-line" d="M16 44h130M16 60h110M16 76h124"/>
            </g>
            <g class="hc-shadow" transform="translate(112 26) rotate(-5)">
                <rect class="hc-card" width="180" height="124" rx="14"/>
                <rect class="hc-head" x="16" y="16" width="64" height="9" rx="4.5"/>
                <rect class="hc-mark-b" x="12" y="53" width="120" height="14" rx="4"/>
                <path class="hc-line" d="M16 44h140M16 60h110M16 76h130M16 92h90"/>
            </g>
            <g class="hc-shadow" transform="translate(60 40)">
                <rect class="hc-card" width="196" height="132" rx="14"/>
                <rect class="hc-head" x="18" y="18" width="70" height="10" rx="5"/>
                <rect class="hc-pill" x="144" y="16" width="34" height="14" rx="7"/>
                <rect class="hc-mark" x="14" y="59" width="132" height="15" rx="4"/>
                <path class="hc-line" d="M18 50h160M18 66h122M18 82h146M18 98h104M18 114h130"/>
            </g>
            <path class="hc-track" d="M40 202h270"/>
            <circle class="hc-dot" cx="64" cy="202" r="6"/>
            <circle class="hc-dot" cx="132" cy="202" r="6"/>
            <circle class="hc-dot" cx="200" cy="202" r="6"/>
            <circle class="hc-dot-on" cx="276" cy="202" r="8"/>
        </svg>
    </div>
    <h1><?= e($greeting) ?>, <?= e($user['full_name']) ?></h1>
    <p>Legislative Viewer keeps every version of an Act in one place. Read any version in English or Malay, follow it along a timeline and see exactly which sections changed between versions.</p>
    <div class="btn-row">
        <a class="btn" href="library.php">Browse the Act Library</a>
        <a class="btn secondary" href="viewer.php">Open the Viewer</a>
        <a class="btn secondary" href="guide.php">How it works</a>
    </div>
</section>

<div class="stats">
    <?php foreach ($stats as $label => $value): ?>
        <div class="stat"><div class="value"><?= (int) $value ?></div><div class="label"><?= e($label) ?></div></div>
    <?php endforeach; ?>
</div>

<section class="features" aria-labelledby="featuresTitle">
    <h2 id="featuresTitle" class="features-title">What you can do</h2>
    <div class="feature-grid">
        <?php foreach ($features as [$key, $title, $text, $href, $cta]): ?>
            <a class="feature-banner feature-<?= $key ?>" href="<?= e($href) ?>">
                <div class="feature-copy">
                    <span class="feature-brand"><img src="<?= FAVICON ?>" alt="" width="18" height="18"> <?= e(APP_NAME) ?></span>
                    <h3><?= e($title) ?></h3>
                    <p><?= e($text) ?></p>
                    <span class="feature-cta"><?= e($cta) ?> &rarr;</span>
                </div>
                <?= feature_art($key) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<div class="grid-2">
    <section class="card">
        <div class="card-head">
            <h2>Recently updated Acts</h2>
            <a href="library.php" class="muted">View all</a>
        </div>
        <?php if (!$recentActs): ?>
            <div class="empty">
                No Acts in the library yet.
                <?php if (is_admin()): ?><br><a href="library.php#upload">Upload the first one</a><?php endif; ?>
            </div>
        <?php else: ?>
            <ul class="feed">
                <?php foreach ($recentActs as $act): ?>
                    <li>
                        <div>
                            <a href="act.php?id=<?= (int) $act['id'] ?>"><b><?= e($act['act_number']) ?></b></a> <?= e($act['title']) ?>
                            <div class="muted"><?= (int) $act['versions'] ?> version<?= $act['versions'] == 1 ? '' : 's' ?></div>
                        </div>
                        <a class="btn small secondary" style="margin-left:auto;align-self:center" href="viewer.php?act=<?= (int) $act['id'] ?>">Open</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-head"><h2><?= is_admin() ? 'Recent activity' : 'My recent activity' ?></h2></div>
        <?php if (!$activity): ?>
            <div class="empty">No activity yet.</div>
        <?php else: ?>
            <ul class="feed">
                <?php foreach ($activity as $row): ?>
                    <li>
                        <div>
                            <?php if (is_admin()): ?><b><?= e($row['full_name'] ?? 'Deleted user') ?></b> &middot; <?php endif; ?>
                            <?= e(action_label($row['action'])) ?>
                            <?php if ($row['act_number']): ?><a href="act.php?id=<?= (int) $row['act_id'] ?>"><?= e($row['act_number']) ?></a><?php endif; ?>
                            <?php if ($row['details']): ?><span class="muted"><?= e($row['details']) ?></span><?php endif; ?>
                        </div>
                        <span class="when"><?= e(format_date($row['created_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php page_end();
