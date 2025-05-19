<?php
/**
 * Database Tools Utility
 * Provides functionality for database maintenance, backup, and optimization
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Require admin level permissions for database operations
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    echo "<div class='alert alert-danger'>This tool requires administrator privileges.</div>";
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

// Initialize database connection
$db = new Database();

// Initialize results array
$results = [];
$errors = [];
$info = [];

// Process database backup
if (isset($_POST['action']) && $_POST['action'] === 'backup') {
    $results['backup'] = backupDatabase();
}

// Process database optimization
if (isset($_POST['action']) && $_POST['action'] === 'optimize') {
    $results['optimize'] = optimizeTables();
}

// Process database repair
if (isset($_POST['action']) && $_POST['action'] === 'repair') {
    $results['repair'] = repairTables();
}

// Process table analysis
if (isset($_POST['action']) && $_POST['action'] === 'analyze') {
    $results['analyze'] = analyzeTables();
}

// Process database check
if (isset($_POST['action']) && $_POST['action'] === 'check') {
    $results['check'] = checkDatabase();
}

// Get database tables and statistics
$tables = getTables();
$database_stats = getDatabaseStats();

/**
 * Backup the database
 */
function backupDatabase() {
    global $errors, $info, $site_root;
    
    // Create backup directory if it doesn't exist
    $backup_dir = $site_root . DS . 'database' . DS . 'backups';
    if (!is_dir($backup_dir)) {
        // Use different permissions based on OS
        $permissions = defined('IS_WINDOWS') && IS_WINDOWS ? 0777 : 0755;
        if (!mkdir($backup_dir, $permissions, true)) {
            $errors[] = "Failed to create backup directory: {$backup_dir}";
            return false;
        }
    }
    
    // Set backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_{$timestamp}.sql";
    $backup_file = $backup_dir . DS . $filename;
    
    // Database credentials
    $host = DB_SERVER;
    $user = DB_USERNAME;
    $pass = DB_PASSWORD;
    $name = DB_NAME;
    $port = defined('DB_PORT') ? DB_PORT : '3306';
    
    if (defined('IS_WINDOWS') && IS_WINDOWS) {
        // Windows implementation using mysqldump
        $mysqldump_path = findMysqldumpPath();
        
        if (!$mysqldump_path) {
            $errors[] = "Unable to find mysqldump executable. Install MySQL client tools or ensure they're in the PATH.";
            return false;
        }
        
        // For older versions of mysqldump, use -p parameter directly
        // Note: This will show the password in process list temporarily, but it's safer than hard-coding it
        $command = "\"{$mysqldump_path}\" --host={$host} --port={$port} --user={$user} --password=\"{$pass}\" {$name} > \"{$backup_file}\" 2>&1";
        
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            $errors[] = "Database backup failed with error code {$return_var}";
            if (!empty($output)) {
                // Log the error but don't show full command in UI
                error_log("Backup error: " . implode("\n", $output));
                // Show a sanitized version to the user
                $errors[] = "Error details: " . sanitizeErrorOutput(implode("\n", $output));
            }
            return false;
        }
    } else {
        // Linux implementation using mysqldump
        // First check if mysqldump exists
        $mysqldump_path = findLinuxMysqldumpPath();
        
        if (!$mysqldump_path) {
            $errors[] = "Unable to find mysqldump executable. Install MySQL client tools or ensure they're in the PATH.";
            return false;
        }
        
        // For older versions of mysqldump, use -p parameter directly
        $command = "{$mysqldump_path} --host={$host} --port={$port} --user={$user} --password=\"{$pass}\" {$name} > \"{$backup_file}\" 2>&1";
        
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            $errors[] = "Database backup failed";
            if (!empty($output)) {
                // Log the error but don't show full command in UI
                error_log("Backup error: " . implode("\n", $output));
                // Show a sanitized version to the user
                $errors[] = "Error details: " . sanitizeErrorOutput(implode("\n", $output));
            }
            return false;
        }
    }
    
    // Check if the backup file was created
    if (!file_exists($backup_file)) {
        $errors[] = "Backup file was not created: {$backup_file}";
        return false;
    }
    
    // Get file size
    $file_size = filesize($backup_file);
    
    $info[] = "Database backup created successfully: {$filename} (" . formatBytes($file_size) . ")";
    
    return [
        'success' => true,
        'filename' => $filename,
        'path' => $backup_file,
        'size' => $file_size,
        'size_formatted' => formatBytes($file_size),
        'timestamp' => $timestamp
    ];
}

