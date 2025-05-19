<?php
/**
 * Email Management & Testing Tools
 * A comprehensive interface for email configuration and testing
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Get the site root directory more reliably
$current_dir = dirname(__FILE__);
$tools_dir = dirname($current_dir);
$admin_dir = dirname($tools_dir);
$site_root = dirname($admin_dir);

// Include environment file first to ensure constants are loaded
if (file_exists($site_root . DIRECTORY_SEPARATOR . 'environment.php')) {
    require_once $site_root . DIRECTORY_SEPARATOR . 'environment.php';
} else {
    die('Environment file not found. Please make sure environment.php exists in the site root directory.');
}

// Include necessary files using absolute paths
include_once $site_root . DS . 'includes' . DS . 'config.php';
include_once $site_root . DS . 'includes' . DS . 'db.php';
include_once $site_root . DS . 'includes' . DS . 'functions.php';
include_once $site_root . DS . 'includes' . DS . 'mail-config.php';
include_once $site_root . DS . 'includes' . DS . 'MailService.php';

// Initialize database connection
$db = new Database();

// Load email log if available
function getEmailLogs($limit = 10) {
    global $db;
    
    // Check if email_logs table exists
    $tables = $db->query("SHOW TABLES LIKE 'email_logs'");
    if ($tables && $tables->num_rows > 0) {
        $logs = $db->fetch_all("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT $limit");
        return $logs;
    }
    
    return [];
}

// Initialize variables
$test_result = null;
$email_logs = getEmailLogs();
$message_templates = [
    'welcome' => [
        'name' => 'Welcome Message',
        'subject' => 'Welcome to St. Raphaela Mary School',
        'body' => "Dear {recipient_name},\n\nWelcome to St. Raphaela Mary School! We're delighted to have you as part of our community.\n\nThis email confirms that your account has been successfully created.\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\nSt. Raphaela Mary School"
    ],
    'event' => [
        'name' => 'Event Notification',
        'subject' => 'Upcoming School Event: [Event Name]',
        'body' => "Dear {recipient_name},\n\nWe're excited to announce our upcoming school event: [Event Name].\n\nDate: [Event Date]\nTime: [Event Time]\nLocation: [Event Location]\n\nWe hope to see you there!\n\nBest regards,\nSt. Raphaela Mary School"
    ],
    'password_reset' => [
        'name' => 'Password Reset',
        'subject' => 'Password Reset Request',
        'body' => "Dear {recipient_name},\n\nWe received a request to reset your password. If you did not make this request, please ignore this email.\n\nTo reset your password, please click on the following link:\n[Reset Link]\n\nThis link will expire in 24 hours.\n\nBest regards,\nSt. Raphaela Mary School"
    ],
    'newsletter' => [
        'name' => 'Monthly Newsletter',
        'subject' => 'St. Raphaela Mary School - Monthly Newsletter',
        'body' => "Dear {recipient_name},\n\nAttached is our monthly newsletter with the latest updates and events from St. Raphaela Mary School.\n\nHighlights this month:\n- [Highlight 1]\n- [Highlight 2]\n- [Highlight 3]\n\nWe hope you find this information useful!\n\nBest regards,\nSt. Raphaela Mary School"
    ]
];

// Process form submission for test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test') {
    $recipient = isset($_POST['test_email']) ? trim($_POST['test_email']) : '';
    $subject = isset($_POST['test_subject']) ? trim($_POST['test_subject']) : 'Test Email from SRMS';
    $message = isset($_POST['test_message']) ? trim($_POST['test_message']) : '';
    $format = isset($_POST['email_format']) ? trim($_POST['email_format']) : 'plain';
    
    if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $test_result = [
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ];
    } elseif (empty($message)) {
        $test_result = [
            'success' => false,
            'message' => 'Please enter a message.'
        ];
    } else {
        // Replace placeholder if present
        $message = str_replace('{recipient_name}', explode('@', $recipient)[0], $message);
        
        // Send test email
        $is_html = ($format === 'html');
        $test_result = MailService::sendMail($recipient, $subject, $message, MAIL_FROM_NAME, MAIL_FROM, [], $is_html);
        
        // Log the email (if email_logs table exists)
        $tables = $db->query("SHOW TABLES LIKE 'email_logs'");
        if ($tables && $tables->num_rows > 0) {
            $escaped_recipient = $db->escape($recipient);
            $escaped_subject = $db->escape($subject);
            $escaped_message = $db->escape(substr($message, 0, 255)); // Truncate message to prevent overflow
            $status = $test_result['success'] ? 'sent' : 'failed';
            $error = $test_result['success'] ? '' : $db->escape($test_result['message']);
            
            $db->query("INSERT INTO email_logs (recipient, subject, message_preview, status, error, sent_at)
                       VALUES ('$escaped_recipient', '$escaped_subject', '$escaped_message', '$status', '$error', NOW())");
        }
        
        // Refresh logs
        $email_logs = getEmailLogs();
    }
}

// Check if email_logs table should be created
if (isset($_POST['action']) && $_POST['action'] === 'create_logs_table') {
    $tables = $db->query("SHOW TABLES LIKE 'email_logs'");
    if (!$tables || $tables->num_rows === 0) {
        $create_table_sql = "CREATE TABLE email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message_preview TEXT,
            status ENUM('sent', 'failed') NOT NULL,
            error TEXT,
            sent_at DATETIME NOT NULL
        )";
        
        if ($db->query($create_table_sql)) {
            $test_result = [
                'success' => true,
                'message' => 'Email logs table created successfully.'
            ];
        } else {
            $test_result = [
                'success' => false,
                'message' => 'Failed to create email logs table.'
            ];
        }
    } else {
        $test_result = [
            'success' => true,
            'message' => 'Email logs table already exists.'
        ];
    }
}

// Check for PHPMailer installation
function checkPhpMailerInstallation() {
    global $site_root;
    
    $phpmailer_path = $site_root . DS . 'libraries' . DS . 'PHPMailer';
    $has_phpmailer = file_exists($phpmailer_path);
    $has_required_files = false;
    
    if ($has_phpmailer) {
        $required_files = [
            $phpmailer_path . DS . 'src' . DS . 'PHPMailer.php',
            $phpmailer_path . DS . 'src' . DS . 'SMTP.php',
            $phpmailer_path . DS . 'src' . DS . 'Exception.php'
        ];
        
        $has_required_files = true;
        foreach ($required_files as $file) {
            if (!file_exists($file)) {
                $has_required_files = false;
                break;
            }
        }
    }
    
    return [
        'has_phpmailer' => $has_phpmailer,
        'has_required_files' => $has_required_files,
        'path' => $phpmailer_path
    ];
}

$phpmailer_status = checkPhpMailerInstallation();

// Use appropriate tab based on POST action
$active_tab = 'test';
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'send_test') {
        $active_tab = 'test';
    } elseif ($_POST['action'] === 'create_logs_table') {
        $active_tab = 'logs';
    } elseif ($_POST['action'] === 'save_config') {
        $active_tab = 'config';
    }
}

// Start output buffer for main content
ob_start();
?>

<div class="email-tools">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-envelope'></i> Email Management Tools</h2>
            <p>Configure, test, and monitor email functionality</p>
        </div>
        <div class="banner-actions">
            <a href="../index.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Tools
            </a>
        </div>
    </div>
    
    <?php if ($test_result !== null): ?>
    <div class="alerts">
        <div class="alert alert-<?php echo $test_result['success'] ? 'success' : 'danger'; ?>">
            <i class='bx bx-<?php echo $test_result['success'] ? 'check-circle' : 'error-circle'; ?>'></i>
            <div class="alert-content"><?php echo htmlspecialchars($test_result['message']); ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Environment Information -->
    <div class="environment-info">
        <div class="alert alert-info">
            <i class='bx bx-info-circle'></i>
            <div class="alert-content">
                <strong>Mail Configuration:</strong> 
                SMTP: <?php echo MAIL_HOST . ':' . MAIL_PORT; ?> | 
                From: <?php echo MAIL_FROM; ?> | 
                Encryption: <?php echo MAIL_ENCRYPTION; ?>
            </div>
        </div>
    </div>
    
    <!-- PHPMailer Status -->
    <?php if (!$phpmailer_status['has_phpmailer'] || !$phpmailer_status['has_required_files']): ?>
    <div class="alerts">
        <div class="alert alert-warning">
            <i class='bx bx-error-circle'></i>
            <div class="alert-content">
                <strong>PHPMailer Issue Detected!</strong> 
                <?php if (!$phpmailer_status['has_phpmailer']): ?>
                    PHPMailer directory not found at: <?php echo htmlspecialchars($phpmailer_status['path']); ?>
                <?php else: ?>
                    Some required PHPMailer files are missing.
                <?php endif; ?>
                <br>
                Please ensure PHPMailer is properly installed in the libraries/PHPMailer directory.
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="tab-container">
        <div class="tab-header">
            <button type="button" class="tab-btn <?php echo $active_tab === 'test' ? 'active' : ''; ?>" data-tab="test">
                <i class='bx bx-send'></i> Test Email
            </button>
            <button type="button" class="tab-btn <?php echo $active_tab === 'config' ? 'active' : ''; ?>" data-tab="config">
                <i class='bx bx-cog'></i> Configuration
            </button>
            <button type="button" class="tab-btn <?php echo $active_tab === 'templates' ? 'active' : ''; ?>" data-tab="templates">
                <i class='bx bx-file'></i> Templates
            </button>
            <button type="button" class="tab-btn <?php echo $active_tab === 'logs' ? 'active' : ''; ?>" data-tab="logs">
                <i class='bx bx-list-ul'></i> Email Logs
            </button>
        </div>
        
        <div class="tab-content">
            <!-- Test Email Tab -->
            <div class="tab-pane <?php echo $active_tab === 'test' ? 'active' : ''; ?>" id="test">
                <div class="email-test-section">
                    <div class="section-header">
                        <h3><i class='bx bx-paper-plane'></i> Send Test Email</h3>
                    </div>
                    
                    <div class="section-content">
                        <form method="post" action="" class="email-test-form">
                            <input type="hidden" name="action" value="send_test">
                            
                            <div class="form-group">
                                <label for="test_email">Recipient Email:</label>
                                <input type="email" id="test_email" name="test_email" class="form-control" required
                                       placeholder="Enter recipient email address">
                                <small class="form-text">The email address where the test message will be sent.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="test_subject">Subject:</label>
                                <input type="text" id="test_subject" name="test_subject" class="form-control" required
                                       placeholder="Email subject" value="Test Email from St. Raphaela Mary School">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label>Email Format:</label>
                                    <div class="radio-group">
                                        <label class="radio-container">
                                            <input type="radio" name="email_format" value="plain" checked> 
                                            <span>Plain Text</span>
                                        </label>
                                        <label class="radio-container">
                                            <input type="radio" name="email_format" value="html"> 
                                            <span>HTML</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group col-md-4">
                                    <label for="template_select">Load Template:</label>
                                    <select id="template_select" class="form-control" onchange="loadTemplate()">
                                        <option value="">Select a template...</option>
                                        <?php foreach ($message_templates as $key => $template): ?>
                                        <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="test_message">Message:</label>
                                <textarea id="test_message" name="test_message" class="form-control" rows="10" required
                                          placeholder="Enter your message here..."></textarea>
                                <small class="form-text">You can use {recipient_name} as a placeholder that will be replaced with the recipient's name.</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class='bx bx-send'></i> Send Test Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="email-info-section">
                    <div class="section-header">
                        <h3><i class='bx bx-info-circle'></i> Email Troubleshooting</h3>
                    </div>
                    
                    <div class="section-content">
                        <div class="troubleshooting-card">
                            <h4>Common Email Issues:</h4>
                            <ul>
                                <li><strong>Email not received:</strong> Check spam/junk folders and verify the recipient address is correct.</li>
                                <li><strong>Authentication failed:</strong> Verify your SMTP username and password in the configuration.</li>
                                <li><strong>Connection timeout:</strong> Check if the SMTP server is accessible and that the port is correct.</li>
                                <li><strong>SSL/TLS issues:</strong> Ensure the correct encryption method is selected for your email provider.</li>
                            </ul>
                            
                            <h4>Email Provider-Specific Notes:</h4>
                            
                            <div class="provider-notes">
                                <div class="provider-note">
                                    <h5><i class='bx bxl-gmail'></i> Gmail</h5>
                                    <ul>
                                        <li>Use <code>smtp.gmail.com</code> with port <code>587</code> (TLS) or <code>465</code> (SSL)</li>
                                        <li>Enable "Less secure apps" or create an App Password if using 2FA</li>
                                    </ul>
                                </div>
                                
                                <div class="provider-note">
                                    <h5><i class='bx bx-globe'></i> Office 365/Outlook</h5>
                                    <ul>
                                        <li>Use <code>smtp.office365.com</code> with port <code>587</code> (TLS)</li>
                                        <li>Use your full email address as the username</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Configuration Tab -->
            <div class="tab-pane <?php echo $active_tab === 'config' ? 'active' : ''; ?>" id="config">
                <div class="config-warning">
                    <i class='bx bx-shield-quarter'></i>
                    <div class="warning-content">
                        <h4>Email Configuration Management</h4>
                        <p>This section shows your current email configuration settings. To modify these settings, edit the <code>includes/mail-config.php</code> file or use the Edit Configuration tool below.</p>
                    </div>
                </div>
                
                <div class="config-section">
                    <div class="section-header">
                        <h3><i class='bx bx-cog'></i> Current Configuration</h3>
                    </div>
                    
                    <div class="section-content">
                        <div class="config-card">
                            <div class="config-group">
                                <h4>SMTP Settings</h4>
                                <div class="config-table">
                                    <div class="config-row">
                                        <div class="config-label">SMTP Host:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(MAIL_HOST); ?></div>
                                    </div>
                                    <div class="config-row">
                                        <div class="config-label">SMTP Port:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(MAIL_PORT); ?></div>
                                    </div>
                                    <div class="config-row">
                                        <div class="config-label">SMTP Username:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(MAIL_USERNAME); ?></div>
                                    </div>
                                    <div class="config-row">
                                        <div class="config-label">SMTP Password:</div>
                                        <div class="config-value">
                                            <span class="password-mask">••••••••••••</span>
                                            <button type="button" class="btn btn-sm btn-light btn-reveal" onclick="revealPassword()">
                                                <i class='bx bx-show'></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="config-row">
                                        <div class="config-label">Encryption:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(MAIL_ENCRYPTION); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="config-group">
                                <h4>Sender Information</h4>
                                <div class="config-table">
                                    <div class="config-row">
                                        <div class="config-label">From Email:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(MAIL_FROM); ?></div>
                                    </div>
                                    <div class="config-row">
                                        <div class="config-label">From Name:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(MAIL_FROM_NAME); ?></div>
                                    </div>
                                    <div class="config-row">
                                        <div class="config-label">Reply-To Email:</div>
                                        <div class="config-value"><?php echo defined('MAIL_REPLY_TO') ? htmlspecialchars(MAIL_REPLY_TO) : 'Not set (defaults to From Email)'; ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="config-group">
                                <h4>Additional Settings</h4>
                                <div class="config-table">
                                    <div class="config-row">
                                        <div class="config-label">Debug Level:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(MAIL_DEBUG); ?> (<?php echo MAIL_DEBUG === 0 ? 'Off' : 'Level ' . MAIL_DEBUG; ?>)</div>
                                    </div>
                                    <div class="config-row">
                                        <div class="config-label">Charset:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(MAIL_CHARSET); ?></div>
                                    </div>
                                    <div class="config-row">
                                        <div class="config-label">Content Type:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(MAIL_CONTENT_TYPE); ?></div>
                                    </div>
                                    <div class="config-row">
                                        <div class="config-label">Admin Email:</div>
                                        <div class="config-value"><?php echo htmlspecialchars(ADMIN_EMAIL); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="config-section">
                    <div class="section-header">
                        <h3><i class='bx bx-edit'></i> Edit Configuration</h3>
                    </div>
                    
                    <div class="section-content">
                        <p>To edit the email configuration, visit the <a href="email-config.php" class="btn btn-primary btn-sm">Email Configuration Editor</a></p>
                        
                        <div class="config-info-card">
                            <h4>About Email Configuration</h4>
                            <p>The email configuration settings are stored in the <code>includes/mail-config.php</code> file. This file contains constants that define how emails are sent from your website.</p>
                            
                            <h5>Important Notes:</h5>
                            <ul>
                                <li>Always back up your configuration file before making changes.</li>
                                <li>After changing the configuration, use the Test Email tool to verify the settings work correctly.</li>
                                <li>For Gmail, you may need to use an "App Password" if you have 2-factor authentication enabled.</li>
                                <li>The Debug Level setting can be useful for troubleshooting (0 = Off, 1-4 = increasing verbosity).</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Templates Tab -->
            <div class="tab-pane <?php echo $active_tab === 'templates' ? 'active' : ''; ?>" id="templates">
                <div class="templates-section">
                    <div class="section-header">
                        <h3><i class='bx bx-file'></i> Email Templates</h3>
                    </div>
                    
                    <div class="section-content">
                        <p>Use these pre-defined templates to quickly create and test common email formats.</p>
                        
                        <div class="templates-grid">
                            <?php foreach ($message_templates as $key => $template): ?>
                            <div class="template-card">
                                <div class="template-header">
                                    <h4><?php echo htmlspecialchars($template['name']); ?></h4>
                                </div>
                                <div class="template-body">
                                    <div class="template-subject">
                                        <strong>Subject:</strong> <?php echo htmlspecialchars($template['subject']); ?>
                                    </div>
                                    <div class="template-preview">
                                        <?php echo nl2br(htmlspecialchars(substr($template['body'], 0, 150) . (strlen($template['body']) > 150 ? '...' : ''))); ?>
                                    </div>
                                </div>
                                <div class="template-actions">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="useTemplate('<?php echo $key; ?>')">
                                        <i class='bx bx-copy'></i> Use Template
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="templates-info">
                            <h4>Template Placeholders</h4>
                            <p>You can use the following placeholders in your templates:</p>
                            <ul>
                                <li><strong>{recipient_name}</strong> - Will be replaced with the recipient's name (derived from email address)</li>
                            </ul>
                            
                            <h4>HTML Email Tips</h4>
                            <ul>
                                <li>Use simple HTML for maximum compatibility across email clients</li>
                                <li>Avoid complex CSS or JavaScript in HTML emails</li>
                                <li>Always include a plain text alternative for HTML emails</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Logs Tab -->
            <div class="tab-pane <?php echo $active_tab === 'logs' ? 'active' : ''; ?>" id="logs">
                <div class="logs-section">
                    <div class="section-header">
                        <h3><i class='bx bx-list-ul'></i> Email Activity Logs</h3>
                    </div>
                    
                    <div class="section-content">
                        <?php
                        $tables = $db->query("SHOW TABLES LIKE 'email_logs'");
                        $logs_table_exists = ($tables && $tables->num_rows > 0);
                        ?>
                        
                        <?php if (!$logs_table_exists): ?>
                        <div class="logs-setup">
                            <div class="setup-message">
                                <i class='bx bx-table'></i>
                                <h4>Email Logs Table Not Found</h4>
                                <p>To enable email logging, you need to create the email_logs table in your database.</p>
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="create_logs_table">
                                    <button type="submit" class="btn btn-primary">
                                        <i class='bx bx-plus'></i> Create Email Logs Table
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php elseif (empty($email_logs)): ?>
                        <div class="empty-logs">
                            <i class='bx bx-envelope-open'></i>
                            <p>No email logs found. Send a test email to see logs here.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Recipient</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Message Preview</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($email_logs as $log): ?>
                                    <tr class="log-entry <?php echo $log['status'] === 'sent' ? 'success' : 'error'; ?>">
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['sent_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['recipient']); ?></td>
                                        <td><?php echo htmlspecialchars($log['subject']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $log['status'] === 'sent' ? 'status-success' : 'status-error'; ?>">
                                                <?php echo ucfirst($log['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="message-preview">
                                                <?php echo htmlspecialchars(substr($log['message_preview'], 0, 50) . (strlen($log['message_preview']) > 50 ? '...' : '')); ?>
                                                <?php if (!empty($log['error'])): ?>
                                                <div class="error-message">
                                                    <i class='bx bx-error'></i> <?php echo htmlspecialchars($log['error']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="logs-actions">
                            <a href="email-logs.php" class="btn btn-secondary">
                                <i class='bx bx-list-check'></i> View All Logs
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to current button and pane
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // HTML format toggle
    const formatRadios = document.querySelectorAll('input[name="email_format"]');
    const messageTextarea = document.getElementById('test_message');
    
    formatRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const isHtml = this.value === 'html';
            if (isHtml) {
                messageTextarea.setAttribute('placeholder', 'Enter your HTML message here... You can use <b>, <i>, <p>, etc.');
            } else {
                messageTextarea.setAttribute('placeholder', 'Enter your plain text message here...');
            }
        });
    });
    
    // Character counter for message
    if (messageTextarea) {
        messageTextarea.addEventListener('input', function() {
            const count = this.value.length;
            const counterEl = document.querySelector('.character-count');
            if (counterEl) {
                counterEl.textContent = `${count} characters`;
                
                if (count > 2000) {
                    counterEl.classList.add('count-warning');
                } else {
                    counterEl.classList.remove('count-warning');
                }
            }
        });
    }
});

// Load template
function loadTemplate() {
    const select = document.getElementById('template_select');
    const templates = <?php echo json_encode($message_templates); ?>;
    const selected = select.value;
    
    if (selected && templates[selected]) {
        document.getElementById('test_subject').value = templates[selected].subject;
        document.getElementById('test_message').value = templates[selected].body;
    }
}

// Use template from templates tab
function useTemplate(templateKey) {
    const templates = <?php echo json_encode($message_templates); ?>;
    
    if (templates[templateKey]) {
        // Switch to the test tab
        document.querySelector('.tab-btn[data-tab="test"]').click();
        
        // Set the template values
        document.getElementById('test_subject').value = templates[templateKey].subject;
        document.getElementById('test_message').value = templates[templateKey].body;
        
        // Set the dropdown to match
        document.getElementById('template_select').value = templateKey;
        
        // Scroll to the form
        document.querySelector('.email-test-form').scrollIntoView({ behavior: 'smooth' });
    }
}

// Reveal password temporarily
function revealPassword() {
    const passwordMask = document.querySelector('.password-mask');
    const revealBtn = document.querySelector('.btn-reveal');
    
    if (passwordMask.textContent === '••••••••••••') {
        passwordMask.textContent = '<?php echo htmlspecialchars(MAIL_PASSWORD); ?>';
        revealBtn.innerHTML = '<i class="bx bx-hide"></i>';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            passwordMask.textContent = '••••••••••••';
            revealBtn.innerHTML = '<i class="bx bx-show"></i>';
        }, 5000);
    } else {
        passwordMask.textContent = '••••••••••••';
        revealBtn.innerHTML = '<i class="bx bx-show"></i>';
    }
}
</script>

<style>
.email-tools {
    max-width: 1200px;
    margin: 0 auto;
}

.header-banner {
    background: linear-gradient(120deg, #3C91E6, #0a3060);
    border-radius: 10px;
    color: white;
    padding: 30px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.environment-info {
    margin-bottom: 20px;
}

.banner-content h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
    display: flex;
    align-items: center;
}

.banner-content h2 i {
    margin-right: 10px;
    font-size: 32px;
}

.banner-content p {
    margin: 0;
    opacity: 0.8;
    font-size: 16px;
}

.btn {
    padding: 8px 15px;
    border-radius: 5px;
    border: none;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-lg {
    padding: 10px 20px;
    font-size: 16px;
}

.btn-primary {
    background-color: #3C91E6;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-light {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.btn-light:hover {
    background-color: rgba(255, 255, 255, 0.3);
}

.alert {
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
    padding: 15px;
    border-radius: 8px;
}

.alerts {
    margin-bottom: 20px;
}

.alert i {
    font-size: 24px;
    margin-right: 15px;
}

.alert-content {
    flex-grow: 1;
}

.alert-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.2);
}

.alert-info {
    background-color: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
    border: 1px solid rgba(23, 162, 184, 0.2);
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #856404;
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.tab-container {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.tab-header {
    display: flex;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-btn {
    padding: 15px 20px;
    background: none;
    border: none;
    font-size: 16px;
    font-weight: 500;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.tab-btn:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.tab-btn.active {
    color: #3C91E6;
    border-bottom: 2px solid #3C91E6;
}

.tab-content {
    padding: 20px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.section-header {
    margin-bottom: 20px;
}

.section-header h3 {
    margin: 0;
    font-size: 18px;
    color: #0a3060;
    display: flex;
    align-items: center;
}

.section-header h3 i {
    margin-right: 10px;
    font-size: 22px;
}

.section-content {
    margin-bottom: 30px;
}

.email-test-form {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -10px;
    margin-left: -10px;
    margin-bottom: 20px;
}

.col-md-8 {
    flex: 0 0 66.67%;
    max-width: 66.67%;
    padding: 0 10px;
}

.col-md-4 {
    flex: 0 0 33.33%;
    max-width: 33.33%;
    padding: 0 10px;
}

.radio-group {
    display: flex;
    gap: 20px;
}

.radio-container {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.radio-container input {
    margin-right: 5px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #3C91E6;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(60, 145, 230, 0.25);
}

.form-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    justify-content: flex-end;
}

.troubleshooting-card {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.troubleshooting-card h4 {
    margin-top: 0;
    color: #0a3060;
    margin-bottom: 10px;
}

.troubleshooting-card ul {
    margin-bottom: 20px;
    padding-left: 20px;
}

.troubleshooting-card li {
    margin-bottom: 5px;
}

.provider-notes {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.provider-note {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.provider-note h5 {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 5px;
    color: #0a3060;
    margin-bottom: 10px;
}

.provider-note ul {
    margin-bottom: 0;
}

.provider-note code {
    background-color: #f1f1f1;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}

.config-warning {
    display: flex;
    align-items: flex-start;
    background-color: rgba(255, 193, 7, 0.1);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.config-warning i {
    font-size: 24px;
    margin-right: 15px;
    color: #856404;
}

.warning-content h4 {
    margin: 0 0 5px 0;
    color: #856404;
}

.warning-content p {
    margin: 0;
    color: #856404;
}

.warning-content code {
    background-color: rgba(255, 255, 255, 0.5);
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}

.config-card {
    background-color: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
}

.config-group {
    margin-bottom: 20px;
    padding: 0 20px;
}

.config-group:last-child {
    margin-bottom: 0;
}

.config-group h4 {
    color: #0a3060;
    margin: 20px 0 10px 0;
    font-size: 16px;
}

.config-table {
    width: 100%;
}

.config-row {
    display: flex;
    border-bottom: 1px solid #dee2e6;
}

.config-row:last-child {
    border-bottom: none;
}

.config-label {
    width: 40%;
    padding: 10px 0;
    font-weight: 500;
}

.config-value {
    width: 60%;
    padding: 10px 0;
    display: flex;
    align-items: center;
}

.password-mask {
    flex-grow: 1;
}

.btn-reveal {
    background: none;
    border: none;
    cursor: pointer;
    color: #6c757d;
    transition: color 0.2s;
}

.btn-reveal:hover {
    color: #0a3060;
}

.config-info-card {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.config-info-card h4 {
    margin-top: 0;
    color: #0a3060;
    margin-bottom: 10px;
}

.config-info-card h5 {
    color: #0a3060;
    margin: 15px 0 10px 0;
}

.config-info-card ul {
    padding-left: 20px;
    margin-bottom: 0;
}

.config-info-card li {
    margin-bottom: 5px;
}

.config-info-card code {
    background-color: #f1f1f1;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.template-card {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.template-header {
    padding: 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.template-header h4 {
    margin: 0;
    color: #0a3060;
    font-size: 16px;
}

.template-body {
    padding: 15px;
}

.template-subject {
    margin-bottom: 10px;
    color: #6c757d;
}

.template-preview {
    color: #6c757d;
    margin-bottom: 10px;
    line-height: 1.5;
}

.template-actions {
    padding: 15px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
}

.templates-info {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.templates-info h4 {
    margin-top: 0;
    color: #0a3060;
    margin-bottom: 10px;
}

.templates-info ul {
    padding-left: 20px;
    margin-bottom: 20px;
}

.templates-info li {
    margin-bottom: 5px;
}

.templates-info p {
    margin-bottom: 10px;
}

.logs-setup {
    display: flex;
    justify-content: center;
    padding: 40px 0;
}

.setup-message {
    text-align: center;
    max-width: 500px;
}

.setup-message i {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 20px;
    opacity: 0.5;
}

.setup-message h4 {
    margin: 0 0 10px 0;
    color: #0a3060;
}

.setup-message p {
    margin-bottom: 20px;
}

.empty-logs {
    text-align: center;
    padding: 40px 0;
    color: #6c757d;
}

.empty-logs i {
    font-size: 48px;
    opacity: 0.5;
    margin-bottom: 10px;
}

.empty-logs p {
    margin: 0;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.data-table th {
    background-color: #f8f9fa;
    font-weight: 500;
    color: #0a3060;
}

.log-entry.success td {
    background-color: rgba(40, 167, 69, 0.05);
}

.log-entry.error td {
    background-color: rgba(220, 53, 69, 0.05);
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
}

.status-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.message-preview {
    font-size: 13px;
    color: #6c757d;
}

.error-message {
    color: #dc3545;
    margin-top: 5px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.logs-actions {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
    
    .col-md-8, .col-md-4 {
        max-width: 100%;
        flex: 0 0 100%;
    }
    
    .header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .banner-actions {
        margin-top: 15px;
    }
    
    .tab-header {
        flex-wrap: wrap;
    }
    
    .tab-btn {
        padding: 10px;
        font-size: 14px;
    }
    
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .provider-notes {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Email Management Tools';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout with absolute path
include_once $site_root . DS . 'admin' . DS . 'layout.php';
?>