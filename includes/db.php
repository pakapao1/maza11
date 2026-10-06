<?php
require_once __DIR__ . '/../config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        $hint = $e->getCode() == 1049
            ? 'The database has not been created yet. Run <a href="install.php">install.php</a> first.'
            : 'Check that MySQL is running in the XAMPP Control Panel and that the settings in config.php are correct.';
        exit('<!doctype html><meta charset="utf-8"><title>Database error</title>'
            . '<div style="font-family:Segoe UI,sans-serif;max-width:560px;margin:60px auto;padding:20px;border:1px solid #f0c078;background:#fff4e5;border-radius:10px">'
            . '<h2 style="margin-top:0">Cannot connect to the database</h2><p>' . $hint . '</p></div>');
    }
    return $pdo;
}
