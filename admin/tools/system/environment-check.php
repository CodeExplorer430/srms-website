<?php
/**
 * Environment Check Tool
 * Comprehensive system for diagnosing server environment, configurations, and requirements
 * Updated for Hostinger compatibility
 * Version: 2.1 - Fixed path resolution issues
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . (defined('IS_PRODUCTION') && IS_PRODUCTION ? '/admin/login.php' : '../../login.php'));
    exit;
}

/**
 * Enhanced environment-aware path resolution
 * This function properly detects the site root directory
 */
function get_site_root() {
    // Get the current script's directory
    $current_dir = __DIR__;
    
    // Navigate up from admin/tools/system to the project root
    $site_root = dirname(dirname(dirname($current_dir)));
    
    // Verify this is the correct directory by checking for key files
    $key_files = ['includes/config.php', 'includes/db.php', 'environment.php'];
    $valid_root = true;
    
    foreach ($key_files as $file) {
        if (!file_exists($site_root . DIRECTORY_SEPARATOR . $file)) {
            $valid_root = false;
            break;
        }
    }
    
    if (!$valid_root) {
        // Fallback: try to detect from document root
        $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        
        // Check if we're in a subdirectory like 'srms-website'
        $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        if (preg_match('#^/([^/]+)/#', $script_path, $matches)) {
            $potential_project = $matches[1];
            $potential_root = $doc_root . DIRECTORY_SEPARATOR . $potential_project;
            
            // Verify this potential root
            $valid_potential = true;
            foreach ($key_files as $file) {
                if (!file_exists($potential_root . DIRECTORY_SEPARATOR . $file)) {
                    $valid_potential = false;
                    break;
                }
            }
            
            if ($valid_potential) {
                return $potential_root;
            }
        }
        
        // Last fallback
        return $doc_root . DIRECTORY_SEPARATOR . 'srms-website';
    }
    
    return $site_root;
}

// Get the site root directory using environment-aware method
$site_root = get_site_root();

// Debug logging
error_log("Environment Check - Site root detected: " . $site_root);
error_log("Environment Check - Current directory: " . __DIR__);
error_log("Environment Check - Document root: " . $_SERVER['DOCUMENT_ROOT']);

// Include necessary files using absolute paths with proper error handling
$required_files = [
    'config.php' => $site_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.php',
    'db.php' => $site_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db.php',
    'functions.php' => $site_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php'
];

foreach ($required_files as $name => $path) {
    if (!file_exists($path)) {
        die("Critical Error: Required file '{$name}' not found at: {$path}");
    }
    
    $include_result = include_once $path;
    if ($include_result === false) {
        die("Critical Error: Failed to include '{$name}' from: {$path}");
    }
    error_log("Environment Check - Successfully included: {$name} from {$path}");
}

// Initialize the database connection if needed
try {
    $db = new Database();
    error_log("Environment Check - Database connection initialized successfully");
} catch (Exception $e) {
    error_log("Environment Check - Database connection failed: " . $e->getMessage());
    $db = null;
}

// Initialize status arrays
$system_info = [];
$php_checks = [];
$directory_checks = [];
$database_checks = [];
$extension_checks = [];
$permission_checks = [];

// Get server information
$system_info = [
    'os' => [
        'name' => 'Operating System',
        'value' => PHP_OS . ' (' . (IS_WINDOWS ? 'Windows' : 'Unix/Linux') . ')',
        'status' => 'info'
    ],
    'php_version' => [
        'name' => 'PHP Version',
        'value' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'warning',
        'message' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'Recommended PHP 7.4 or higher' : 'PHP 7.4 or higher is recommended'
    ],
    'server_software' => [
        'name' => 'Server Software',
        'value' => $_SERVER['SERVER_SOFTWARE'],
        'status' => 'info'
    ],
    'document_root' => [
        'name' => 'Document Root',
        'value' => $_SERVER['DOCUMENT_ROOT'],
        'status' => 'info'
    ],
    'site_root' => [
        'name' => 'Site Root',
        'value' => $site_root,
        'status' => 'info'
    ],
    'server_type' => [
        'name' => 'Server Type',
        'value' => defined('SERVER_TYPE') ? SERVER_TYPE : 'Unknown',
        'status' => defined('SERVER_TYPE') ? 'success' : 'warning',
        'message' => defined('SERVER_TYPE') ? '' : 'Server type not detected properly'
    ],
    'environment' => [
        'name' => 'Environment',
        'value' => defined('IS_PRODUCTION') && IS_PRODUCTION ? 'Production' : 'Development',
        'status' => 'info'
    ],
    'site_url' => [
        'name' => 'Site URL',
        'value' => SITE_URL,
        'status' => 'info'
    ],
    'directory_separator' => [
        'name' => 'Directory Separator',
        'value' => DIRECTORY_SEPARATOR,
        'status' => 'info'
    ]
];

