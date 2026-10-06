<?php
require_once __DIR__ . '/../config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    try {
        if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('PDO MySQL driver (pdo_mysql) is not installed in this PHP image.');
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Aiven SSL support. DB_SSL_CA may contain either a file path or
        // the PEM certificate text copied from Aiven.
        $ca = defined('DB_SSL_CA') ? trim((string) DB_SSL_CA) : '';
        if ($ca !== '') {
            if (str_contains($ca, 'BEGIN CERTIFICATE')) {
                $caPath = sys_get_temp_dir() . '/aiven-ca.pem';
                if (file_put_contents($caPath, str_replace('\\n', "\n", $ca)) === false) {
                    throw new RuntimeException('Could not write the Aiven CA certificate to a temporary file.');
                }
            } else {
                $caPath = $ca;
            }

            if (!is_file($caPath)) {
                throw new RuntimeException('DB_SSL_CA is set, but the CA certificate file cannot be found.');
            }

            $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $pdo->query('SELECT 1');

        return $pdo;
    } catch (Throwable $e) {
        // Put the real reason in Render Logs, but do not expose credentials
        // or sensitive connection details to visitors.
        error_log('Database connection failed: ' . $e->getMessage());

        http_response_code(500);
        exit('<!doctype html><meta charset="utf-8"><title>Database error</title>'
            . '<div style="font-family:Segoe UI,sans-serif;max-width:560px;margin:60px auto;padding:20px;border:1px solid #f0c078;background:#fff4e5;border-radius:10px">'
            . '<h2 style="margin-top:0">Cannot connect to the database</h2>'
            . '<p>The database connection failed. Check the Render service logs for the exact reason.</p>'
            . '</div>');
    }
}
