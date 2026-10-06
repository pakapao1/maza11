<?php
// Streams one stored XML file to a signed-in user. The storage folder itself is not web-accessible.
require_once __DIR__ . '/../includes/auth.php';

if (!current_user()) {
    http_response_code(401);
    exit('Not signed in.');
}

$stmt = db()->prepare('SELECT act_id, file_name, stored_name FROM act_versions WHERE id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$version = $stmt->fetch();
$path = $version ? STORAGE_DIR . '/' . $version['act_id'] . '/' . basename($version['stored_name']) : '';
if (!$version || !is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Type: text/xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
$disposition = isset($_GET['download']) ? 'attachment' : 'inline';
header("Content-Disposition: $disposition; filename=\"" . str_replace('"', '', $version['file_name']) . '"');
readfile($path);
