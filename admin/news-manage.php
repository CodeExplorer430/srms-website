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

// Handle deletion
if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->query("DELETE FROM news WHERE id = $id");
    header('Location: news-manage.php?msg=deleted');
    exit;
}

// Get all news articles
$news_articles = $db->fetch_all("SELECT n.*, u.username as author FROM news n LEFT JOIN users u ON n.author_id = u.id ORDER BY n.published_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage News | Admin Dashboard</title>
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
        }
        .logout-btn:hover {
            background-color: #dc3545;
            color: white;
        }
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .action-bar .left {
            display: flex;
            align-items: center;
        }
        .action-bar .search-box {
            position: relative;
            margin-right: 15px;
        }
        .action-bar .search-box input {
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 250px;
            transition: all 0.3s;
        }
        .action-bar .search-box input:focus {
            outline: none;
            border-color: #3C91E6;
            box-shadow: 0 0 0 2px rgba(60,145,230,0.1);
        }
        .action-bar .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }
        .filter-dropdown {
            margin-right: 15px;
            position: relative;
        }
        .filter-dropdown select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
            cursor: pointer;
            appearance: none;
            padding-right: 30px;
        }
        .filter-dropdown::after {
            content: "\25BC";
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            pointer-events: none;
            font-size: 10px;
        }
        .add-btn {
            background-color: #3C91E6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
        }
        .add-btn:hover {
            background-color: #2c7ed6;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .add-btn i {
            margin-right: 8px;
            font-size: 18px;
        }
        .message {
            background-color: #d1e7dd;
            color: #0f5132;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            border-left: 4px solid #0f5132;
        }
        .message i {
            margin-right: 10px;
            font-size: 22px;
        }
        .news-table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .news-table {
            width: 100%;
            border-collapse: collapse;
        }
        .news-table th, .news-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .news-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-top: none;
            border-bottom: 2px solid #e9ecef;
        }
        .news-table td {
            color: #495057;
            vertical-align: middle;
        }
        .news-table tr:hover {
            background-color: #f8f9fa;
        }
        .news-table tr:last-child td {
            border-bottom: none;
        }
        .news-table .title-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
        }
        .status-published {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-draft {
            background-color: #f8d7da;
            color: #842029;
        }
        .featured-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            background-color: #fff3cd;
            color: #856404;
            text-align: center;
        }
        .action-links {
            display: flex;
            gap: 10px;
        }
        .action-links a {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .edit-link {
            color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.1);
        }
        .edit-link:hover {
            background-color: rgba(13, 110, 253, 0.2);
        }
        .delete-link {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }
        .delete-link:hover {
            background-color: rgba(220, 53, 69, 0.2);
        }
        .action-links a i {
            margin-right: 5px;
            font-size: 16px;
        }
        .pagination {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 5px;
        }
        .pagination a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 4px;
            background-color: white;
            color: #495057;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        .pagination a:hover,
        .pagination a.active {
            background-color: #3C91E6;
            color: white;
            border-color: #3C91E6;
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
                <div class="menu-item active">
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
                <h2>Manage News</h2>
                <div class="user-info">
                    <div class="name">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
                    <a href="logout.php" class="logout-btn">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="action-bar">
                <div class="left">
                    <div class="search-box">
                        <i class='bx bx-search'></i>
                        <input type="text" placeholder="Search articles..." id="searchInput">
                    </div>
                    <div class="filter-dropdown">
                        <select id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>
                <a href="news-edit.php" class="add-btn">
                    <i class='bx bx-plus'></i> Add New Article
                </a>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="message">
                <i class='bx bx-check-circle'></i>
                <span>News article has been successfully deleted.</span>
            </div>
            <?php endif; ?>
            
            <div class="news-table-container">
                <table class="news-table" id="newsTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Published Date</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($news_articles)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px;">No news articles found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($news_articles as $article): ?>
                            <tr>
                                <td class="title-cell" title="<?php echo htmlspecialchars($article['title']); ?>">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($article['published_date'])); ?></td>
                                <td><?php echo htmlspecialchars($article['author']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $article['status']; ?>">
                                        <?php echo ucfirst($article['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($article['featured']): ?>
                                    <span class="featured-badge">Featured</span>
                                    <?php else: ?>
                                    <span>No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-links">
                                    <a href="news-edit.php?id=<?php echo $article['id']; ?>" class="edit-link">
                                        <i class='bx bxs-edit'></i> Edit
                                    </a>
                                    <a href="news-manage.php?action=delete&id=<?php echo $article['id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this article?')">
                                        <i class='bx bxs-trash'></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination">
                <a href="#" class="active">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#"><i class='bx bx-chevron-right'></i></a>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('newsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const titleCell = rows[i].getElementsByTagName('td')[0];
                if (titleCell) {
                    const title = titleCell.textContent || titleCell.innerText;
                    if (title.toLowerCase().indexOf(searchValue) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });
        
        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            const filterValue = this.value;
            const table = document.getElementById('newsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const statusCell = rows[i].getElementsByTagName('td')[3];
                if (statusCell) {
                    const status = statusCell.textContent || statusCell.innerText;
                    if (filterValue === 'all' || status.toLowerCase().includes(filterValue)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });
    </script>
</body>
</html>