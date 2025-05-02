<?php
// Create as environment.php in the root directory

// Detect the operating system
define('IS_WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// Define OS-specific constants
define('DS', DIRECTORY_SEPARATOR); // Will be \ on Windows, / on Linux

// Set environment-specific database configurations
if (IS_WINDOWS) {
    // Windows (WAMP) configuration
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PORT', '3308'); 
    define('DB_PASSWORD', '');
    define('DB_NAME', 'srms_database');
    
    // Windows-specific paths
    define('UPLOAD_TMP_DIR', 'C:/wamp/tmp');
} else {
    // Linux (XAMPP) configuration
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PORT', '3306'); // Default MySQL port on Linux XAMPP
    define('DB_PASSWORD', '');
    define('DB_NAME', 'srms_database');
    
    // Linux-specific paths
    define('UPLOAD_TMP_DIR', '/opt/lampp/temp');
}

// Common configurations
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/srms-website");
define('ADMIN_EMAIL', 'admin@srms.edu.ph');
// Set proper timezone
date_default_timezone_set('Asia/Manila');
?>