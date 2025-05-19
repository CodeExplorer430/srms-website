<?php
/**
 * Admin Base Layout
 * 
 * This file serves as the base template for all admin pages.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

// FINAL SOLUTION: Dead-simple, bulletproof approach
// Get the site root directory (works anywhere in the site)
$site_root = $_SERVER['DOCUMENT_ROOT'] . '/srms-website';

// Is this script in the tools directory?
$is_in_tools = strpos($_SERVER['PHP_SELF'], '/admin/tools/') !== false; 

// Include config using absolute path
require_once $site_root . '/includes/config.php';

// Get additional parameters
$page_specific_css = isset($page_specific_css) ? $page_specific_css : [];
$page_specific_js = isset($page_specific_js) ? $page_specific_js : [];
$body_class = isset($body_class) ? $body_class : '';

// Count directory depth for tools
if ($is_in_tools) {
    $current_path = $_SERVER['PHP_SELF'];
    $tools_pos = strpos($current_path, '/admin/tools/');
    $subpath = substr($current_path, $tools_pos + strlen('/admin/tools/'));
    $slash_count = substr_count($subpath, '/');
    
    // For main tools directory: ../../assets
    // For subdirectories (content/, media/, etc): ../../../assets
    $assets_url = str_repeat('../', 2 + $slash_count) . 'assets';
} else {
    $assets_url = '../assets';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>SRMS Admin</title>
    
    <!-- Common CSS with explicit paths -->
    <link rel="stylesheet" href="<?php echo $assets_url; ?>/css/styles.css">
    <link rel="stylesheet" href="<?php echo $assets_url; ?>/css/admin-dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Page-specific CSS -->
    <?php if (!empty($page_specific_css)): ?>
        <?php foreach ($page_specific_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo $assets_url; ?>/images/branding/favicon.ico" type="image/x-icon">
</head>
<body class="<?php echo $body_class; ?>">
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner"></div>
    </div>

    <div class="admin-container">
        <!-- Include Sidebar with absolute path -->
        <?php include_once $site_root . '/admin/includes/admin-sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Include Header with absolute path -->
            <?php include_once $site_root . '/admin/includes/admin-header.php'; ?>
            
            <!-- Page content will be injected here -->
            <div class="page-content">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
    
    <!-- Page-specific JS -->
    <?php if (!empty($page_specific_js)): ?>
        <?php foreach ($page_specific_js as $js_file): ?>
            <script src="<?php echo $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // Preloader fade out
        document.addEventListener('DOMContentLoaded', function() {
            const preloader = document.querySelector('.preloader');
            if (preloader) {
                setTimeout(() => {
                    preloader.classList.add('fade-out');
                }, 300);
            }
        });
    </script>
</body>
</html>