<?php
// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php';

// Determine project folder from SITE_URL
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // Should be "srms-website"
}

// Get document root without trailing slash
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

// Media directories to check
$media_directories = [
    'branding' => '/assets/images/branding',
    'news' => '/assets/images/news',
    'events' => '/assets/images/events',
    'promotional' => '/assets/images/promotional',
    'facilities' => '/assets/images/facilities',
    'campus' => '/assets/images/campus'
];

// Results array
$results = [];

// Main document
echo "<!DOCTYPE html>
<html>
<head>
    <title>Directory Debugging Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        h1, h2 { color: #3C91E6; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        hr { margin: 30px 0; border: 0; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Directory Debugging Tool</h1>
    <p>This tool will help diagnose issues with directory scanning in the media library.</p>
    
    <h2>Configuration Information</h2>
    <table>
        <tr><th>Setting</th><th>Value</th></tr>
        <tr><td>Document Root</td><td>{$_SERVER['DOCUMENT_ROOT']}</td></tr>
        <tr><td>Project Folder</td><td>{$project_folder}</td></tr>
        <tr><td>SITE_URL</td><td>" . SITE_URL . "</td></tr>
        <tr><td>OS</td><td>" . PHP_OS . "</td></tr>
        <tr><td>Directory Separator</td><td>" . DIRECTORY_SEPARATOR . "</td></tr>
    </table>
    
    <h2>Directory Analysis</h2>
    <table>
        <tr>
            <th>Category</th>
            <th>Directory Path</th>
            <th>Directory Exists</th>
            <th>Files Count</th>
            <th>Files Found</th>
        </tr>";

foreach ($media_directories as $category => $dir) {
    // Build the full server path INCLUDING project folder
    $path = $doc_root;
    if (!empty($project_folder)) {
        $path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $path .= str_replace('/', DIRECTORY_SEPARATOR, $dir);
    
    $exists = is_dir($path);
    $count = 0;
    $files_list = '';
    
    if ($exists) {
        // Use a platform-neutral pattern for globbing
        $pattern = $path . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}";
        $files = glob($pattern, GLOB_BRACE);
        if ($files !== false) {
            $count = count($files);
            foreach ($files as $file) {
                $files_list .= basename($file) . "<br>";
            }
        }
    }
    
    echo "<tr>
        <td>{$category}</td>
        <td>{$path}</td>
        <td class='" . ($exists ? "pass" : "fail") . "'>" . ($exists ? "Yes" : "No") . "</td>
        <td>{$count}</td>
        <td>{$files_list}</td>
    </tr>";
    
    $results[$category] = [
        'path' => $path,
        'exists' => $exists,
        'count' => $count,
        'files' => $files_list
    ];
}

echo "</table>

    <h2>Recursive Directory Check</h2>
    <p>This checks all files recursively in each directory:</p>
    <pre>";

// Recursive directory check
foreach ($media_directories as $category => $dir) {
    $path = $doc_root;
    if (!empty($project_folder)) {
        $path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $path .= str_replace('/', DIRECTORY_SEPARATOR, $dir);
    
    echo "Category: {$category}\n";
    echo "Path: {$path}\n";
    
    if (is_dir($path)) {
        echo "Directory exists.\n";
        
        // Get all files recursively
        $all_files = [];
        $it = new RecursiveDirectoryIterator($path);
        $it = new RecursiveIteratorIterator($it);
        foreach ($it as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $all_files[] = $file->getPathname();
                }
            }
        }
        
        echo "Files found (recursive): " . count($all_files) . "\n";
        foreach ($all_files as $file) {
            echo "  " . basename($file) . "\n";
        }
    } else {
        echo "Directory does not exist.\n";
    }
    
    echo "\n------------------------------------------\n\n";
}

echo "</pre>

    <h2>Create Test Images</h2>
    <p>Attempt to create a test image in each directory:</p>
    <table>
        <tr>
            <th>Category</th>
            <th>Status</th>
        </tr>";

// Create test images in each directory
foreach ($media_directories as $category => $dir) {
    $path = $doc_root;
    if (!empty($project_folder)) {
        $path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $path .= str_replace('/', DIRECTORY_SEPARATOR, $dir);
    
    // Ensure directory exists
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    
    // Create a simple test image
    $img_path = $path . DIRECTORY_SEPARATOR . 'test_debug_' . time() . '.png';
    $web_path = $dir . '/test_debug_' . time() . '.png';
    $success = false;
    
    // Create a 100x100 red square image
    $im = @imagecreate(100, 100);
    if ($im) {
        $background_color = imagecolorallocate($im, 255, 0, 0);
        $text_color = imagecolorallocate($im, 255, 255, 255);
        imagestring($im, 5, 20, 40, 'Test Image', $text_color);
        $success = @imagepng($im, $img_path);
        imagedestroy($im);
    }
    
    echo "<tr>
        <td>{$category}</td>
        <td class='" . ($success ? "pass" : "fail") . "'>";
    
    if ($success) {
        echo "Created: <a href='" . $web_path . "' target='_blank'>" . basename($img_path) . "</a>";
    } else {
        echo "Failed to create image. Check permissions.";
    }
    
    echo "</td>
    </tr>";
}

echo "</table>
</body>
</html>";
?>