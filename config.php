<?php

// Database settings.
// Render reads these from Environment Variables.
// Local XAMPP falls back to the old local settings.
function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env_value('DB_PORT', '3307'));
define('DB_NAME', env_value('DB_NAME', 'legislative_viewer'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));

// Optional. For Aiven, paste the CA certificate into a Render environment
// variable named DB_SSL_CA. Leave empty for local XAMPP.
define('DB_SSL_CA', env_value('DB_SSL_CA', ''));

// Uploaded XML files are kept on disk; the database stores their details.
define('STORAGE_DIR', __DIR__ . '/storage/acts');
define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024);

define('APP_NAME', 'Legislative Viewer');
define('APP_VERSION', '6.0');
