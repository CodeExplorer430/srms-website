<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Security - optional password protection
$allowed = true;
if (isset($_GET['key']) && $_GET['key'] === 'check123') {
    $allowed = true;
}

if (!$allowed) {
    die('Access denied. Add ?key=check123 to the URL.');
}

// Get data from database
$db = db_connect();
$facilities = $db->fetch_all("SELECT * FROM facilities LIMIT 5");
$slideshow = $db->fetch_all("SELECT * FROM slideshow LIMIT 5");

// Extract site folder name
$site_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $site_folder = $matches[1];
}

// Function to check all path variations for a given image
function check_all_paths($image_path) {
    global $site_folder;
    
    // Normalize path
    $norm_path = normalize_image_path($image_path);
    
    // Calculate path variations
    $document_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    $site_path = $document_root . '/' . $site_folder . $norm_path;
    $root_path = $document_root . $norm_path;
    
    // Alternative formats
    $site_path_alt = str_replace('/', DIRECTORY_SEPARATOR, $site_path);
    $root_path_alt = str_replace('/', DIRECTORY_SEPARATOR, $root_path);
    
    // Check all variations
    $results = [
        'original' => $image_path,
        'normalized' => $norm_path,
        'paths' => [
            'site_path' => [
                'path' => $site_path,
                'exists' => file_exists($site_path)
            ],
            'site_path_alt' => [
                'path' => $site_path_alt,
                'exists' => file_exists($site_path_alt)
            ],
            'root_path' => [
                'path' => $root_path,
                'exists' => file_exists($root_path)
            ],
            'root_path_alt' => [
                'path' => $root_path_alt,
                'exists' => file_exists($root_path_alt)
            ]
        ],
        'correct_url' => '',
        'verify_image_exists' => verify_image_exists($norm_path)
    ];
    
    // Determine correct URL
    if ($results['paths']['site_path']['exists'] || $results['paths']['site_path_alt']['exists']) {
        $results['correct_url'] = SITE_URL . $norm_path;
    } else if ($results['paths']['root_path']['exists'] || $results['paths']['root_path_alt']['exists']) {
        $results['correct_url'] = str_replace('/' . $site_folder, '', SITE_URL) . $norm_path;
    }
    
    return $results;
}

// Test if a file exists and can be created in the site folder
function test_file_creation() {
    global $site_folder;
    
    $document_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    $test_dir = $document_root . '/' . $site_folder . '/assets/images/test';
    $test_file = $test_dir . '/test_' . time() . '.txt';
    
    // Create test directory if it doesn't exist
    if (!is_dir($test_dir)) {
        $dir_result = @mkdir($test_dir, 0755, true);
    } else {
        $dir_result = true;
    }
    
    // Try to create a test file
    $content = 'Test file created at ' . date('Y-m-d H:i:s');
    $file_result = @file_put_contents($test_file, $content);
    
    return [
        'test_dir' => $test_dir,
        'dir_exists' => is_dir($test_dir),
        'dir_created' => $dir_result,
        'test_file' => $test_file,
        'file_created' => ($file_result !== false),
        'file_exists' => file_exists($test_file)
    ];
}

// Run tests
$system_info = [
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
    'SITE_URL' => SITE_URL,
    'site_folder' => $site_folder,
    'OS' => (IS_WINDOWS ? 'Windows' : 'Linux'),
    'SERVER_TYPE' => SERVER_TYPE,
    'PHP_VERSION' => PHP_VERSION,
    'file_creation_test' => test_file_creation()
];

// Check facilities images
$facility_results = [];
foreach ($facilities as $facility) {
    $facility_results[] = check_all_paths($facility['image']);
}

// Check slideshow images
$slideshow_results = [];
foreach ($slideshow as $slide) {
    $slideshow_results[] = check_all_paths($slide['image']);
}

