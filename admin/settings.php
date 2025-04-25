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
$errors = [];
$success = false;

// Get school information
$school_info = $db->fetch_row("SELECT * FROM school_information LIMIT 1");

// If no school information exists, create default values
if(!$school_info) {
    $school_info = [
        'name' => 'ST. RAPHAELA MARY SCHOOL',
        'logo' => '/assets/images/branding/logo-primary.png',
        'mission' => '',
        'vision' => '',
        'philosophy' => '',
        'email' => 'srmseduc@gmail.com',
        'phone' => '8253-3801/0920 832 7705',
        'address' => '#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City'
    ];
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $logo = isset($_POST['logo']) ? trim($_POST['logo']) : '';
    $mission = isset($_POST['mission']) ? trim($_POST['mission']) : '';
    $vision = isset($_POST['vision']) ? trim($_POST['vision']) : '';
    $philosophy = isset($_POST['philosophy']) ? trim($_POST['philosophy']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    
    // Validate required fields
    if(empty($name)) {
        $errors[] = 'School name is required';
    }
    
    if(empty($email)) {
        $errors[] = 'Email is required';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address';
    }
    
    if(empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if(empty($address)) {
        $errors[] = 'Address is required';
    }
    
    // Process if no errors
    if(empty($errors)) {
        $name = $db->escape($name);
        $logo = $db->escape($logo);
        $mission = $db->escape($mission);
        $vision = $db->escape($vision);
        $philosophy = $db->escape($philosophy);
        $email = $db->escape($email);
        $phone = $db->escape($phone);
        $address = $db->escape($address);
        
        if(isset($school_info['id'])) {
            // Update existing record
            $sql = "UPDATE school_information SET 
                    name = '$name', 
                    logo = '$logo', 
                    mission = '$mission', 
                    vision = '$vision', 
                    philosophy = '$philosophy', 
                    email = '$email', 
                    phone = '$phone', 
                    address = '$address' 
                    WHERE id = {$school_info['id']}";
        } else {
            // Insert new record
            $sql = "INSERT INTO school_information (name, logo, mission, vision, philosophy, email, phone, address) 
                    VALUES ('$name', '$logo', '$mission', '$vision', '$philosophy', '$email', '$phone', '$address')";
        }
        
        if($db->query($sql)) {
            $success = true;
            // Refresh school info
            $school_info = $db->fetch_row("SELECT * FROM school_information LIMIT 1");
        } else {
            $errors[] = 'An error occurred while saving the settings';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Settings | Admin Dashboard</title>
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
        
        /* Settings form styles */
        .settings-container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section h3 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }
        .form-group textarea {
            height: 120px;
            resize: vertical;
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
                <div class="menu-item active">
                    <a href="settings.php">
                        <i class='bx bxs-cog'></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h2>School Settings</h2>
                <div class="user-info">
                    <div class="name">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <?php if($success): ?>
                <div class="success-message">Settings have been saved successfully.</div>
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
            
            <div class="settings-container">
                <form action="settings.php" method="post">
                    <div class="form-section">
                        <h3>Basic Information</h3>
                        <div class="form-group">
                            <label for="name">School Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($school_info['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="logo">Logo Path</label>
                            <input type="text" id="logo" name="logo" value="<?php echo htmlspecialchars($school_info['logo']); ?>">
                            <small style="color:#666;">Relative path to logo file (e.g., /assets/images/logo.png)</small>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Contact Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($school_info['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($school_info['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" required><?php echo htmlspecialchars($school_info['address']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>School Philosophy</h3>
                        <div class="form-group">
                            <label for="mission">Mission</label>
                            <textarea id="mission" name="mission"><?php echo htmlspecialchars($school_info['mission']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="vision">Vision</label>
                            <textarea id="vision" name="vision"><?php echo htmlspecialchars($school_info['vision']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="philosophy">Philosophy</label>
                            <textarea id="philosophy" name="philosophy"><?php echo htmlspecialchars($school_info['philosophy']); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="save-btn">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>