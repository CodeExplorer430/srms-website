<?php
/**
 * Admin Header Component
 * Updated to work with existing environment configuration
 */

// Get current page title
function getPageTitle($current_page) {
    switch ($current_page) {
        case 'index.php':
            return 'Dashboard';
        case 'news-manage.php':
            return 'Manage News';
        case 'news-edit.php':
            return isset($_GET['id']) ? 'Edit News Article' : 'Add News Article';
        case 'contact-submissions.php':
            return 'Contact Submissions';
        case 'reply.php':
            return 'Reply to Contact';
        case 'media-manager.php':
            return 'Media Library';
        case 'users.php':
            return 'User Management';
        case 'settings.php':
            return 'School Settings';
        case 'faculty-manage.php':
            return 'Faculty Management';
        case 'faculty-edit.php':
            return isset($_GET['id']) ? 'Edit Faculty Member' : 'Add Faculty Member';
        case 'faculty-categories.php':
            return 'Faculty Categories';
        case 'academics-manage.php':
            return 'Academic Programs';
        case 'admissions-manage.php':
            return 'Admissions Management';
        case 'homepage-manage.php':
            return 'Homepage Elements';
        case 'pages-manage.php':
            return 'Page Management';
        case 'page-edit.php':
            return isset($_GET['id']) ? 'Edit Page' : 'Add Page';
        case 'navigation-manage.php':
            return 'Navigation Menu';
        default:
            return 'Admin Dashboard';
    }
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = getPageTitle($current_page);

// Calculate logout URL based on environment
function calculate_logout_url() {
    $current_path = $_SERVER['PHP_SELF'];
    $is_in_tools = strpos($current_path, '/admin/tools/') !== false;
    
    if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
        // For Hostinger production, use absolute admin path
        return '/admin/logout.php';
    }
    
    // For development, calculate relative path
    if ($is_in_tools) {
        $tools_pos = strpos($current_path, '/admin/tools/');
        $subpath = substr($current_path, $tools_pos + strlen('/admin/tools/'));
        $slash_count = substr_count($subpath, '/');
        
        return str_repeat('../', 1 + $slash_count) . 'logout.php';
    } else {
        return 'logout.php';
    }
}

$logout_url = calculate_logout_url();

// Get username with fallback
$username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin';
?>

<div class="top-bar">
    <div class="left-section">
        <!-- Mobile menu toggle button (visible only on mobile) -->
        <button class="mobile-menu-toggle" id="mobile-menu-toggle">
            <i class='bx bx-menu'></i>
        </button>
        
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
        
        <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT): ?>
        <!-- Development environment indicator -->
        <div class="env-badge">
            <i class='bx bx-code-alt'></i>
            <span>DEV</span>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="user-info">
        <div class="current-date-time">
            <i class='bx bx-calendar'></i>
            <span id="current-date-time"></span>
        </div>
        
        <div class="user-details">
            <div class="name">Welcome, <?php echo htmlspecialchars($username); ?></div>
            <?php if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT): ?>
            <div class="server-info">
                <small><?php echo defined('SERVER_TYPE') ? SERVER_TYPE : 'Unknown'; ?></small>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="user-actions">
            <!-- Quick actions dropdown -->
            <div class="dropdown">
                <button class="dropdown-toggle" id="quick-actions-toggle">
                    <i class='bx bx-grid-alt'></i>
                </button>
                <div class="dropdown-menu" id="quick-actions-menu">
                    <a href="<?php echo defined('SITE_URL') ? SITE_URL : '/'; ?>" class="dropdown-item" target="_blank">
                        <i class='bx bx-link-external'></i> View Website
                    </a>
                    <a href="<?php echo calculate_admin_path('media-manager.php'); ?>" class="dropdown-item">
                        <i class='bx bx-images'></i> Media Library
                    </a>
                    <a href="<?php echo calculate_admin_path('settings.php'); ?>" class="dropdown-item">
                        <i class='bx bx-cog'></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo $logout_url; ?>" class="dropdown-item text-danger">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </div>
            </div>
            
            <a href="<?php echo $logout_url; ?>" class="logout-btn">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </div>
    </div>
</div>

<?php
// Helper function to calculate admin paths
function calculate_admin_path($page) {
    if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
        return '/admin/' . $page;
    }
    
    $current_path = $_SERVER['PHP_SELF'];
    $is_in_tools = strpos($current_path, '/admin/tools/') !== false;
    
    if ($is_in_tools) {
        $tools_pos = strpos($current_path, '/admin/tools/');
        $subpath = substr($current_path, $tools_pos + strlen('/admin/tools/'));
        $slash_count = substr_count($subpath, '/');
        
        return str_repeat('../', 1 + $slash_count) . $page;
    } else {
        return $page;
    }
}
?>

<style>
/* Enhanced header styles */
.env-badge {
    display: inline-flex;
    align-items: center;
    background-color: #ff6b6b;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    margin-left: 10px;
}

.env-badge i {
    margin-right: 4px;
    font-size: 12px;
}

.user-details {
    text-align: right;
    margin-right: 15px;
}

.user-details .name {
    font-weight: 600;
    color: var(--text-color);
}

.user-details .server-info {
    color: #6c757d;
    font-size: 11px;
    margin-top: 2px;
}

.user-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.dropdown {
    position: relative;
}

.dropdown-toggle {
    background: none;
    border: none;
    color: var(--text-color);
    font-size: 20px;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.dropdown-toggle:hover {
    background-color: #f8f9fa;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    min-width: 200px;
    z-index: 1000;
    display: none;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: var(--text-color);
    text-decoration: none;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item i {
    margin-right: 10px;
    width: 16px;
}

.dropdown-item.text-danger {
    color: var(--danger-color);
}

.dropdown-divider {
    height: 1px;
    background-color: #dee2e6;
    margin: 5px 0;
}

@media (max-width: 768px) {
    .env-badge {
        display: none;
    }
    
    .user-details .server-info {
        display: none;
    }
    
    .dropdown-toggle {
        display: none;
    }
}
</style>

<script>
    // Update current date and time
    function updateDateTime() {
        const now = new Date();
        const options = { 
            weekday: 'short', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        };
        
        const dateTimeElement = document.getElementById('current-date-time');
        if (dateTimeElement) {
            dateTimeElement.textContent = now.toLocaleDateString('en-US', options);
        }
    }
    
    // Call immediately and update every minute
    updateDateTime();
    setInterval(updateDateTime, 60000);
    
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-visible');
        });
    }
    
    // Quick actions dropdown
    const quickActionsToggle = document.getElementById('quick-actions-toggle');
    const quickActionsMenu = document.getElementById('quick-actions-menu');
    
    if (quickActionsToggle && quickActionsMenu) {
        quickActionsToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            quickActionsMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            quickActionsMenu.classList.remove('show');
        });
        
        // Prevent dropdown from closing when clicking inside
        quickActionsMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Environment-specific console logging
    if (window.SRMS_CONFIG && window.SRMS_CONFIG.IS_DEVELOPMENT) {
        console.log('SRMS Admin Header loaded');
        console.log('Current page:', '<?php echo $current_page; ?>');
        console.log('Logout URL:', '<?php echo $logout_url; ?>');
    }
</script>