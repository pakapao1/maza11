<?php
require_once __DIR__ . '/db.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        flash('error', 'Only administrators can open that page.');
        header('Location: index.php');
        exit;
    }
    return $user;
}

// ---------- CSRF protection for every form that changes data ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

// ---------- One-time messages shown at the top of the next page ----------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function log_activity(string $action, string $details = '', ?int $actId = null): void
{
    $stmt = db()->prepare('INSERT INTO activity_log (user_id, action, act_id, details) VALUES (?, ?, ?, ?)');
    $stmt->execute([current_user()['id'] ?? null, $action, $actId, mb_substr($details, 0, 255)]);
}