/**
 * Sanitize error output to remove sensitive information
 */
function sanitizeErrorOutput($error) {
    // Remove any potential passwords
    $sanitized = preg_replace('/password=([^\s]+)/', 'password=********', $error);
    $sanitized = preg_replace('/--password=([^\s]+)/', '--password=********', $sanitized);
    
    // Remove any file paths that might reveal server structure
    $sanitized = preg_replace('/in\s+([\/\\\\][^\s:]+)/', 'in [PATH]', $sanitized);
    
    return $sanitized;
}

/**
 * Find mysqldump executable path on Windows
 */
function findMysqldumpPath() {
    // Common mysqldump paths on Windows
    $possible_paths = [
        'C:' . DS . 'xampp' . DS . 'mysql' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'wamp' . DS . 'bin' . DS . 'mysql' . DS . 'mysql5.7.26' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'wamp64' . DS . 'bin' . DS . 'mysql' . DS . 'mysql5.7.26' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'Program Files' . DS . 'MySQL' . DS . 'MySQL Server 5.7' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'Program Files' . DS . 'MySQL' . DS . 'MySQL Server 8.0' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'Program Files (x86)' . DS . 'MySQL' . DS . 'MySQL Server 5.7' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'Program Files (x86)' . DS . 'MySQL' . DS . 'MySQL Server 8.0' . DS . 'bin' . DS . 'mysqldump.exe',
        // Additional XAMPP paths with version folders
        'C:' . DS . 'xampp' . DS . 'mysql' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'xampp7' . DS . 'mysql' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'xampp8' . DS . 'mysql' . DS . 'bin' . DS . 'mysqldump.exe',
        // MariaDB paths
        'C:' . DS . 'Program Files' . DS . 'MariaDB' . DS . 'MariaDB 10.4' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'Program Files' . DS . 'MariaDB' . DS . 'MariaDB 10.5' . DS . 'bin' . DS . 'mysqldump.exe',
        'C:' . DS . 'Program Files' . DS . 'MariaDB' . DS . 'MariaDB 10.6' . DS . 'bin' . DS . 'mysqldump.exe',
    ];
    
    // Check each path
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // If not found, try to locate using 'where' command
    $output = [];
    $return_var = 0;
    exec('where mysqldump', $output, $return_var);
    
    if ($return_var === 0 && !empty($output)) {
        return trim($output[0]);
    }
    
    return false;
}

/**
 * Find mysqldump executable path on Linux
 */
function findLinuxMysqldumpPath() {
    // Common mysqldump paths on Linux
    $possible_paths = [
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        '/usr/local/mysql/bin/mysqldump',
        '/opt/lampp/bin/mysqldump',
        '/opt/mysql/bin/mysqldump',
        '/opt/bitnami/mysql/bin/mysqldump'
    ];
    
    // Check each path
    foreach ($possible_paths as $path) {
        if (file_exists($path) && is_executable($path)) {
            return $path;
        }
    }
    
    // If not found, try to locate using 'which' command
    $output = [];
    $return_var = 0;
    exec('which mysqldump', $output, $return_var);
    
    if ($return_var === 0 && !empty($output)) {
        return trim($output[0]);
    }
    
    return false;
}

/**
 * Optimize database tables
 */
