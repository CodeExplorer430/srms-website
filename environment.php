<?php
/**
 * Enhanced environment detection and configuration for Hostinger
 * This version includes better error handling and logging
 */

// Detect operating system
define('IS_WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// Define OS-specific constants
define('DS', DIRECTORY_SEPARATOR);

// Enhanced Hostinger detection
function is_hostinger() {
    $hostinger_indicators = [
        strpos($_SERVER['HTTP_HOST'], 'hostingersite.com') !== false,
        strpos($_SERVER['HTTP_HOST'], 'hostinger.com') !== false,
        strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'LiteSpeed') !== false,
        strpos($_SERVER['DOCUMENT_ROOT'] ?? '', '/public_html') !== false
    ];
    
    return in_array(true, $hostinger_indicators);
}

// Detect server software with enhanced logic
function detect_server_type() {
    if (is_hostinger()) {
        return 'HOSTINGER';
    }
    
    // Default to XAMPP for local development
    $server_type = 'XAMPP';
    
    if (IS_WINDOWS) {
        // Check for WAMP-specific paths
        if (file_exists('C:/wamp/www') || file_exists('C:/wamp64/www') || 
            strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'wamp') !== false) {
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
try {
    if ($server_type === 'HOSTINGER') {
        // Hostinger configuration
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'u238599530_root');
        define('DB_PASSWORD', '$4R;G1b7Hs');
        define('DB_NAME', 'u238599530_srms_database');
        define('DB_PORT', '3306');
        
        // Hostinger paths
        define('UPLOAD_TMP_DIR', '/tmp');
        
        // Set site URL for Hostinger (no /srms-website suffix)
        define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}");
        
        // Hostinger-specific settings
        define('IS_PRODUCTION', true);
        define('MAX_FILE_SIZE', 8 * 1024 * 1024); // 8MB for Hostinger
        
    } elseif (IS_WINDOWS) {
        if ($server_type === 'WAMP') {
            // Windows (WAMP) configuration
            define('DB_SERVER', 'localhost');
            define('DB_USERNAME', 'root');
            define('DB_PORT', '3308');
            define('DB_PASSWORD', '');
            define('DB_NAME', 'srms_database');
            define('UPLOAD_TMP_DIR', 'C:/wamp/tmp');
        } else {
            // Windows (XAMPP) configuration
            define('DB_SERVER', 'localhost');
            define('DB_USERNAME', 'root');
            define('DB_PORT', '3306');
            define('DB_PASSWORD', '');
            define('DB_NAME', 'srms_database');
            define('UPLOAD_TMP_DIR', 'C:/xampp/tmp');
        }
        
        // Local development URL with /srms-website path
        define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/srms-website");
        define('IS_PRODUCTION', false);
        define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB for local
        
    } else {
        if ($server_type === 'LAMP') {
            // Linux (Traditional LAMP) configuration
            define('DB_SERVER', 'localhost');
            define('DB_USERNAME', 'root');
            define('DB_PORT', '3306');
            define('DB_PASSWORD', '');
            define('DB_NAME', 'srms_database');
            define('UPLOAD_TMP_DIR', '/tmp');
        } else {
            // Linux (XAMPP) configuration
            define('DB_SERVER', 'localhost');
            define('DB_USERNAME', 'root');
            define('DB_PORT', '3306');
            define('DB_PASSWORD', '');
            define('DB_NAME', 'srms_database');
            define('UPLOAD_TMP_DIR', '/opt/lampp/temp');
        }
        
        // Local development URL with /srms-website path
        define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/srms-website");
        define('IS_PRODUCTION', false);
        define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB for local
    }
    
} catch (Exception $e) {
    error_log("Environment configuration error: " . $e->getMessage());
    // Fallback configuration
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'srms_database');
    define('DB_PORT', '3306');
    define('SITE_URL', 'http://localhost');
}

// Common configurations
define('ADMIN_EMAIL', 'admin@srms.edu.ph');

// Set proper timezone
date_default_timezone_set('Asia/Manila');

// Additional information for debugging
define('SERVER_SOFTWARE', $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
define('PHP_VERSION_INFO', phpversion());
define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT'] ?? '');

// Automatically detect if we're on a development or production environment
define('IS_DEVELOPMENT', 
    !is_hostinger() && (
        (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) || 
        (strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) ||
        (strpos($_SERVER['HTTP_HOST'] ?? '', '.test') !== false) ||
        (strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false)
    )
);

// Configure error reporting based on environment
if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
    // Production settings (Hostinger)
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    
    // Set custom error log location for production
    $error_log_path = $_SERVER['DOCUMENT_ROOT'] . '/logs/error.log';
    if (is_dir(dirname($error_log_path)) || mkdir(dirname($error_log_path), 0755, true)) {
        ini_set('error_log', $error_log_path);
    }
} else {
    // Development settings
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Log environment details for debugging
$log_message = sprintf(
    "Environment initialized: OS=%s | Server=%s | Host=%s | PHP=%s",
    (IS_WINDOWS ? 'Windows' : 'Linux'),
    SERVER_TYPE,
    $_SERVER['HTTP_HOST'] ?? 'unknown',
    PHP_VERSION_INFO
);
error_log($log_message);

// Create necessary directories if they don't exist (for Hostinger)
if ($server_type === 'HOSTINGER') {
    $required_dirs = [
        $_SERVER['DOCUMENT_ROOT'] . '/logs',
        $_SERVER['DOCUMENT_ROOT'] . '/assets/images/uploads',
        $_SERVER['DOCUMENT_ROOT'] . '/assets/images/news',
        $_SERVER['DOCUMENT_ROOT'] . '/assets/images/events'
    ];
    
    foreach ($required_dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}
?>