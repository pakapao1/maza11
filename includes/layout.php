<?php
require_once __DIR__ . '/auth.php';

const FAVICON = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%237a4f9e'/%3E%3Cpath d='M6 22h20' stroke='%23ffafcc' stroke-width='2.5' stroke-linecap='round'/%3E%3Ccircle cx='9' cy='22' r='3' fill='%23ffafcc'/%3E%3Ccircle cx='16' cy='22' r='3' fill='%23ffafcc'/%3E%3Ccircle cx='23' cy='22' r='3' fill='%23fff' stroke='%23ffafcc' stroke-width='2'/%3E%3Cpath d='M9 7h14M9 11h14M9 15h9' stroke='%23fff' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E";

// Shared <head>: theme is applied before the page paints so dark mode never flashes white.
function page_head(string $title): void
{ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
    <link rel="icon" href="<?= FAVICON ?>">
    <script>
        try {
            var saved = localStorage.getItem("lv-theme");
            var dark = saved ? saved === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
            document.documentElement.setAttribute("data-theme", dark ? "dark" : "light");
        } catch (e) {}
    </script>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<?php }

function page_start(string $title, string $active): void
{
    $user = current_user();
    $menu = [
        'home'    => ['index.php', 'Home'],
        'library' => ['library.php', 'Act Library'],
        'viewer'  => ['viewer.php', 'Viewer'],
        'guide'   => ['guide.php', 'User Guide'],
        'about'   => ['about.php', 'About'],
    ];
    if (is_admin()) $menu['users'] = ['users.php', 'Users'];
    page_head($title); ?>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<nav class="navbar">
    <div class="nav-inner">
        <a class="brand" href="index.php">
            <img src="<?= FAVICON ?>" alt="" width="28" height="28">
            <span><?= e(APP_NAME) ?></span>
        </a>
        <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navMenu">&#9776; Menu</button>
        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <?php foreach ($menu as $key => [$href, $label]): ?>
                    <li><a href="<?= $href ?>"<?= $key === $active ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="nav-user">
                <a href="profile.php" class="nav-profile<?= $active === 'profile' ? ' active' : '' ?>" title="My profile">
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
                    <span><?= e($user['full_name']) ?> <small class="role-tag"><?= e($user['role']) ?></small></span>
                </a>
                <button type="button" class="theme-btn" id="themeBtn" aria-pressed="false"></button>
                <form method="post" action="logout.php" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="theme-btn">Log out</button>
                </form>
            </div>
        </div>
    </div>
</nav>
<div class="container" id="main">
    <?php foreach (take_flashes() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
    <?php endforeach;
}

function page_end(): void
{ ?>
    <footer class="site-footer">
        <span><?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?> &middot; Internship Individual Project, UiTM</span>
        <span><a href="guide.php">User Guide</a> &middot; <a href="about.php">About</a></span>
    </footer>
</div>
<button type="button" class="to-top" id="toTop" aria-label="Back to top" title="Back to top">&#8593;</button>
<script src="assets/js/app.js"></script>
</body>
</html>
<?php }

// Small helpers used by several pages.
function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    return max(1, round($bytes / 1024)) . ' KB';
}

function format_date(?string $datetime): string
{
    return $datetime ? date('j M Y, g:i a', strtotime($datetime)) : '—';
}

function action_label(string $action): string
{
    return [
        'login' => 'Signed in', 'upload' => 'Uploaded', 'view' => 'Viewed', 'compare' => 'Compared',
        'delete_act' => 'Deleted Act', 'delete_version' => 'Deleted version',
        'user_add' => 'Added user', 'user_update' => 'Updated user', 'user_delete' => 'Deleted user',
        'password' => 'Changed password',
    ][$action] ?? ucfirst($action);
}
