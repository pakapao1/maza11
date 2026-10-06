<?php
require_once __DIR__ . '/../config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Aiven requires SSL. With mysqlnd/PDO MySQL this requests an encrypted
        // connection while allowing Aiven's managed certificate chain.
        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Put the real reason in Render Logs, but do not expose credentials
        // or server details to visitors.
        error_log(
            'Database connection failed: [' .
            $e->getCode() . '] ' . $e->getMessage()
        );

        http_response_code(500);
        exit(
            '<!doctype html><meta charset="utf-8"><title>Database error</title>' .
            '<div style="font-family:Segoe UI,sans-serif;max-width:560px;margin:60px auto;padding:20px;border:1px solid #f0c078;background:#fff4e5;border-radius:10px">' .
            '<h2 style="margin-top:0">Cannot connect to the database</h2>' .
            '<p>The database connection failed. Check the Render service logs for the exact reason.</p>' .
            '</div>'
        );
    }

    return $pdo;
}
