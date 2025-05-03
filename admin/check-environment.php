<?php

// Include environment settings
require_once __DIR__ . '/../environment.php';

// Start session for admin users
session_start();

// Security check
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Unauthorized access";
    exit;
}

// Function to check if a directory is writable
function check_writable($path, $create_if_missing = true) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $path;
    
    if (!file_exists($full_path)) {
        if ($create_if_missing) {
            if (!mkdir($full_path, IS_WINDOWS ? 0777 : 0755, true)) {
                return [
                    'status' => 'error',
                    'message' => "Directory {$path} doesn't exist and couldn't be created"
                ];
            }
        } else {
            return [
                'status' => 'error',
                'message' => "Directory {$path} doesn't exist"
            ];
        }
    }
    
    if (!is_writable($full_path)) {
        return [
            'status' => 'error',
            'message' => "Directory {$path} exists but is not writable"
        ];
    }
    
    return [
        'status' => 'success',
        'message' => "Directory {$path} exists and is writable"
    ];
}

// List of directories to check
$directories = [
    '/assets/images/news',
    '/assets/images/events',
    '/assets/images/promotional',
    '/assets/images/facilities',
    '/assets/images/campus',
    '/assets/images/people',
    '/assets/uploads/temp'
];

// Check directories
$directory_results = [];
foreach ($directories as $dir) {
    $directory_results[$dir] = check_writable($dir);
}

// Check database connection
try {
    $db = new PDO(
        "mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, 
        DB_USERNAME, 
        DB_PASSWORD
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_status = [
        'status' => 'success',
        'message' => "Successfully connected to database {" . DB_NAME . "} on port {" . DB_PORT . "}"
    ];
} catch (PDOException $e) {
    $db_status = [
        'status' => 'error',
        'message' => "Database connection failed: " . $e->getMessage()
    ];
}

// Check PHP extensions
$required_extensions = ['pdo', 'pdo_mysql', 'gd', 'fileinfo', 'mbstring'];
$extension_results = [];
foreach ($required_extensions as $ext) {
    $extension_results[$ext] = [
        'status' => extension_loaded($ext) ? 'success' : 'error',
        'message' => extension_loaded($ext) ? "Extension {$ext} is loaded" : "Extension {$ext} is not loaded"
    ];
}

// Check PHP configuration
$php_config = [
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit')
];

// Display the results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environment Check | SRMS Admin</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #0a3060;
        }
        .status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .success-text {
            color: #28a745;
        }
        .error-text {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <h1>Environment Check</h1>
    
    <h2>System Information</h2>
    <table>
        <tr>
            <th>Item</th>
            <th>Value</th>
        </tr>
        <tr>
            <td>Operating System</td>
            <td><?php echo PHP_OS; ?> (<?php echo IS_WINDOWS ? 'Windows' : 'Unix/Linux'; ?>)</td>
        </tr>
        <tr>
            <td>PHP Version</td>
            <td><?php echo PHP_VERSION; ?></td>
        </tr>
        <tr>
            <td>Server Software</td>
            <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
        </tr>
        <tr>
            <td>Document Root</td>
            <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
        </tr>
        <tr>
            <td>Server Type</td>
                <td><?php echo SERVER_TYPE; ?> (Auto-detected)</td>
            </tr>
        <tr>
            <td>Server Software</td>
            <td><?php echo SERVER_SOFTWARE; ?></td>
        </tr>
    </table>
    
    <h2>Directory Permissions</h2>
    <table>
        <tr>
            <th>Directory</th>
            <th>Status</th>
            <th>Message</th>
        </tr>
        <?php foreach ($directory_results as $dir => $result): ?>
        <tr>
            <td><?php echo htmlspecialchars($dir); ?></td>
            <td class="<?php echo $result['status'] === 'success' ? 'success-text' : 'error-text'; ?>">
                <?php echo ucfirst($result['status']); ?>
            </td>
            <td><?php echo htmlspecialchars($result['message']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>Database Connection</h2>
    <table>
        <tr>
            <th>Item</th>
            <th>Status</th>
            <th>Message</th>
        </tr>
        <tr>
            <td>Database Connection</td>
            <td class="<?php echo $db_status['status'] === 'success' ? 'success-text' : 'error-text'; ?>">
                <?php echo ucfirst($db_status['status']); ?>
            </td>
            <td><?php echo htmlspecialchars($db_status['message']); ?></td>
        </tr>
        <tr>
            <td>Database Server</td>
            <td colspan="2"><?php echo DB_SERVER; ?></td>
        </tr>
        <tr>
            <td>Database Port</td>
            <td colspan="2"><?php echo DB_PORT; ?></td>
        </tr>
        <tr>
            <td>Database Name</td>
            <td colspan="2"><?php echo DB_NAME; ?></td>
        </tr>
    </table>
    
    <h2>PHP Extensions</h2>
    <table>
        <tr>
            <th>Extension</th>
            <th>Status</th>
            <th>Message</th>
        </tr>
        <?php foreach ($extension_results as $ext => $result): ?>
        <tr>
            <td><?php echo htmlspecialchars($ext); ?></td>
            <td class="<?php echo $result['status'] === 'success' ? 'success-text' : 'error-text'; ?>">
                <?php echo ucfirst($result['status']); ?>
            </td>
            <td><?php echo htmlspecialchars($result['message']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>PHP Configuration</h2>
    <table>
        <tr>
            <th>Setting</th>
            <th>Value</th>
        </tr>
        <?php foreach ($php_config as $setting => $value): ?>
        <tr>
            <td><?php echo htmlspecialchars($setting); ?></td>
            <td><?php echo htmlspecialchars($value); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <p><a href="../admin/index.php">Back to Dashboard</a></p>
</body>
</html>