// Check PHP configuration
$php_config_checks = [
    'display_errors' => [
        'name' => 'Display Errors',
        'value' => ini_get('display_errors') ? 'On' : 'Off',
        'status' => ini_get('display_errors') ? (IS_DEVELOPMENT ? 'success' : 'warning') : (IS_DEVELOPMENT ? 'warning' : 'success'),
        'message' => ini_get('display_errors') ? 
            (IS_DEVELOPMENT ? 'Good for development' : 'Should be turned off in production') : 
            (IS_DEVELOPMENT ? 'Consider enabling for development' : 'Correctly disabled for production')
    ],
    'file_uploads' => [
        'name' => 'File Uploads',
        'value' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
        'status' => ini_get('file_uploads') ? 'success' : 'error',
        'message' => ini_get('file_uploads') ? '' : 'File uploads are required for media management'
    ],
    'upload_max_filesize' => [
        'name' => 'Upload Max Filesize',
        'value' => ini_get('upload_max_filesize'),
        'status' => (intval(ini_get('upload_max_filesize')) >= 8) ? 'success' : 'warning',
        'message' => (intval(ini_get('upload_max_filesize')) >= 8) ? '' : 'Recommend 8M or higher'
    ],
    'post_max_size' => [
        'name' => 'Post Max Size',
        'value' => ini_get('post_max_size'),
        'status' => (intval(ini_get('post_max_size')) >= 8) ? 'success' : 'warning',
        'message' => (intval(ini_get('post_max_size')) >= 8) ? '' : 'Recommend 8M or higher'
    ],
    'memory_limit' => [
        'name' => 'Memory Limit',
        'value' => ini_get('memory_limit'),
        'status' => (intval(ini_get('memory_limit')) >= 128) ? 'success' : 'warning',
        'message' => (intval(ini_get('memory_limit')) >= 128) ? '' : 'Recommend 128M or higher'
    ],
    'max_execution_time' => [
        'name' => 'Max Execution Time',
        'value' => ini_get('max_execution_time') . ' seconds',
        'status' => (intval(ini_get('max_execution_time')) >= 30) ? 'success' : 'warning',
        'message' => (intval(ini_get('max_execution_time')) >= 30) ? '' : 'Recommend 30 seconds or higher'
    ],
    'date_timezone' => [
        'name' => 'Date Timezone',
        'value' => date_default_timezone_get(),
        'status' => (date_default_timezone_get() === 'Asia/Manila') ? 'success' : 'warning',
        'message' => (date_default_timezone_get() === 'Asia/Manila') ? '' : 'Timezone should be set to Asia/Manila'
    ]
];

// Enhanced environment detection
$is_production = defined('IS_PRODUCTION') && IS_PRODUCTION;

// Check directory permissions using the corrected site root
$directory_paths = [
    '/assets/images/news',
    '/assets/images/events',
    '/assets/images/promotional',
    '/assets/images/facilities',
    '/assets/images/campus',
    '/assets/images/branding',
    '/assets/uploads/temp'
];

foreach ($directory_paths as $dir_path) {
    // Build the full server path using the correct site root
    $full_path = $site_root . str_replace('/', DIRECTORY_SEPARATOR, $dir_path);
    
    $exists = is_dir($full_path);
    $writable = $exists ? is_writable($full_path) : false;
    
    $directory_checks[$dir_path] = [
        'name' => $dir_path,
        'path' => $full_path,
        'exists' => $exists,
        'writable' => $writable,
        'status' => $exists ? ($writable ? 'success' : 'warning') : 'error',
        'message' => $exists ? 
            ($writable ? 'Directory exists and is writable' : 'Directory exists but is not writable') : 
            'Directory does not exist'
    ];
}

