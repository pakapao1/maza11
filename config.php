<?php

// Database configuration.
// Render reads these values from Environment Variables.
// Local XAMPP falls back to the original local settings.

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int) (getenv('DB_PORT') ?: 3307));
define('DB_NAME', getenv('DB_NAME') ?: 'legislative_viewer');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Uploaded XML files are kept on disk; the database stores their details.
define('STORAGE_DIR', __DIR__ . '/storage/acts');
define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024);

define('APP_NAME', 'Legislative Viewer');
define('APP_VERSION', '6.0');
