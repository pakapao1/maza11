<?php
// Records viewer actions (currently comparisons) in the activity log.
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!current_user() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    exit(json_encode(['ok' => false]));
}
csrf_check();

$action = $_POST['action'] ?? '';
if (!in_array($action, ['compare'], true)) {
    http_response_code(400);
    exit(json_encode(['ok' => false]));
}
$actId = (int) ($_POST['act_id'] ?? 0);
$exists = db()->prepare('SELECT 1 FROM acts WHERE id = ?');
$exists->execute([$actId]);
log_activity($action, (string) ($_POST['details'] ?? ''), $exists->fetchColumn() ? $actId : null);
echo json_encode(['ok' => true]);
