<?php
// JSON list of an Act's versions, in timeline order, for the viewer.
require_once __DIR__ . '/../includes/acts.php';
header('Content-Type: application/json; charset=utf-8');

if (!current_user()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Not signed in.']));
}

$stmt = db()->prepare('SELECT v.id, v.file_name FROM act_versions v WHERE v.act_id = ? ORDER BY ' . VERSION_ORDER_SQL);
$stmt->execute([(int) ($_GET['act'] ?? 0)]);
echo json_encode(['versions' => $stmt->fetchAll()]);
