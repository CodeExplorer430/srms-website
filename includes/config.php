<?php
// Include environment settings
require_once __DIR__ . '/../environment.php';

// App settings - only define if not already defined
if (!defined('SITE_URL')) {
    define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/srms-website");
}

if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'admin@srms.edu.ph');
}
?>