// Check required PHP extensions
$required_extensions = [
    'pdo' => 'PDO (Database)',
    'pdo_mysql' => 'PDO MySQL Driver',
    'gd' => 'GD Library (Image Processing)',
    'mbstring' => 'Multibyte String',
    'fileinfo' => 'File Info',
    'curl' => 'cURL',
    'json' => 'JSON',
    'xml' => 'XML'
];

foreach ($required_extensions as $ext => $description) {
    $loaded = extension_loaded($ext);
    $extension_checks[$ext] = [
        'name' => $description,
        'loaded' => $loaded,
        'status' => $loaded ? 'success' : ($ext == 'curl' || $ext == 'xml' ? 'warning' : 'error'),
        'message' => $loaded ? 'Extension loaded' : ($ext == 'curl' || $ext == 'xml' ? 'Recommended but not required' : 'Required extension')
    ];
}

// Check database connection
if ($db !== null) {
    try {
        // Get database version
        $version_stmt = $db->query('SELECT VERSION() as version');
        if ($version_stmt) {
            $db_version_row = $version_stmt->fetch_assoc();
            $db_version = $db_version_row['version'];
        } else {
            $db_version = 'Unknown';
        }
        
        // Check database tables
        $table_query = "SHOW TABLES";
        $tables_stmt = $db->query($table_query);
        $table_count = 0;
        $tables = [];
        
        if ($tables_stmt) {
            while ($row = $tables_stmt->fetch_array()) {
                $tables[] = $row[0];
                $table_count++;
            }
        }
        
        $database_checks['connection'] = [
            'name' => 'Database Connection',
            'status' => 'success',
            'message' => 'Successfully connected to database'
        ];
        
        $database_checks['server'] = [
            'name' => 'Database Server',
            'value' => DB_SERVER . ':' . (defined('DB_PORT') ? DB_PORT : '3306'),
            'status' => 'info'
        ];
        
        $database_checks['version'] = [
            'name' => 'Database Version',
            'value' => $db_version,
            'status' => 'info'
        ];
        
        $database_checks['database'] = [
            'name' => 'Database Name',
            'value' => DB_NAME,
            'status' => 'info'
        ];
        
        $database_checks['tables'] = [
            'name' => 'Database Tables',
            'value' => $table_count . ' tables found',
            'status' => ($table_count > 0) ? 'success' : 'warning',
            'message' => ($table_count > 0) ? '' : 'No tables found in the database'
        ];
    } catch (Exception $e) {
        $database_checks['connection'] = [
            'name' => 'Database Connection',
            'status' => 'error',
            'message' => 'Connection failed: ' . $e->getMessage()
        ];
        
        $database_checks['server'] = [
            'name' => 'Database Server',
            'value' => (defined('DB_SERVER') ? DB_SERVER : 'localhost') . ':' . (defined('DB_PORT') ? DB_PORT : '3306'),
            'status' => 'info'
        ];
        
        $database_checks['database'] = [
            'name' => 'Database Name',
            'value' => defined('DB_NAME') ? DB_NAME : 'srms_database',
            'status' => 'info'
        ];
    }
} else {
    $database_checks['connection'] = [
        'name' => 'Database Connection',
        'status' => 'error',
        'message' => 'Database class could not be initialized'
    ];
}

// Check file and directory permissions based on environment
$permission_checks['uploads_dir'] = [
    'name' => 'Uploads Directory',
    'path' => $site_root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads',
    'recommendation' => IS_WINDOWS ? '0777 (Full access)' : '0755 (rwxr-xr-x)',
    'status' => 'info'
];

$permission_checks['images_dir'] = [
    'name' => 'Images Directory',
    'path' => $site_root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images',
    'recommendation' => IS_WINDOWS ? '0777 (Full access)' : '0755 (rwxr-xr-x)',
    'status' => 'info'
];

$permission_checks['includes_dir'] = [
    'name' => 'Includes Directory',
    'path' => $site_root . DIRECTORY_SEPARATOR . 'includes',
    'recommendation' => IS_WINDOWS ? '0755 (Full access)' : '0755 (rwxr-xr-x)',
    'status' => 'info'
];

// Check if directories are writable for testing
$tmp_dir = sys_get_temp_dir();
$permission_checks['temp_dir'] = [
    'name' => 'Temporary Directory',
    'path' => $tmp_dir,
    'writable' => is_writable($tmp_dir),
    'status' => is_writable($tmp_dir) ? 'success' : 'warning',
    'message' => is_writable($tmp_dir) ? 'Directory is writable' : 'Temporary directory is not writable'
];

