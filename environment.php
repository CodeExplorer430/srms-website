<?php
// Enhanced environment detection and configuration

// Detect operating system
define('IS_WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// Define OS-specific constants
define('DS', DIRECTORY_SEPARATOR); // Will be \ on Windows, / on Linux

// Detect server software (XAMPP vs WAMP vs LAMP)
function detect_server_type() {
    // Default to XAMPP
    $server_type = 'XAMPP';
    
    if (IS_WINDOWS) {
        // Check for WAMP-specific paths
        if (file_exists('C:/wamp/www') || file_exists('C:/wamp64/www') || 
            strpos($_SERVER['DOCUMENT_ROOT'], 'wamp') !== false) {
            $server_type = 'WAMP';
        }
    } else {
        // On Linux, check for LAMP-specific configuration
        if (file_exists('/etc/apache2/sites-available') && !file_exists('/opt/lampp')) {
            $server_type = 'LAMP';
        }
    }
    
    return $server_type;
}

// Get server type
$server_type = detect_server_type();
define('SERVER_TYPE', $server_type);

// Set environment-specific database configurations
if (IS_WINDOWS) {
    if ($server_type === 'WAMP') {
        // Windows (WAMP) configuration
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PORT', '3308'); // Common WAMP MySQL port
        define('DB_PASSWORD', '');
        define('DB_NAME', 'srms_database');
        
        // WAMP-specific paths
        define('UPLOAD_TMP_DIR', 'C:/wamp/tmp');
    } else {
        // Windows (XAMPP) configuration
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PORT', '3306'); // Default XAMPP MySQL port
        define('DB_PASSWORD', '');
        define('DB_NAME', 'srms_database');
        
        // XAMPP-specific paths
        define('UPLOAD_TMP_DIR', 'C:/xampp/tmp');
    }
} else {
    if ($server_type === 'LAMP') {
        // Linux (Traditional LAMP) configuration
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PORT', '3306');
        define('DB_PASSWORD', '');
        define('DB_NAME', 'srms_database');
        
        // LAMP-specific paths
        define('UPLOAD_TMP_DIR', '/tmp');
    } else {
        // Linux (XAMPP) configuration
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PORT', '3306');
        define('DB_PASSWORD', '');
        define('DB_NAME', 'srms_database');
        
        // Linux XAMPP-specific paths
        define('UPLOAD_TMP_DIR', '/opt/lampp/temp');
    }
}

// Common configurations
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/srms-website");
define('ADMIN_EMAIL', 'admin@srms.edu.ph');

// Set proper timezone
date_default_timezone_set('Asia/Manila');

// Additional information for debugging
define('SERVER_SOFTWARE', $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
define('PHP_VERSION_INFO', phpversion());
define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

// Automatically detect if we're on a development or production environment
define('IS_DEVELOPMENT', 
    (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) || 
    (strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) ||
    (strpos($_SERVER['HTTP_HOST'], '.test') !== false) ||
    (strpos($_SERVER['HTTP_HOST'], '.local') !== false)
);

// Log environment details for debugging if in development mode
if (IS_DEVELOPMENT) {
    error_log("Environment: " . (IS_WINDOWS ? 'Windows' : 'Linux') . " | Server: " . SERVER_TYPE);
}
?>