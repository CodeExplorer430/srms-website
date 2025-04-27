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
// Include the MailService class
require_once '../includes/MailService.php';

$db = new Database();

// Get submission ID from URL
$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get submission details
$submission = $db->fetch_row("SELECT * FROM contact_submissions WHERE id = $submission_id");
if(!$submission) {
    header('Location: contact-submissions.php?error=not_found');
    exit;
}

// Process form submission
$success = false;
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply_message = isset($_POST['reply_message']) ? trim($_POST['reply_message']) : '';
    
    if(empty($reply_message)) {
        $error = 'Reply message cannot be empty';
    } else {
        // Prepare email content
        $to = $submission['email'];
        $subject = "Re: " . $submission['subject'];
        
        // Create HTML message
        $htmlMessage = "<html><body>";
        $htmlMessage .= "<p>Dear " . htmlspecialchars($submission['name']) . ",</p>";
        $htmlMessage .= "<p>" . nl2br(htmlspecialchars($reply_message)) . "</p>";
        $htmlMessage .= "<p>Best regards,<br>St. Raphaela Mary School</p>";
        $htmlMessage .= "</body></html>";
        
        // Send email using MailService
        $emailResult = MailService::sendMail(
            $to,                     // Recipient
            $subject,                // Subject
            $htmlMessage,            // Message
            MAIL_FROM_NAME,          // From name
            MAIL_FROM,               // From email
            [],                      // No attachments
            true                     // Is HTML
        );

        if(!$emailResult['success']) {
            $error = 'Failed to send email: ' . $emailResult['message'];
            // Log the error for troubleshooting
            error_log("Email sending failed: " . $emailResult['message']);
        }
        
        if($emailResult['success']) {
            // Update submission status
            $db->query("UPDATE contact_submissions SET status = 'replied' WHERE id = $submission_id");
            
            // Log the reply
            $admin_id = $_SESSION['admin_user_id'];
            $reply_content = $db->escape($reply_message);
            $db->query("INSERT INTO submission_replies (submission_id, admin_id, reply_content, reply_date) 
                       VALUES ($submission_id, $admin_id, '$reply_content', NOW())");
            
            $success = true;
        } else {
            $error = 'Failed to send email: ' . $emailResult['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply to Contact | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Add the same admin panel styling here */
        
        /* Reply-specific styles */
        .submission-details {
            background-color: #f8f9fa;
            border-left: 4px solid #0a3060;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        
        .submission-header {
            margin-bottom: 15px;
        }
        
        .submission-header h3 {
            margin: 0 0 5px 0;
            color: #0a3060;
        }
        
        .submission-meta {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .submission-content {
            background-color: white;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        
        .reply-form {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: relative;
        }

        .reply-form::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 30px;
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-bottom: 15px solid white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .form-header h3 {
            margin: 0;
            color: #0a3060;
            font-size: 18px;
            font-weight: 600;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }

        .form-group textarea {
            width: 100%;
            height: 220px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            transition: all 0.3s;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #3C91E6;
            box-shadow: 0 0 0 2px rgba(60,145,230,0.1);
        }

        .reply-template-selector {
            margin-bottom: 15px;
        }

        .reply-template-selector select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
            color: #495057;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 10px;
        }
        
        .send-btn {
            background-color: #3C91E6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .send-btn i {
            margin-right: 8px;
        }
        
        .send-btn:hover {
            background-color: #2c7ed6;
        }
        
        .cancel-btn {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .cancel-btn:hover {
            background-color: #e9ecef;
        }
        
        .success-message {
            background-color: #d1e7dd;
            color: #0f5132;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .success-message i {
            margin-right: 10px;
            font-size: 22px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #842029;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 22px;
        }
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
        @media screen and (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: relative;
            }
            
            .sidebar .menu {
                display: flex;
                overflow-x: auto;
            }
            
            .sidebar .menu-item {
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .sidebar .menu-item.active {
                border-left: none;
                border-bottom: 3px solid #3C91E6;
            }
            
            .submission-details,
            .reply-form {
                padding: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons a,
            .action-buttons button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Include sidebar here -->
        
        <div class="main-content">
            <div class="top-bar">
                <h2>Reply to Contact</h2>
                <div class="user-info">
                    <div class="name">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
                    <a href="logout.php" class="logout-btn">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </div>
            </div>
            
            <?php if($success): ?>
            <div class="success-message">
                <i class='bx bx-check-circle'></i>
                <div>
                    <strong>Reply sent successfully!</strong>
                    <p>The submission status has been updated to "Replied" and the sender has been notified.</p>
                    <div style="margin-top: 15px;">
                        <a href="contact-submissions.php" class="action-btn view-btn" style="text-decoration: none;">
                            <i class='bx bx-arrow-back'></i> Back to All Submissions
                        </a>
                        <a href="index.php" class="action-btn" style="background-color: rgba(25, 135, 84, 0.1); color: #198754; text-decoration: none; margin-left: 10px;">
                            <i class='bx bxs-dashboard'></i> Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
                        
            <?php if($error): ?>
            <div class="error-message">
                <i class='bx bx-error-circle'></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <div class="submission-details">
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
            </div>
            
            <?php if(!$success): ?>
            <div class="reply-form">
                <div class="form-header">
                    <h3>Compose Reply</h3>
                </div>
                
                <div class="reply-template-selector">
                    <label for="template">Choose template:</label>
                    <select id="template" onchange="loadTemplate()">
                        <option value="">Select a template...</option>
                        <option value="general">General Response</option>
                        <option value="enrollment">Enrollment Inquiry</option>
                        <option value="tuition">Tuition Information</option>
                        <option value="schedule">Schedule Information</option>
                    </select>
                </div>
                
                <form method="post" action="reply.php?id=<?php echo $submission_id; ?>">
                    <div class="form-group">
                        <label for="reply_message">Your Reply</label>
                        <textarea id="reply_message" name="reply_message" required><?php echo isset($_POST['reply_message']) ? htmlspecialchars($_POST['reply_message']) : ''; ?></textarea>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="contact-submissions.php" class="cancel-btn">Cancel</a>
                        <button type="submit" class="send-btn">
                            <i class='bx bx-send'></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>

            <script>
                function loadTemplate() {
                    const templateSelect = document.getElementById('template');
                    const replyMessage = document.getElementById('reply_message');
                    const recipientName = "<?php echo htmlspecialchars($submission['name']); ?>";
                    
                    // Template content
                    const templates = {
                        general: `Dear ${recipientName},\n\nThank you for contacting St. Raphaela Mary School. We appreciate your interest.\n\n[Your response here]\n\nIf you have any further questions, please don't hesitate to contact us.\n\nBest regards,\nSt. Raphaela Mary School`,
                        
                        enrollment: `Dear ${recipientName},\n\nThank you for your inquiry about enrollment at St. Raphaela Mary School.\n\nFor enrollment, you will need to submit the following requirements:\n1. Report Card from previous school\n2. Good Moral Certificate\n3. PSA Birth Certificate (original + photocopy)\n4. Completed application form\n\nPlease visit our Admissions Office during office hours (Monday to Friday, 8:00 AM to 4:00 PM) to submit these requirements and take the entrance examination.\n\nBest regards,\nSt. Raphaela Mary School`,
                        
                        tuition: `Dear ${recipientName},\n\nThank you for your inquiry about tuition fees at St. Raphaela Mary School.\n\nWe're pleased to inform you that our Senior High School program is FREE for all students, whether from public or private schools. Additionally, incoming Grade 11 students from public schools will receive FREE school and P.E. uniform with FREE ID with lace and a CASH incentive.\n\nFor more detailed information about our tuition and fee structure for other grade levels, please visit our Accounting Office.\n\nBest regards,\nSt. Raphaela Mary School`,
                        
                        schedule: `Dear ${recipientName},\n\nThank you for your inquiry about schedules at St. Raphaela Mary School.\n\nOur regular class schedules are as follows:\n- Preschool: 8:00 AM to 11:30 AM\n- Elementary: 7:30 AM to 3:30 PM\n- Junior High School: 7:30 AM to 4:00 PM\n- Senior High School: 7:30 AM to 4:30 PM\n\nFor specific concerns about class schedules, please contact our Registrar's Office.\n\nBest regards,\nSt. Raphaela Mary School`
                    };
                    
                    // Set the template content
                    if (templateSelect.value && templates[templateSelect.value]) {
                        replyMessage.value = templates[templateSelect.value];
                    }
                }
            </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>