// Output as HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Path Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 20px; border-radius: 4px; }
        .exists { color: green; font-weight: bold; }
        .not-exists { color: red; }
        table { border-collapse: collapse; width: 100%; }
        th, td { text-align: left; padding: 8px; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Image Path Diagnostics</h1>
    
    <div class="section">
        <h2>System Information</h2>
        <pre><?php echo json_encode($system_info, JSON_PRETTY_PRINT); ?></pre>
    </div>
    
    <div class="section">
        <h2>Facility Images</h2>
        <table>
            <tr>
                <th>Original Path</th>
                <th>Site Folder Path</th>
                <th>Root Path</th>
                <th>verify_image_exists</th>
                <th>Recommended URL</th>
            </tr>
            <?php foreach ($facility_results as $result): ?>
            <tr>
                <td><?php echo htmlspecialchars($result['original']); ?></td>
                <td class="<?php echo $result['paths']['site_path']['exists'] ? 'exists' : 'not-exists'; ?>">
                    <?php echo htmlspecialchars($result['paths']['site_path']['path']); ?>
                    (<?php echo $result['paths']['site_path']['exists'] ? 'EXISTS' : 'NOT FOUND'; ?>)
                </td>
                <td class="<?php echo $result['paths']['root_path']['exists'] ? 'exists' : 'not-exists'; ?>">
                    <?php echo htmlspecialchars($result['paths']['root_path']['path']); ?>
                    (<?php echo $result['paths']['root_path']['exists'] ? 'EXISTS' : 'NOT FOUND'; ?>)
                </td>
                <td class="<?php echo $result['verify_image_exists'] ? 'exists' : 'not-exists'; ?>">
                    <?php echo $result['verify_image_exists'] ? 'YES' : 'NO'; ?>
                </td>
                <td><?php echo htmlspecialchars($result['correct_url']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Slideshow Images</h2>
        <table>
            <tr>
                <th>Original Path</th>
                <th>Site Folder Path</th>
                <th>Root Path</th>
                <th>verify_image_exists</th>
                <th>Recommended URL</th>
            </tr>
            <?php foreach ($slideshow_results as $result): ?>
            <tr>
                <td><?php echo htmlspecialchars($result['original']); ?></td>
                <td class="<?php echo $result['paths']['site_path']['exists'] ? 'exists' : 'not-exists'; ?>">
                    <?php echo htmlspecialchars($result['paths']['site_path']['path']); ?>
                    (<?php echo $result['paths']['site_path']['exists'] ? 'EXISTS' : 'NOT FOUND'; ?>)
                </td>
                <td class="<?php echo $result['paths']['root_path']['exists'] ? 'exists' : 'not-exists'; ?>">
                    <?php echo htmlspecialchars($result['paths']['root_path']['path']); ?>
                    (<?php echo $result['paths']['root_path']['exists'] ? 'EXISTS' : 'NOT FOUND'; ?>)
                </td>
                <td class="<?php echo $result['verify_image_exists'] ? 'exists' : 'not-exists'; ?>">
                    <?php echo $result['verify_image_exists'] ? 'YES' : 'NO'; ?>
                </td>
                <td><?php echo htmlspecialchars($result['correct_url']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Fix Recommendations</h2>
        <p>Based on the diagnostics above, here are possible solutions:</p>
        <ol>
            <li>
                <strong>If files exist only in the root directory:</strong>
                <ul>
                    <li>Move files into the site folder: <code><?php echo rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . $site_folder . '/assets/images/'; ?></code></li>
                    <li>OR update your URLs to point to the root: <code><?php echo str_replace('/' . $site_folder, '', SITE_URL); ?>/assets/images/...</code></li>
                </ul>
            </li>
            <li>
                <strong>If files don't exist in either location:</strong>
                <ul>
                    <li>Upload all media to the correct site folder path</li>
                    <li>Check file permissions</li>
                </ul>
            </li>
            <li>
                <strong>If files exist in site folder but aren't being found:</strong>
                <ul>
                    <li>Check file permissions</li>
                    <li>Verify that PHP can read the directories</li>
                </ul>
            </li>
        </ol>
    </div>
</body>
</html>