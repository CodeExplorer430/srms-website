<?php
/**
 * Email Configuration Editor
 * Edit email server settings and preferences
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Require admin level permissions
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    echo "<div class='alert alert-danger'>This tool requires administrator privileges.</div>";
    exit;
}

// Get the site root directory more reliably
$current_dir = dirname(__FILE__);
$tools_dir = dirname($current_dir);
$admin_dir = dirname($tools_dir);
$site_root = dirname($admin_dir);

// Include environment file to get constants
if (file_exists($site_root . DIRECTORY_SEPARATOR . 'environment.php')) {
    require_once $site_root . DIRECTORY_SEPARATOR . 'environment.php';
} else {
    die('Environment file not found. Please make sure environment.php exists in the site root directory.');
}

// Include necessary files
include_once $site_root . DS . 'includes' . DS . 'config.php';
include_once $site_root . DS . 'includes' . DS . 'mail-config.php';
include_once $site_root . DS . 'includes' . DS . 'MailService.php';

// Define file paths
$mail_config_file = $site_root . DS . 'includes' . DS . 'mail-config.php';

// Initialize variables
$config_saved = false;
$config_error = false;
$error_message = '';
$success_message = '';

// Get current configuration values from constants
$current_config = [
    'MAIL_HOST' => defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com',
    'MAIL_PORT' => defined('MAIL_PORT') ? MAIL_PORT : 587,
    'MAIL_USERNAME' => defined('MAIL_USERNAME') ? MAIL_USERNAME : '',
    'MAIL_PASSWORD' => defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '',
    'MAIL_FROM' => defined('MAIL_FROM') ? MAIL_FROM : '',
    'MAIL_FROM_NAME' => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'St. Raphaela Mary School',
    'MAIL_ENCRYPTION' => defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'tls',
    'MAIL_DEBUG' => defined('MAIL_DEBUG') ? MAIL_DEBUG : 0,
    'MAIL_CHARSET' => defined('MAIL_CHARSET') ? MAIL_CHARSET : 'UTF-8',
    'MAIL_CONTENT_TYPE' => defined('MAIL_CONTENT_TYPE') ? MAIL_CONTENT_TYPE : 'text/plain',
    'ADMIN_EMAIL' => defined('ADMIN_EMAIL') ? ADMIN_EMAIL : ''
];

// Check if the mail config file is writable
$is_writable = is_writable($mail_config_file);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_config') {
        if (!$is_writable) {
            $config_error = true;
            $error_message = "Configuration file is not writable. Please set proper permissions.";
        } else {
            // Get submitted values
            $new_config = [
                'MAIL_HOST' => isset($_POST['mail_host']) ? trim($_POST['mail_host']) : $current_config['MAIL_HOST'],
                'MAIL_PORT' => isset($_POST['mail_port']) ? (int)$_POST['mail_port'] : $current_config['MAIL_PORT'],
                'MAIL_USERNAME' => isset($_POST['mail_username']) ? trim($_POST['mail_username']) : $current_config['MAIL_USERNAME'],
                'MAIL_PASSWORD' => isset($_POST['mail_password']) ? trim($_POST['mail_password']) : $current_config['MAIL_PASSWORD'],
                'MAIL_FROM' => isset($_POST['mail_from']) ? trim($_POST['mail_from']) : $current_config['MAIL_FROM'],
                'MAIL_FROM_NAME' => isset($_POST['mail_from_name']) ? trim($_POST['mail_from_name']) : $current_config['MAIL_FROM_NAME'],
                'MAIL_ENCRYPTION' => isset($_POST['mail_encryption']) ? trim($_POST['mail_encryption']) : $current_config['MAIL_ENCRYPTION'],
                'MAIL_DEBUG' => isset($_POST['mail_debug']) ? (int)$_POST['mail_debug'] : $current_config['MAIL_DEBUG'],
                'MAIL_CHARSET' => isset($_POST['mail_charset']) ? trim($_POST['mail_charset']) : $current_config['MAIL_CHARSET'],
                'MAIL_CONTENT_TYPE' => isset($_POST['mail_content_type']) ? trim($_POST['mail_content_type']) : $current_config['MAIL_CONTENT_TYPE'],
                'ADMIN_EMAIL' => isset($_POST['admin_email']) ? trim($_POST['admin_email']) : $current_config['ADMIN_EMAIL']
            ];
            
            // Validate required fields
            $required_fields = ['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM', 'MAIL_FROM_NAME'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (empty($new_config[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                $config_error = true;
                $error_message = "Required fields are missing: " . implode(', ', $missing_fields);
            } else {
                // Create backup of original file
                $backup_file = $mail_config_file . '.bak.' . date('YmdHis');
                if (!copy($mail_config_file, $backup_file)) {
                    $config_error = true;
                    $error_message = "Failed to create backup of the configuration file.";
                } else {
                    // Generate new config file content
                    $config_content = "<?php\n";
                    $config_content .= "/**\n";
                    $config_content .= " * Mail Configuration Settings\n";
                    $config_content .= " * This file contains the SMTP settings for the PHPMailer library\n";
                    $config_content .= " * Last updated: " . date('Y-m-d H:i:s') . "\n";
                    $config_content .= " */\n\n";
                    
                    // Email Configuration Constants
                    $config_content .= "// Email Configuration Constants\n";
                    $config_content .= "defined('MAIL_HOST') or define('MAIL_HOST', '{$new_config['MAIL_HOST']}');  // SMTP server address\n";
                    $config_content .= "defined('MAIL_PORT') or define('MAIL_PORT', {$new_config['MAIL_PORT']});  // SMTP port (typically 587 for TLS, 465 for SSL)\n";
                    $config_content .= "defined('MAIL_USERNAME') or define('MAIL_USERNAME', '{$new_config['MAIL_USERNAME']}');  // Email username\n";
                    $config_content .= "defined('MAIL_PASSWORD') or define('MAIL_PASSWORD', '{$new_config['MAIL_PASSWORD']}');  // Email password or app password\n";
                    $config_content .= "defined('MAIL_FROM') or define('MAIL_FROM', '{$new_config['MAIL_FROM']}');  // From email address\n";
                    $config_content .= "defined('MAIL_FROM_NAME') or define('MAIL_FROM_NAME', '{$new_config['MAIL_FROM_NAME']}');  // From name\n";
                    $config_content .= "defined('MAIL_ENCRYPTION') or define('MAIL_ENCRYPTION', '{$new_config['MAIL_ENCRYPTION']}');  // Encryption type (tls or ssl)\n";
                    $config_content .= "defined('MAIL_DEBUG') or define('MAIL_DEBUG', {$new_config['MAIL_DEBUG']});  // Debug level (0 = off, 1-4 for increasing verbosity)\n";
                    $config_content .= "\n";
                    
                    // Admin Email for Receiving Notifications
                    $config_content .= "// Admin Email for Receiving Notifications\n";
                    $config_content .= "defined('ADMIN_EMAIL') or define('ADMIN_EMAIL', '{$new_config['ADMIN_EMAIL']}');  // School admin email address\n";
                    $config_content .= "\n";
                    
                    // General Mail Settings
                    $config_content .= "// General Mail Settings\n";
                    $config_content .= "defined('MAIL_CHARSET') or define('MAIL_CHARSET', '{$new_config['MAIL_CHARSET']}');  // Email character set\n";
                    $config_content .= "defined('MAIL_CONTENT_TYPE') or define('MAIL_CONTENT_TYPE', '{$new_config['MAIL_CONTENT_TYPE']}');  // Content type (text/plain or text/html)";
                    
                    // Write the new config file
                    if (file_put_contents($mail_config_file, $config_content)) {
                        $config_saved = true;
                        $success_message = "Email configuration has been updated successfully.";
                        
                        // Update current config values
                        $current_config = $new_config;
                    } else {
                        $config_error = true;
                        $error_message = "Failed to write the new configuration to the file.";
                    }
                }
            }
        }
    } elseif ($_POST['action'] === 'send_test') {
        // Send a test email with current configuration
        $to = isset($_POST['test_email']) ? trim($_POST['test_email']) : '';
        
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $config_error = true;
            $error_message = "Please enter a valid email address for testing.";
        } else {
            $subject = "Email Configuration Test";
            $message = "This is a test email from St. Raphaela Mary School website.\n\n";
            $message .= "If you received this email, the email configuration is working correctly.\n\n";
            $message .= "Configuration details:\n";
            $message .= "- SMTP: " . MAIL_HOST . ":" . MAIL_PORT . "\n";
            $message .= "- Username: " . MAIL_USERNAME . "\n";
            $message .= "- From: " . MAIL_FROM . "\n";
            $message .= "- Encryption: " . MAIL_ENCRYPTION . "\n";
            
            $result = MailService::sendMail($to, $subject, $message);
            
            if ($result['success']) {
                $config_saved = true;
                $success_message = "Test email has been sent successfully to " . $to;
            } else {
                $config_error = true;
                $error_message = "Failed to send test email: " . $result['message'];
            }
        }
    }
}