// Handle action to create missing directories
$action_results = [];
if (isset($_POST['action']) && $_POST['action'] === 'create_directories') {
    foreach ($directory_checks as $dir_path => $info) {
        if (!$info['exists']) {
            $success = @mkdir($info['path'], 0755, true);
            $action_results[$dir_path] = [
                'path' => $info['path'],
                'success' => $success,
                'message' => $success ? 'Directory created successfully' : 'Failed to create directory'
            ];
            
            // Update directory checks
            if ($success) {
                $directory_checks[$dir_path]['exists'] = true;
                $directory_checks[$dir_path]['writable'] = is_writable($info['path']);
                $directory_checks[$dir_path]['status'] = $directory_checks[$dir_path]['writable'] ? 'success' : 'warning';
                $directory_checks[$dir_path]['message'] = $directory_checks[$dir_path]['writable'] ? 
                    'Directory created and is writable' : 'Directory created but is not writable';
            }
        }
    }
}

// Handle single directory creation action
if (isset($_POST['action']) && $_POST['action'] === 'create_single_directory' && isset($_POST['directory'])) {
    $dir_path = $_POST['directory'];
    if (isset($directory_checks[$dir_path]) && !$directory_checks[$dir_path]['exists']) {
        $success = @mkdir($directory_checks[$dir_path]['path'], 0755, true);
        $action_results[$dir_path] = [
            'path' => $directory_checks[$dir_path]['path'],
            'success' => $success,
            'message' => $success ? 'Directory created successfully' : 'Failed to create directory'
        ];
        
        // Update directory checks
        if ($success) {
            $directory_checks[$dir_path]['exists'] = true;
            $directory_checks[$dir_path]['writable'] = is_writable($directory_checks[$dir_path]['path']);
            $directory_checks[$dir_path]['status'] = $directory_checks[$dir_path]['writable'] ? 'success' : 'warning';
            $directory_checks[$dir_path]['message'] = $directory_checks[$dir_path]['writable'] ? 
                'Directory created and is writable' : 'Directory created but is not writable';
        }
    }
}

// Start output buffer for main content
ob_start();
?>

