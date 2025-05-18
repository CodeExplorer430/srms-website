<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include_once '../includes/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Path Debugging Tool</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1, h2 { color: #3C91E6; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; font-weight: bold; }
        .error { color: red; }
        .code { font-family: monospace; background: #f5f5f5; padding: 10px; }
        .img-test { width: 100px; height: 100px; object-fit: cover; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Path Debugging Tool</h1>
    
    <h2>Environment Information</h2>
    <table>
        <tr><th>Variable</th><th>Value</th></tr>
        <tr><td>Document Root</td><td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td></tr>
        <tr><td>SITE_URL</td><td><?php echo SITE_URL; ?></td></tr>
        <tr><td>PHP_OS</td><td><?php echo PHP_OS; ?></td></tr>
        <tr><td>DIRECTORY_SEPARATOR</td><td><?php echo htmlspecialchars(DIRECTORY_SEPARATOR); ?></td></tr>
        <tr><td>Current Script Path</td><td><?php echo $_SERVER['SCRIPT_FILENAME']; ?></td></tr>
        <tr><td>Server Software</td><td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td></tr>
    </table>
    
    <h2>Project Path Detection</h2>
    <?php
    $project_folder = '';
    if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $project_folder = $matches[1];
    }
    ?>
    <table>
        <tr><th>Variable</th><th>Value</th></tr>
        <tr><td>Detected Project Folder</td><td><?php echo $project_folder; ?></td></tr>
        <tr><td>Calculated Project Path</td><td><?php echo $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $project_folder; ?></td></tr>
    </table>
    
    <h2>Image Directory Tests</h2>
    <?php
    $categories = ['branding', 'news', 'events', 'promotional', 'facilities', 'campus'];
    $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    
    // Create a test image
    $im = imagecreate(100, 100);
    $bg = imagecolorallocate($im, 255, 0, 0);
    $text_color = imagecolorallocate($im, 255, 255, 255);
    imagestring($im, 5, 20, 40, 'Test', $text_color);
    
    echo '<table>';
    echo '<tr><th>Category</th><th>Directory</th><th>Exists</th><th>Files Count</th><th>Test File</th><th>Test Image Display</th></tr>';
    
    foreach ($categories as $category) {
        $dir_path = $doc_root;
        if (!empty($project_folder)) {
            $dir_path .= DIRECTORY_SEPARATOR . $project_folder;
        }
        $dir_path .= DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $category;
        
        $dir_exists = is_dir($dir_path);
        
        // Count files
        $file_count = 0;
        if ($dir_exists) {
            $files = glob($dir_path . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            $file_count = count($files);
        }
        
        // Create test image for this category
        $test_file = '';
        $web_path = '';
        if ($dir_exists) {
            // Ensure directory exists
            if (!is_dir($dir_path)) {
                mkdir($dir_path, 0755, true);
            }
            
            // Create the test image
            $test_file = $dir_path . DIRECTORY_SEPARATOR . 'debug_test_' . time() . '.png';
            imagepng($im, $test_file);
            
            // Calculate the web path versions
            $relative_path = 'assets/images/' . $category . '/debug_test_' . time() . '.png';
            
            // Different path variations to test
            $web_paths = [
                '/' . $relative_path,
                $relative_path,
                '/' . $project_folder . '/' . $relative_path
            ];
            
            // Use the first path for display
            $web_path = $web_paths[0];
        }
        
        echo '<tr>';
        echo '<td>' . $category . '</td>';
        echo '<td>' . $dir_path . '</td>';
        echo '<td>' . ($dir_exists ? '<span class="success">Yes</span>' : '<span class="error">No</span>') . '</td>';
        echo '<td>' . $file_count . '</td>';
        echo '<td>' . ($test_file ? basename($test_file) : 'N/A') . '</td>';
        echo '<td>';
        
        if ($web_path) {
            echo '<div style="font-size:12px; margin-bottom:5px;">Testing path: ' . $web_path . '</div>';
            echo '<img src="' . $web_path . '" class="img-test" alt="Test">';
            
            // Also test alternative paths
            if (!empty($web_paths[1])) {
                echo '<div style="font-size:12px; margin-top:10px; margin-bottom:5px;">Testing alternate path 1: ' . $web_paths[1] . '</div>';
                echo '<img src="' . $web_paths[1] . '" class="img-test" alt="Test">';
            }
            
            if (!empty($web_paths[2])) {
                echo '<div style="font-size:12px; margin-top:10px; margin-bottom:5px;">Testing alternate path 2: ' . $web_paths[2] . '</div>';
                echo '<img src="' . $web_paths[2] . '" class="img-test" alt="Test">';
            }
        } else {
            echo 'N/A';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // Clean up
    imagedestroy($im);
    ?>
    
    <h2>Recommendations</h2>
    <ol>
        <li>Make sure all image paths use forward slashes (/) for web URLs, regardless of OS</li>
        <li>Check if images need the project folder in the URL path</li>
        <li>Update both PHP and JavaScript code to use consistent path formatting</li>
        <li>Test image visibility with the paths generated by this tool</li>
        <li>Use the correct img src format based on what works from the tests above</li>
    </ol>
</body>
</html>