// Determine if we should show recommended settings
$show_recommendations = isset($_GET['show_recommendations']) && $_GET['show_recommendations'] === '1';

// Common email provider recommendations
$email_recommendations = [
    'gmail' => [
        'name' => 'Gmail',
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'notes' => [
            'You need to enable "Less secure app access" in your Google account or create an App Password if you have 2-step verification enabled.',
            'Google may block sign-in attempts from apps that don\'t use modern security standards.'
        ]
    ],
    'outlook' => [
        'name' => 'Outlook/Office 365',
        'host' => 'smtp.office365.com',
        'port' => 587,
        'encryption' => 'tls',
        'notes' => [
            'Use your full email address as the username.',
            'You might need to create an app password if you have two-factor authentication enabled.'
        ]
    ],
    'yahoo' => [
        'name' => 'Yahoo Mail',
        'host' => 'smtp.mail.yahoo.com',
        'port' => 587,
        'encryption' => 'tls',
        'notes' => [
            'You need to generate an app password for your Yahoo account.',
            'Go to Account Security > Generate app password.'
        ]
    ],
    'zoho' => [
        'name' => 'Zoho Mail',
        'host' => 'smtp.zoho.com',
        'port' => 587,
        'encryption' => 'tls',
        'notes' => [
            'Use your full email address as the username.',
            'Make sure IMAP/POP is enabled in your Zoho Mail account.'
        ]
    ]
];

