<?php
// Include environment settings
require_once __DIR__ . '/../environment.php';

// App settings - only define if not already defined in environment.php
// Note: SITE_URL is now properly set in environment.php based on server type

if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'admin@srms.edu.ph');
}

// Additional admin-specific constants
if (!defined('ADMIN_SESSION_NAME')) {
    define('ADMIN_SESSION_NAME', 'SRMS_ADMIN_SESSION');
}

if (!defined('ADMIN_COOKIE_LIFETIME')) {
    define('ADMIN_COOKIE_LIFETIME', 3600); // 1 hour
}
?>