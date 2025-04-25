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

// Initialize variables
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = '';
$slug = '';
$content = '';
$summary = '';
$image = '';
$published_date = date('Y-m-d H:i:s');
$status = 'draft';
$featured = 0;
$errors = [];
$success = false;

// Load article data if editing
if($id > 0) {
    $article = $db->fetch_row("SELECT * FROM news WHERE id = $id");
    if($article) {
        $title = $article['title'];
        $slug = $article['slug'];
        $content = $article['content'];
        $summary = $article['summary'];
        $image = $article['image'];
        $published_date = $article['published_date'];
        $status = $article['status'];
        $featured = $article['featured'];
    } else {
        $errors[] = 'Article not found';
    }
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $summary = isset($_POST['summary']) ? trim($_POST['summary']) : '';
    $image = isset($_POST['image']) ? trim($_POST['image']) : '';
    $published_date = isset($_POST['published_date']) ? trim($_POST['published_date']) : date('Y-m-d H:i:s');
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'draft';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate inputs
    if(empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if(empty($slug)) {
        // Generate slug from title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }
    
    if(empty($content)) {
        $errors[] = 'Content is required';
    }
    
    // Process if no errors
    if(empty($errors)) {
        $title = $db->escape($title);
        $slug = $db->escape($slug);
        $content = $db->escape($content);
        $summary = $db->escape($summary);
        $image = $db->escape($image);
        $published_date = $db->escape($published_date);
        $author_id = $_SESSION['admin_user_id'];
        
        if($id > 0) {
            // Update existing article
            $sql = "UPDATE news SET 
                    title = '$title', 
                    slug = '$slug', 
                    content = '$content', 
                    summary = '$summary', 
                    image = '$image', 
                    published_date = '$published_date', 
                    status = '$status', 
                    featured = $featured 
                    WHERE id = $id";
                    
            if($db->query($sql)) {
                $success = true;
            } else {
                $errors[] = 'An error occurred while updating the article';
            }
        } else {
            // Insert new article
            $sql = "INSERT INTO news (title, slug, content, summary, image, published_date, author_id, status, featured) 
                    VALUES ('$title', '$slug', '$content', '$summary', '$image', '$published_date', $author_id, '$status', $featured)";
                    
            if($db->query($sql)) {
                $id = $db->insert_id();
                $success = true;
            } else {
                $errors[] = 'An error occurred while creating the article';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Edit' : 'Add'; ?> News Article | Admin Dashboard</title>
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
        
        /* Form styles */
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="datetime-local"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }
        .form-group textarea {
            height: 200px;
            resize: vertical;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .save-btn {
            background-color: #3C91E6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .cancel-btn {
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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
                <h2><?php echo $id > 0 ? 'Edit' : 'Add'; ?> News Article</h2>
                <div class="user-info">
                    <div class="name">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <?php if($success): ?>
            <div class="success-message">
                News article has been successfully <?php echo $id > 0 ? 'updated' : 'created'; ?>.
                <a href="news-manage.php">Return to News Management</a>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="<?php echo $_SERVER['PHP_SELF'] . ($id > 0 ? '?id=' . $id : ''); ?>" method="post">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug (URL-friendly version of title)</label>
                        <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="summary">Summary</label>
                        <textarea id="summary" name="summary"><?php echo htmlspecialchars($summary); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" required><?php echo htmlspecialchars($content); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Image Path</label>
                        <input type="text" id="image" name="image" value="<?php echo htmlspecialchars($image); ?>">
                        <small style="color:#666;">Relative path to image file (e.g., /assets/images/news/example.jpg)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="published_date">Published Date</label>
                        <input type="datetime-local" id="published_date" name="published_date" value="<?php echo date('Y-m-d\TH:i', strtotime($published_date)); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="featured" name="featured" value="1" <?php echo $featured ? 'checked' : ''; ?>>
                        <label for="featured">Featured Article</label>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="news-manage.php" class="cancel-btn">Cancel</a>
                        <button type="submit" class="save-btn">Save Article</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>