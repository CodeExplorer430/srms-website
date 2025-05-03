<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php';
$db = new Database();

// Get media statistics
$media_counts = [
    'total' => 0,
    'news' => 0,
    'events' => 0,
    'promotional' => 0,
    'facilities' => 0,
    'campus' => 0
];

// Initialize status variables
$delete_success = false;
$delete_error = null;

// Count images in each directory
$media_directories = [
    'news' => '/assets/images/news',
    'events' => '/assets/images/events',
    'promotional' => '/assets/images/promotional',
    'facilities' => '/assets/images/facilities',
    'campus' => '/assets/images/campus'
];

foreach ($media_directories as $key => $dir) {
    // Normalize directory path for cross-platform compatibility
    $dir_path = str_replace(['\\', '/'], DS, $dir);
    $path = $_SERVER['DOCUMENT_ROOT'] . $dir_path;
    
    if (is_dir($path)) {
        // Use a platform-neutral pattern for globbing
        $pattern = $path . DS . "*.{jpg,jpeg,png,gif}";
        $files = glob($pattern, GLOB_BRACE);
        $count = count($files);
        $media_counts[$key] = $count;
        $media_counts['total'] += $count;
    }
}

// Handle file deletion if requested
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    $file_path = urldecode($_GET['file']);
    // Normalize file path for cross-platform compatibility
    $file_path = str_replace(['\\', '/'], DS, $file_path);
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
    
    // Verify path is within allowed directories
    $is_allowed = false;
    foreach ($media_directories as $dir) {
        $normalized_dir = str_replace(['\\', '/'], DS, $dir);
        if (strpos($file_path, $normalized_dir) === 0) {
            $is_allowed = true;
            break;
        }
    }
    
    if ($is_allowed && file_exists($full_path) && is_file($full_path)) {
        if (unlink($full_path)) {
            $delete_success = true;
        } else {
            $delete_error = 'Failed to delete the file. Check file permissions.';
        }
    } else {
        $delete_error = 'Invalid file path or file not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Manager | SRMS Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/media-modal-fixes.css">
    <link rel="stylesheet" href="../assets/css/media-library.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f3f3;
            margin: 0;
            padding: 0;
        }
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #0a3060;
            color: #fff;
            padding: 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            background-color: #072548;
            margin-bottom: 10px;
        }
        .sidebar .logo img {
            width: 70px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.2);
        }
        .sidebar .logo h3 {
            margin: 10px 0 0;
            font-size: 18px;
            font-weight: 600;
        }
        .sidebar .menu {
            margin-top: 20px;
        }
        .sidebar .menu-item {
            padding: 0;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        .sidebar .menu-item a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar .menu-item:hover, 
        .sidebar .menu-item.active {
            background-color: rgba(255,255,255,0.1);
            border-left-color: #3C91E6;
        }
        .sidebar .menu-item:hover a, 
        .sidebar .menu-item.active a {
            color: #fff;
        }
        .sidebar .menu-item i {
            margin-right: 10px;
            font-size: 20px;
        }
        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f8f9fa;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .top-bar h2 {
            color: #0a3060;
            margin: 0;
            font-weight: 600;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-info .name {
            margin-right: 15px;
            font-weight: 500;
            color: #495057;
        }
        .logout-btn {
            background-color: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
        }
        .logout-btn i {
            margin-right: 5px;
        }
        .logout-btn:hover {
            background-color: #dc3545;
            color: white;
        }
        
        /* Media Manager Specific Styles */
        .media-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.total {
            background-color: #3C91E6;
        }
        
        .stat-icon.news {
            background-color: #0d6efd;
        }
        
        .stat-icon.events {
            background-color: #198754;
        }
        
        .stat-icon.promotional {
            background-color: #dc3545;
        }
        
        .stat-icon.facilities {
            background-color: #fd7e14;
        }
        
        .stat-icon.campus {
            background-color: #6f42c1;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .action-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-button {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            color: #0a3060;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .action-button i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .action-button.primary {
            background-color: #3C91E6;
            color: white;
        }
        
        .action-button.success {
            background-color: #198754;
            color: white;
        }
        
        .action-button.danger {
            background-color: #dc3545;
            color: white;
        }
        
        .action-button.warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .category-section {
            margin-bottom: 30px;
        }
        
        .category-header {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .category-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #0a3060;
            display: flex;
            align-items: center;
        }
        
        .category-title i {
            margin-right: 10px;
        }
        
        .category-actions {
            display: flex;
            gap: 10px;
        }
        
        .category-action {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            border: none;
            transition: all 0.2s;
        }
        
        .category-action i {
            margin-right: 5px;
        }
        
        .category-action.primary {
            background-color: rgba(60, 145, 230, 0.1);
            color: #3C91E6;
        }
        
        .category-action.primary:hover {
            background-color: rgba(60, 145, 230, 0.2);
        }
        
        .category-action.success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .category-action.success:hover {
            background-color: rgba(25, 135, 84, 0.2);
        }
        
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            padding: 20px;
            background-color: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .media-item {
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.2s;
            position: relative;
        }
        
        .media-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .media-thumbnail {
            position: relative;
            padding-top: 100%;
            background-color: #f8f9fa;
        }
        
        .media-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .media-info {
            padding: 10px;
            background-color: white;
        }
        
        .media-name {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .media-path {
            font-size: 11px;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .media-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .media-item:hover .media-actions {
            opacity: 1;
        }
        
        .media-action {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a3060;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 16px;
            border: none;
        }
        
        .media-action:hover {
            background-color: white;
            transform: scale(1.1);
        }
        
        .media-action.delete {
            color: #dc3545;
        }
        
        .media-action.view {
            color: #3C91E6;
        }
        
        .media-action.copy {
            color: #198754;
        }
        
        .empty-state {
            padding: 50px 0;
            text-align: center;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 15px;
            font-size: 24px;
        }
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
            border-left: 4px solid #198754;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }
        
        .alert-message {
            flex: 1;
        }
        
        .alert-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        /* Media Viewer Modal */
        .media-viewer-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1001;
            overflow: hidden;
        }
        
        .media-viewer-content {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .media-viewer-header {
            padding: 15px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .media-viewer-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 80%;
        }
        
        .media-viewer-close {
            font-size: 28px;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
        }
        
        .media-viewer-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .media-viewer-image {
            max-width: 90%;
            max-height: 80vh;
            object-fit: contain;
        }
        
        .media-viewer-details {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 20px;
        }
        
        .media-viewer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .media-viewer-path {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .media-viewer-actions {
            display: flex;
            gap: 15px;
        }
        
        .media-viewer-action {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }
        
        .media-viewer-action i {
            margin-right: 8px;
        }
        
        .media-viewer-action:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .media-stats {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
            
            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            }
            
            .category-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .category-actions {
                margin-top: 10px;
            }
            
            .action-bar {
                flex-direction: column;
            }
            
            .action-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">
                <img src="../assets/images/branding/logo-primary.png" alt="St. Raphaela Mary School Logo">
                <h3>SRMS Admin</h3>
            </div>
            
            <div class="menu">
                <div class="menu-item">
                    <a href="index.php">
                        <i class='bx bxs-dashboard'></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="news-manage.php">
                        <i class='bx bxs-news'></i>
                        <span>News</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="contact-submissions.php">
                        <i class='bx bxs-message-detail'></i>
                        <span>Contact Submissions</span>
                    </a>
                </div>
                <div class="menu-item active">
                    <a href="media-manager.php">
                        <i class='bx bx-images'></i>
                        <span>Media Library</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="users.php">
                        <i class='bx bxs-user'></i>
                        <span>Users</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="settings.php">
                        <i class='bx bxs-cog'></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>Media Library</h2>
                <div class="user-info">
                    <div class="name">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
                    <a href="logout.php" class="logout-btn">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </div>
            </div>
            
            <?php if ($delete_success): ?>
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i>
                <div class="alert-message">
                    <div class="alert-title">Success!</div>
                    <p>File has been deleted successfully.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($delete_error): ?>
            <div class="alert alert-danger">
            <i class='bx bx-error-circle'></i>
                <div class="alert-message">
                    <div class="alert-title">Error!</div>
                    <p><?php echo $delete_error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="media-stats">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class='bx bx-images'></i>
                    </div>
                    <div class="stat-number"><?php echo $media_counts['total']; ?></div>
                    <div class="stat-label">Total Media Files</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon news">
                        <i class='bx bxs-news'></i>
                    </div>
                    <div class="stat-number"><?php echo $media_counts['news']; ?></div>
                    <div class="stat-label">News Images</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon events">
                        <i class='bx bx-calendar-event'></i>
                    </div>
                    <div class="stat-number"><?php echo $media_counts['events']; ?></div>
                    <div class="stat-label">Event Images</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon promotional">
                        <i class='bx bx-bullhorn'></i>
                    </div>
                    <div class="stat-number"><?php echo $media_counts['promotional']; ?></div>
                    <div class="stat-label">Promotional Images</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon facilities">
                        <i class='bx bx-building-house'></i>
                    </div>
                    <div class="stat-number"><?php echo $media_counts['facilities']; ?></div>
                    <div class="stat-label">Facility Images</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon campus">
                        <i class='bx bx-landscape'></i>
                    </div>
                    <div class="stat-number"><?php echo $media_counts['campus']; ?></div>
                    <div class="stat-label">Campus Images</div>
                </div>
            </div>
            
            <div class="action-bar">
                <button type="button" class="action-button primary" id="upload-media-btn">
                    <i class='bx bx-upload'></i> Upload New Media
                </button>
                
                <a href="maintenance/setup-directories.php" class="action-button">
                    <i class='bx bx-folder-plus'></i> Setup Directories
                </a>
                
                <button type="button" class="action-button" id="open-media-library-btn">
                    <i class='bx bx-search'></i> Browse Media Library
                </button>

                <button type="button" class="action-button success" id="bulk-upload-btn">
                    <i class='bx bx-upload-multiple'></i> Bulk Upload
                </button>
            </div>
            
            <?php
            // Generate media sections by category
            foreach ($media_directories as $category => $dir):
                // Normalize directory path for cross-platform compatibility
                $dir_path = str_replace(['\\', '/'], DS, $dir);
                $path = $_SERVER['DOCUMENT_ROOT'] . $dir_path;
                
                if (is_dir($path)):
                    // Use a platform-neutral pattern for globbing
                    $pattern = $path . DS . "*.{jpg,jpeg,png,gif}";
                    $files = glob($pattern, GLOB_BRACE);
                    
                    // Sort files by modification time (newest first)
                    usort($files, function($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    
                    // Get category icon
                    $icon_class = 'bx-image';
                    switch ($category) {
                        case 'news':
                            $icon_class = 'bxs-news';
                            break;
                        case 'events':
                            $icon_class = 'bx-calendar-event';
                            break;
                        case 'promotional':
                            $icon_class = 'bx-bullhorn';
                            break;
                        case 'facilities':
                            $icon_class = 'bx-building-house';
                            break;
                        case 'campus':
                            $icon_class = 'bx-landscape';
                            break;
                    }
            ?>
            <div class="category-section">
                <div class="category-header">
                    <h3 class="category-title">
                        <i class='bx <?php echo $icon_class; ?>'></i> <?php echo ucfirst($category); ?> Images
                    </h3>
                    <div class="category-actions">
                        <button type="button" class="category-action success" onclick="uploadToCategory('<?php echo $category; ?>')">
                            <i class='bx bx-upload'></i> Upload to <?php echo ucfirst($category); ?>
                        </button>
                    </div>
                </div>
                
                <div class="media-grid">
                    <?php if (empty($files)): ?>
                    <div class="empty-state" style="grid-column: 1 / -1">
                        <i class='bx bx-image'></i>
                        <p>No images found in the <?php echo ucfirst($category); ?> category.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($files as $file): 
                            $file_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
                            $file_name = basename($file);
                            $file_time = filemtime($file);
                        ?>
                        <div class="media-item">
                            <div class="media-thumbnail">
                                <img src="<?php echo $file_path; ?>" alt="<?php echo htmlspecialchars($file_name); ?>">
                                <div class="media-actions">
                                    <button type="button" class="media-action view" onclick="viewMedia('<?php echo htmlspecialchars(addslashes($file_path)); ?>', '<?php echo htmlspecialchars(addslashes($file_name)); ?>')">
                                        <i class='bx bx-fullscreen'></i>
                                    </button>
                                    <button type="button" class="media-action copy" onclick="copyPath('<?php echo htmlspecialchars(addslashes($file_path)); ?>')">
                                        <i class='bx bx-copy'></i>
                                    </button>
                                    <button type="button" class="media-action delete" onclick="confirmDelete('<?php echo htmlspecialchars(addslashes($file_path)); ?>')">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </div>
                            <div class="media-info">
                                <div class="media-name" title="<?php echo htmlspecialchars($file_name); ?>"><?php echo htmlspecialchars($file_name); ?></div>
                                <div class="media-path" title="<?php echo $file_path; ?>"><?php echo date('M j, Y', $file_time); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    </div>
    
    <!-- Media Upload Modal -->
    <div id="upload-modal" class="media-library-modal">
        <div class="media-library-content" style="max-width: 600px; height: auto;">
            <div class="media-library-header">
                <h2><i class='bx bx-upload'></i> Upload Media</h2>
                <span class="media-library-close" id="close-upload-modal">&times;</span>
            </div>
            <div class="section-body" style="padding: 20px;">
                <form id="media-upload-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="upload-file">Select Image File</label>
                        <input type="file" id="upload-file" name="quick_upload" accept="image/jpeg, image/png, image/gif" required>
                        <small style="color: #6c757d;">Accepted formats: JPG, PNG, GIF. Max size: 2MB</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="upload-category">Category</label>
                        <select id="upload-category" name="quick_category" required>
                            <option value="news">News</option>
                            <option value="events">Events</option>
                            <option value="promotional">Promotional</option>
                            <option value="facilities">Facilities</option>
                            <option value="campus">Campus</option>
                        </select>
                    </div>
                    
                    <div class="upload-preview" style="margin: 20px 0; display: none;">
                        <h4>Preview</h4>
                        <div class="image-preview" style="width: 100%; height: 200px; background-color: #f8f9fa; border-radius: 4px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="" alt="Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </div>
                    </div>
                    
                    <div style="text-align: right;">
                        <button type="button" class="cancel-btn" id="cancel-upload" style="background-color: #f8f9fa; border: 1px solid #ddd; color: #495057; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Cancel</button>
                        <button type="submit" class="save-btn" style="background-color: #3C91E6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Upload File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Media Viewer Modal -->
    <div id="media-viewer-modal" class="media-viewer-modal">
        <div class="media-viewer-content">
            <div class="media-viewer-header">
                <h3 class="media-viewer-title" id="viewer-title">Image Title</h3>
                <button type="button" class="media-viewer-close" id="close-media-viewer">&times;</button>
            </div>
            <div class="media-viewer-body">
                <img src="" alt="Image Preview" class="media-viewer-image" id="viewer-image">
            </div>
            <div class="media-viewer-details">
                <div class="media-viewer-info">
                    <div class="media-viewer-path" id="viewer-path">/path/to/image.jpg</div>
                </div>
                <div class="media-viewer-actions">
                    <button type="button" class="media-viewer-action" id="copy-path-btn">
                        <i class='bx bx-copy'></i> Copy Path
                    </button>
                    <button type="button" class="media-viewer-action" id="view-full-btn">
                        <i class='bx bx-link-external'></i> View Full Size
                    </button>
                    <button type="button" class="media-viewer-action" id="delete-btn">
                        <i class='bx bx-trash'></i> Delete Image
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Media Library Modal -->
    <?php include_once 'includes/media-library.php'; ?>
    <?php render_media_library('dummy-field'); ?>
    <input type="hidden" id="dummy-field" name="dummy-field" value="">

    <script src="../assets/js/media-library.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Upload Modal Functionality
            const uploadModal = document.getElementById('upload-modal');
            const uploadBtn = document.getElementById('upload-media-btn');
            const closeUploadModal = document.getElementById('close-upload-modal');
            const cancelUpload = document.getElementById('cancel-upload');
            const uploadForm = document.getElementById('media-upload-form');
            const uploadFile = document.getElementById('upload-file');
            const uploadCategory = document.getElementById('upload-category');
            const uploadPreview = document.querySelector('.upload-preview');
            const previewImg = uploadPreview.querySelector('img');
            
            // Open Upload Modal
            uploadBtn.addEventListener('click', function() {
                uploadModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
            
            // Close Upload Modal
            function closeUploadModalFn() {
                uploadModal.style.display = 'none';
                document.body.style.overflow = '';
                uploadForm.reset();
                uploadPreview.style.display = 'none';
            }
            
            closeUploadModal.addEventListener('click', closeUploadModalFn);
            cancelUpload.addEventListener('click', closeUploadModalFn);
            uploadModal.addEventListener('click', function(e) {
                if (e.target === uploadModal) {
                    closeUploadModalFn();
                }
            });
            
            // Preview uploaded image
            uploadFile.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        uploadPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    uploadPreview.style.display = 'none';
                }
            });
            
            // Handle form submission
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                
                // Show loading state
                submitButton.textContent = 'Uploading...';
                submitButton.disabled = true;
                
                // Send AJAX request
                fetch('ajax/upload-media.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Check if response is OK
                    if (!response.ok) {
                        throw new Error(`Server responded with status: ${response.status}`);
                    }
                    
                    // Get content type to verify we're receiving JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        // If it's not JSON, get the text and log it for debugging
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Expected JSON response but received: ' + (text.substring(0, 100) + '...'));
                        });
                    }
                    
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message and close modal
                        alert('File uploaded successfully!');
                        closeUploadModalFn();
                        // Reload page to reflect changes
                        window.location.reload();
                    } else {
                        alert('Upload failed: ' + data.message);
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Upload failed: ' + error.message);
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                });
            });
            
            // Media Viewer Modal
            const viewerModal = document.getElementById('media-viewer-modal');
            const viewerTitle = document.getElementById('viewer-title');
            const viewerImage = document.getElementById('viewer-image');
            const viewerPath = document.getElementById('viewer-path');
            const closeViewer = document.getElementById('close-media-viewer');
            const copyPathBtn = document.getElementById('copy-path-btn');
            const viewFullBtn = document.getElementById('view-full-btn');
            const deleteBtn = document.getElementById('delete-btn');
            
            // Close viewer
            function closeViewerFn() {
                viewerModal.style.display = 'none';
                document.body.style.overflow = '';
            }
            
            closeViewer.addEventListener('click', closeViewerFn);
            viewerModal.addEventListener('click', function(e) {
                if (e.target === viewerModal) {
                    closeViewerFn();
                }
            });
            
            // Copy path button
            copyPathBtn.addEventListener('click', function() {
                const path = viewerPath.textContent;
                navigator.clipboard.writeText(path).then(() => {
                    alert('Image path copied to clipboard!');
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                });
            });
            
            // View full size button
            viewFullBtn.addEventListener('click', function() {
                window.open(viewerImage.src, '_blank');
            });
            
            // Delete button
            deleteBtn.addEventListener('click', function() {
                const path = viewerPath.textContent;
                if (confirm('Are you sure you want to delete this image?')) {
                    window.location.href = 'media-manager.php?action=delete&file=' + encodeURIComponent(path);
                }
            });
            
            // Open Media Library from sidebar
            const openMediaLibraryBtn = document.getElementById('open-media-library-btn');
            const mediaLibraryModal = document.getElementById('media-library-modal');
            
            if (openMediaLibraryBtn && mediaLibraryModal) {
                openMediaLibraryBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    mediaLibraryModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
            }
        });
        
        // Function to upload to specific category
        function uploadToCategory(category) {
            const uploadModal = document.getElementById('upload-modal');
            const uploadCategory = document.getElementById('upload-category');
            
            uploadCategory.value = category;
            uploadModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Function to view media in modal
        function viewMedia(path, name) {
            const viewerModal = document.getElementById('media-viewer-modal');
            const viewerTitle = document.getElementById('viewer-title');
            const viewerImage = document.getElementById('viewer-image');
            const viewerPath = document.getElementById('viewer-path');
            
            viewerTitle.textContent = name;
            viewerImage.src = path;
            viewerPath.textContent = path;
            
            viewerModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Function to copy image path
        function copyPath(path) {
            navigator.clipboard.writeText(path).then(() => {
                alert('Image path copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
        
        // Function to confirm and delete image
        function confirmDelete(path) {
            if (confirm('Are you sure you want to delete this image?')) {
                window.location.href = 'media-manager.php?action=delete&file=' + encodeURIComponent(path);
            }
        }
    </script>

    <!-- Bulk Upload Modal -->
<div id="bulk-upload-modal" class="media-library-modal">
    <div class="media-library-content" style="max-width: 700px; height: auto;">
        <div class="media-library-header">
            <h2><i class='bx bx-upload'></i> Bulk Upload Media</h2>
            <span class="media-library-close" id="close-bulk-upload-modal">&times;</span>
        </div>
        <div class="section-body" style="padding: 20px;">
            <form id="bulk-upload-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="bulk-upload-files">Select Multiple Image Files</label>
                    <input type="file" id="bulk-upload-files" name="bulk_files[]" accept="image/jpeg, image/png, image/gif" multiple required>
                    <small style="color: #6c757d;">Accepted formats: JPG, PNG, GIF. Max size per file: 2MB</small>
                </div>
                
                <div class="form-group">
                    <label for="bulk-upload-category">Target Category</label>
                    <select id="bulk-upload-category" name="bulk_category" required>
                        <option value="news">News</option>
                        <option value="events">Events</option>
                        <option value="promotional">Promotional</option>
                        <option value="facilities">Facilities</option>
                        <option value="campus">Campus</option>
                    </select>
                </div>
                
                <div class="bulk-preview" style="margin: 20px 0; display: none;">
                    <h4>Files to Upload (<span id="file-count">0</span>)</h4>
                    <div class="file-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px;">
                        <!-- File preview items will be added here dynamically -->
                    </div>
                </div>
                
                <div id="upload-progress" style="display: none; margin-bottom: 20px;">
                    <h4>Upload Progress</h4>
                    <div class="progress" style="height: 20px; background-color: #e9ecef; border-radius: 4px; overflow: hidden;">
                        <div class="progress-bar" style="height: 100%; background-color: #3C91E6; width: 0%; transition: width 0.3s; color: white; text-align: center; line-height: 20px;"></div>
                    </div>
                    <div class="progress-text" style="margin-top: 5px; text-align: center; font-size: 12px;">0%</div>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="cancel-btn" id="cancel-bulk-upload" style="background-color: #f8f9fa; border: 1px solid #ddd; color: #495057; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Cancel</button>
                    <button type="submit" class="save-btn" style="background-color: #3C91E6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Upload Files</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/media-library.js"></script>
<script src="../assets/js/media-modal.js"></script>
<script>
// Add to existing script in media-manager.php
document.addEventListener('DOMContentLoaded', function() {
    // Bulk Upload Modal Functionality
    const bulkUploadModal = document.getElementById('bulk-upload-modal');
    const bulkUploadBtn = document.getElementById('bulk-upload-btn');
    const closeBulkUploadModal = document.getElementById('close-bulk-upload-modal');
    const cancelBulkUpload = document.getElementById('cancel-bulk-upload');
    const bulkUploadForm = document.getElementById('bulk-upload-form');
    const bulkUploadFiles = document.getElementById('bulk-upload-files');
    const bulkUploadCategory = document.getElementById('bulk-upload-category');
    const bulkPreview = document.querySelector('.bulk-preview');
    const fileList = document.querySelector('.file-list');
    const fileCount = document.getElementById('file-count');
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.querySelector('.progress-text');
    
    // Open Bulk Upload Modal
    if (bulkUploadBtn) {
        bulkUploadBtn.addEventListener('click', function() {
            bulkUploadModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close Bulk Upload Modal
    function closeBulkUploadModalFn() {
        bulkUploadModal.style.display = 'none';
        document.body.style.overflow = '';
        bulkUploadForm.reset();
        bulkPreview.style.display = 'none';
        fileList.innerHTML = '';
        fileCount.textContent = '0';
        uploadProgress.style.display = 'none';
        progressBar.style.width = '0%';
        progressText.textContent = '0%';
    }
    
    if (closeBulkUploadModal) {
        closeBulkUploadModal.addEventListener('click', closeBulkUploadModalFn);
    }
    
    if (cancelBulkUpload) {
        cancelBulkUpload.addEventListener('click', closeBulkUploadModalFn);
    }
    
    if (bulkUploadModal) {
        bulkUploadModal.addEventListener('click', function(e) {
            if (e.target === bulkUploadModal) {
                closeBulkUploadModalFn();
            }
        });
    }
    
    // Preview selected files
    if (bulkUploadFiles) {
        bulkUploadFiles.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                bulkPreview.style.display = 'block';
                fileList.innerHTML = '';
                fileCount.textContent = this.files.length;
                
                // Display preview for each file
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'file-item';
                        fileItem.style.display = 'flex';
                        fileItem.style.alignItems = 'center';
                        fileItem.style.marginBottom = '10px';
                        fileItem.style.padding = '5px';
                        fileItem.style.borderBottom = '1px solid #dee2e6';
                        
                        fileItem.innerHTML = `
                            <img src="${e.target.result}" alt="${file.name}" style="width: 40px; height: 40px; object-fit: cover; margin-right: 10px; border-radius: 4px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 500; font-size: 14px;">${file.name}</div>
                                <div style="color: #6c757d; font-size: 12px;">${(file.size / 1024).toFixed(2)} KB</div>
                            </div>
                        `;
                        
                        fileList.appendChild(fileItem);
                    };
                    
                    reader.readAsDataURL(file);
                }
            } else {
                bulkPreview.style.display = 'none';
                fileList.innerHTML = '';
                fileCount.textContent = '0';
            }
        });
    }
    
    // Handle bulk upload form submission
    if (bulkUploadForm) {
        bulkUploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!bulkUploadFiles.files || bulkUploadFiles.files.length === 0) {
                alert('Please select files to upload');
                return;
            }
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.textContent = 'Uploading...';
            submitButton.disabled = true;
            uploadProgress.style.display = 'block';
            
            // Upload files with progress tracking
            const xhr = new XMLHttpRequest();
            
            xhr.open('POST', 'ajax/bulk-upload.php', true);
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percentComplete + '%';
                    progressText.textContent = percentComplete + '%';
                }
            });
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            alert(response.message);
                            // Reload page to reflect changes
                            window.location.reload();
                        } else {
                            alert('Upload failed: ' + response.message);
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        }
                    } catch (error) {
                        console.error('Error parsing response:', error);
                        alert('Error processing server response. Please try again.');
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                    }
                } else {
                    alert('Upload failed with status: ' + xhr.status);
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                }
            };
            
            xhr.onerror = function() {
                alert('Upload failed. Please check your connection and try again.');
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            };
            
            xhr.send(formData);
        });
    }
});
</script>
</body>
</html>