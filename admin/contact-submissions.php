<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
$db = new Database();

// Handle status updates
if(isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $db->escape($_GET['status']);
    
    if(in_array($status, ['new', 'read', 'replied', 'archived'])) {
        $db->query("UPDATE contact_submissions SET status = '$status' WHERE id = $id");
        header('Location: contact-submissions.php?msg=updated');
        exit;
    }
}

// Handle deletion
if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->query("DELETE FROM contact_submissions WHERE id = $id");
    header('Location: contact-submissions.php?msg=deleted');
    exit;
}

// Get submissions with optional filtering
$status_filter = isset($_GET['status']) ? $db->escape($_GET['status']) : '';
$where_clause = '';

if($status_filter && in_array($status_filter, ['new', 'read', 'replied', 'archived'])) {
    $where_clause = "WHERE status = '$status_filter'";
}

$submissions = $db->fetch_all("SELECT * FROM contact_submissions $where_clause ORDER BY submission_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Submissions | Admin Dashboard</title>
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
            background-color: #003366;
            color: #fff;
            padding: 20px 0;
        }
        .sidebar .logo {
            text-align: center;
            padding: 10px 0 20px;
        }
        .sidebar .logo img {
            width: 80px;
            border-radius: 50%;
        }
        .sidebar .menu {
            margin-top: 20px;
        }
        .sidebar .menu-item {
            padding: 10px 20px;
            border-left: 4px solid transparent;
        }
        .sidebar .menu-item:hover, .sidebar .menu-item.active {
            background-color: rgba(255,255,255,0.1);
            border-left-color: #3C91E6;
        }
        .sidebar .menu-item a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .sidebar .menu-item i {
            margin-right: 10px;
            font-size: 20px;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-info .name {
            margin-right: 15px;
        }
        .logout-btn {
            background-color: transparent;
            color: #e74c3c;
            border: 1px solid #e74c3c;
            padding: 5px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }
        .logout-btn:hover {
            background-color: #e74c3c;
            color: white;
        }
        
        /* Submissions specific styles */
        .filters {
            display: flex;
            margin-bottom: 20px;
        }
        .filter-btn {
            padding: 8px 15px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
            color: #212529;
            cursor: pointer;
            text-decoration: none;
        }
        .filter-btn.active {
            background-color: #3C91E6;
            color: white;
            border-color: #3C91E6;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        .submissions-table th, .submissions-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .submissions-table th {
            background-color: #003366;
            color: white;
        }
        .submissions-table tr:last-child td {
            border-bottom: none;
        }
        .submissions-table tr:hover {
            background-color: #f5f5f5;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        .status-new {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-read {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-replied {
            background-color: #d4edda;
            color: #155724;
        }
        .status-archived {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .action-links a {
            margin-right: 10px;
            text-decoration: none;
        }
        .view-link {
            color: #3C91E6;
        }
        .delete-link {
            color: #e74c3c;
        }
        .submission-detail {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
            display: none;
        }
        .submission-detail.active {
            display: block;
        }
        .submission-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .submission-header h3 {
            margin: 0 0 5px 0;
        }
        .submission-meta {
            color: #777;
            font-size: 0.9em;
        }
        .submission-content {
            margin-bottom: 20px;
        }
        .submission-actions {
            display: flex;
            gap: 10px;
        }
        .status-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
        }
        .mark-read {
            background-color: #cce5ff;
            color: #004085;
        }
        .mark-replied {
            background-color: #d4edda;
            color: #155724;
        }
        .mark-archived {
            background-color: #e2e3e5;
            color: #383d41;
        }
    </style>
    <script>
        // JavaScript function to toggle submission details
        function toggleSubmissionDetail(id) {
            const detailElement = document.getElementById('submission-' + id);
            if (detailElement) {
                detailElement.classList.toggle('active');
            }
        }
    </script>
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
                <div class="menu-item active">
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
                <h2>Contact Submissions</h2>
                <div class="user-info">
                    <div class="name">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <div class="filters">
                <a href="contact-submissions.php" class="filter-btn <?php echo empty($status_filter) ? 'active' : ''; ?>">All</a>
                <a href="contact-submissions.php?status=new" class="filter-btn <?php echo $status_filter === 'new' ? 'active' : ''; ?>">New</a>
                <a href="contact-submissions.php?status=read" class="filter-btn <?php echo $status_filter === 'read' ? 'active' : ''; ?>">Read</a>
                <a href="contact-submissions.php?status=replied" class="filter-btn <?php echo $status_filter === 'replied' ? 'active' : ''; ?>">Replied</a>
                <a href="contact-submissions.php?status=archived" class="filter-btn <?php echo $status_filter === 'archived' ? 'active' : ''; ?>">Archived</a>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] === 'updated'): ?>
                    <div class="message">Submission status has been updated successfully.</div>
                <?php elseif($_GET['msg'] === 'deleted'): ?>
                    <div class="message">Submission has been deleted successfully.</div>
                <?php endif; ?>
            <?php endif; ?>
            
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($submissions)): ?>
                    <tr>
                        <td colspan="6">No submissions found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['name']); ?></td>
                            <td><?php echo htmlspecialchars($submission['email']); ?></td>
                            <td><?php echo htmlspecialchars($submission['subject']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($submission['submission_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $submission['status']; ?>">
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>
                            </td>
                            <td class="action-links">
                                <a href="javascript:void(0);" onclick="toggleSubmissionDetail(<?php echo $submission['id']; ?>)" class="view-link">
                                    <i class='bx bxs-show'></i> View
                                </a>
                                <a href="contact-submissions.php?action=delete&id=<?php echo $submission['id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this submission?')">
                                    <i class='bx bxs-trash'></i> Delete
                                </a>
                            </td>
                        </tr>
                        
                        <!-- Submission Detail Section (Hidden by default) -->
                        <tr>
                            <td colspan="6" style="padding: 0;">
                                <div id="submission-<?php echo $submission['id']; ?>" class="submission-detail">
                                    <div class="submission-header">
                                        <h3><?php echo htmlspecialchars($submission['subject']); ?></h3>
                                        <div class="submission-meta">
                                            From: <?php echo htmlspecialchars($submission['name']); ?> (<?php echo htmlspecialchars($submission['email']); ?>)
                                            <?php if($submission['phone']): ?>
                                                | Phone: <?php echo htmlspecialchars($submission['phone']); ?>
                                            <?php endif; ?>
                                            | Submitted on: <?php echo date('F j, Y g:i A', strtotime($submission['submission_date'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="submission-content">
                                        <?php echo nl2br(htmlspecialchars($submission['message'])); ?>
                                    </div>
                                    
                                    <div class="submission-actions">
                                        <?php if($submission['status'] !== 'read'): ?>
                                            <a href="contact-submissions.php?action=update_status&id=<?php echo $submission['id']; ?>&status=read" class="status-btn mark-read">Mark as Read</a>
                                        <?php endif; ?>
                                        
                                        <?php if($submission['status'] !== 'replied'): ?>
                                            <a href="contact-submissions.php?action=update_status&id=<?php echo $submission['id']; ?>&status=replied" class="status-btn mark-replied">Mark as Replied</a>
                                        <?php endif; ?>
                                        
                                        <?php if($submission['status'] !== 'archived'): ?>
                                            <a href="contact-submissions.php?action=update_status&id=<?php echo $submission['id']; ?>&status=archived" class="status-btn mark-archived">Archive</a>
                                        <?php endif; ?>
                                        
                                        <a href="mailto:<?php echo htmlspecialchars($submission['email']); ?>?subject=Re: <?php echo htmlspecialchars($submission['subject']); ?>" class="status-btn" style="background-color: #3C91E6; color: white;">
                                            Reply via Email
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>