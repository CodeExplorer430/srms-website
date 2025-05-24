<?php
/**
 * Admin Sidebar Component
 * Updated to work with existing environment configuration
 */

// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = dirname($_SERVER['PHP_SELF']);
$current_folder = basename($current_folder); 
$is_in_tools = (strpos($_SERVER['PHP_SELF'], '/admin/tools/') !== false);

// Calculate paths based on environment
function calculate_paths() {
    global $is_in_tools;
    
    if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
        // For Hostinger production, use absolute paths
        return [
            'assets_path' => '/assets',
            'admin_path' => '/admin/'
        ];
    }
    
    // For development, calculate relative paths
    if ($is_in_tools) {
        $current_path = $_SERVER['PHP_SELF'];
        $path_parts = explode('/admin/tools/', $current_path);
        if (isset($path_parts[1])) {
            $subpath = $path_parts[1];
            $slash_count = substr_count($subpath, '/');
            
            $assets_path = str_repeat('../', 2 + $slash_count) . 'assets';
            $admin_path = str_repeat('../', 1 + $slash_count);
        } else {
            $assets_path = '../../assets';
            $admin_path = '../';
        }
    } else {
        $assets_path = '../assets';
        $admin_path = '';
    }
    
    return [
        'assets_path' => $assets_path,
        'admin_path' => $admin_path
    ];
}

$paths = calculate_paths();
$assets_path = $paths['assets_path'];
$admin_path = $paths['admin_path'];

// Get school logo with error handling
$school_logo = '/images/branding/logo-primary.png'; // Default fallback

// Try to get from database if available
if (class_exists('Database')) {
    try {
        $db = new Database();
        $school_info = $db->fetch_row("SELECT logo FROM school_information LIMIT 1");
        if ($school_info && !empty($school_info['logo'])) {
            $school_logo = $school_info['logo'];
        }
    } catch (Exception $e) {
        error_log("Error fetching logo from database: " . $e->getMessage());
    }
}

// Normalize logo path
if (substr($school_logo, 0, 1) !== '/') {
    $school_logo = '/' . $school_logo;
}

// Build final logo URL
$logo_url = $assets_path . $school_logo;
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo htmlspecialchars($logo_url); ?>" 
             alt="St. Raphaela Mary School Logo" 
             class="logo-img" 
             onerror="this.onerror=null; this.src='<?php echo $assets_path; ?>/images/branding/logo-primary.png';">
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
            
            <?php $tools_base = $admin_path . 'tools/'; ?>
            
            <a href="<?php echo $tools_base; ?>maintenance/setup-directories.php" class="tool-item">
                <i class='bx bx-folder-plus'></i>
                <span class="menu-text">Setup Directories</span>
            </a>
            <a href="<?php echo $tools_base; ?>media/upload-tester.php" class="tool-item">
                <i class='bx bx-upload'></i>
                <span class="menu-text">Upload Tester</span>
            </a>
            <a href="<?php echo $tools_base; ?>system/environment-check.php" class="tool-item">
                <i class='bx bx-check-shield'></i>
                <span class="menu-text">System Check</span>
            </a>
            <a href="<?php echo $tools_base; ?>maintenance/fix-paths.php" class="tool-item">
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
            <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT): ?>
            <div class="env-indicator">
                <i class='bx bx-code-alt'></i>
                <span class="menu-text" style="color: #ff6b6b;">DEV</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Update server time
    function updateServerTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour12: false,
            hour: '2-digit',
            minute: '2-digit'
        });
        const dateString = now.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
        
        const timeElement = document.getElementById('server-time');
        if (timeElement) {
            timeElement.textContent = `${dateString} ${timeString}`;
        }
    }
    
    // Call immediately and then update every minute
    updateServerTime();
    setInterval(updateServerTime, 60000);
    
    // Sidebar toggle functionality
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
            
            // Save state to localStorage
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            try {
                localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
            } catch (e) {
                console.warn('Could not save sidebar state:', e);
            }
            
            // Change the toggle icon
            const icon = this.querySelector('i');
            if (icon) {
                if (isCollapsed) {
                    icon.classList.remove('bx-chevron-left');
                    icon.classList.add('bx-chevron-right');
                } else {
                    icon.classList.remove('bx-chevron-right');
                    icon.classList.add('bx-chevron-left');
                }
            }
        });
    }
    
    // Check saved state on page load
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            if (isCollapsed) {
                document.body.classList.add('sidebar-collapsed');
                const icon = document.querySelector('#sidebar-toggle i');
                if (icon) {
                    icon.classList.remove('bx-chevron-left');
                    icon.classList.add('bx-chevron-right');
                }
            }
        } catch (e) {
            console.warn('Could not load sidebar state:', e);
        }
    });
</script>