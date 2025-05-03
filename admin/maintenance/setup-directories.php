<?php
session_start();

// Include environment settings
require_once __DIR__ . '/../../environment.php';

// Security check
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Unauthorized access";
    exit;
}

// Required directories
$directories = [
    '/assets/images/news',
    '/assets/images/events',
    '/assets/images/promotional',
    '/assets/images/facilities',
    '/assets/images/campus',
    '/assets/images/people',
    '/assets/uploads/temp'
];

// Create directories
$success = true;
$results = [];

foreach ($directories as $dir) {
    // Normalize directory path for cross-platform compatibility
    $dir = str_replace(['\\', '/'], DS, $dir);
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $dir;
    
    if (!is_dir($full_path)) {
        // Use different permissions for Windows vs Linux
        $permissions = IS_WINDOWS ? 0777 : 0755;
        
        $created = mkdir($full_path, $permissions, true);
        $results[] = [
            'path' => $dir,
            'status' => $created ? 'Created' : 'Failed',
            'error' => $created ? '' : error_get_last()['message']
        ];
        
        // Set additional permissions on Linux if needed
        if ($created && !IS_WINDOWS) {
            // Try to make the directory group-writable
            @chmod($full_path, 0775);
            
            // Try to set the group to web server group if possible
            if (function_exists('posix_getgrgid') && function_exists('posix_getgid')) {
                $group = posix_getgrgid(posix_getgid())['name'] ?? 'www-data';
                @chgrp($full_path, $group);
            }
        }
        
        if (!$created) {
            $success = false;
        }
    } else {
        $results[] = [
            'path' => $dir,
            'status' => 'Already exists',
            'error' => ''
        ];
        
        // On Linux, ensure existing directories have correct permissions
        if (!IS_WINDOWS) {
            @chmod($full_path, 0775);
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
    // Normalize path for cross-platform compatibility
    $path = str_replace(['\\', '/'], DS, $path);
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $path;
    
    if (!file_exists($full_path)) {
        $image_data = @file_get_contents($url);
        
        if ($image_data) {
            $saved = file_put_contents($full_path, $image_data);
            
            // Set appropriate file permissions on Linux
            if ($saved && !IS_WINDOWS) {
                @chmod($full_path, 0664); // rw-rw-r--
            }
            
            $results[] = [
                'path' => $path,
                'status' => $saved ? 'Created' : 'Failed',
                'error' => $saved ? '' : error_get_last()['message']
            ];
            
            if (!$saved) {
                $success = false;
            }
        } else {
            $results[] = [
                'path' => $path,
                'status' => 'Failed to download',
                'error' => error_get_last()['message']
            ];
            $success = false;
        }
    } else {
        $results[] = [
            'path' => $path,
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
        .created {
            color: #28a745;
        }
        .failed {
            color: #dc3545;
        }
        .exists {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <h1>Directory Setup</h1>
    
    <div class="status <?php echo $success ? 'success' : 'error'; ?>">
        <?php echo $success ? 'All directories and placeholders were set up successfully!' : 'There were issues setting up some directories or placeholders.'; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Path</th>
                <th>Status</th>
                <th>Error</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result): ?>
            <tr>
                <td><?php echo htmlspecialchars($result['path']); ?></td>
                <td class="<?php echo strtolower($result['status']) === 'created' ? 'created' : (strtolower($result['status']) === 'failed' ? 'failed' : 'exists'); ?>">
                    <?php echo htmlspecialchars($result['status']); ?>
                </td>
                <td><?php echo htmlspecialchars($result['error']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p><a href="../index.php">Back to Dashboard</a></p>
</body>
</html>