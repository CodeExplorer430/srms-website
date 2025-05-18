<?php
// Emergency path fix tool
// Run this directly to diagnose and fix path issues

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Authentication required");
}

// Include config
include_once '../includes/config.php';

// Get the project folder
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // "srms-website"
}

// Document root
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

// Media directories
$media_directories = [
    'branding' => '/assets/images/branding',
    'news' => '/assets/images/news',
    'events' => '/assets/images/events',
    'promotional' => '/assets/images/promotional',
    'facilities' => '/assets/images/facilities',
    'campus' => '/assets/images/campus'
];

// Diagnostic information
echo "<h1>Path Fix Tool</h1>";
echo "<p>Document Root: " . htmlspecialchars($doc_root) . "</p>";
echo "<p>Project Folder: " . htmlspecialchars($project_folder) . "</p>";
echo "<p>SITE_URL: " . htmlspecialchars(SITE_URL) . "</p>";

// Test scanning each directory with different path combinations
echo "<h2>Directory Tests</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Category</th><th>Path Type</th><th>Full Path</th><th>Files Found</th></tr>";

foreach ($media_directories as $category => $dir) {
    // Test 1: Direct document root
    $path1 = $doc_root . str_replace('/', DIRECTORY_SEPARATOR, $dir);
    $files1 = glob($path1 . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}", GLOB_BRACE) ?: [];
    
    // Test 2: With project folder
    $path2 = $doc_root . DIRECTORY_SEPARATOR . $project_folder . str_replace('/', DIRECTORY_SEPARATOR, $dir);
    $files2 = glob($path2 . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}", GLOB_BRACE) ?: [];
    
    echo "<tr><td rowspan='2'>{$category}</td><td>Without Project Folder</td><td>" . htmlspecialchars($path1) . "</td><td>" . count($files1) . "</td></tr>";
    echo "<tr><td>With Project Folder</td><td>" . htmlspecialchars($path2) . "</td><td>" . count($files2) . "</td></tr>";
}

echo "</table>";