// Content Type Options
$content_type_options = [
    'text/plain' => 'Plain Text',
    'text/html' => 'HTML'
];

// Encryption Options
$encryption_options = [
    'tls' => 'TLS',
    'ssl' => 'SSL',
    '' => 'None'
];

// Debug Level Options
$debug_level_options = [
    0 => 'Off (0)',
    1 => 'Client (1)',
    2 => 'Client & Server (2)',
    3 => 'Client & Server + Connection Status (3)',
    4 => 'Low-level Data Output (4)'
];

// Start output buffer for main content
ob_start();
?>

<div class="email-config-editor">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-cog'></i> Email Configuration Editor</h2>
            <p>Modify email server settings and preferences</p>
        </div>
        <div class="banner-actions">
            <a href="email-tools.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Email Tools
            </a>
        </div>
    </div>
    
    <?php if ($config_saved): ?>
    <div class="alerts">
        <div class="alert alert-success">
            <i class='bx bx-check-circle'></i>
            <div class="alert-content"><?php echo htmlspecialchars($success_message); ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($config_error): ?>
    <div class="alerts">
        <div class="alert alert-danger">
            <i class='bx bx-error-circle'></i>
            <div class="alert-content"><?php echo htmlspecialchars($error_message); ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!$is_writable): ?>
    <div class="alerts">
        <div class="alert alert-warning">
            <i class='bx bx-error'></i>
            <div class="alert-content">
                <strong>Warning:</strong> The configuration file is not writable. 
                Please set the correct permissions to <?php echo htmlspecialchars($mail_config_file); ?> before making changes.
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="config-container">
        <div class="config-column main-column">
            <div class="config-panel">
                <div class="panel-header">
                    <h3><i class='bx bx-edit'></i> Edit Email Configuration</h3>
                </div>
                <div class="panel-body">
                    <form method="post" action="" id="config-form" class="config-form">
                        <input type="hidden" name="action" value="save_config">
                        
                        <div class="form-section">
                            <h4>SMTP Server Settings</h4>
                            
                            <div class="form-group">
                                <label for="mail_host">SMTP Host:</label>
                                <input type="text" id="mail_host" name="mail_host" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_config['MAIL_HOST']); ?>" required>
                                <small class="form-text">The address of your SMTP server (e.g., smtp.gmail.com)</small>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="mail_port">SMTP Port:</label>
                                    <input type="number" id="mail_port" name="mail_port" class="form-control"
                                           value="<?php echo htmlspecialchars($current_config['MAIL_PORT']); ?>" required>
                                    <small class="form-text">Usually 587 for TLS or 465 for SSL</small>
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="mail_encryption">Encryption:</label>
                                    <select id="mail_encryption" name="mail_encryption" class="form-control">
                                        <?php foreach ($encryption_options as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $current_config['MAIL_ENCRYPTION'] === $value ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text">Type of encryption to use</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Authentication</h4>
                            
                            <div class="form-group">
                                <label for="mail_username">SMTP Username:</label>
                                <input type="text" id="mail_username" name="mail_username" class="form-control"
                                       value="<?php echo htmlspecialchars($current_config['MAIL_USERNAME']); ?>" required>
                                <small class="form-text">Usually your full email address</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="mail_password">SMTP Password:</label>
                                <div class="password-field">
                                    <input type="password" id="mail_password" name="mail_password" class="form-control"
                                           value="<?php echo htmlspecialchars($current_config['MAIL_PASSWORD']); ?>" required>
                                    <button type="button" class="btn btn-light btn-password-toggle" onclick="togglePassword()">
                                        <i class='bx bx-show'></i>
                                    </button>
                                </div>
                                <small class="form-text">Your email password or app password</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Sender Information</h4>
                            
                            <div class="form-group">
                                <label for="mail_from">From Email:</label>
                                <input type="email" id="mail_from" name="mail_from" class="form-control"
                                       value="<?php echo htmlspecialchars($current_config['MAIL_FROM']); ?>" required>
                                <small class="form-text">The email address that will appear as the sender</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="mail_from_name">From Name:</label>
                                <input type="text" id="mail_from_name" name="mail_from_name" class="form-control"
                                       value="<?php echo htmlspecialchars($current_config['MAIL_FROM_NAME']); ?>" required>
                                <small class="form-text">The sender name that will appear in emails</small>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>Additional Settings</h4>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="mail_debug">Debug Level:</label>
                                    <select id="mail_debug" name="mail_debug" class="form-control">
                                        <?php foreach ($debug_level_options as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (int)$current_config['MAIL_DEBUG'] === $value ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text">Set to higher level for troubleshooting</small>
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="mail_content_type">Default Content Type:</label>
                                    <select id="mail_content_type" name="mail_content_type" class="form-control">
                                        <?php foreach ($content_type_options as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $current_config['MAIL_CONTENT_TYPE'] === $value ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text">Default content type for emails</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="mail_charset">Character Set:</label>
                                <input type="text" id="mail_charset" name="mail_charset" class="form-control"
                                       value="<?php echo htmlspecialchars($current_config['MAIL_CHARSET']); ?>">
                                <small class="form-text">Usually UTF-8 is recommended</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_email">Admin Email:</label>
                                <input type="email" id="admin_email" name="admin_email" class="form-control"
                                       value="<?php echo htmlspecialchars($current_config['ADMIN_EMAIL']); ?>">
                                <small class="form-text">Email for receiving administrative notifications</small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg" <?php echo !$is_writable ? 'disabled' : ''; ?>>
                                <i class='bx bx-save'></i> Save Configuration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="config-panel">
                <div class="panel-header">
                    <h3><i class='bx bx-paper-plane'></i> Test Current Configuration</h3>
                </div>
                <div class="panel-body">
                    <form method="post" action="" class="test-form">
                        <input type="hidden" name="action" value="send_test">
                        
                        <div class="form-group">
                            <label for="test_email">Send Test Email To:</label>
                            <input type="email" id="test_email" name="test_email" class="form-control" required
                                   placeholder="Enter a valid email address">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                <i class='bx bx-send'></i> Send Test Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="config-column side-column">
            <div class="config-panel">
                <div class="panel-header">
                    <h3><i class='bx bx-info-circle'></i> Email Provider Settings</h3>
                </div>
                <div class="panel-body">
                    <p>Select a preset configuration for common email providers:</p>
                    
                    <div class="provider-buttons">
                        <?php foreach ($email_recommendations as $key => $provider): ?>
                        <button type="button" class="btn btn-provider" onclick="applyProviderSettings('<?php echo $key; ?>')">
                            <i class='bx bxl-<?php echo $key; ?>'></i> <?php echo htmlspecialchars($provider['name']); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="provider-info">
                        <h4>Provider Information</h4>
                        <div id="provider-details">
                            <p>Click on a provider button above to see recommended settings.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="config-panel">
                <div class="panel-header">
                    <h3><i class='bx bx-help-circle'></i> Configuration Help</h3>
                </div>
                <div class="panel-body">
                    <div class="help-content">
                        <h4>SMTP Configuration Tips</h4>
                        <ul>
                            <li>For <strong>Gmail</strong>, you might need to generate an "App Password" if you have 2-Step Verification enabled. <a href="https://support.google.com/accounts/answer/185833" target="_blank">Learn more</a></li>
                            <li>For most email providers, the username is your full email address</li>
                            <li>TLS is typically used with port 587, while SSL is used with port 465</li>
                            <li>Set Debug Level to a higher value for troubleshooting, but set it back to 0 in production</li>
                            <li>The From Email should match or be authorized by your SMTP provider to avoid emails being marked as spam</li>
                        </ul>
                        
                        <h4>Configuring App Passwords</h4>
                        <p>Many email providers now require using an App Password instead of your regular account password:</p>
                        <ol>
                            <li>Enable 2-Step Verification in your email account</li>
                            <li>Generate an App Password specifically for this application</li>
                            <li>Use the generated App Password in the SMTP Password field</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const configForm = document.getElementById('config-form');
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            const requiredFields = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_from', 'mail_from_name'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }
    
    // Add event listeners to remove is-invalid class on input
    const formInputs = document.querySelectorAll('.form-control');
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            input.classList.remove('is-invalid');
        });
    });
});

