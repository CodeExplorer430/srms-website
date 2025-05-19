<?php
/**
 * Mail Configuration Settings
 * This file contains the SMTP settings for the PHPMailer library
 */

// Email Configuration Constants
defined('MAIL_HOST') or define('MAIL_HOST', 'smtp.gmail.com');  // SMTP server address (e.g., smtp.gmail.com)
defined('MAIL_PORT') or define('MAIL_PORT', 587);               // SMTP port (typically 587 for TLS, 465 for SSL)
defined('MAIL_USERNAME') or define('MAIL_USERNAME', 'miguel.velasco.dev@gmail.com');  // Your email address
defined('MAIL_PASSWORD') or define('MAIL_PASSWORD', 'fvpo wooi jkzw dlra ');     // Your email password or app password
defined('MAIL_FROM') or define('MAIL_FROM', 'srms.edu.ph@gmail.com');      // From email address
defined('MAIL_FROM_NAME') or define('MAIL_FROM_NAME', 'St. Raphaela Mary School');  // From name
defined('MAIL_REPLY_TO') or define('MAIL_REPLY_TO', '');       // Reply-to email address (leave empty to use MAIL_FROM)
defined('MAIL_ENCRYPTION') or define('MAIL_ENCRYPTION', 'tls');       // Encryption type (tls or ssl)
defined('MAIL_DEBUG') or define('MAIL_DEBUG', 0);                // Debug level (0 = off, 1-4 for increasing verbosity)

// Admin Email for Receiving Notifications
defined('ADMIN_EMAIL') or define('ADMIN_EMAIL', 'admin@srms.edu.ph');  // School admin email address

// General Mail Settings
defined('MAIL_CHARSET') or define('MAIL_CHARSET', 'UTF-8');        // Email character set
defined('MAIL_CONTENT_TYPE') or define('MAIL_CONTENT_TYPE', 'text/plain');  // Content type (text/plain or text/html)