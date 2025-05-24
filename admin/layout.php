<?php
/**
 * Admin Base Layout
 * 
 * This file serves as the base template for all admin pages.
 * Updated to work with existing environment.php configuration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . (defined('IS_PRODUCTION') && IS_PRODUCTION ? '/admin/login.php' : '../admin/login.php'));
    exit;
}

// Include config using safe path resolution
function get_config_path() {
    $possible_paths = [
        __DIR__ . '/../includes/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php',
        dirname(__FILE__) . '/../includes/config.php'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return $possible_paths[0]; // Return first as fallback
}

require_once get_config_path();

// Get additional parameters
$page_specific_css = isset($page_specific_css) ? $page_specific_css : [];
$page_specific_js = isset($page_specific_js) ? $page_specific_js : [];
$body_class = isset($body_class) ? $body_class : '';

// Determine asset paths based on environment
$is_in_tools = strpos($_SERVER['PHP_SELF'], '/admin/tools/') !== false;

function calculate_assets_path() {
    global $is_in_tools;
    
    if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
        // For Hostinger production, use absolute paths
        return '/assets';
    }
    
    // For development, calculate relative paths
    if ($is_in_tools) {
        $current_path = $_SERVER['PHP_SELF'];
        $tools_pos = strpos($current_path, '/admin/tools/');
        $subpath = substr($current_path, $tools_pos + strlen('/admin/tools/'));
        $slash_count = substr_count($subpath, '/');
        
        return str_repeat('../', 2 + $slash_count) . 'assets';
    } else {
        return '../assets';
    }
}

$assets_url = calculate_assets_path();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>SRMS Admin</title>
    
    <!-- Common CSS -->
    <link rel="stylesheet" href="<?php echo $assets_url; ?>/css/styles.css">
    <link rel="stylesheet" href="<?php echo $assets_url; ?>/css/admin-dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Page-specific CSS -->
    <?php if (!empty($page_specific_css)): ?>
        <?php foreach ($page_specific_css as $css_file): ?>
            <?php 
            if (strpos($css_file, 'http') === 0 || strpos($css_file, '//') === 0) {
                echo '<link rel="stylesheet" href="' . $css_file . '">' . "\n";
            } else {
                $css_path = (strpos($css_file, '../') === 0) ? $css_file : $assets_url . '/' . ltrim($css_file, '/');
                echo '<link rel="stylesheet" href="' . $css_path . '">' . "\n";
            }
            ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo $assets_url; ?>/images/branding/favicon.ico" type="image/x-icon">
    
    <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT): ?>
    <style>
        body::before {
            content: "DEV: <?php echo defined('SERVER_TYPE') ? SERVER_TYPE : 'Unknown'; ?>";
            position: fixed;
            top: 0;
            right: 0;
            background: #ff6b6b;
            color: white;
            padding: 2px 8px;
            font-size: 10px;
            z-index: 10000;
            border-radius: 0 0 0 4px;
        }
    </style>
    <?php endif; ?>
</head>
<body class="<?php echo $body_class; ?>">
    <!-- Preloader -->
    <div class="preloader">
        <div class="spinner"></div>
    </div>

    <div class="admin-container">
        <!-- Include Sidebar -->
        <?php 
        $sidebar_paths = [
            __DIR__ . '/includes/admin-sidebar.php',
            dirname(__FILE__) . '/includes/admin-sidebar.php'
        ];
        
        $sidebar_included = false;
        foreach ($sidebar_paths as $sidebar_path) {
            if (file_exists($sidebar_path)) {
                include_once $sidebar_path;
                $sidebar_included = true;
                break;
            }
        }
        
        if (!$sidebar_included) {
            echo "<!-- Sidebar file not found -->";
        }
        ?>
        
        <div class="main-content">
            <!-- Include Header -->
            <?php 
            $header_paths = [
                __DIR__ . '/includes/admin-header.php',
                dirname(__FILE__) . '/includes/admin-header.php'
            ];
            
            $header_included = false;
            foreach ($header_paths as $header_path) {
                if (file_exists($header_path)) {
                    include_once $header_path;
                    $header_included = true;
                    break;
                }
            }
            
            if (!$header_included) {
                echo "<!-- Header file not found -->";
            }
            ?>
            
            <!-- Page content will be injected here -->
            <div class="page-content">
                <?php 
                if (isset($content)) {
                    echo $content; 
                } else {
                    echo '<p>Content not available</p>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Common JavaScript -->
    <script>
        // Set global variables for JavaScript
        window.SRMS_CONFIG = {
            IS_DEVELOPMENT: <?php echo (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) ? 'true' : 'false'; ?>,
            IS_PRODUCTION: <?php echo (defined('IS_PRODUCTION') && IS_PRODUCTION) ? 'true' : 'false'; ?>,
            SITE_URL: '<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>',
            ASSETS_URL: '<?php echo $assets_url; ?>',
            SERVER_TYPE: '<?php echo defined('SERVER_TYPE') ? SERVER_TYPE : 'Unknown'; ?>'
        };
    </script>
    
    <!-- Page-specific JS -->
    <?php if (!empty($page_specific_js)): ?>
        <?php foreach ($page_specific_js as $js_file): ?>
            <?php 
            if (strpos($js_file, 'http') === 0 || strpos($js_file, '//') === 0) {
                echo '<script src="' . $js_file . '"></script>' . "\n";
            } else {
                $js_path = (strpos($js_file, '../') === 0) ? $js_file : $assets_url . '/' . ltrim($js_file, '/');
                echo '<script src="' . $js_path . '"></script>' . "\n";
            }
            ?>
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
            
            // Development environment console info
            if (window.SRMS_CONFIG.IS_DEVELOPMENT) {
                console.log('SRMS Admin Panel - Development Mode');
                console.log('Server Type:', window.SRMS_CONFIG.SERVER_TYPE);
                console.log('Site URL:', window.SRMS_CONFIG.SITE_URL);
                console.log('Assets URL:', window.SRMS_CONFIG.ASSETS_URL);
            }
        });
        
        <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT): ?>
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
        });
        <?php endif; ?>
    </script>
</body>
</html>