<div class="environment-check">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-check-shield'></i> Environment Check Tool</h2>
            <p>Verify system requirements, configurations, and permissions</p>
        </div>
        <div class="banner-actions">
            <a href="../index.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Tools
            </a>
        </div>
    </div>
    
    <div class="dashboard-summary">
        <div class="summary-card system">
            <div class="card-icon">
                <i class='bx bx-server'></i>
            </div>
            <div class="card-content">
                <h3>System</h3>
                <p><?php echo $system_info['os']['value']; ?></p>
                <p>PHP <?php echo $system_info['php_version']['value']; ?></p>
                <div class="card-status <?php echo $system_info['php_version']['status']; ?>">
                    <?php echo $system_info['php_version']['status'] === 'success' ? 'Compatible' : 'Check Version'; ?>
                </div>
            </div>
        </div>
        
        <div class="summary-card database">
            <div class="card-icon">
                <i class='bx bx-data'></i>
            </div>
            <div class="card-content">
                <h3>Database</h3>
                <?php if (isset($database_checks['connection']) && $database_checks['connection']['status'] === 'success'): ?>
                    <p><?php echo isset($database_checks['version']) ? $database_checks['version']['value'] : 'Connected'; ?></p>
                    <p><?php echo isset($database_checks['tables']) ? $database_checks['tables']['value'] : ''; ?></p>
                    <div class="card-status success">Connected</div>
                <?php else: ?>
                    <p>Connection Error</p>
                    <p><?php echo isset($database_checks['connection']) ? $database_checks['connection']['message'] : 'Unknown Error'; ?></p>
                    <div class="card-status error">Connection Failed</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="summary-card directories">
            <div class="card-icon">
                <i class='bx bx-folder'></i>
            </div>
            <div class="card-content">
                <h3>Directories</h3>
                <?php
                $total_dirs = count($directory_checks);
                $existing_dirs = 0;
                $writable_dirs = 0;
                
                foreach ($directory_checks as $info) {
                    if ($info['exists']) {
                        $existing_dirs++;
                        if ($info['writable']) {
                            $writable_dirs++;
                        }
                    }
                }
                ?>
                <p><?php echo $existing_dirs; ?> of <?php echo $total_dirs; ?> directories exist</p>
                <p><?php echo $writable_dirs; ?> of <?php echo $total_dirs; ?> directories writable</p>
                <?php if ($existing_dirs === $total_dirs && $writable_dirs === $total_dirs): ?>
                    <div class="card-status success">All OK</div>
                <?php elseif ($existing_dirs === $total_dirs): ?>
                    <div class="card-status warning">Permission Issues</div>
                <?php else: ?>
                    <div class="card-status error">Missing Directories</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="summary-card extensions">
            <div class="card-icon">
                <i class='bx bx-extension'></i>
            </div>
            <div class="card-content">
                <h3>Extensions</h3>
                <?php
                $total_ext = count($extension_checks);
                $loaded_ext = 0;
                $missing_required = 0;
                
                foreach ($extension_checks as $info) {
                    if ($info['loaded']) {
                        $loaded_ext++;
                    } elseif ($info['status'] === 'error') {
                        $missing_required++;
                    }
                }
                ?>
                <p><?php echo $loaded_ext; ?> of <?php echo $total_ext; ?> extensions loaded</p>
                <p><?php echo $missing_required; ?> required extensions missing</p>
                <?php if ($missing_required === 0): ?>
                    <div class="card-status success">All Required Loaded</div>
                <?php else: ?>
                    <div class="card-status error">Missing Requirements</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($existing_dirs < $total_dirs): ?>
    <div class="quick-actions">
        <form method="post" action="">
            <input type="hidden" name="action" value="create_directories">
            <button type="submit" class="btn btn-primary">
                <i class='bx bx-folder-plus'></i> Create Missing Directories
            </button>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($action_results)): ?>
    <div class="action-results">
        <?php foreach ($action_results as $dir => $result): ?>
            <div class="alert <?php echo $result['success'] ? 'alert-success' : 'alert-danger'; ?>">
                <i class='bx <?php echo $result['success'] ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
                <div>
                    <strong><?php echo $result['success'] ? 'Success:' : 'Error:'; ?></strong>
                    <?php echo $result['message']; ?>: <?php echo htmlspecialchars($result['path']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="check-details">
        <!-- System Information -->
        <div class="panel" id="system-panel">
            <div class="panel-header">
                <h3><i class='bx bx-server'></i> System Information</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="system-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="system-content">
                <div class="info-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($system_info as $key => $info): ?>
                                <tr>
                                    <td><?php echo $info['name']; ?></td>
                                    <td><?php echo htmlspecialchars($info['value']); ?></td>
                                    <td>
                                        <?php if ($info['status'] !== 'info'): ?>
                                            <div class="status-badge <?php echo $info['status']; ?>">
                                                <?php echo ucfirst($info['status']); ?>
                                            </div>
                                            <?php if (isset($info['message']) && !empty($info['message'])): ?>
                                                <div class="status-message"><?php echo $info['message']; ?></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- PHP Configuration -->
        <div class="panel" id="php-panel">
            <div class="panel-header">
                <h3><i class='bx bxl-php'></i> PHP Configuration</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="php-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="php-content">
                <div class="info-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($php_config_checks as $key => $info): ?>
                                <tr>
                                    <td><?php echo $info['name']; ?></td>
                                    <td><?php echo htmlspecialchars($info['value']); ?></td>
                                    <td>
                                        <div class="status-badge <?php echo $info['status']; ?>">
                                            <?php echo ucfirst($info['status']); ?>
                                        </div>
                                        <?php if (!empty($info['message'])): ?>
                                            <div class="status-message"><?php echo $info['message']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Directory Checks -->
        <div class="panel" id="directory-panel">
            <div class="panel-header">
                <h3><i class='bx bx-folder'></i> Directory Status</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="directory-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="directory-content">
                <div class="info-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Directory</th>
                                <th>Path</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($directory_checks as $dir => $info): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dir); ?></td>
                                    <td>
                                        <div class="path-display"><?php echo htmlspecialchars($info['path']); ?></div>
                                    </td>
                                    <td>
                                        <div class="status-badge <?php echo $info['status']; ?>">
                                            <?php echo ucfirst($info['status']); ?>
                                        </div>
                                        <div class="status-message"><?php echo $info['message']; ?></div>
                                    </td>
                                    <td>
                                        <?php if (!$info['exists']): ?>
                                            <form method="post" action="" class="inline-form">
                                                <input type="hidden" name="action" value="create_single_directory">
                                                <input type="hidden" name="directory" value="<?php echo htmlspecialchars($dir); ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class='bx bx-folder-plus'></i> Create
                                                </button>
                                            </form>
                                        <?php elseif (!$info['writable']): ?>
                                            <button type="button" class="btn btn-sm btn-warning" data-tooltip="Set permissions to <?php echo IS_WINDOWS ? '0777' : '0755'; ?>">
                                                <i class='bx bx-key'></i> Fix Permissions
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Database Checks -->
        <div class="panel" id="database-panel">
            <div class="panel-header">
                <h3><i class='bx bx-data'></i> Database Connection</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="database-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="database-content">
                <div class="info-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($database_checks as $key => $info): ?>
                                <tr>
                                    <td><?php echo $info['name']; ?></td>
                                    <td><?php echo isset($info['value']) ? htmlspecialchars($info['value']) : ''; ?></td>
                                    <td>
                                        <div class="status-badge <?php echo $info['status']; ?>">
                                            <?php echo ucfirst($info['status']); ?>
                                        </div>
                                        <?php if (isset($info['message']) && !empty($info['message'])): ?>
                                            <div class="status-message"><?php echo $info['message']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (isset($database_checks['connection']) && $database_checks['connection']['status'] === 'success' && isset($tables) && !empty($tables)): ?>
                <div class="database-tables">
                    <h4>Database Tables</h4>
                    <div class="table-list">
                        <?php foreach ($tables as $table): ?>
                            <div class="table-item">
                                <i class='bx bx-table'></i> <?php echo htmlspecialchars($table); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Extension Checks -->
        <div class="panel" id="extensions-panel">
            <div class="panel-header">
                <h3><i class='bx bx-extension'></i> PHP Extensions</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="extensions-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="extensions-content">
                <div class="info-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Extension</th>
                                <th>Status</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($extension_checks as $ext => $info): ?>
                                <tr>
                                    <td><?php echo $info['name']; ?></td>
                                    <td>
                                        <div class="status-badge <?php echo $info['status']; ?>">
                                            <?php echo $info['loaded'] ? 'Loaded' : 'Not Loaded'; ?>
                                        </div>
                                    </td>
                                    <td><?php echo $info['message']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Recommendations -->
        <div class="panel" id="recommendations-panel">
            <div class="panel-header">
                <h3><i class='bx bx-bulb'></i> Recommendations</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="recommendations-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="recommendations-content">
                <div class="recommendations">
                    <h4>System Setup Recommendations</h4>
                    <ul class="recommendation-list">
                        <?php if (version_compare(PHP_VERSION, '7.4.0', '<')): ?>
                        <li class="recommendation-item warning">
                            <div class="recommendation-header">
                                <i class='bx bx-error-circle'></i>
                                <h5>Upgrade PHP Version</h5>
                            </div>
                            <p>Your PHP version (<?php echo PHP_VERSION; ?>) is below the recommended version. Upgrade to PHP 7.4 or higher for better performance and security.</p>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $missing_extensions = [];
                        foreach ($extension_checks as $ext => $info) {
                            if (!$info['loaded'] && $info['status'] === 'error') {
                                $missing_extensions[] = $info['name'];
                            }
                        }
                        if (!empty($missing_extensions)):
                        ?>
                        <li class="recommendation-item error">
                            <div class="recommendation-header">
                                <i class='bx bx-error-circle'></i>
                                <h5>Install Required Extensions</h5>
                            </div>
                            <p>The following required PHP extensions are missing:</p>
                            <ul>
                                <?php foreach ($missing_extensions as $ext): ?>
                                    <li><?php echo $ext; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p>Contact your hosting provider or system administrator to install these extensions.</p>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isset($database_checks['connection']) && $database_checks['connection']['status'] === 'error'): ?>
                        <li class="recommendation-item error">
                            <div class="recommendation-header">
                                <i class='bx bx-error-circle'></i>
                                <h5>Fix Database Connection</h5>
                            </div>
                            <p>Unable to connect to the database. Check your database configuration in the environment.php file.</p>
                            <p>Make sure the database server is running and the credentials are correct.</p>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $missing_dirs = [];
                        foreach ($directory_checks as $dir => $info) {
                            if (!$info['exists']) {
                                $missing_dirs[] = $dir;
                            }
                        }
                        if (!empty($missing_dirs)):
                        ?>
                        <li class="recommendation-item error">
                            <div class="recommendation-header">
                                <i class='bx bx-error-circle'></i>
                                <h5>Create Missing Directories</h5>
                            </div>
                            <p>The following directories are missing:</p>
                            <ul>
                                <?php foreach ($missing_dirs as $dir): ?>
                                    <li><?php echo $dir; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p>Use the "Create Missing Directories" button above to create them automatically.</p>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $unwritable_dirs = [];
                        foreach ($directory_checks as $dir => $info) {
                            if ($info['exists'] && !$info['writable']) {
                                $unwritable_dirs[] = $dir;
                            }
                        }
                        if (!empty($unwritable_dirs)):
                        ?>
                        <li class="recommendation-item warning">
                            <div class="recommendation-header">
                                <i class='bx bx-error-circle'></i>
                                <h5>Fix Directory Permissions</h5>
                            </div>
                            <p>The following directories are not writable:</p>
                            <ul>
                                <?php foreach ($unwritable_dirs as $dir): ?>
                                    <li><?php echo $dir; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p>Set the permissions to <?php echo IS_WINDOWS ? '0777' : '0755'; ?> to make them writable.</p>
                            <p class="command-example">
                                <?php if (IS_WINDOWS): ?>
                                    <code>Right-click on folder > Properties > Security > Edit > Add Everyone with Full Control</code>
                                <?php else: ?>
                                    <code>chmod -R 755 /path/to/directory</code>
                                <?php endif; ?>
                            </p>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (!ini_get('file_uploads')): ?>
                        <li class="recommendation-item error">
                            <div class="recommendation-header">
                                <i class='bx bx-error-circle'></i>
                                <h5>Enable File Uploads</h5>
                            </div>
                            <p>File uploads are disabled in your PHP configuration. Enable them by setting file_uploads = On in your php.ini file.</p>
                        </li>
                        <?php endif; ?>

                        <?php if ($is_production): ?>
                        <li class="recommendation-item info">
                            <div class="recommendation-header">
                                <i class='bx bx-info-circle'></i>
                                <h5>Production Environment Detected</h5>
                            </div>
                            <p>This site is running in production mode. Make sure error display is turned off and logging is enabled for security.</p>
                        </li>
                        <?php else: ?>
                        <li class="recommendation-item info">
                            <div class="recommendation-header">
                                <i class='bx bx-info-circle'></i>
                                <h5>Development Environment Detected</h5>
                            </div>
                            <p>This site is running in development mode. For production deployment, update the IS_PRODUCTION flag in environment.php.</p>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Panel toggle functionality
    const toggleButtons = document.querySelectorAll('.panel-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetContent = document.getElementById(targetId);
            
            if (targetContent.style.display === 'none') {
                targetContent.style.display = 'block';
                this.querySelector('i').classList.remove('bx-chevron-down');
                this.querySelector('i').classList.add('bx-chevron-up');
            } else {
                targetContent.style.display = 'none';
                this.querySelector('i').classList.remove('bx-chevron-up');
                this.querySelector('i').classList.add('bx-chevron-down');
            }
        });
    });
    
    // Tooltip functionality
    const tooltipButtons = document.querySelectorAll('[data-tooltip]');
    
    tooltipButtons.forEach(button => {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip-content';
        tooltip.textContent = button.getAttribute('data-tooltip');
        
        button.appendChild(tooltip);
        
        button.addEventListener('mouseenter', function() {
            tooltip.style.display = 'block';
        });
        
        button.addEventListener('mouseleave', function() {
            tooltip.style.display = 'none';
        });
    });
});
</script>

