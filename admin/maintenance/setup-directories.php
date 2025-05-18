<?php
session_start();

// Include environment settings
require_once __DIR__ . '/../../environment.php';

// Security check
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Unauthorized access";
    exit;
}

// Debug information
$debug_info = [];
$debug_info[] = "Server OS: " . PHP_OS;
$debug_info[] = "Document Root: " . $_SERVER['DOCUMENT_ROOT'];
$debug_info[] = "Script Path: " . __DIR__;
$debug_info[] = "Server Software: " . $_SERVER['SERVER_SOFTWARE'];

// IMPORTANT: Extract project folder from SITE_URL
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // Should be "srms-website"
} else {
    $project_folder = 'srms-website'; // Fallback default
}

$debug_info[] = "Project Folder: " . $project_folder;

// Get the correct base path that includes the project folder
function get_base_path() {
    global $project_folder;
    
    // Get the document root
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    
    // Check if the document root already includes the project folder
    if (strpos($doc_root, $project_folder) !== false) {
        return $doc_root;
    }
    
    // If not, append the project folder to the document root
    if (IS_WINDOWS) {
        // Windows path
        return rtrim($doc_root, '/\\') . '\\' . $project_folder;
    } else {
        // Linux path
        return rtrim($doc_root, '/\\') . '/' . $project_folder;
    }
}

// Explicitly set the directory structure using constants for consistency
define('PROJECT_DIR', get_base_path());
define('ASSETS_DIR', PROJECT_DIR . (IS_WINDOWS ? '\\assets' : '/assets'));
define('IMAGES_DIR', ASSETS_DIR . (IS_WINDOWS ? '\\images' : '/images'));
define('UPLOADS_DIR', ASSETS_DIR . (IS_WINDOWS ? '\\uploads' : '/uploads'));

// Log the defined paths for debugging
$debug_info[] = "PROJECT_DIR: " . PROJECT_DIR;
$debug_info[] = "ASSETS_DIR: " . ASSETS_DIR;
$debug_info[] = "IMAGES_DIR: " . IMAGES_DIR;
$debug_info[] = "UPLOADS_DIR: " . UPLOADS_DIR;

// Resolve a relative path to the full path including project folder
function get_proper_path($relative_path) {
    global $project_folder;
    
    // Normalize path separators for the current OS
    if (IS_WINDOWS) {
        $relative_path = str_replace('/', '\\', $relative_path);
        return PROJECT_DIR . $relative_path;
    } else {
        $relative_path = str_replace('\\', '/', $relative_path);
        return PROJECT_DIR . $relative_path;
    }
}

// Required directories - using relative paths for cleaner code
$directories = [
    '/assets/images/news',
    '/assets/images/events',
    '/assets/images/promotional',
    '/assets/images/facilities',
    '/assets/images/campus',
    '/assets/images/people',
    '/assets/images/branding', // Added branding directory for consistency
    '/assets/uploads/temp'
];

// Add base path to debug info
$debug_info[] = "Base Project Path: " . get_base_path();

// Create directories
$success = true;
$results = [];

foreach ($directories as $dir) {
    // Get the full path using our improved path resolution
    $full_path = get_proper_path($dir);
    
    $results[] = [
        'path' => $dir,
        'full_path' => $full_path,
    ];
    
    if (!is_dir($full_path)) {
        // Use appropriate permissions based on OS
        $permissions = IS_WINDOWS ? 0777 : 0777;
        
        // Log the path we're trying to create
        error_log("Attempting to create directory: " . $full_path);
        
        // Create the directory with full permissions
        $created = @mkdir($full_path, $permissions, true);
        
        // For Linux, try system commands if PHP's mkdir fails
        if (!$created && !IS_WINDOWS) {
            $cmd = "mkdir -p " . escapeshellarg($full_path);
            $output = [];
            @exec($cmd . " 2>&1", $output, $return_var);
            
            if ($return_var === 0) {
                $created = true;
                // Set permissions after creation
                @exec("chmod -R 777 " . escapeshellarg($full_path));
            } else {
                $error_msg = implode("\n", $output);
            }
        }
        
        $status = $created ? 'Created' : 'Failed';
        $error = $created ? '' : (isset($error_msg) ? $error_msg : (error_get_last() ? error_get_last()['message'] : 'Unknown error'));
        
        $results[count($results) - 1]['status'] = $status;
        $results[count($results) - 1]['error'] = $error;
        
        // Set permissions on Linux
        if ($created && !IS_WINDOWS) {
            @chmod($full_path, 0777);
        }
        
        if (!$created) {
            $success = false;
        }
    } else {
        $results[count($results) - 1]['status'] = 'Already exists';
        $results[count($results) - 1]['error'] = '';
        
        // Update permissions on Linux
        if (!IS_WINDOWS) {
            @chmod($full_path, 0777);
        }
    }
}

