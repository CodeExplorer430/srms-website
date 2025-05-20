<?php
/**
 * Admin Sidebar Component
 * This file provides a consistent sidebar across all admin pages
 * 
 * Updated to include improved tools section
 */

// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = dirname($_SERVER['PHP_SELF']);
$current_folder = basename($current_folder); 
$is_in_tools = (strpos($_SERVER['PHP_SELF'], '/admin/tools/') !== false);

// BULLETPROOF PATH CALCULATION
// Explicitly calculate the depth using full path analysis
$current_path = $_SERVER['PHP_SELF'];

// Debug the paths for troubleshooting
// echo "<!-- Current Path: " . $current_path . " -->";

// Simple absolute URL approach (most reliable)
$assets_path = '/srms-website/assets';
$admin_path = '/srms-website/admin/';

// If you need relative paths, use this more robust calculation
if (false) { // Disabled in favor of absolute paths
    if ($is_in_tools) {
        // Count slashes after /admin/tools/ to determine depth
        $path_parts = explode('/admin/tools/', $current_path);
        if (isset($path_parts[1])) {
            $subpath = $path_parts[1];
            $slash_count = substr_count($subpath, '/');
            
            // For main tools directory: ../../assets
            // For subdirectories (content/, media/, etc): ../../../assets
            $assets_path = str_repeat('../', 2 + $slash_count) . 'assets';
            $admin_path = str_repeat('../', 1 + $slash_count);
        } else {
            // Fallback for main tools directory
            $assets_path = '../../assets';
            $admin_path = '../';
        }
    } else {
        $assets_path = '../assets';
        $admin_path = '';
    }
}
?>
<?php
// Get school logo from database at the top of the file after path calculations

// First make sure we have the DB connection (include if needed)
if (!class_exists('Database')) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/srms-website/includes/db.php')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/srms-website/includes/db.php';
    }
}

// Get logo path with fallback
$school_logo = '/assets/images/branding/logo-primary.png'; // Default fallback

// Try to get from database if Database class is available
if (class_exists('Database')) {
    try {
        $db = new Database();
        $school_info = $db->fetch_row("SELECT logo FROM school_information LIMIT 1");
        if ($school_info && !empty($school_info['logo'])) {
            $school_logo = $school_info['logo'];
        }
    } catch (Exception $e) {
        // Silently fall back to default if error occurs
        error_log("Error fetching logo from database: " . $e->getMessage());
    }
}

// Make sure logo path starts with a slash
if (substr($school_logo, 0, 1) !== '/') {
    $school_logo = '/' . $school_logo;
}
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <!-- FIXED LOGO PATH: Uses absolute path -->
       <img src="<?php echo $assets_path . $school_logo; ?>" alt="St. Raphaela Mary School Logo" class="logo-img" onerror="this.src='<?php echo $assets_path; ?>/images/branding/logo-primary.png';">
        <h3 class="logo-text">SRMS Admin</h3>
        <button id="sidebar-toggle" class="sidebar-toggle">
            <i class='bx bx-chevron-left'></i>
        </button>
    </div>
    
    <div class="sidebar-menu">
        <ul class="menu-list">
            <li class="menu-item <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>index.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'pages-manage.php' || $current_page === 'page-edit.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>pages-manage.php">
                    <i class='bx bxs-file'></i>
                    <span class="menu-text">Pages</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'navigation-manage.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>navigation-manage.php">
                    <i class='bx bx-menu'></i>
                    <span class="menu-text">Navigation Menu</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'homepage-manage.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>homepage-manage.php">
                    <i class='bx bxs-home-circle'></i>
                    <span class="menu-text">Homepage</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'faculty-manage.php' || $current_page === 'faculty-edit.php' || $current_page === 'faculty-categories.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>faculty-manage.php">
                    <i class='bx bxs-user-detail'></i>
                    <span class="menu-text">Faculty</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'academics-manage.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>academics-manage.php">
                    <i class='bx bxs-graduation'></i>
                    <span class="menu-text">Academics</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'admissions-manage.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>admissions-manage.php">
                    <i class='bx bxs-user-plus'></i>
                    <span class="menu-text">Admissions</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'news-manage.php' || $current_page === 'news-edit.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>news-manage.php">
                    <i class='bx bxs-news'></i>
                    <span class="menu-text">News</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'contact-submissions.php' || $current_page === 'reply.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>contact-submissions.php">
                    <i class='bx bxs-message-detail'></i>
                    <span class="menu-text">Contact Submissions</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'media-manager.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>media-manager.php">
                    <i class='bx bx-images'></i>
                    <span class="menu-text">Media Library</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>users.php">
                    <i class='bx bxs-user'></i>
                    <span class="menu-text">Users</span>
                </a>
            </li>
            <li class="menu-item <?php echo $is_in_tools ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>tools/index.php">
                    <i class='bx bx-wrench'></i>
                    <span class="menu-text">Tools</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <a href="<?php echo $admin_path; ?>settings.php">
                    <i class='bx bxs-cog'></i>
                    <span class="menu-text">Settings</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <div class="admin-tools">
            <h4 class="tools-header">Quick Tools</h4>
            <a href="<?php echo $admin_path; ?>tools/maintenance/setup-directories.php" class="tool-item">
                <i class='bx bx-folder-plus'></i>
                <span class="menu-text">Setup Directories</span>
            </a>
            <a href="<?php echo $admin_path; ?>tools/media/upload-tester.php" class="tool-item">
                <i class='bx bx-upload'></i>
                <span class="menu-text">Upload Tester</span>
            </a>
            <a href="<?php echo $admin_path; ?>tools/system/environment-check.php" class="tool-item">
                <i class='bx bx-check-shield'></i>
                <span class="menu-text">System Check</span>
            </a>
            <a href="<?php echo $admin_path; ?>tools/maintenance/fix-paths.php" class="tool-item">
                <i class='bx bx-link-alt'></i>
                <span class="menu-text">Fix Paths</span>
            </a>
        </div>
        
        <div class="server-info">
            <div class="server-time">
                <i class='bx bx-time'></i>
                <span id="server-time" class="menu-text"></span>
            </div>
            <div class="server-type">
                <i class='bx bx-server'></i>
                <span class="menu-text"><?php echo defined('SERVER_TYPE') ? SERVER_TYPE : 'Server'; ?></span>
            </div>
        </div>
    </div>
</div>

<script>
    // Update server time
    function updateServerTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        const dateString = now.toLocaleDateString();
        document.getElementById('server-time').textContent = `${dateString} ${timeString}`;
    }
    
    // Call immediately and then update every second
    updateServerTime();
    setInterval(updateServerTime, 1000);
    
    // Sidebar toggle functionality
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.body.classList.toggle('sidebar-collapsed');
        
        // Save state to localStorage
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
        
        // Change the toggle icon
        const icon = this.querySelector('i');
        if (isCollapsed) {
            icon.classList.remove('bx-chevron-left');
            icon.classList.add('bx-chevron-right');
        } else {
            icon.classList.remove('bx-chevron-right');
            icon.classList.add('bx-chevron-left');
        }
    });
    
    // Check saved state on page load
    document.addEventListener('DOMContentLoaded', function() {
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed) {
            document.body.classList.add('sidebar-collapsed');
            const icon = document.querySelector('#sidebar-toggle i');
            icon.classList.remove('bx-chevron-left');
            icon.classList.add('bx-chevron-right');
        }
    });
</script>