<?php
/**
 * Send Test Email Script
 * Handles test email requests for the Email Management Tools
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get the site root directory
$current_dir = dirname(__FILE__);
$tools_dir = dirname($current_dir);
$admin_dir = dirname($tools_dir);
$site_root = dirname($admin_dir);

// Define directory separator
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Include necessary files
require_once $site_root . DS . 'includes' . DS . 'config.php';
require_once $site_root . DS . 'includes' . DS . 'mail-config.php';
require_once $site_root . DS . 'includes' . DS . 'db.php';
require_once $site_root . DS . 'includes' . DS . 'MailService.php';

// Initialize response
$response = [
    'success' => false,
    'message' => 'An error occurred while sending the email'
];

// Process test email request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $to = isset($_POST['test_email']) ? trim($_POST['test_email']) : '';
    $subject = isset($_POST['test_subject']) ? trim($_POST['test_subject']) : 'Test Email from St. Raphaela Mary School';
    $message = isset($_POST['test_message']) ? trim($_POST['test_message']) : '';
    $format = isset($_POST['email_format']) ? trim($_POST['email_format']) : 'plain';
    
    // Validate inputs
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address';
    } elseif (empty($message)) {
        $response['message'] = 'Please enter a message';
    } else {
        // Replace placeholders
        $message = str_replace('{recipient_name}', explode('@', $to)[0], $message);
        
        // Send the email
        $is_html = ($format === 'html');
        $result = MailService::sendMail($to, $subject, $message, MAIL_FROM_NAME, MAIL_FROM, [], $is_html);
        
        // Set response based on result
        $response = $result;
        
        // Log the email sent result
        $db = new Database();
        
        // Check if email_logs table exists
        $tables = $db->query("SHOW TABLES LIKE 'email_logs'");
        if ($tables && $tables->num_rows > 0) {
            $escaped_recipient = $db->escape($to);
            $escaped_subject = $db->escape($subject);
            $escaped_message = $db->escape(substr($message, 0, 255)); // Truncate message to prevent overflow
            $status = $result['success'] ? 'sent' : 'failed';
            $error = $result['success'] ? '' : $db->escape($result['message']);
            
            $db->query("INSERT INTO email_logs (recipient, subject, message_preview, status, error, sent_at)
                       VALUES ('$escaped_recipient', '$escaped_subject', '$escaped_message', '$status', '$error', NOW())");
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>