<?php
/**
 * Contact Reply Page
 * Improved UI/UX version adapted to match existing site structure
 */

// Start session and check login
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
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
if (!$submission) {
    header('Location: contact-submissions.php?error=not_found');
    exit;
}

// Process form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply_message = isset($_POST['reply_message']) ? trim($_POST['reply_message']) : '';
    
    if (empty($reply_message)) {
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

        if (!$emailResult['success']) {
            $error = 'Failed to send email: ' . $emailResult['message'];
            // Log the error for troubleshooting
            error_log("Email sending failed: " . $emailResult['message']);
        }
        
        if ($emailResult['success']) {
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

// Set page title
$page_title = 'Reply to Contact';

// Start output buffer for main content
ob_start();
?>

<!-- Page-specific CSS -->
<style>
/* Enhanced styling for the Reply to Contact page */

/* General Layout */
.content-section {
    padding: 1.5rem;
    background-color: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}

/* Breadcrumb Navigation - adapted to match existing structure */
.custom-breadcrumb {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.custom-breadcrumb a {
    color: #0d6efd;
    text-decoration: none;
}

.custom-breadcrumb a:hover {
    text-decoration: underline;
}

.custom-breadcrumb i {
    margin: 0 0.5rem;
    color: #6c757d;
    font-size: 0.75rem;
}

/* Alerts */
.alert {
    display: flex;
    margin-bottom: 1.5rem;
    border-radius: 0.375rem;
    padding: 1rem 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.alert-success {
    background-color: #d1e7dd;
    border-left: 4px solid #198754;
}

.alert-danger {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
}

.alert-icon {
    margin-right: 1rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
}

.alert-success .alert-icon {
    color: #198754;
}

.alert-danger .alert-icon {
    color: #dc3545;
}

.alert-content {
    flex: 1;
}

.alert-content h4 {
    margin-top: 0;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 1.1rem;
}

/* Message Details Section */
.message-header {
    margin-bottom: 1.5rem;
    text-align: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.message-subject {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0a284c;
    margin-bottom: 1rem;
}

.message-meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 0.75rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meta-item i {
    color: #0d6efd;
}

.message-content-wrapper {
    display: flex;
    margin-bottom: 1.5rem;
}

.message-label {
    display: flex;
    align-items: flex-start;
    min-width: 150px;
    margin-right: 1rem;
}

.message-label .envelope-icon {
    margin-right: 0.5rem;
    color: #0d6efd;
}

.message-label-text {
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    text-transform: uppercase;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.75rem;
    background-color: #28a745;
    color: white;
}

.message-text {
    flex: 1;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    padding: 1.25rem;
    border-left: 3px solid #0d6efd;
    line-height: 1.6;
}

/* Reply Section */
.reply-section {
    margin-top: 2rem;
}

.compose-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.compose-header i {
    margin-right: 0.5rem;
    color: #0d6efd;
    font-size: 1.25rem;
}

.compose-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #212529;
}

.template-control {
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.template-label {
    font-weight: 500;
    margin-right: 0.5rem;
}

.custom-select {
    position: relative;
    min-width: 250px;
}

.custom-select select {
    appearance: none;
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    background-color: #fff;
    cursor: pointer;
}

.custom-select::after {
    content: '';
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 5px solid #6c757d;
    pointer-events: none;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

textarea.form-control {
    min-height: 200px;
    resize: vertical;
}

.character-counter {
    text-align: right;
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.375rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    padding: 0.75rem 1.25rem;
    border-radius: 0.375rem;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    user-select: none;
    border: 1px solid transparent;
    font-size: 1rem;
    line-height: 1.5;
    transition: all 0.15s ease-in-out;
}

.btn-primary {
    color: #fff;
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

.btn-outline {
    color: #6c757d;
    border-color: #ced4da;
    background-color: transparent;
}

.btn-outline:hover {
    color: #495057;
    background-color: #f8f9fa;
    border-color: #adb5bd;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .message-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .message-content-wrapper {
        flex-direction: column;
    }
    
    .message-label {
        margin-bottom: 0.75rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
    
    .template-control {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .custom-select {
        width: 100%;
    }
}

/* Animation for textarea highlight */
@keyframes highlight {
    0% { background-color: #e6f4ff; }
    100% { background-color: #fff; }
}

.highlight-animation {
    animation: highlight 1.5s ease;
}
</style>

<!-- Main Content -->
<div class="main-content-wrapper">
    <!-- Custom Breadcrumb - Matching the structure shown in your screenshot -->
    <div class="custom-breadcrumb">
        <a href="index.php"><i class='bx bx-home'></i> Dashboard</a>
        <i class='bx bx-chevron-right'></i>
        <a href="contact-submissions.php">Contact Submissions</a>
        <i class='bx bx-chevron-right'></i>
        <span>Reply to Contact</span>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <div class="alert-icon">
            <i class='bx bx-check-circle'></i>
        </div>
        <div class="alert-content">
            <h4>Reply Sent Successfully!</h4>
            <p>The submission status has been updated to "Replied" and the sender has been notified.</p>
            <div class="alert-actions">
                <a href="contact-submissions.php" class="btn btn-outline">
                    <i class='bx bx-arrow-back'></i> Back to All Submissions
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class='bx bxs-dashboard'></i> Go to Dashboard
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
                    
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <div class="alert-icon">
            <i class='bx bx-error-circle'></i>
        </div>
        <div class="alert-content">
            <h4>Error</h4>
            <p><?php echo $error; ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <!-- Message Content Section -->
    <div class="content-section">
        <!-- Message Header with Centered Information -->
        <div class="message-header">
            <h2 class="message-subject"><?php echo htmlspecialchars($submission['subject']); ?></h2>
            
            <div class="message-meta">
                <div class="meta-item">
                    <i class='bx bx-user'></i>
                    <span><?php echo htmlspecialchars($submission['name']); ?></span>
                </div>
                <div class="meta-item">
                    <i class='bx bx-envelope'></i>
                    <span><?php echo htmlspecialchars($submission['email']); ?></span>
                </div>
                <?php if ($submission['phone']): ?>
                <div class="meta-item">
                    <i class='bx bx-phone'></i>
                    <span><?php echo htmlspecialchars($submission['phone']); ?></span>
                </div>
                <?php endif; ?>
                <div class="meta-item">
                    <i class='bx bx-calendar'></i>
                    <span><?php echo date('F j, Y g:i A', strtotime($submission['submission_date'])); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Message Content with Label Structure -->
        <div class="message-content-wrapper">
            <div class="message-label">
                <i class='bx bx-envelope envelope-icon'></i>
                <div>
                    <span class="message-label-text">Message Details</span>
                    <span class="status-badge">READ</span>
                </div>
            </div>
            <div class="message-text">
                <?php echo nl2br(htmlspecialchars($submission['message'])); ?>
            </div>
        </div>
    </div>

    <!-- Reply Form Section -->
    <div class="content-section reply-section">
        <div class="compose-header">
            <i class='bx bx-edit'></i>
            <h3 class="compose-title">Compose Reply</h3>
        </div>
        
        <form method="post" action="reply.php?id=<?php echo $submission_id; ?>" class="reply-form">
            <div class="template-control">
                <span class="template-label">Choose a reply template:</span>
                <div class="custom-select">
                    <select id="template" onchange="loadTemplate()" class="form-control">
                        <option value="">Select a template...</option>
                        <option value="general">General Response</option>
                        <option value="enrollment">Enrollment Inquiry</option>
                        <option value="tuition">Tuition Information</option>
                        <option value="schedule">Schedule Information</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reply_message">Your Reply</label>
                <textarea id="reply_message" name="reply_message" class="form-control" required><?php echo isset($_POST['reply_message']) ? htmlspecialchars($_POST['reply_message']) : ''; ?></textarea>
                <div class="character-counter" id="character-counter">0 characters</div>
            </div>
            
            <div class="form-actions">
                <a href="contact-submissions.php" class="btn btn-outline">
                    <i class='bx bx-x'></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-send'></i> Send Reply
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- Inline JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Focus management for the textarea
        const replyTextarea = document.getElementById('reply_message');
        const templateSelect = document.getElementById('template');
        const counterElement = document.getElementById('character-counter');
        
        if (replyTextarea) {
            // Initial character count
            updateCounter();
            
            // Auto-focus on the textarea when the page loads if no template is selected
            if (templateSelect.value === '') {
                setTimeout(() => {
                    replyTextarea.focus();
                }, 500);
            }
            
            // Add confirmation before leaving page with unsaved changes
            let originalContent = replyTextarea.value;
            
            window.addEventListener('beforeunload', function(e) {
                if (replyTextarea.value !== originalContent && !document.querySelector('.form-submitted')) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                    return e.returnValue;
                }
            });
            
            // Update character counter
            replyTextarea.addEventListener('input', updateCounter);
            
            function updateCounter() {
                const count = replyTextarea.value.length;
                counterElement.textContent = `${count} characters`;
                
                // Change color if getting too long
                if (count > 2000) {
                    counterElement.style.color = '#dc3545';
                } else {
                    counterElement.style.color = '#6c757d';
                }
            }
        }
        
        // Mark form as submitted when the submit button is clicked
        const replyForm = document.querySelector('form.reply-form');
        if (replyForm) {
            replyForm.addEventListener('submit', function() {
                this.classList.add('form-submitted');
            });
        }
    });

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
            
            // Highlight animation
            replyMessage.classList.add('highlight-animation');
            
            // Update character counter
            const counterElement = document.getElementById('character-counter');
            const count = replyMessage.value.length;
            counterElement.textContent = `${count} characters`;
                
            if (count > 2000) {
                counterElement.style.color = '#dc3545';
            } else {
                counterElement.style.color = '#6c757d';
            }
            
            // Set focus to the textarea
            replyMessage.focus();
        }
    }
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Include the layout which will use $content
include 'layout.php';
?>