<?php
/**
 * Mail Service Class (Improved Version)
 * A wrapper for PHPMailer to handle email functionality across the website
 * with enhanced error handling and logging capabilities
 */

// Include mail configuration
require_once 'mail-config.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Fixed paths to PHPMailer files using relative paths
require_once __DIR__ . '/../libraries/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libraries/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/PHPMailer/src/SMTP.php';

class MailService {
    /**
     * Send an email using PHPMailer
     *
     * @param string|array $to Recipient email address(es)
     * @param string $subject Email subject
     * @param string $message Email message body
     * @param string $fromName Optional sender name (defaults to config value)
     * @param string $fromEmail Optional sender email (defaults to config value)
     * @param array $attachments Optional array of file paths to attach
     * @param boolean $isHTML Whether the message is HTML (defaults to false)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendMail($to, $subject, $message, $fromName = '', $fromEmail = '', $attachments = [], $isHTML = false) {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = MAIL_DEBUG;
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = MAIL_CHARSET;
            
            // Sender
            $fromName = !empty($fromName) ? $fromName : MAIL_FROM_NAME;
            $fromEmail = !empty($fromEmail) ? $fromEmail : MAIL_FROM;
            $mail->setFrom($fromEmail, $fromName);
            
            // Add reply-to if defined
            if (defined('MAIL_REPLY_TO') && !empty(MAIL_REPLY_TO)) {
                $mail->addReplyTo(MAIL_REPLY_TO, $fromName);
            }
            
            // Add recipients
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $mail->addAddress($recipient);
                }
            } else {
                $mail->addAddress($to);
            }
            
            // Add attachments
            if (!empty($attachments) && is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    }
                }
            }
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            
            // If the message is HTML, set a plain text alternative
            if ($isHTML) {
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
            }
            
            // Send the email
            $mail->send();
            
            // Log successful email in the database if enabled
            self::logEmail($to, $subject, $message, 'sent');
            
            return [
                'success' => true,
                'message' => 'Email has been sent successfully'
            ];
            
        } catch (Exception $e) {
            // Get the detailed error message
            $errorMessage = $mail->ErrorInfo;
            
            // Log the error
            error_log("Mail Error: {$errorMessage}");
            
            // Log failed email in the database if enabled
            self::logEmail($to, $subject, $message, 'failed', $errorMessage);
            
            return [
                'success' => false,
                'message' => "Message could not be sent. Error: {$errorMessage}"
            ];
        }
    }
    
    /**
     * Send a contact form submission notification
     *
     * @param string $name Sender's name
     * @param string $email Sender's email
     * @param string $phone Sender's phone number
     * @param string $subject Email subject
     * @param string $message Contact form message
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendContactNotification($name, $email, $phone, $subject, $message) {
        $emailSubject = "New Contact Form Submission: $subject";
        
        $emailBody = "A new contact form submission has been received at St. Raphaela Mary School website.\n\n";
        $emailBody .= "Name: $name\n";
        $emailBody .= "Email: $email\n";
        $emailBody .= "Phone: " . ($phone ? $phone : "Not provided") . "\n\n";
        $emailBody .= "Subject: $subject\n\n";
        $emailBody .= "Message:\n$message\n\n";
        $emailBody .= "This message was submitted on " . date('Y-m-d H:i:s') . ".";
        
        return self::sendMail(ADMIN_EMAIL, $emailSubject, $emailBody, MAIL_FROM_NAME, MAIL_FROM);
    }
    
    /**
     * Send notification email using a template
     *
     * @param string $to Recipient email
     * @param string $templateKey Template identifier
     * @param array $data Data to populate in the template
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendTemplate($to, $templateKey, $data = []) {
        // Template definitions - move to database in the future
        $templates = [
            'welcome' => [
                'subject' => 'Welcome to St. Raphaela Mary School',
                'body' => "Dear {name},\n\nWelcome to St. Raphaela Mary School! We're delighted to have you as part of our community.\n\nThis email confirms that your account has been successfully created.\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\nSt. Raphaela Mary School",
                'isHTML' => false
            ],
            'password_reset' => [
                'subject' => 'Password Reset Request',
                'body' => "Dear {name},\n\nWe received a request to reset your password for your account.\n\nPlease click on the link below to reset your password:\n{reset_link}\n\nThis link will expire in 24 hours.\n\nIf you did not request a password reset, please ignore this email.\n\nRegards,\nSt. Raphaela Mary School",
                'isHTML' => false
            ],
            'contact_reply' => [
                'subject' => 'Re: {original_subject}',
                'body' => "Dear {name},\n\nThank you for contacting St. Raphaela Mary School.\n\n{message}\n\nIf you have any further questions, please don't hesitate to contact us.\n\nBest regards,\nSt. Raphaela Mary School",
                'isHTML' => false
            ]
        ];
        
        // Check if template exists
        if (!isset($templates[$templateKey])) {
            return [
                'success' => false,
                'message' => "Template $templateKey not found"
            ];
        }
        
        $template = $templates[$templateKey];
        
        // Replace placeholders in subject and body
        $subject = $template['subject'];
        $body = $template['body'];
        
        foreach ($data as $key => $value) {
            $subject = str_replace("{{$key}}", $value, $subject);
            $body = str_replace("{{$key}}", $value, $body);
        }
        
        // Send the email
        return self::sendMail($to, $subject, $body, MAIL_FROM_NAME, MAIL_FROM, [], $template['isHTML']);
    }
    
    /**
     * Log email to database if logging table exists
     *
     * @param string|array $to Recipient email(s)
     * @param string $subject Email subject
     * @param string $message Email message
     * @param string $status Email status (sent/failed)
     * @param string $error Error message if status is failed
     * @return void
     */
    private static function logEmail($to, $subject, $message, $status = 'sent', $error = '') {
        // Skip logging if not in a web context
        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            return;
        }
        
        try {
            // Auto-detect the includes directory
            $dir = dirname(__FILE__);
            
            // Try to include the db.php file
            if (file_exists($dir . DIRECTORY_SEPARATOR . 'db.php')) {
                include_once $dir . DIRECTORY_SEPARATOR . 'db.php';
            } else {
                return; // Can't find db.php, skip logging
            }
            
            // Initialize database connection
            $db = new Database();
            
            // Convert recipient array to string
            if (is_array($to)) {
                $to = implode(', ', $to);
            }
            
            // Check if email_logs table exists
            $tables = $db->query("SHOW TABLES LIKE 'email_logs'");
            if ($tables && $tables->num_rows > 0) {
                // Escape data for database insertion
                $to = $db->escape($to);
                $subject = $db->escape($subject);
                
                // Truncate message to prevent issues with very long content
                $messagePreview = $db->escape(substr($message, 0, 500));
                $error = $db->escape($error);
                
                // Insert log entry
                $db->query("INSERT INTO email_logs (recipient, subject, message_preview, status, error, sent_at)
                           VALUES ('$to', '$subject', '$messagePreview', '$status', '$error', NOW())");
            }
        } catch (Exception $e) {
            // Just log the error but don't affect email flow
            error_log('Error logging email: ' . $e->getMessage());
        }
    }
}