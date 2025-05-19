<?php
/**
 * Admin Header Component
 * This file provides a consistent header across all admin pages
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
        case 'upload-debug.php':
            return 'Upload Debug Tool';
        case 'mail-test.php':
            return 'Mail Test Tool';
        default:
            return 'Admin Dashboard';
    }
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = getPageTitle($current_page);

// Calculate the correct logout URL based on current path
$is_in_tools = strpos($_SERVER['PHP_SELF'], '/admin/tools/') !== false;

if ($is_in_tools) {
    $current_path = $_SERVER['PHP_SELF'];
    $tools_pos = strpos($current_path, '/admin/tools/');
    $subpath = substr($current_path, $tools_pos + strlen('/admin/tools/'));
    $slash_count = substr_count($subpath, '/');
    
    // For main tools directory: ../logout.php
    // For subdirectories (system/, media/, etc): ../../logout.php
    $logout_url = str_repeat('../', 1 + $slash_count) . 'logout.php';
} else {
    $logout_url = 'logout.php';
}
?>

<div class="top-bar">
    <div class="left-section">
        <!-- Mobile menu toggle button (visible only on mobile) -->
        <button class="mobile-menu-toggle" id="mobile-menu-toggle">
            <i class='bx bx-menu'></i>
        </button>
        
        <h2><?php echo $page_title; ?></h2>
    </div>
    
    <div class="user-info">
        <div class="current-date-time">
            <i class='bx bx-calendar'></i>
            <span id="current-date-time"></span>
        </div>
        
        <div class="name">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
        
        <a href="<?php echo $logout_url; ?>" class="logout-btn">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </div>
</div>

<script>
    // Update current date and time
    function updateDateTime() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        document.getElementById('current-date-time').textContent = now.toLocaleDateString('en-US', options);
    }
    
    // Call immediately and update every second
    updateDateTime();
    setInterval(updateDateTime, 1000);
    
    // Mobile menu toggle
    document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
        document.body.classList.toggle('sidebar-visible');
    });
</script>