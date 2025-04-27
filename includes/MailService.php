<?php
/**
 * Mail Service Class
 * A wrapper for PHPMailer to handle email functionality across the website
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
     * @param string $to Recipient email address
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
            
            // Add a recipient
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
                $mail->AltBody = strip_tags($message);
            }
            
            // Send the email
            $mail->send();
            
            return [
                'success' => true,
                'message' => 'Email has been sent successfully'
            ];
            
        } catch (Exception $e) {
            // Log the error for administrators
            error_log("Mail Error: {$mail->ErrorInfo}");
            
            return [
                'success' => false,
                'message' => "Message could not be sent. Error: {$mail->ErrorInfo}"
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
}