<?php
/**
 * Admin Sidebar Component
 * This file provides a consistent sidebar across all admin pages
 */

// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/branding/logo-primary.png" alt="St. Raphaela Mary School Logo" class="logo-img">
        <h3 class="logo-text">SRMS Admin</h3>
        <button id="sidebar-toggle" class="sidebar-toggle">
            <i class='bx bx-chevron-left'></i>
        </button>
    </div>
    
    <div class="sidebar-menu">
        <ul class="menu-list">
            <li class="menu-item <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
                <a href="index.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'pages-manage.php' || $current_page === 'page-edit.php') ? 'active' : ''; ?>">
                <a href="pages-manage.php">
                    <i class='bx bxs-file'></i>
                    <span class="menu-text">Pages</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'homepage-manage.php') ? 'active' : ''; ?>">
                <a href="homepage-manage.php">
                    <i class='bx bxs-home-circle'></i>
                    <span class="menu-text">Homepage</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'faculty-manage.php' || $current_page === 'faculty-edit.php' || $current_page === 'faculty-categories.php') ? 'active' : ''; ?>">
                <a href="faculty-manage.php">
                    <i class='bx bxs-user-detail'></i>
                    <span class="menu-text">Faculty</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'academics-manage.php') ? 'active' : ''; ?>">
                <a href="academics-manage.php">
                    <i class='bx bxs-graduation'></i>
                    <span class="menu-text">Academics</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'admissions-manage.php') ? 'active' : ''; ?>">
                <a href="admissions-manage.php">
                    <i class='bx bxs-user-plus'></i>
                    <span class="menu-text">Admissions</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'news-manage.php' || $current_page === 'news-edit.php') ? 'active' : ''; ?>">
                <a href="news-manage.php">
                    <i class='bx bxs-news'></i>
                    <span class="menu-text">News</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'contact-submissions.php' || $current_page === 'reply.php') ? 'active' : ''; ?>">
                <a href="contact-submissions.php">
                    <i class='bx bxs-message-detail'></i>
                    <span class="menu-text">Contact Submissions</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'media-manager.php') ? 'active' : ''; ?>">
                <a href="media-manager.php">
                    <i class='bx bx-images'></i>
                    <span class="menu-text">Media Library</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>">
                <a href="users.php">
                    <i class='bx bxs-user'></i>
                    <span class="menu-text">Users</span>
                </a>
            </li>
            <li class="menu-item <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <a href="settings.php">
                    <i class='bx bxs-cog'></i>
                    <span class="menu-text">Settings</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <div class="admin-tools">
            <h4 class="tools-header">Admin Tools</h4>
            <a href="maintenance/setup-directories.php" class="tool-item">
                <i class='bx bx-folder-plus'></i>
                <span class="menu-text">Setup Directories</span>
            </a>
            <button id="open-media-library-btn" class="tool-item">
                <i class='bx bx-images'></i>
                <span class="menu-text">Media Library</span>
            </button>
            <a href="upload-debug.php" class="tool-item">
                <i class='bx bx-bug'></i>
                <span class="menu-text">Debug Tool</span>
            </a>
            <a href="mail-test.php" class="tool-item">
                <i class='bx bx-envelope'></i>
                <span class="menu-text">Mail Test</span>
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
        
        // Handle media library button if it exists
        const mediaLibraryBtn = document.getElementById('open-media-library-btn');
        const mediaLibraryModal = document.getElementById('media-library-modal');
        
        if (mediaLibraryBtn && mediaLibraryModal) {
            mediaLibraryBtn.addEventListener('click', function() {
                mediaLibraryModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        }
    });
</script>