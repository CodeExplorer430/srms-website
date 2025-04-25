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
$db = new Database();

// Get counts for dashboard
$news_count = $db->fetch_row("SELECT COUNT(*) as count FROM news")['count'];
$users_count = $db->fetch_row("SELECT COUNT(*) as count FROM users")['count'];
$contacts_count = $db->fetch_row("SELECT COUNT(*) as count FROM contact_submissions WHERE status = 'new'")['count'];

// Get recent submissions
$recent_contacts = $db->fetch_all("SELECT * FROM contact_submissions ORDER BY submission_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | St. Raphaela Mary School</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
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
        .recent-contacts {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 0;
            overflow: hidden;
        }
        .recent-contacts h3 {
            margin: 0;
            padding: 20px 25px;
            background-color: #0a3060;
            color: white;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
        }
        .recent-contacts h3 i {
            margin-right: 10px;
            font-size: 22px;
        }
        .contact-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        .contact-item {
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.3s;
            position: relative;
        }
        .contact-item:last-child {
            border-bottom: none;
        }
        .contact-item:hover {
            background-color: #f8f9fa;
        }
        .contact-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: #dc3545;
            opacity: 0;
            transition: all 0.3s;
        }
        .contact-item:hover::before {
            opacity: 1;
        }
        .contact-item .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .contact-item .name {
            font-weight: 600;
            color: #0a3060;
            display: flex;
            align-items: center;
        }
        .contact-item .name::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #dc3545;
            margin-right: 8px;
        }
        .contact-item .date {
            color: #6c757d;
            font-size: 0.9em;
        }
        .contact-item .subject {
            color: #0a3060;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .contact-item .message {
            font-size: 0.9em;
            color: #495057;
            line-height: 1.5;
        }
        .contact-item .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .contact-item .action-btn {
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
        }
        .contact-item .action-btn i {
            margin-right: 5px;
        }
        .contact-item .view-btn {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        .contact-item .view-btn:hover {
            background-color: rgba(13, 110, 253, 0.2);
        }
        .contact-item .reply-btn {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        .contact-item .reply-btn:hover {
            background-color: rgba(25, 135, 84, 0.2);
        }
        .empty-message {
            padding: 30px;
            text-align: center;
            color: #6c757d;
        }
        .empty-message i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
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
            </div>
            
            <div class="recent-contacts">
                <h3><i class='bx bxs-envelope'></i> Recent Contact Submissions</h3>
                
                <?php if(empty($recent_contacts)): ?>
                <div class="empty-message">
                    <i class='bx bx-envelope-open'></i>
                    <p>No recent contact submissions.</p>
                </div>
                <?php else: ?>
                    <ul class="contact-list">
                        <?php foreach($recent_contacts as $contact): ?>
                        <li class="contact-item">
                            <div class="header">
                                <div class="name"><?php echo htmlspecialchars($contact['name']); ?></div>
                                <div class="date"><?php echo date('M j, Y g:i A', strtotime($contact['submission_date'])); ?></div>
                            </div>
                            <div class="subject"><?php echo htmlspecialchars($contact['subject']); ?></div>
                            <div class="message"><?php echo substr(htmlspecialchars($contact['message']), 0, 150) . (strlen($contact['message']) > 150 ? '...' : ''); ?></div>
                            <div class="actions">
                                <a href="contact-submissions.php?action=view&id=<?php echo $contact['id']; ?>" class="action-btn view-btn">
                                    <i class='bx bx-show'></i> View Details
                                </a>
                                <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>?subject=Re: <?php echo htmlspecialchars($contact['subject']); ?>" class="action-btn reply-btn">
                                    <i class='bx bx-reply'></i> Reply
                                </a>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>