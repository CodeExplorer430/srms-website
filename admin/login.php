<?php
session_start();

// If already logged in, redirect to admin dashboard
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
$db = new Database();

$login_error = '';

// Process login form
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if(empty($username) || empty($password)) {
        $login_error = 'Please enter both username and password';
    } else {
        // Get user from database
        $username = $db->escape($username);
        $user = $db->fetch_row("SELECT * FROM users WHERE username = '$username' AND active = TRUE");
        
        if($user && password_verify($password, $user['password'])) {
            // Update last login timestamp
            $user_id = $user['id'];
            $db->query("UPDATE users SET last_login = NOW() WHERE id = $user_id");
            
            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            
            // Redirect to admin dashboard
            header('Location: index.php');
            exit;
        } else {
            $login_error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | St. Raphaela Mary School</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f3f3;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: linear-gradient(rgba(10, 48, 96, 0.8), rgba(10, 48, 96, 0.8)), url('assets/images/campus/hero-main.jpg');
            background-size: cover;
            background-position: center;
        }
        .login-container {
            width: 400px;
            max-width: 90%;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            background-color: #0a3060;
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        .login-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 15px;
        }
        .login-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .login-header p {
            margin: 5px 0 0 0;
            opacity: 0.8;
            font-size: 14px;
        }
        .login-body {
            padding: 30px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #842029;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        .error-message i {
            margin-right: 10px;
            font-size: 18px;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3C91E6;
            box-shadow: 0 0 0 3px rgba(60, 145, 230, 0.15);
        }
        .form-group i {
            position: absolute;
            left: 15px;
            top: 38px;
            color: #adb5bd;
            font-size: 18px;
        }
        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .remember-me {
            display: flex;
            align-items: center;
        }
        .remember-me input {
            margin-right: 8px;
        }
        .remember-me label {
            font-size: 14px;
            color: #6c757d;
        }
        .forgot-password {
            font-size: 14px;
            color: #3C91E6;
            text-decoration: none;
            transition: all 0.3s;
        }
        .forgot-password:hover {
            color: #0a3060;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #3C91E6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .submit-btn:hover {
            background-color: #2c7ed6;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .submit-btn:active {
            transform: translateY(0);
        }
        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-site a {
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
        }
        .back-to-site a:hover {
            color: #0a3060;
        }
        .back-to-site a i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/images/branding/logo-primary.png" alt="St. Raphaela Mary School Logo">
            <h2>SRMS Admin Panel</h2>
            <p>Login to access dashboard</p>
        </div>
        
        <div class="login-body">
            <?php if(!empty($login_error)): ?>
            <div class="error-message">
                <i class='bx bx-error-circle'></i>
                <span><?php echo $login_error; ?></span>
            </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class='bx bxs-user'></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="form-footer">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="submit-btn">Login</button>
            </form>
            
            <div class="back-to-site">
                <a href="../index.php">
                    <i class='bx bx-arrow-back'></i> Back to Website
                </a>
            </div>
        </div>
    </div>
</body>
</html>