function optimizeTables() {
    global $db, $errors, $info;
    
    $tables = getTables();
    $results = [];
    
    foreach ($tables as $table) {
        $table_name = $table['name'];
        
        try {
            $query = "OPTIMIZE TABLE `{$table_name}`";
            $optimize_result = $db->query($query);
            
            if ($optimize_result) {
                $info[] = "Table {$table_name} optimized successfully.";
                $results[$table_name] = [
                    'success' => true,
                    'message' => 'Optimized successfully'
                ];
            } else {
                $errors[] = "Failed to optimize table {$table_name}.";
                $results[$table_name] = [
                    'success' => false,
                    'message' => 'Optimization failed'
                ];
            }
        } catch (Exception $e) {
            $errors[] = "Error optimizing table {$table_name}: " . $e->getMessage();
            $results[$table_name] = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    return $results;
}

/**
 * Repair database tables
 */
function repairTables() {
    global $db, $errors, $info;
    
    $tables = getTables();
    $results = [];
    
    foreach ($tables as $table) {
        $table_name = $table['name'];
        
        try {
            $query = "REPAIR TABLE `{$table_name}`";
            $repair_result = $db->query($query);
            
            if ($repair_result) {
                $info[] = "Table {$table_name} repaired successfully.";
                $results[$table_name] = [
                    'success' => true,
                    'message' => 'Repaired successfully'
                ];
            } else {
                $errors[] = "Failed to repair table {$table_name}.";
                $results[$table_name] = [
                    'success' => false,
                    'message' => 'Repair failed'
                ];
            }
        } catch (Exception $e) {
            $errors[] = "Error repairing table {$table_name}: " . $e->getMessage();
            $results[$table_name] = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    return $results;
}

/**
 * Analyze database tables
 */
function analyzeTables() {
    global $db, $errors, $info;
    
    $tables = getTables();
    $results = [];
    
    foreach ($tables as $table) {
        $table_name = $table['name'];
        
        try {
            $query = "ANALYZE TABLE `{$table_name}`";
            $analyze_result = $db->query($query);
            
            if ($analyze_result) {
                $info[] = "Table {$table_name} analyzed successfully.";
                $results[$table_name] = [
                    'success' => true,
                    'message' => 'Analyzed successfully'
                ];
            } else {
                $errors[] = "Failed to analyze table {$table_name}.";
                $results[$table_name] = [
                    'success' => false,
                    'message' => 'Analysis failed'
                ];
            }
        } catch (Exception $e) {
            $errors[] = "Error analyzing table {$table_name}: " . $e->getMessage();
            $results[$table_name] = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    return $results;
}

/**
 * Check database for errors
 */
function checkDatabase() {
    global $db, $errors, $info;
    
    $tables = getTables();
    $results = [];
    
    foreach ($tables as $table) {
        $table_name = $table['name'];
        
        try {
            $query = "CHECK TABLE `{$table_name}`";
            $check_result = $db->query($query);
            
            if ($check_result) {
                $info[] = "Table {$table_name} checked successfully.";
                $results[$table_name] = [
                    'success' => true,
                    'message' => 'Checked successfully'
                ];
            } else {
                $errors[] = "Failed to check table {$table_name}.";
                $results[$table_name] = [
                    'success' => false,
                    'message' => 'Check failed'
                ];
            }
        } catch (Exception $e) {
            $errors[] = "Error checking table {$table_name}: " . $e->getMessage();
            $results[$table_name] = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    return $results;
}

/**
 * Get all database tables and their info
 */
function getTables() {
    global $db, $errors;
    
    try {
        $query = "SHOW TABLE STATUS";
        $result = $db->query($query);
        
        $tables = [];
        
        // If result is not a mysqli_result, return empty array
        if (!$result || !method_exists($result, 'fetch_assoc')) {
            return [];
        }
        
        while ($row = $result->fetch_assoc()) {
            $tables[] = [
                'name' => $row['Name'],
                'engine' => $row['Engine'],
                'rows' => $row['Rows'],
                'data_length' => $row['Data_length'],
                'index_length' => $row['Index_length'],
                'data_free' => $row['Data_free'],
                'auto_increment' => $row['Auto_increment'],
                'collation' => $row['Collation'],
                'comment' => $row['Comment']
            ];
        }
        
        return $tables;
    } catch (Exception $e) {
        $errors[] = "Error fetching table information: " . $e->getMessage();
        return [];
    }
}

/**
 * Get database statistics
 */
function getDatabaseStats() {
    global $db, $errors;
    
    $stats = [
        'total_tables' => 0,
        'total_rows' => 0,
        'total_size' => 0,
        'total_index_size' => 0,
        'total_data_free' => 0,
        'largest_table' => [
            'name' => '',
            'size' => 0
        ],
        'smallest_table' => [
            'name' => '',
            'size' => 0
        ]
    ];
    
    $tables = getTables();
    
    if (empty($tables)) {
        return $stats;
    }
    
    $stats['total_tables'] = count($tables);
    
    foreach ($tables as $table) {
        $stats['total_rows'] += $table['rows'];
        $stats['total_size'] += $table['data_length'];
        $stats['total_index_size'] += $table['index_length'];
        $stats['total_data_free'] += $table['data_free'];
        
        $table_size = $table['data_length'] + $table['index_length'];
        
        if ($table_size > $stats['largest_table']['size']) {
            $stats['largest_table'] = [
                'name' => $table['name'],
                'size' => $table_size,
                'size_formatted' => formatBytes($table_size)
            ];
        }
        
        if ($stats['smallest_table']['size'] === 0 || $table_size < $stats['smallest_table']['size']) {
            $stats['smallest_table'] = [
                'name' => $table['name'],
                'size' => $table_size,
                'size_formatted' => formatBytes($table_size)
            ];
        }
    }
    
    // Format sizes
    $stats['total_size_formatted'] = formatBytes($stats['total_size']);
    $stats['total_index_size_formatted'] = formatBytes($stats['total_index_size']);
    $stats['total_data_free_formatted'] = formatBytes($stats['total_data_free']);
    
    return $stats;
}

/**
 * Format bytes to human-readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get available database backups
 */
function getBackups() {
    global $site_root;
    $backups = [];
    $backup_dir = $site_root . DS . 'database' . DS . 'backups';
    
    if (is_dir($backup_dir)) {
        // Use glob with normalized path
        $pattern = $backup_dir . DS . 'backup_*.sql';
        // Standardize slashes for glob
        $pattern = str_replace('\\', '/', $pattern);
        
        $files = glob($pattern);
        
        if (!empty($files)) {
            foreach ($files as $file) {
                $filename = basename($file);
                $size = filesize($file);
                $time = filemtime($file);
                
                $backups[] = [
                    'filename' => $filename,
                    'path' => $file,
                    'size' => $size,
                    'size_formatted' => formatBytes($size),
                    'time' => $time,
                    'time_formatted' => date('Y-m-d H:i:s', $time)
                ];
            }
            
            // Sort backups by time (newest first)
            usort($backups, function($a, $b) {
                return $b['time'] - $a['time'];
            });
        }
    }
    
    return $backups;
}

// Get available backups
$backups = getBackups();

// Define tab to show based on POST action or default to 'tables'
$active_tab = 'tables';
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'backup':
            $active_tab = 'backup';
            break;
        case 'optimize':
        case 'repair':
        case 'analyze':
        case 'check':
            $active_tab = 'maintenance';
            break;
    }
}

// Start output buffer for main content
ob_start();
?>

<div class="database-tools">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-data'></i> Database Tools</h2>
            <p>Manage, maintain, and back up your database</p>
        </div>
        <div class="banner-actions">
            <a href="../index.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Tools
            </a>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="alerts">
        <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger">
            <i class='bx bx-error-circle'></i>
            <div class="alert-content"><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($info)): ?>
    <div class="alerts">
        <?php foreach ($info as $message): ?>
        <div class="alert alert-success">
            <i class='bx bx-check-circle'></i>
            <div class="alert-content"><?php echo htmlspecialchars($message); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Environment Information -->
    <div class="environment-info">
        <div class="alert alert-info">
            <i class='bx bx-info-circle'></i>
            <div class="alert-content">
                <strong>Server Environment:</strong> 
                <?php echo defined('IS_WINDOWS') && IS_WINDOWS ? 'Windows' : 'Linux'; ?> | 
                <?php echo defined('SERVER_TYPE') ? SERVER_TYPE : 'Unknown Server'; ?> | 
                PHP <?php echo PHP_VERSION_INFO; ?>
            </div>
        </div>
    </div>
    
    <!-- Database Statistics -->
    <div class="stats-overview">
        <div class="stats-card">
            <div class="stats-header">
                <i class='bx bx-table'></i>
                <h3>Tables</h3>
            </div>
            <div class="stats-value"><?php echo number_format($database_stats['total_tables']); ?></div>
        </div>
        
        <div class="stats-card">
            <div class="stats-header">
                <i class='bx bx-list-ul'></i>
                <h3>Rows</h3>
            </div>
            <div class="stats-value"><?php echo number_format($database_stats['total_rows']); ?></div>
        </div>
        
        <div class="stats-card">
            <div class="stats-header">
                <i class='bx bx-hdd'></i>
                <h3>Size</h3>
            </div>
            <div class="stats-value"><?php echo $database_stats['total_size_formatted']; ?></div>
        </div>
        
        <div class="stats-card">
            <div class="stats-header">
                <i class='bx bx-refresh'></i>
                <h3>Overhead</h3>
            </div>
            <div class="stats-value"><?php echo $database_stats['total_data_free_formatted']; ?></div>
        </div>
    </div>
    
    <div class="tab-container">
        <div class="tab-header">
            <button type="button" class="tab-btn <?php echo $active_tab === 'tables' ? 'active' : ''; ?>" data-tab="tables">
                <i class='bx bx-table'></i> Tables
            </button>
            <button type="button" class="tab-btn <?php echo $active_tab === 'maintenance' ? 'active' : ''; ?>" data-tab="maintenance">
                <i class='bx bx-wrench'></i> Maintenance
            </button>
            <button type="button" class="tab-btn <?php echo $active_tab === 'backup' ? 'active' : ''; ?>" data-tab="backup">
                <i class='bx bx-archive'></i> Backup
            </button>
        </div>
        
        <div class="tab-content">
            <!-- Tables Tab -->
            <div class="tab-pane <?php echo $active_tab === 'tables' ? 'active' : ''; ?>" id="tables">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Engine</th>
                                <th>Rows</th>
                                <th>Data Size</th>
                                <th>Index Size</th>
                                <th>Overhead</th>
                                <th>Collation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $table): ?>
                                <?php
                                $data_size = $table['data_length'];
                                $index_size = $table['index_length'];
                                $overhead = $table['data_free'];
                                ?>
                                <tr>
                                    <td class="table-name"><?php echo htmlspecialchars($table['name']); ?></td>
                                    <td><?php echo htmlspecialchars($table['engine']); ?></td>
                                    <td><?php echo number_format($table['rows']); ?></td>
                                    <td><?php echo formatBytes($data_size); ?></td>
                                    <td><?php echo formatBytes($index_size); ?></td>
                                    <td>
                                        <?php if ($overhead > 0): ?>
                                            <span class="overhead-value"><?php echo formatBytes($overhead); ?></span>
                                        <?php else: ?>
                                            <span class="no-overhead">0 B</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($table['collation']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total: <?php echo count($tables); ?> tables</th>
                                <th></th>
                                <th><?php echo number_format($database_stats['total_rows']); ?> rows</th>
                                <th><?php echo $database_stats['total_size_formatted']; ?></th>
                                <th><?php echo $database_stats['total_index_size_formatted']; ?></th>
                                <th><?php echo $database_stats['total_data_free_formatted']; ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="additional-info">
                    <div class="info-item">
                        <div class="info-label">Largest Table:</div>
                        <div class="info-value">
                            <?php if (!empty($database_stats['largest_table']['name'])): ?>
                                <?php echo htmlspecialchars($database_stats['largest_table']['name']); ?> 
                                (<?php echo $database_stats['largest_table']['size_formatted']; ?>)
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Smallest Table:</div>
                        <div class="info-value">
                            <?php if (!empty($database_stats['smallest_table']['name'])): ?>
                                <?php echo htmlspecialchars($database_stats['smallest_table']['name']); ?> 
                                (<?php echo $database_stats['smallest_table']['size_formatted']; ?>)
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Maintenance Tab -->
            <div class="tab-pane <?php echo $active_tab === 'maintenance' ? 'active' : ''; ?>" id="maintenance">
                <div class="maintenance-warning">
                    <i class='bx bx-error-circle'></i>
                    <div class="warning-content">
                        <h4>Warning: Database Maintenance</h4>
                        <p>Performing maintenance operations may temporarily lock tables and affect website performance. It's recommended to run these operations during low-traffic periods.</p>
                    </div>
                </div>
                
                <div class="maintenance-cards">
                    <div class="maintenance-card">
                        <div class="card-header">
                            <i class='bx bx-refresh'></i>
                            <h3>Optimize Tables</h3>
                        </div>
                        <div class="card-content">
                            <p>Optimizes database tables by defragmenting and reclaiming unused space. Recommended if you have tables with significant overhead.</p>
                            <form method="post" action="">
                                <input type="hidden" name="action" value="optimize">
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-refresh'></i> Optimize All Tables
                                </button>
                            </form>
                            
                            <?php if (isset($results['optimize'])): ?>
                            <div class="operation-results">
                                <h4>Results:</h4>
                                <ul class="results-list">
                                    <?php foreach ($results['optimize'] as $table => $result): ?>
                                    <li class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                                        <span class="table-name"><?php echo htmlspecialchars($table); ?></span>
                                        <span class="result-message"><?php echo htmlspecialchars($result['message']); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="maintenance-card">
                        <div class="card-header">
                            <i class='bx bx-wrench'></i>
                            <h3>Repair Tables</h3>
                        </div>
                        <div class="card-content">
                            <p>Repairs corrupted database tables. Use this if you're experiencing database errors or inconsistencies.</p>
                            <form method="post" action="">
                                <input type="hidden" name="action" value="repair">
                                <button type="submit" class="btn btn-warning">
                                    <i class='bx bx-wrench'></i> Repair All Tables
                                </button>
                            </form>
                            
                            <?php if (isset($results['repair'])): ?>
                            <div class="operation-results">
                                <h4>Results:</h4>
                                <ul class="results-list">
                                    <?php foreach ($results['repair'] as $table => $result): ?>
                                    <li class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                                        <span class="table-name"><?php echo htmlspecialchars($table); ?></span>
                                        <span class="result-message"><?php echo htmlspecialchars($result['message']); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="maintenance-card">
                        <div class="card-header">
                            <i class='bx bx-search-alt'></i>
                            <h3>Analyze Tables</h3>
                        </div>
                        <div class="card-content">
                            <p>Analyzes and stores the key distribution for tables. This helps the query optimizer make better decisions.</p>
                            <form method="post" action="">
                                <input type="hidden" name="action" value="analyze">
                                <button type="submit" class="btn btn-info">
                                    <i class='bx bx-search-alt'></i> Analyze All Tables
                                </button>
                            </form>
                            
                            <?php if (isset($results['analyze'])): ?>
                            <div class="operation-results">
                                <h4>Results:</h4>
                                <ul class="results-list">
                                    <?php foreach ($results['analyze'] as $table => $result): ?>
                                    <li class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                                        <span class="table-name"><?php echo htmlspecialchars($table); ?></span>
                                        <span class="result-message"><?php echo htmlspecialchars($result['message']); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="maintenance-card">
                        <div class="card-header">
                            <i class='bx bx-check-shield'></i>
                            <h3>Check Tables</h3>
                        </div>
                        <div class="card-content">
                            <p>Checks tables for errors. Use this to verify the integrity of your database tables.</p>
                            <form method="post" action="">
                                <input type="hidden" name="action" value="check">
                                <button type="submit" class="btn btn-secondary">
                                    <i class='bx bx-check-shield'></i> Check All Tables
                                </button>
                            </form>
                            
                            <?php if (isset($results['check'])): ?>
                            <div class="operation-results">
                                <h4>Results:</h4>
                                <ul class="results-list">
                                    <?php foreach ($results['check'] as $table => $result): ?>
                                    <li class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                                        <span class="table-name"><?php echo htmlspecialchars($table); ?></span>
                                        <span class="result-message"><?php echo htmlspecialchars($result['message']); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Backup Tab -->
            <div class="tab-pane <?php echo $active_tab === 'backup' ? 'active' : ''; ?>" id="backup">
                <div class="backup-section">
                    <div class="backup-header">
                        <h3><i class='bx bx-download'></i> Create Backup</h3>
                    </div>
                    <div class="backup-content">
                        <p>Create a full backup of your database. The backup file will be stored in the <code>database/backups</code> directory.</p>
                        <form method="post" action="" class="backup-form">
                            <input type="hidden" name="action" value="backup">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-download'></i> Create Backup Now
                            </button>
                        </form>
                        
                        <?php if (isset($results['backup']) && $results['backup'] !== false): ?>
                        <div class="backup-results">
                            <div class="backup-success">
                                <i class='bx bx-check-circle'></i>
                                <div class="success-details">
                                    <h4>Backup Created Successfully</h4>
                                    <p>
                                        Filename: <?php echo htmlspecialchars($results['backup']['filename']); ?><br>
                                        Size: <?php echo $results['backup']['size_formatted']; ?><br>
                                        Date: <?php echo date('Y-m-d H:i:s', strtotime($results['backup']['timestamp'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="backup-section">
                    <div class="backup-header">
                        <h3><i class='bx bx-archive'></i> Available Backups</h3>
                    </div>
                    
                    <?php if (empty($backups)): ?>
                    <div class="no-backups">
                        <i class='bx bx-file-blank'></i>
                        <p>No backup files found. Create your first backup using the form above.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                    <td><?php echo $backup['size_formatted']; ?></td>
                                    <td><?php echo $backup['time_formatted']; ?></td>
                                    <td>
                                        <a href="download-backup.php?file=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-primary">
                                            <i class='bx bx-download'></i> Download
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="backup-section">
                    <div class="backup-header">
                        <h3><i class='bx bx-info-circle'></i> Backup Information</h3>
                    </div>
                    <div class="backup-content">
                        <div class="info-card">
                            <h4>Backup Location</h4>
                            <p>Backup files are stored in:</p>
                            <code><?php echo $site_root . DS . 'database' . DS . 'backups'; ?></code>
                            
                            <h4>Important Notes</h4>
                            <ul>
                                <li>Regular backups are essential for data protection</li>
                                <li>Keep backup files in a secure location outside your web server</li>
                                <li>For large databases, consider scheduling backups during low-traffic periods</li>
                                <li>The automatic backup feature requires mysqldump to be installed and accessible</li>
                            </ul>
                            
                            <h4>Manual Backup</h4>
                            <p>If automatic backup fails, you can manually backup using these commands:</p>
                            
                            <div class="code-block">
                                <h5>Linux/Mac</h5>
                                <pre><code>mysqldump -u <?php echo DB_USERNAME; ?> -p <?php echo DB_NAME; ?> > backup_manual.sql</code></pre>
                            </div>
                            
                            <div class="code-block">
                                <h5>Windows</h5>
                                <pre><code>"C:\xampp\mysql\bin\mysqldump.exe" -u <?php echo DB_USERNAME; ?> -p <?php echo DB_NAME; ?> > backup_manual.sql</code></pre>
                            </div>
                        </div>
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
});
</script>

<style>
.database-tools {
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
}

.btn i {
    font-size: 16px;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-primary {
    background-color: #3C91E6;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
}

.btn-light {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
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

.stats-overview {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stats-card {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 20px;
    text-align: center;
}

.stats-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 15px;
}

.stats-header i {
    font-size: 32px;
    color: #3C91E6;
    margin-bottom: 10px;
}

.stats-header h3 {
    margin: 0;
    font-size: 16px;
    color: #0a3060;
}

.stats-value {
    font-size: 24px;
    font-weight: 700;
    color: #0a3060;
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

.data-table tfoot th {
    font-weight: 500;
    color: #0a3060;
}

.table-name {
    font-family: monospace;
    color: #0a3060;
}

.overhead-value {
    color: #dc3545;
    font-weight: 500;
}

.no-overhead {
    color: #6c757d;
}

.additional-info {
    margin-top: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.info-item {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    flex-grow: 1;
}

.info-label {
    font-weight: 500;
    color: #0a3060;
    margin-bottom: 5px;
}

.maintenance-warning {
    background-color: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.2);
    color: #856404;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
}

.maintenance-warning i {
    font-size: 24px;
    margin-right: 15px;
}

.warning-content h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
}

.warning-content p {
    margin: 0;
}

.maintenance-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
    gap: 20px;
}

.maintenance-card {
    background-color: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
}

.card-header {
    background-color: #0a3060;
    color: white;
    padding: 15px;
    display: flex;
    align-items: center;
}

.card-header i {
    font-size: 20px;
    margin-right: 10px;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
}

.card-content {
    padding: 15px;
}

.card-content p {
    margin-top: 0;
    margin-bottom: 15px;
}

.operation-results {
    margin-top: 20px;
    border-top: 1px solid #dee2e6;
    padding-top: 15px;
}

.operation-results h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #0a3060;
}

.results-list {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 150px;
    overflow-y: auto;
}

.results-list li {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    font-size: 13px;
}

.results-list li.success {
    color: #28a745;
}

.results-list li.error {
    color: #dc3545;
}

.backup-section {
    margin-bottom: 30px;
}

.backup-header {
    margin-bottom: 15px;
}

.backup-header h3 {
    margin: 0;
    color: #0a3060;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.backup-content {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.backup-form {
    margin-top: 15px;
}

.backup-results {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
}

.backup-success {
    display: flex;
    align-items: flex-start;
}

.backup-success i {
    font-size: 24px;
    margin-right: 15px;
    color: #28a745;
}

.success-details h4 {
    margin: 0 0 10px 0;
    color: #28a745;
    font-size: 16px;
}

.success-details p {
    margin: 0;
}

.no-backups {
    text-align: center;
    padding: 40px 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    color: #6c757d;
}

.no-backups i {
    font-size: 48px;
    margin-bottom: 10px;
    opacity: 0.5;
}

.no-backups p {
    margin: 0;
}

.info-card {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
}

.info-card h4 {
    margin: 0 0 10px 0;
    color: #0a3060;
    font-size: 16px;
}

.info-card p {
    margin: 0 0 15px 0;
}

.info-card code {
    display: block;
    padding: 10px;
    background-color: #f1f1f1;
    border-radius: 5px;
    font-family: monospace;
    margin-bottom: 20px;
}

.info-card ul {
    margin: 0 0 20px 0;
    padding-left: 20px;
}

.info-card li {
    margin-bottom: 5px;
}

.code-block {
    margin-bottom: 15px;
}

.code-block h5 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #0a3060;
}

.code-block pre {
    margin: 0;
    padding: 10px;
    background-color: #f1f1f1;
    border-radius: 5px;
    overflow-x: auto;
}

.code-block code {
    font-family: monospace;
    margin: 0;
    padding: 0;
    display: block;
}

@media (max-width: 768px) {
    .header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .banner-actions {
        margin-top: 15px;
    }
    
    .stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .maintenance-cards {
        grid-template-columns: 1fr;
    }
    
    .tab-btn {
        padding: 10px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .stats-overview {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Database Tools';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout with absolute path
include_once $site_root . DS . 'admin' . DS . 'layout.php';
?>