// Create the fixed code for index.php
echo "<h2>Fixed Code for index.php</h2>";
echo "<pre>";
echo htmlspecialchars('
// Get media statistics
$media_counts = [
    \'total\' => 0,
    \'branding\' => 0,
    \'news\' => 0,
    \'events\' => 0,
    \'promotional\' => 0,
    \'facilities\' => 0,
    \'campus\' => 0
];

// Determine project folder from SITE_URL
$project_folder = \'\';
if (preg_match(\'#/([^/]+)$#\', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // Should be "srms-website"
}

// Media directories
$media_directories = [
    \'branding\' => \'/assets/images/branding\',
    \'news\' => \'/assets/images/news\',
    \'events\' => \'/assets/images/events\',
    \'promotional\' => \'/assets/images/promotional\',
    \'facilities\' => \'/assets/images/facilities\',
    \'campus\' => \'/assets/images/campus\'
];

// Get document root without trailing slash
$doc_root = rtrim($_SERVER[\'DOCUMENT_ROOT\'], \'/\\\\\');

// Count media files
foreach ($media_directories as $key => $dir) {
    // Build the correct server path
    $path = $doc_root;
    if (!empty($project_folder)) {
        $path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $path .= str_replace(\'/\', DIRECTORY_SEPARATOR, $dir);
    
    // Echo for debugging
    // echo "Checking path: {$path}<br>";
    
    if (is_dir($path)) {
        $pattern = $path . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}";
        $files = glob($pattern, GLOB_BRACE) ?: [];
        $count = count($files);
        $media_counts[$key] = $count;
        $media_counts[\'total\'] += $count;
    }
}

// Get recent media uploads
$recent_media = [];
foreach ($media_directories as $key => $dir) {
    // Build the correct server path
    $path = $doc_root;
    if (!empty($project_folder)) {
        $path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $path .= str_replace(\'/\', DIRECTORY_SEPARATOR, $dir);
    
    if (is_dir($path)) {
        $pattern = $path . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}";
        $files = glob($pattern, GLOB_BRACE) ?: [];
        
        foreach ($files as $file) {
            if (is_file($file)) {
                // Convert server path to web path
                $file_web_path = str_replace(
                    [$doc_root . DIRECTORY_SEPARATOR . $project_folder, DIRECTORY_SEPARATOR],
                    [\'\', \'/\'],
                    $file
                );
                
                // Ensure path starts with slash
                if (substr($file_web_path, 0, 1) !== \'/\') {
                    $file_web_path = \'/\' . $file_web_path;
                }
                
                // Add project folder to path if needed
                if (!empty($project_folder) && strpos($file_web_path, \'/\' . $project_folder) !== 0) {
                    $file_web_path = \'/\' . $project_folder . $file_web_path;
                }
                
                $recent_media[] = [
                    \'path\' => $file_web_path,
                    \'name\' => basename($file),
                    \'type\' => $key,
                    \'modified\' => filemtime($file)
                ];
            }
        }
    }
}

// Sort by modified time (newest first) and limit to 6
usort($recent_media, function($a, $b) {
    return $b[\'modified\'] - $a[\'modified\'];
});
$recent_media = array_slice($recent_media, 0, 6);
');
echo "</pre>";

// Image display code
echo "<h2>How to display images in HTML</h2>";
echo "<pre>";
echo htmlspecialchars('
<div class="media-grid">
    <?php if (empty($recent_media)): ?>
        <div class="empty-state">
            <i class=\'bx bx-image\'></i>
            <p>No media files have been uploaded yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($recent_media as $media): ?>
            <div class="media-item">
                <div class="media-thumbnail">
                    <img src="<?php echo $media[\'path\']; ?>" alt="<?php echo htmlspecialchars($media[\'name\']); ?>">
                    <span class="media-badge badge-<?php echo $media[\'type\']; ?>"><?php echo ucfirst($media[\'type\']); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
');
echo "</pre>";

// Create a test image
echo "<h2>Testing image display:</h2>";

// Create sample image for testing
echo "<p>Creating a test image in each directory...</p>";

// Create a test image
$testImage = imagecreate(100, 100);
$bg = imagecolorallocate($testImage, 255, 0, 0);
$textColor = imagecolorallocate($testImage, 255, 255, 255);
imagestring($testImage, 5, 20, 40, 'TEST', $textColor);

$testImages = [];

foreach ($media_directories as $category => $dir) {
    // Create directory if it doesn't exist
    $path = $doc_root;
    if (!empty($project_folder)) {
        $path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $path .= str_replace('/', DIRECTORY_SEPARATOR, $dir);
    
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            echo "<p>Created directory: {$path}</p>";
        } else {
            echo "<p>Failed to create directory: {$path}</p>";
            continue;
        }
    }
    
    $testFile = $path . DIRECTORY_SEPARATOR . "test_fix_" . time() . ".png";
    if (imagepng($testImage, $testFile)) {
        // Convert to web path
        $web_path = str_replace(
            [$doc_root . DIRECTORY_SEPARATOR . $project_folder, DIRECTORY_SEPARATOR],
            ['', '/'],
            $testFile
        );
        
        // Ensure path starts with slash
        if (substr($web_path, 0, 1) !== '/') {
            $web_path = '/' . $web_path;
        }
        
        // Add project folder to path if needed
        if (!empty($project_folder) && strpos($web_path, '/' . $project_folder) !== 0) {
            $web_path = '/' . $project_folder . $web_path;
        }
        
        $testImages[] = [
            'category' => $category,
            'file' => $testFile,
            'web_path' => $web_path
        ];
        
        echo "<p>Created test image in {$category}: {$web_path}</p>";
    } else {
        echo "<p>Failed to create test image in {$path}</p>";
    }
}

// Display test images
if (!empty($testImages)) {
    echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
    foreach ($testImages as $img) {
        echo "<div style='border: 1px solid #ccc; padding: 10px;'>";
        echo "<p>{$img['category']}</p>";
        echo "<img src='{$img['web_path']}' style='max-width: 100px;'>";
        echo "<p>{$img['web_path']}</p>";
        echo "</div>";
    }
    echo "</div>";
}

// Clean up
imagedestroy($testImage);
?>