// Toggle password visibility
function togglePassword() {
    const passwordField = document.getElementById('mail_password');
    const toggleButton = document.querySelector('.btn-password-toggle i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleButton.classList.remove('bx-show');
        toggleButton.classList.add('bx-hide');
    } else {
        passwordField.type = 'password';
        toggleButton.classList.remove('bx-hide');
        toggleButton.classList.add('bx-show');
    }
}

// Apply provider settings
function applyProviderSettings(provider) {
    const providers = <?php echo json_encode($email_recommendations); ?>;
    const selected = providers[provider];
    
    if (selected) {
        // Update form fields
        document.getElementById('mail_host').value = selected.host;
        document.getElementById('mail_port').value = selected.port;
        document.getElementById('mail_encryption').value = selected.encryption;
        
        // Show provider details
        let detailsHtml = `<h5>${selected.name} Recommended Settings</h5>`;
        detailsHtml += `<div class="provider-settings">`;
        detailsHtml += `<div class="setting-row"><span>Host:</span> <strong>${selected.host}</strong></div>`;
        detailsHtml += `<div class="setting-row"><span>Port:</span> <strong>${selected.port}</strong></div>`;
        detailsHtml += `<div class="setting-row"><span>Encryption:</span> <strong>${selected.encryption.toUpperCase()}</strong></div>`;
        detailsHtml += `</div>`;
        
        detailsHtml += `<div class="provider-notes">`;
        detailsHtml += `<h6>Important Notes:</h6>`;
        detailsHtml += `<ul>`;
        selected.notes.forEach(note => {
            detailsHtml += `<li>${note}</li>`;
        });
        detailsHtml += `</ul>`;
        detailsHtml += `</div>`;
        
        document.getElementById('provider-details').innerHTML = detailsHtml;
        
        // Add active class to selected provider button
        document.querySelectorAll('.btn-provider').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.querySelector(`.btn-provider[onclick="applyProviderSettings('${provider}')"]`).classList.add('active');
    }
}
</script>

