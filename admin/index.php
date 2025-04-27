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

// Get counts for dashboard
$news_count = $db->fetch_row("SELECT COUNT(*) as count FROM news")['count'];
$users_count = $db->fetch_row("SELECT COUNT(*) as count FROM users")['count'];
$contacts_count = $db->fetch_row("SELECT COUNT(*) as count FROM contact_submissions WHERE status = 'new'")['count'];

// Get media counts
$media_counts = [
    'total' => 0,
    'news' => 0,
    'events' => 0,
    'promotional' => 0
];

// Count images in each directory
$media_directories = [
    'news' => '/assets/images/news',
    'events' => '/assets/images/events',
    'promotional' => '/assets/images/promotional',
    'facilities' => '/assets/images/facilities',
    'campus' => '/assets/images/campus'
];

foreach ($media_directories as $key => $dir) {
    $path = $_SERVER['DOCUMENT_ROOT'] . $dir;
    if (is_dir($path)) {
        $files = glob($path . "/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        $count = count($files);
        $media_counts[$key] = $count;
        $media_counts['total'] += $count;
    }
}

// Get recent submissions
$recent_contacts = $db->fetch_all("SELECT * FROM contact_submissions ORDER BY submission_date DESC LIMIT 5");

// Get recent media uploads
$recent_media = [];
foreach ($media_directories as $key => $dir) {
    $path = $_SERVER['DOCUMENT_ROOT'] . $dir;
    if (is_dir($path)) {
        $files = glob($path . "/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $recent_media[] = [
                    'path' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $file),
                    'name' => basename($file),
                    'type' => $key,
                    'modified' => filemtime($file)
                ];
            }
        }
    }
}

