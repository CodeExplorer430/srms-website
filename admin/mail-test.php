<?php
/**
 * PHPMailer Test Script
 * This script tests your email configuration to ensure it's working correctly.
 * Place this in a secure location, e.g., an admin folder with proper access controls.
 */

// Set page title
$page_title = 'Email System Test';

// Include necessary files
require_once '../includes/MailService.php';

// Initialize result variable
$result = null;

// Process the test if form is submitted
if (isset($_POST['send_test'])) {
    $to = isset($_POST['test_email']) ? trim($_POST['test_email']) : '';
    
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $result = [
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ];
    } else {
        // Send a test email
        $subject = 'Test Email from St. Raphaela Mary School Website';
        $message = "This is a test email sent from your website at " . date('Y-m-d H:i:s') . ".\n\n";
        $message .= "If you received this email, your mail configuration is working correctly.\n\n";
        $message .= "Mail Settings:\n";
        $message .= "- Host: " . MAIL_HOST . "\n";
        $message .= "- Port: " . MAIL_PORT . "\n";
        $message .= "- Encryption: " . MAIL_ENCRYPTION . "\n";
        $message .= "- From Email: " . MAIL_FROM . "\n";
        
        $result = MailService::sendMail($to, $subject, $message);
    }
}

// Include header
include_once('../includes/header.php');
?>

<div class="container" style="padding-top: 120px; max-width: 800px; margin: 0 auto;">
    <h1>Email System Test</h1>
    <p>Use this page to test your email configuration. This will help you verify that emails can be sent correctly from your website.</p>
    
    <?php if ($result !== null): ?>
        <div class="<?php echo $result['success'] ? 'success-message' : 'error-message'; ?>">
            <p><?php echo $result['message']; ?></p>
        </div>
    <?php endif; ?>
    
    <div class="card" style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); margin-top: 20px;">
        <h2>Send Test Email</h2>
        
        <form method="post" action="mail-test.php">
            <div class="form-group">
                <label for="test_email">Recipient Email:</label>
                <input type="email" id="test_email" name="test_email" required placeholder="Enter your email address">
                <small>This is where the test email will be sent.</small>
            </div>
            
            <div class="form-group">
                <button type="submit" name="send_test" class="submit-btn">Send Test Email</button>
            </div>
        </form>
    </div>
    
    <div class="card" style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); margin-top: 20px;">
        <h2>Email Configuration</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #eee;">Setting</th>
                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #eee;">Value</th>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">SMTP Host</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo MAIL_HOST; ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">SMTP Port</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo MAIL_PORT; ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">Encryption</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo MAIL_ENCRYPTION; ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">From Email</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo MAIL_FROM; ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">From Name</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo MAIL_FROM_NAME; ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">Admin Email</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><?php echo ADMIN_EMAIL; ?></td>
            </tr>
        </table>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>