// Set up placeholder images
$placeholder_images = [
    '/assets/images/news/news-placeholder.jpg' => 'https://via.placeholder.com/800x600/3C91E6/FFFFFF?text=News+Placeholder',
    '/assets/images/events/events-placeholder.jpg' => 'https://via.placeholder.com/800x600/28A745/FFFFFF?text=Event+Placeholder',
    '/assets/images/facilities/facility-placeholder.jpg' => 'https://via.placeholder.com/800x600/FD7E14/FFFFFF?text=Facility+Placeholder',
    '/assets/images/campus/campus-placeholder.jpg' => 'https://via.placeholder.com/800x600/6F42C1/FFFFFF?text=Campus+Placeholder',
    '/assets/images/placeholder.jpg' => 'https://via.placeholder.com/800x600/343A40/FFFFFF?text=Image+Placeholder'
];

foreach ($placeholder_images as $path => $url) {
    $full_path = get_proper_path($path);
    
    if (!file_exists($full_path)) {
        // Make sure parent directory exists
        $parent_dir = dirname($full_path);
        if (!is_dir($parent_dir)) {
            @mkdir($parent_dir, 0777, true);
            if (!IS_WINDOWS) {
                @chmod($parent_dir, 0777);
            }
        }
        
        $image_data = @file_get_contents($url);
        
        if ($image_data) {
            $saved = @file_put_contents($full_path, $image_data);
            
            // Set appropriate file permissions on Linux
            if ($saved && !IS_WINDOWS) {
                @chmod($full_path, 0666);
            }
            
            $results[] = [
                'path' => $path,
                'full_path' => $full_path,
                'status' => $saved ? 'Created' : 'Failed',
                'error' => $saved ? '' : (error_get_last() ? error_get_last()['message'] : 'Unknown error')
            ];
            
            if (!$saved) {
                $success = false;
            }
        } else {
            $results[] = [
                'path' => $path,
                'full_path' => $full_path,
                'status' => 'Failed to download',
                'error' => error_get_last() ? error_get_last()['message'] : 'Unknown error'
            ];
            $success = false;
        }
    } else {
        $results[] = [
            'path' => $path,
            'full_path' => $full_path,
            'status' => 'Already exists',
            'error' => ''
        ];
    }
}

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directory Setup | SRMS Admin</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1, h2 {
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
        .created {
            color: #28a745;
        }
        .failed {
            color: #dc3545;
        }
        .exists {
            color: #6c757d;
        }
        .debug-info {
            background-color: #e2e3e5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        .code-block {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 4px solid #0a3060;
        }
    </style>
</head>
<body>
    <h1>Directory Setup</h1>
    
    <div class="debug-info">
        <h2>System Information</h2>
        <ul>
            <?php foreach ($debug_info as $info): ?>
            <li><?php echo htmlspecialchars($info); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="status <?php echo $success ? 'success' : 'error'; ?>">
        <?php echo $success ? 'All directories and placeholders were set up successfully!' : 'There were issues setting up some directories or placeholders.'; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Path</th>
                <th>Full Path</th>
                <th>Status</th>
                <th>Error</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result): ?>
            <tr>
                <td><?php echo htmlspecialchars($result['path']); ?></td>
                <td><?php echo htmlspecialchars($result['full_path'] ?? 'N/A'); ?></td>
                <td class="<?php echo isset($result['status']) ? (strtolower($result['status']) === 'created' ? 'created' : (strtolower($result['status']) === 'failed' ? 'failed' : 'exists')) : ''; ?>">
                    <?php echo htmlspecialchars($result['status'] ?? 'Unknown'); ?>
                </td>
                <td><?php echo htmlspecialchars($result['error'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2>Manual Fix Instructions</h2>
    <div class="code-block">
        <h3>Windows (XAMPP) Commands:</h3>
        <pre>
mkdir "C:\xampp\htdocs\<?php echo htmlspecialchars($project_folder); ?>\assets\images\news"
mkdir "C:\xampp\htdocs\<?php echo htmlspecialchars($project_folder); ?>\assets\images\events"
mkdir "C:\xampp\htdocs\<?php echo htmlspecialchars($project_folder); ?>\assets\images\promotional"
mkdir "C:\xampp\htdocs\<?php echo htmlspecialchars($project_folder); ?>\assets\images\facilities"
mkdir "C:\xampp\htdocs\<?php echo htmlspecialchars($project_folder); ?>\assets\images\campus"
mkdir "C:\xampp\htdocs\<?php echo htmlspecialchars($project_folder); ?>\assets\images\people"
mkdir "C:\xampp\htdocs\<?php echo htmlspecialchars($project_folder); ?>\assets\images\branding"
mkdir "C:\xampp\htdocs\<?php echo htmlspecialchars($project_folder); ?>\assets\uploads\temp"
</pre>
    </div>

    <div class="code-block">
        <h3>Linux (XAMPP) Commands:</h3>
        <pre>
sudo mkdir -p /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets/images/news
sudo mkdir -p /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets/images/events
sudo mkdir -p /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets/images/promotional
sudo mkdir -p /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets/images/facilities
sudo mkdir -p /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets/images/campus
sudo mkdir -p /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets/images/people
sudo mkdir -p /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets/images/branding
sudo mkdir -p /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets/uploads/temp
sudo chmod -R 777 /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets
sudo chown -R daemon:daemon /opt/lampp/htdocs/<?php echo htmlspecialchars($project_folder); ?>/assets
</pre>
    </div>
    
    <p><a href="../index.php">Back to Dashboard</a></p>
</body>
</html>