<style>
.email-config-editor {
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

.btn-primary:hover {
    background-color: #2c7ed6;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
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

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #856404;
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.config-container {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.main-column {
    flex: 3;
}

.side-column {
    flex: 1;
}

.config-panel {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    overflow: hidden;
}

.panel-header {
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
}

.panel-header h3 {
    margin: 0;
    font-size: 18px;
    color: #0a3060;
    display: flex;
    align-items: center;
}

.panel-header h3 i {
    margin-right: 10px;
    font-size: 20px;
}

.panel-body {
    padding: 20px;
}

.form-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.form-section h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #0a3060;
    font-size: 16px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-row {
    display: flex;
    margin-left: -10px;
    margin-right: -10px;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding: 0 10px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #3C91E6;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(60, 145, 230, 0.25);
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.password-field {
    position: relative;
}

.btn-password-toggle {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    background: none;
    border: none;
    color: #6c757d;
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #6c757d;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
}

.provider-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.btn-provider {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.btn-provider:hover, .btn-provider.active {
    background-color: #e9ecef;
    border-color: #ced4da;
    color: #212529;
}

.provider-info {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.provider-info h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
    color: #0a3060;
}

.provider-info h5 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 15px;
    color: #0a3060;
}

.provider-info h6 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 14px;
    color: #495057;
}

.provider-settings {
    margin-bottom: 15px;
    font-size: 14px;
}

.setting-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.provider-notes ul {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
}

.provider-notes li {
    margin-bottom: 5px;
}

.help-content {
    font-size: 14px;
}

.help-content h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
    color: #0a3060;
}

.help-content ul, .help-content ol {
    padding-left: 20px;
    margin-bottom: 15px;
}

.help-content li {
    margin-bottom: 5px;
}

.help-content p {
    margin-top: 0;
    margin-bottom: 10px;
}

.help-content a {
    color: #3C91E6;
    text-decoration: none;
}

.help-content a:hover {
    text-decoration: underline;
}

@media (max-width: 992px) {
    .config-container {
        flex-direction: column;
    }
    
    .main-column, .side-column {
        flex: none;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .banner-actions {
        margin-top: 15px;
    }
    
    .form-row {
        flex-direction: column;
    }
    
    .col-md-6 {
        max-width: 100%;
        flex: 0 0 100%;
    }
}
</style>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Email Configuration Editor';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout with absolute path
include_once $site_root . DS . 'admin' . DS . 'layout.php';
?>