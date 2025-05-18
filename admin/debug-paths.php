<?php
// Save as debug-paths.php in the admin folder

session_start();
// Security check
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include configuration and functions
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set up test paths
$test_paths = [
    '/assets/images/news/example.jpg',
    'assets/images/events/example.jpg',
    '\\assets\\images\\campus\\example.jpg',
    'C:/xampp/htdocs/srms-website/assets/images/news/example.jpg',
    $_SERVER['DOCUMENT_ROOT'] . '/assets/images/facilities/example.jpg',
];

// Get site URL info
$site_url = SITE_URL;
$site_url_parsed = parse_url($site_url);
$project_folder = '';
if (preg_match('#/([^/]+)$#', $site_url_parsed['path'] ?? '', $matches)) {
    $project_folder = $matches[1];
}

// Environment info
$env_info = [
    'OS' => PHP_OS,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'],
    'SITE_URL' => SITE_URL,
    'Project Folder' => $project_folder
];

// Directory existence
$dirs = [
    'Root' => $_SERVER['DOCUMENT_ROOT'],
    'Project' => $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $project_folder,
    'Images' => $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $project_folder . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images',
    'News' => $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $project_folder . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'news',
    'Events' => $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $project_folder . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'events',
    'Branding' => $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $project_folder . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'branding'
];

// Start output
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Path Debugging Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #3a5998; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .true { color: green; font-weight: bold; }
        .false { color: red; }
        .section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Path Debugging Tool</h1>
    
    <div class="section">
        <h2>Environment Information</h2>
        <table>
            <tr><th>Setting</th><th>Value</th></tr>
            <?php foreach($env_info as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Directory Existence</h2>
        <table>
            <tr><th>Directory</th><th>Path</th><th>Exists</th></tr>
            <?php foreach($dirs as $name => $path): ?>
                <tr>
                    <td><?php echo htmlspecialchars($name); ?></td>
                    <td><?php echo htmlspecialchars($path); ?></td>
                    <td class="<?php echo is_dir($path) ? 'true' : 'false'; ?>">
                        <?php echo is_dir($path) ? 'Yes' : 'No'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Path Normalization Tests</h2>
        <table>
            <tr>
                <th>Original Path</th>
                <th>Normalized Path</th>
                <th>Server Path</th>
                <th>File Exists</th>
            </tr>
            <?php foreach($test_paths as $path): ?>
                <?php 
                $normalized = normalize_image_path($path);
                $server_path = $_SERVER['DOCUMENT_ROOT'];
                if (!empty($project_folder)) {
                    $server_path .= DIRECTORY_SEPARATOR . $project_folder;
                }
                $server_path .= str_replace('/', DIRECTORY_SEPARATOR, $normalized);
                $exists = file_exists($server_path);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($path); ?></td>
                    <td><?php echo htmlspecialchars($normalized); ?></td>
                    <td><?php echo htmlspecialchars($server_path); ?></td>
                    <td class="<?php echo $exists ? 'true' : 'false'; ?>">
                        <?php echo $exists ? 'Yes' : 'No'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Create Test Image</h2>
        <?php
        // Create test image in each directory
        $img_dirs = ['news', 'events', 'promotional', 'facilities', 'campus', 'branding'];
        $results = [];
        
        foreach($img_dirs as $dir) {
            $dir_path = $_SERVER['DOCUMENT_ROOT'];
            if (!empty($project_folder)) {
                $dir_path .= DIRECTORY_SEPARATOR . $project_folder;
            }
            $dir_path .= DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $dir;
            
            // Ensure directory exists
            if (!is_dir($dir_path)) {
                @mkdir($dir_path, 0755, true);
            }
            
            // Create a simple test image
            $img_path = $dir_path . DIRECTORY_SEPARATOR . 'test_' . time() . '.png';
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
            
            $results[$dir] = [
                'directory' => $dir_path,
                'file' => $img_path,
                'success' => $success,
                'dir_exists' => is_dir($dir_path),
                'web_path' => '/assets/images/' . $dir . '/test_' . time() . '.png'
            ];
        }
        ?>
        
        <table>
            <tr>
                <th>Directory</th>
                <th>Directory Exists</th>
                <th>Image Created</th>
                <th>Web Path</th>
            </tr>
            <?php foreach($results as $dir => $result): ?>
                <tr>
                    <td><?php echo htmlspecialchars($dir); ?></td>
                    <td class="<?php echo $result['dir_exists'] ? 'true' : 'false'; ?>">
                        <?php echo $result['dir_exists'] ? 'Yes' : 'No'; ?>
                    </td>
                    <td class="<?php echo $result['success'] ? 'true' : 'false'; ?>">
                        <?php echo $result['success'] ? 'Yes' : 'No'; ?>
                    </td>
                    <td>
                        <?php if($result['success']): ?>
                            <a href="<?php echo htmlspecialchars(SITE_URL . $result['web_path']); ?>" target="_blank">
                                <?php echo htmlspecialchars($result['web_path']); ?>
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>