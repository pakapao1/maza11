<?php
// Database connection. XAMPP's MariaDB on this machine listens on port 3307
// (see C:\xampp\mysql\bin\my.ini). Change these if your setup differs.
const DB_HOST = '127.0.0.1';
const DB_PORT = 3307;
const DB_NAME = 'legislative_viewer';
const DB_USER = 'root';
const DB_PASS = '';

// Uploaded XML files are kept on disk; the database stores their details.
const STORAGE_DIR = __DIR__ . '/storage/acts';
const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;

const APP_NAME = 'Legislative Viewer';
const APP_VERSION = '6.0';
