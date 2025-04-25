<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Check if user has admin role
if($_SESSION['admin_role'] !== 'admin') {
    header('Location: index.php?error=access_denied');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
$db = new Database();

// Initialize variables
$errors = [];
$success = '';

// Handle user deletion
if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Prevent deleting own account
    if($id === (int)$_SESSION['admin_user_id']) {
        header('Location: users.php?error=self_delete');
        exit;
    }
    
    $db->query("DELETE FROM users WHERE id = $id");
    header('Location: users.php?msg=deleted');
    exit;
}

// Handle user status toggle
if(isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Prevent deactivating own account
    if($id === (int)$_SESSION['admin_user_id']) {
        header('Location: users.php?error=self_deactivate');
        exit;
    }
    
    $user = $db->fetch_row("SELECT active FROM users WHERE id = $id");
    $new_status = $user['active'] ? 0 : 1;
    
    $db->query("UPDATE users SET active = $new_status WHERE id = $id");
    header('Location: users.php?msg=status_updated');
    exit;
}

// Process AJAX form submission for adding/editing user
if(isset($_POST['ajax_action']) && ($_POST['ajax_action'] === 'add' || $_POST['ajax_action'] === 'edit')) {
    $response = ['success' => false, 'message' => '', 'errors' => []];
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'editor';
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validate inputs
    if(empty($username)) {
        $response['errors'][] = 'Username is required';
    }
    
    if(empty($email)) {
        $response['errors'][] = 'Email is required';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors'][] = 'Please provide a valid email address';
    }
    
    if($_POST['ajax_action'] === 'add' && empty($password)) {
        $response['errors'][] = 'Password is required for new users';
    }
    
    // Check for existing username or email
    if(!empty($username)) {
        $check_username = $db->fetch_row("SELECT id FROM users WHERE username = '{$db->escape($username)}' AND id != $id");
        if($check_username) {
            $response['errors'][] = 'Username already exists';
        }
    }
    
    if(!empty($email)) {
        $check_email = $db->fetch_row("SELECT id FROM users WHERE email = '{$db->escape($email)}' AND id != $id");
        if($check_email) {
            $response['errors'][] = 'Email already exists';
        }
    }
    
    // Process if no errors
    if(empty($response['errors'])) {
        $username = $db->escape($username);
        $email = $db->escape($email);
        $role = $db->escape($role);
        
        if($_POST['ajax_action'] === 'edit') {
            // Update existing user
            if(!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET 
                        username = '$username', 
                        email = '$email', 
                        password = '$hashed_password', 
                        role = '$role', 
                        active = $active 
                        WHERE id = $id";
            } else {
                $sql = "UPDATE users SET 
                        username = '$username', 
                        email = '$email', 
                        role = '$role', 
                        active = $active 
                        WHERE id = $id";
            }
            
            if($db->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'User has been updated successfully';
            } else {
                $response['errors'][] = 'An error occurred while updating the user';
            }
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, role, active) 
                    VALUES ('$username', '$email', '$hashed_password', '$role', $active)";
            
            if($db->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'User has been created successfully';
                $response['user_id'] = $db->insert_id();
            } else {
                $response['errors'][] = 'An error occurred while creating the user';
            }
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get user data for AJAX edit request
if(isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_user' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $user = $db->fetch_row("SELECT id, username, email, role, active FROM users WHERE id = $id");
    
    header('Content-Type: application/json');
    echo json_encode($user ? $user : ['error' => 'User not found']);
    exit;
}

// Get all users
$users = $db->fetch_all("SELECT * FROM users ORDER BY username");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Admin Dashboard</title>
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
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            border-left: 4px solid;
        }
        .message i {
            margin-right: 10px;
            font-size: 22px;
        }
        .success-message {
            background-color: #d1e7dd;
            color: #0f5132;
            border-left-color: #0f5132;
        }
        .error-message {
            background-color: #f8d7da;
            color: #842029;
            border-left-color: #842029;
        }
        .users-table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th, .users-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .users-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-top: none;
            border-bottom: 2px solid #e9ecef;
        }
        .users-table td {
            color: #495057;
            vertical-align: middle;
        }
        .users-table tr:hover {
            background-color: #f8f9fa;
        }
        .users-table tr:last-child td {
            border-bottom: none;
        }
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
        }
        .role-admin {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .role-editor {
            background-color: #cfe2ff;
            color: #084298;
        }
        .role-content_manager {
            background-color: #fff3cd;
            color: #664d03;
        }
        .status-active {
            color: #0f5132;
            display: flex;
            align-items: center;
        }
        .status-active::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #0f5132;
            margin-right: 8px;
        }
        .status-inactive {
            color: #842029;
            display: flex;
            align-items: center;
        }
        .status-inactive::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #842029;
            margin-right: 8px;
        }
        .action-links {
            display: flex;
            gap: 10px;
        }
        .action-links button, .action-links a {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            background-color: transparent;
        }
        .edit-link {
            color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.1) !important;
        }
        .edit-link:hover {
            background-color: rgba(13, 110, 253, 0.2) !important;
        }
        .activate-link {
            color: #198754;
            background-color: rgba(25, 135, 84, 0.1) !important;
        }
        .activate-link:hover {
            background-color: rgba(25, 135, 84, 0.2) !important;
        }
        .deactivate-link {
            color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        .deactivate-link:hover {
            background-color: rgba(255, 193, 7, 0.2) !important;
        }
        .delete-link {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        .delete-link:hover {
            background-color: rgba(220, 53, 69, 0.2) !important;
        }
        .action-links button i, .action-links a i {
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
        
        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal {
            background-color: white;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-20px);
            transition: all 0.3s;
        }
        .modal-overlay.active .modal {
            transform: translateY(0);
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #0a3060;
            font-weight: 600;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
            transition: all 0.3s;
        }
        .modal-close:hover {
            color: #dc3545;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .modal-btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .cancel-btn {
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #ddd;
        }
        .cancel-btn:hover {
            background-color: #e9ecef;
        }
        .save-btn {
            background-color: #3C91E6;
            color: white;
            border: none;
        }
        .save-btn:hover {
            background-color: #2c7ed6;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3C91E6;
            box-shadow: 0 0 0 2px rgba(60,145,230,0.1);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .checkbox-group input {
            margin-right: 10px;
            width: auto;
        }
        .checkbox-group label {
            margin: 0;
        }
        .modal-errors {
            background-color: #f8d7da;
            color: #842029;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: none;
        }
        .modal-errors ul {
            margin: 0;
            padding-left: 20px;
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
                <h2>User Management</h2>
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
                        <input type="text" placeholder="Search users..." id="searchInput">
                    </div>
                </div>
                <button class="add-btn" id="addUserBtn">
                    <i class='bx bx-user-plus'></i> Add New User
                </button>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] === 'deleted'): ?>
                    <div class="message success-message">
                        <i class='bx bx-check-circle'></i>
                        <span>User has been deleted successfully.</span>
                    </div>
                <?php elseif($_GET['msg'] === 'status_updated'): ?>
                    <div class="message success-message">
                        <i class='bx bx-check-circle'></i>
                        <span>User status has been updated successfully.</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <?php if($_GET['error'] === 'self_delete'): ?>
                    <div class="message error-message">
                        <i class='bx bx-error-circle'></i>
                        <span>You cannot delete your own account.</span>
                    </div>
                <?php elseif($_GET['error'] === 'self_deactivate'): ?>
                    <div class="message error-message">
                        <i class='bx bx-error-circle'></i>
                        <span>You cannot deactivate your own account.</span>
                    </div>
                <?php elseif($_GET['error'] === 'not_found'): ?>
                    <div class="message error-message">
                        <i class='bx bx-error-circle'></i>
                        <span>User not found.</span>
                    </div>
                <?php elseif($_GET['error'] === 'access_denied'): ?>
                    <div class="message error-message">
                        <i class='bx bx-error-circle'></i>
                        <span>Access denied. Only administrators can manage users.</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="users-table-container">
                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px;">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php 
                                                switch($user['role']) {
                                                    case 'admin':
                                                        echo 'Administrator';
                                                        break;
                                                    case 'content_manager':
                                                        echo 'Content Manager';
                                                        break;
                                                    default:
                                                        echo 'Editor';
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="<?php echo $user['active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                    </td>
                                    <td class="action-links">
                                        <button type="button" class="edit-link edit-user-btn" data-id="<?php echo $user['id']; ?>">
                                            <i class='bx bxs-edit'></i> Edit
                                        </button>
                                        
                                        <?php if($user['id'] !== (int)$_SESSION['admin_user_id']): ?>
                                            <?php if($user['active']): ?>
                                                <a href="users.php?action=toggle_status&id=<?php echo $user['id']; ?>" class="deactivate-link" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                    <i class='bx bxs-x-circle'></i> Deactivate
                                                </a>
                                            <?php else: ?>
                                                <a href="users.php?action=toggle_status&id=<?php echo $user['id']; ?>" class="activate-link">
                                                    <i class='bx bxs-check-circle'></i> Activate
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class='bx bxs-trash'></i> Delete
                                            </a>
                                        <?php endif; ?>
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
    
    <!-- User Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Add New User</h3>
                <button type="button" class="modal-close" id="modalClose">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-errors" id="modalErrors">
                    <ul id="errorList"></ul>
                </div>
                <form id="userForm">
                    <input type="hidden" id="userId" name="id" value="0">
                    <input type="hidden" id="ajaxAction" name="ajax_action" value="add">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" id="passwordLabel">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role">
                            <option value="admin">Administrator</option>
                            <option value="editor" selected>Editor</option>
                            <option value="content_manager">Content Manager</option>
                        </select>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="active" name="active" value="1" checked>
                        <label for="active">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel-btn" id="cancelBtn">Cancel</button>
                <button type="button" class="modal-btn save-btn" id="saveBtn">Save User</button>
            </div>
        </div>
    </div>

    <script>
        // DOM elements
        const modal = document.getElementById('userModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalErrors = document.getElementById('modalErrors');
        const errorList = document.getElementById('errorList');
        const userForm = document.getElementById('userForm');
        const userId = document.getElementById('userId');
        const ajaxAction = document.getElementById('ajaxAction');
        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const passwordLabel = document.getElementById('passwordLabel');
        const role = document.getElementById('role');
        const active = document.getElementById('active');
        const addUserBtn = document.getElementById('addUserBtn');
        const modalClose = document.getElementById('modalClose');
        const cancelBtn = document.getElementById('cancelBtn');
        const saveBtn = document.getElementById('saveBtn');
        const searchInput = document.getElementById('searchInput');
        const usersTable = document.getElementById('usersTable');
        
        // Open modal for adding new user
        addUserBtn.addEventListener('click', function() {
            modalTitle.textContent = 'Add New User';
            ajaxAction.value = 'add';
            userId.value = '0';
            passwordLabel.textContent = 'Password';
            password.required = true;
            userForm.reset();
            role.value = 'editor';
            active.checked = true;
            modalErrors.style.display = 'none';
            modal.classList.add('active');
        });
        
        // Close modal
        function closeModal() {
            modal.classList.remove('active');
        }
        
        modalClose.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Edit user button click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-user-btn') || e.target.parentElement.classList.contains('edit-user-btn')) {
                const btn = e.target.classList.contains('edit-user-btn') ? e.target : e.target.parentElement;
                const userId = btn.dataset.id;
                
                // Fetch user data
                fetch(`users.php?ajax_action=get_user&id=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        
                        modalTitle.textContent = 'Edit User';
                        ajaxAction.value = 'edit';
                        document.getElementById('userId').value = data.id;
                        username.value = data.username;
                        email.value = data.email;
                        password.value = '';
                        passwordLabel.textContent = 'Password (leave blank to keep current)';
                        password.required = false;
                        role.value = data.role;
                        active.checked = data.active == 1;
                        modalErrors.style.display = 'none';
                        modal.classList.add('active');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while fetching user data');
                    });
            }
        });
        
        // Save user
        saveBtn.addEventListener('click', function() {
            const formData = new FormData(userForm);
            
            // Add checkbox value if not checked
            if (!active.checked) {
                formData.append('active', '0');
            }
            
            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the page to show updated data
                    window.location.href = 'users.php?msg=' + (ajaxAction.value === 'add' ? 'added' : 'updated');
                } else {
                    // Show errors
                    errorList.innerHTML = '';
                    data.errors.forEach(error => {
                        const li = document.createElement('li');
                        li.textContent = error;
                        errorList.appendChild(li);
                    });
                    modalErrors.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the user');
            });
        });
        
        // Search functionality
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = usersTable.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const usernameCell = rows[i].getElementsByTagName('td')[0];
                const emailCell = rows[i].getElementsByTagName('td')[1];
                
                if (usernameCell && emailCell) {
                    const username = usernameCell.textContent.toLowerCase();
                    const email = emailCell.textContent.toLowerCase();
                    
                    if (username.indexOf(searchValue) > -1 || email.indexOf(searchValue) > -1) {
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