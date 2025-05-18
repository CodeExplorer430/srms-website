<?php
session_start();

// If already logged in, redirect to admin dashboard
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Check for remember me cookie
if(isset($_COOKIE['srms_remember']) && !empty($_COOKIE['srms_remember'])) {
    // Include database connection
    include_once '../includes/config.php';
    include_once '../includes/db.php';
    $db = new Database();
    
    // Get the remember token and user ID from cookie
    list($user_id, $token) = explode(':', $_COOKIE['srms_remember']);
    $user_id = intval($user_id);
    
    // Verify token in database
    $user_id = $db->escape($user_id);
    $token = $db->escape($token);
    $user = $db->fetch_row("SELECT * FROM users WHERE id = '$user_id' AND remember_token = '$token' AND active = TRUE");
    
    if($user) {
        // Update last login timestamp
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
        // Invalid token, clear the cookie
        setcookie('srms_remember', '', time() - 3600, '/');
    }
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
    $remember = isset($_POST['remember']) ? true : false;
    
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
            
            // Handle "Remember Me" functionality
            if($remember) {
                // Generate a secure token
                $token = bin2hex(random_bytes(32));
                
                // Store the token in the database
                $db->query("UPDATE users SET remember_token = '$token' WHERE id = $user_id");
                
                // Set a cookie that expires in 30 days
                setcookie('srms_remember', $user_id . ':' . $token, time() + (86400 * 30), '/');
            }
            
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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0a3060;
            --secondary-color: #3C91E6;
            --accent-color: #4dabf7;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --error-color: #dc3545;
            --success-color: #28a745;
            --transition-speed: 0.3s;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 2rem 1rem;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(
                rgba(10, 48, 96, 0.85), 
                rgba(10, 48, 96, 0.9)
            ), url('../assets/images/campus/hero-background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            filter: brightness(0.95);
            z-index: -1;
        }
        
        .login-wrapper {
            width: 100%;
            max-width: 1100px;
            display: flex;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            overflow: hidden;
            background-color: white;
            position: relative;
        }
        
        .login-branding {
            width: 55%;
            background-image: linear-gradient(
                135deg,
                rgba(60, 145, 230, 0.8),
                rgba(10, 48, 96, 0.9)
            ), url('../assets/images/campus/school-building.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 3rem;
            position: relative;
            color: white;
        }
        
        .school-logo {
            position: absolute;
            top: 2rem;
            left: 2rem;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            padding: 8px;
            background-color: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .branding-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .branding-text p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 500px;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .login-form-container {
            width: 45%;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .login-header h2 {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .error-message {
            background-color: #fce8ea;
            border-left: 4px solid var(--error-color);
            color: #842029;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: fadeInDown 0.5s;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 1.2rem;
            color: var(--error-color);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.95rem;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all var(--transition-speed);
            background-color: #f8f9fa;
        }
        
        .input-with-icon input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(60, 145, 230, 0.15);
            background-color: white;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 1.2rem;
            transition: all var(--transition-speed);
        }
        
        .input-with-icon input:focus + i {
            color: var(--secondary-color);
        }
        
        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            margin-right: 8px;
            cursor: pointer;
        }
        
        .remember-me label {
            font-size: 0.9rem;
            color: #6c757d;
            cursor: pointer;
        }
        
        .forgot-password {
            font-size: 0.9rem;
            color: var(--secondary-color);
            text-decoration: none;
            transition: all var(--transition-speed);
        }
        
        .forgot-password:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .submit-btn {
            width: 100%;
            padding: 14px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all var(--transition-speed);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(60, 145, 230, 0.3);
        }
        
        .submit-btn:hover {
            background-color: #3285d7;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(60, 145, 230, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(60, 145, 230, 0.3);
        }
        
        .submit-btn i {
            font-size: 1.2rem;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-to-site a {
            color: #6c757d;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            transition: all var(--transition-speed);
            padding: 8px 16px;
            border-radius: 6px;
        }
        
        .back-to-site a:hover {
            color: var(--primary-color);
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .back-to-site a i {
            margin-right: 8px;
            font-size: 1rem;
        }
        
        /* Modal Styles for Forgot Password */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            max-width: 450px;
            width: 90%;
            margin: 15vh auto;
            padding: 30px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideDown 0.4s;
        }
        
        .modal-header {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .modal-header i {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 15px;
            display: block;
        }
        
        .modal-header h3 {
            color: var(--dark-color);
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .modal-body {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .modal-body p {
            margin-bottom: 15px;
        }
        
        .modal-footer {
            text-align: center;
        }
        
        .modal-btn {
            padding: 10px 20px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-speed);
        }
        
        .modal-btn:hover {
            background-color: #3285d7;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.2rem;
            color: #adb5bd;
            cursor: pointer;
            transition: all var(--transition-speed);
        }
        
        .close-modal:hover {
            color: var(--dark-color);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInDown {
            from { 
                opacity: 0; 
                transform: translateY(-10px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes floatUp {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-15px);
            }
            100% {
                transform: translateY(0px);
            }
        }
        
        /* Media queries for responsiveness */
        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 500px;
            }
            
            .login-branding, .login-form-container {
                width: 100%;
            }
            
            .login-branding {
                padding: 2rem;
                min-height: 300px;
                justify-content: center;
                align-items: center;
                text-align: center;
            }
            
            .branding-text h1 {
                font-size: 2rem;
            }
            
            .branding-text p {
                font-size: 1rem;
            }
            
            .school-logo {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .login-wrapper {
                box-shadow: none;
                background-color: transparent;
            }
            
            .login-branding {
                display: none;
            }
            
            .login-form-container {
                padding: 2rem;
                background-color: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            
            .form-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left side branding -->
        <div class="login-branding">
            <div class="school-logo">
                <img src="../assets/images/branding/logo-primary.png" alt="St. Raphaela Mary School Logo">
            </div>
            
            <div class="branding-text">
                <h1>St. Raphaela Mary School</h1>
                <p>Welcome to the SRMS Administration Portal. Please log in with your administrator credentials to access the school content management system.</p>
            </div>
        </div>
        
        <!-- Right side login form -->
        <div class="login-form-container">
            <div class="login-header">
                <h2>Admin Login</h2>
                <p>Enter your credentials to continue</p>
            </div>
            
            <?php if(!empty($login_error)): ?>
            <div class="error-message">
                <i class='bx bx-error-circle'></i>
                <span><?php echo $login_error; ?></span>
            </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                        <i class='bx bxs-user'></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class='bx bxs-lock-alt'></i>
                    </div>
                </div>
                
                <div class="form-footer">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="javascript:void(0);" id="forgot-password" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class='bx bx-log-in'></i> Log In
                </button>
            </form>
            
            <div class="back-to-site">
                <a href="../index.php">
                    <i class='bx bx-arrow-back'></i> Back to Website
                </a>
            </div>
        </div>
    </div>
    
    <!-- Forgot Password Modal -->
    <div id="forgot-password-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="close-modal">&times;</span>
            <div class="modal-header">
                <i class='bx bx-envelope'></i>
                <h3>Password Assistance</h3>
            </div>
            <div class="modal-body">
                <p>If you've forgotten your password, please contact the system administrator for assistance.</p>
                <p>You can reach out to the administrator via email at <strong><?php echo ADMIN_EMAIL; ?></strong> or by contacting the school's IT department directly.</p>
                <p>For security reasons, password reset requests must be verified and processed manually by authorized personnel.</p>
            </div>
            <div class="modal-footer">
                <button id="close-modal-btn" class="modal-btn">Got it</button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Forgot Password Modal
            const modal = document.getElementById('forgot-password-modal');
            const forgotPasswordLink = document.getElementById('forgot-password');
            const closeModal = document.getElementById('close-modal');
            const closeModalBtn = document.getElementById('close-modal-btn');
            
            // Open modal
            forgotPasswordLink.addEventListener('click', function() {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            });
            
            // Close modal functions
            function closeModalFunction() {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Re-enable scrolling
            }
            
            // Close modal events
            closeModal.addEventListener('click', closeModalFunction);
            closeModalBtn.addEventListener('click', closeModalFunction);
            
            // Close modal if clicked outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModalFunction();
                }
            });
            
            // Prevent form resubmission on page refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });
    </script>
</body>
</html>