<style>
.environment-check {
    max-width: 1200px;
    margin: 0 auto;
}

.header-banner {
    background: linear-gradient(120deg, #3C91E6, #0a3060);
    border-radius: 10px;
    color: white;
    padding: 30px;
    margin-bottom: 30px;
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

.dashboard-summary {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    display: flex;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
}

.summary-card.system::before {
    background-color: #3C91E6;
}

.summary-card.database::before {
    background-color: #28a745;
}

.summary-card.directories::before {
    background-color: #ffc107;
}

.summary-card.extensions::before {
    background-color: #dc3545;
}

.card-icon {
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.summary-card.system .card-icon {
    background-color: rgba(60, 145, 230, 0.1);
    color: #3C91E6;
}

.summary-card.database .card-icon {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.summary-card.directories .card-icon {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.summary-card.extensions .card-icon {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.card-icon i {
    font-size: 24px;
}

.card-content {
    flex-grow: 1;
}

.card-content h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #0a3060;
}

.card-content p {
    margin: 0 0 5px 0;
    color: #6c757d;
    font-size: 14px;
}

.card-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
    margin-top: 10px;
}

.card-status.success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.card-status.warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.card-status.error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.quick-actions {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 30px;
}

.btn {
    padding: 10px 15px;
    border-radius: 5px;
    border: none;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn i {
    font-size: 16px;
}

.btn-primary {
    background-color: #3C91E6;
    color: white;
}

.btn-warning {
    background-color: #ffc107;
    color: #343a40;
}

.btn-light {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.action-results {
    margin-bottom: 30px;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
}

.alert i {
    margin-right: 10px;
    font-size: 20px;
    flex-shrink: 0;
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

.check-details {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.panel {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.panel-header h3 {
    margin: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    color: #0a3060;
}

.panel-header h3 i {
    margin-right: 10px;
    font-size: 20px;
    color: #3C91E6;
}

.panel-toggle {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.panel-toggle:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.panel-content {
    padding: 20px;
}

.info-table {
    width: 100%;
    overflow-x: auto;
}

.info-table table {
    width: 100%;
    border-collapse: collapse;
}

.info-table th, .info-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.info-table th {
    background-color: #f8f9fa;
    font-weight: 500;
    color: #495057;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-badge.warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.status-badge.error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.status-badge.info {
    background-color: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.status-message {
    margin-top: 5px;
    font-size: 12px;
    color: #6c757d;
}

.path-display {
    font-family: monospace;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.path-display:hover {
    white-space: normal;
    word-break: break-all;
}

.inline-form {
    display: inline-block;
}

.database-tables {
    margin-top: 30px;
}

.database-tables h4 {
    margin: 0 0 15px 0;
    color: #0a3060;
    font-size: 16px;
}

.table-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.table-item {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    color: #495057;
}

.table-item i {
    color: #3C91E6;
}

.recommendations {
    padding: 10px;
}

.recommendations h4 {
    margin: 0 0 20px 0;
    color: #0a3060;
    font-size: 18px;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 10px;
}

.recommendation-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.recommendation-item {
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid;
}

.recommendation-item.error {
    background-color: rgba(220, 53, 69, 0.05);
    border-left-color: #dc3545;
}

.recommendation-item.warning {
    background-color: rgba(255, 193, 7, 0.05);
    border-left-color: #ffc107;
}

.recommendation-item.success {
    background-color: rgba(40, 167, 69, 0.05);
    border-left-color: #28a745;
}

.recommendation-item.info {
    background-color: rgba(60, 145, 230, 0.05);
    border-left-color: #3C91E6;
}

.recommendation-header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.recommendation-header i {
    margin-right: 10px;
    font-size: 20px;
}

.recommendation-item.error .recommendation-header i {
    color: #dc3545;
}

.recommendation-item.warning .recommendation-header i {
    color: #ffc107;
}

.recommendation-item.success .recommendation-header i {
    color: #28a745;
}

.recommendation-item.info .recommendation-header i {
    color: #3C91E6;
}

.recommendation-header h5 {
    margin: 0;
    font-size: 16px;
    color: #0a3060;
}

.recommendation-item p {
    margin: 0 0 10px 0;
    color: #495057;
}

.recommendation-item ul {
    margin: 10px 0;
    padding-left: 20px;
}

.recommendation-item li {
    margin-bottom: 5px;
    color: #495057;
}

.command-example {
    margin-top: 10px;
}

.command-example code {
    display: block;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
    font-family: monospace;
    color: #495057;
    overflow-x: auto;
}

.tooltip-content {
    display: none;
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 10px;
    background-color: #000;
    color: #fff;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 10;
    margin-bottom: 5px;
}

@media (max-width: 768px) {
    .header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .banner-actions {
        margin-top: 15px;
    }
    
    .dashboard-summary {
        grid-template-columns: 1fr;
    }
    
    .path-display {
        max-width: 150px;
    }
}
</style>

<?php
$content = ob_get_clean();

// Define the page title and specific CSS/JS
$page_title = 'Environment Check Tool';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout with environment-aware path
$layout_path = '../../layout.php';
if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
    $layout_path = $_SERVER['DOCUMENT_ROOT'] . '/layout.php';
}

include $layout_path;
?>