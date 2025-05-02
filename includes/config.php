<?php
// Include environment settings
require_once __DIR__ . '/../environment.php';

// App settings
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/srms-website");
define('ADMIN_EMAIL', 'admin@srms.edu.ph');
?>