// Sort by modified time (newest first) and limit to 6
usort($recent_media, function($a, $b) {
    return $b['modified'] - $a['modified'];
});
$recent_media = array_slice($recent_media, 0, 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | St. Raphaela Mary School</title>
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
        
        /* Admin Tools Section */
        .admin-tools {
            margin-top: 30px;
            padding: 0 20px 20px;
        }
        
        .admin-tools h4 {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            margin-bottom: 15px;
            padding-left: 20px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .admin-action-card {
            background-color: rgba(255,255,255,0.1);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .admin-action-card:hover {
            background-color: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }
        
        .admin-action-card i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #3C91E6;
        }
        
        .admin-action-title {
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 15px;
        }
        
        .admin-action-desc {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
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
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            grid-gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
            overflow: hidden;
            position: relative;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .card .number {
            font-size: 32px;
            font-weight: 700;
        }
        .card .label {
            color: #6c757d;
            margin-top: 5px;
            font-size: 14px;
        }
        .card i {
            font-size: 48px;
            opacity: 0.8;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }
        .card.news {
            color: #0d6efd;
        }
        .card.news::before {
            background-color: #0d6efd;
        }
        .card.news i {
            color: rgba(13, 110, 253, 0.2);
        }
        .card.messages {
            color: #dc3545;
        }
        .card.messages::before {
            background-color: #dc3545;
        }
        .card.messages i {
            color: rgba(220, 53, 69, 0.2);
        }
        .card.users {
            color: #198754;
        }
        .card.users::before {
            background-color: #198754;
        }
        .card.users i {
            color: rgba(25, 135, 84, 0.2);
        }
        .card.media {
            color: #6f42c1;
        }
        .card.media::before {
            background-color: #6f42c1;
        }
        .card.media i {
            color: rgba(111, 66, 193, 0.2);
        }
        
        /* Content Sections */
        .content-section {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 20px 25px;
            background-color: #0a3060;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .section-header h3 i {
            margin-right: 10px;
            font-size: 22px;
        }
        
        .section-header .view-all {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.2s;
            display: flex;
            align-items: center;
        }
        
        .section-header .view-all:hover {
            color: white;
        }
        
        .section-header .view-all i {
            margin-left: 5px;
        }
        
        .section-body {
            padding: 20px 25px;
        }
        
        /* Recent Contacts */
        .contact-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .contact-item {
            padding: 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .contact-item:last-child {
            border-bottom: none;
        }
        
        .contact-card {
            display: flex;
            padding: 20px 0;
        }
        
        .contact-status {
            flex-shrink: 0;
            width: 40px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 9px;
        }
        
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0px;
        }
        
        .status-new {
            background-color: #dc3545;
        }
        
        .status-read {
            background-color: #0d6efd;
        }
        
        .status-replied {
            background-color: #198754;
        }
        
        .contact-content {
            flex-grow: 1;
        }
        
        .contact-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .contact-info {
            flex-grow: 1;
        }
        
        .contact-name {
            font-weight: 600;
            color: #0a3060;
            margin-bottom: 3px;
        }
        
        .contact-email {
            color: #6c757d;
            font-size: 13px;
        }
        
        .contact-date {
            background-color: #f8f9fa;
            color: #6c757d;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .contact-subject {
            color: #0a3060;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .contact-message {
            font-size: 14px;
            color: #495057;
            line-height: 1.5;
            margin-bottom: 15px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .contact-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .view-btn {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        
        .view-btn:hover {
            background-color: rgba(13, 110, 253, 0.2);
        }
        
        .reply-btn {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .reply-btn:hover {
            background-color: rgba(25, 135, 84, 0.2);
        }
        
        /* Recent Media */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
        }
        
        .media-item {
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
            transition: all 0.2s;
        }
        
        .media-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.15);
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
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px;
            background-color: rgba(0,0,0,0.7);
            color: white;
            font-size: 12px;
        }
        
        .media-path {
            font-size: 10px;
            opacity: 0.8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .media-time {
            font-size: 10px;
            opacity: 0.7;
        }
        
        .media-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 3px 8px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 500;
            color: white;
        }
        
        .badge-news {
            background-color: #0d6efd;
        }
        
        .badge-events {
            background-color: #198754;
        }
        
        .badge-promotional {
            background-color: #dc3545;
        }
        
        .badge-facilities {
            background-color: #fd7e14;
        }
        
        .badge-campus {
            background-color: #6f42c1;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 30px 0;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Media Manager Quick Actions */
        .media-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .media-action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .media-action-btn i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .media-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-browse-media {
            background-color: #0d6efd;
        }
        
        .btn-upload-media {
            background-color: #198754;
        }
        
        .btn-manage-media {
            background-color: #6f42c1;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .contact-card {
                flex-direction: column;
            }
            
            .contact-status {
                width: auto;
                margin-bottom: 10px;
            }
            
            .contact-header {
                flex-direction: column;
            }
            
            .contact-date {
                margin-top: 10px;
            }
            
            .contact-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
            
            .media-actions {
                flex-direction: column;
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
                <div class="menu-item active">
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
                <div class="menu-item">
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
            
            <div class="admin-tools">
                <h4>Admin Tools</h4>
                <a href="maintenance/setup-directories.php" class="admin-action-card">
                    <i class='bx bx-folder-plus'></i>
                    <div class="admin-action-title">Setup Media Directories</div>
                    <div class="admin-action-desc">Create required directories and placeholders</div>
                </a>
                <a href="#" class="admin-action-card" id="open-media-library-btn">
                    <i class='bx bx-images'></i>
                    <div class="admin-action-title">Open Media Library</div>
                    <div class="admin-action-desc">Browse and manage media files</div>
                </a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>Admin Dashboard</h2>
                <div class="user-info">
                    <div class="name">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
                    <a href="logout.php" class="logout-btn">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="dashboard-cards">
                <div class="card news">
                    <div>
                        <div class="number"><?php echo $news_count; ?></div>
                        <div class="label">News Articles</div>
                    </div>
                    <i class='bx bxs-news'></i>
                </div>
                
                <div class="card messages">
                    <div>
                        <div class="number"><?php echo $contacts_count; ?></div>
                        <div class="label">New Messages</div>
                    </div>
                    <i class='bx bxs-message-detail'></i>
                </div>
                
                <div class="card users">
                    <div>
                        <div class="number"><?php echo $users_count; ?></div>
                        <div class="label">Admin Users</div>
                    </div>
                    <i class='bx bxs-user'></i>
                </div>
                
                <div class="card media">
                    <div>
                        <div class="number"><?php echo $media_counts['total']; ?></div>
                        <div class="label">Media Files</div>
                    </div>
                    <i class='bx bx-images'></i>
                </div>
            </div>
            
            <!-- Media Manager Panel -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class='bx bx-images'></i> Media Library</h3>
                    <a href="media-manager.php" class="view-all">View All <i class='bx bx-chevron-right'></i></a>
                </div>
                <div class="section-body">
                    <div class="media-actions">
                        <a href="media-manager.php" class="media-action-btn btn-browse-media">
                            <i class='bx bx-image'></i> Browse Media
                        </a>
                        <button class="media-action-btn btn-upload-media" id="upload-media-btn">
                            <i class='bx bx-upload'></i> Upload New
                        </button>
                        <a href="maintenance/setup-directories.php" class="media-action-btn btn-manage-media">
                            <i class='bx bx-folder'></i> Setup Directories
                        </a>
                    </div>
                    
                    <?php if (empty($recent_media)): ?>
                    <div class="empty-state">
                        <i class='bx bx-image'></i>
                        <p>No media files have been uploaded yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="media-grid">
                        <?php foreach ($recent_media as $media): ?>
                        <div class="media-item">
                            <div class="media-thumbnail">
                                <img src="<?php echo $media['path']; ?>" alt="<?php echo htmlspecialchars($media['name']); ?>">
                                <span class="media-badge badge-<?php echo $media['type']; ?>"><?php echo ucfirst($media['type']); ?></span>
                            </div>
                            <div class="media-info">
                                <div class="media-path" title="<?php echo $media['path']; ?>"><?php echo $media['path']; ?></div>
                                <div class="media-time"><?php echo date('M j, Y', $media['modified']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Contact Submissions -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class='bx bxs-envelope'></i> Recent Contact Submissions</h3>
                    <a href="contact-submissions.php" class="view-all">View All <i class='bx bx-chevron-right'></i></a>
                </div>
                
                <?php if(empty($recent_contacts)): ?>
                <div class="empty-state">
                    <i class='bx bx-envelope-open'></i>
                    <p>No recent contact submissions.</p>
                </div>
                <?php else: ?>
                    <ul class="contact-list">
                        <?php foreach($recent_contacts as $contact): ?>
                        <li class="contact-item">
                            <div class="contact-card">
                                <div class="contact-status">
                                    <div class="status-indicator status-<?php echo $contact['status'] ?: 'new'; ?>"></div>
                                </div>
                                <div class="contact-content">
                                    <div class="contact-header">
                                        <div class="contact-info">
                                            <div class="contact-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                                            <div class="contact-email"><?php echo htmlspecialchars($contact['email']); ?></div>
                                        </div>
                                        <div class="contact-date">
                                            <i class='bx bx-calendar'></i> <?php echo date('M j, Y g:i A', strtotime($contact['submission_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="contact-subject"><?php echo htmlspecialchars($contact['subject']); ?></div>
                                    <div class="contact-message"><?php echo substr(htmlspecialchars($contact['message']), 0, 150) . (strlen($contact['message']) > 150 ? '...' : ''); ?></div>
                                    <div class="contact-actions">
                                        <a href="contact-submissions.php?action=view&id=<?php echo $contact['id']; ?>" class="action-btn view-btn">
                                            <i class='bx bx-show'></i> View Details
                                        </a>
                                        <a href="reply.php?id=<?php echo $contact['id']; ?>" class="action-btn reply-btn">
                                            <i class='bx bx-reply'></i> Reply
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
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

    <!-- Media Library Modal -->
    <?php include_once 'includes/media-library.php'; ?>
    <?php render_media_library('dummy-field'); ?>
    <input type="hidden" id="dummy-field" name="dummy-field" value="">

    <script src="../assets/js/media-library.js"></script>
    <script src="../assets/js/media-modal.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Upload Modal Functionality
            const uploadModal = document.getElementById('upload-modal');
            const uploadBtn = document.getElementById('upload-media-btn');
            const closeUploadModal = document.getElementById('close-upload-modal');
            const cancelUpload = document.getElementById('cancel-upload');
            const uploadForm = document.getElementById('media-upload-form');
            const uploadFile = document.getElementById('upload-file');
            const uploadPreview = document.querySelector('.upload-preview');
            const previewImg = uploadPreview ? uploadPreview.querySelector('img') : null;
            
            // Only initialize if elements exist
            if (uploadBtn && uploadModal) {
                // Open Upload Modal
                uploadBtn.addEventListener('click', function() {
                    uploadModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
                
                // Close Upload Modal
                function closeUploadModalFn() {
                    uploadModal.style.display = 'none';
                    document.body.style.overflow = '';
                    if (uploadForm) uploadForm.reset();
                    if (uploadPreview) uploadPreview.style.display = 'none';
                }
                
                if (closeUploadModal) {
                    closeUploadModal.addEventListener('click', closeUploadModalFn);
                }
                
                if (cancelUpload) {
                    cancelUpload.addEventListener('click', closeUploadModalFn);
                }
                
                if (uploadModal) {
                    uploadModal.addEventListener('click', function(e) {
                        if (e.target === uploadModal) {
                            closeUploadModalFn();
                        }
                    });
                }
                
                // Preview uploaded image
                if (uploadFile && previewImg) {
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
                }
                
                // Handle form submission
                if (uploadForm) {
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
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message and close modal
                                alert('File uploaded successfully!');
                                closeUploadModalFn();
                                // Reload page to reflect changes
                                window.location.reload();
                            } else {
                                alert('Upload failed: ' + (data.message || 'Unknown error'));
                                submitButton.textContent = originalText;
                                submitButton.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred during upload.');
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        });
                    });
                }
            }

            // Media Library Modal Handling
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
    </script>
</body>
</html>