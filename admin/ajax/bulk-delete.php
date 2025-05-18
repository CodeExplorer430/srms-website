<?php
// Start with a clean output buffer to capture any unexpected output
ob_start();

// Set up enhanced logging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../../logs/delete-debug.log');
error_log("========= BULK DELETE REQUEST: " . date('Y-m-d H:i:s') . " =========");

// Suppress all errors from being output (but still log them)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Enhanced logging for debugging
error_log('Bulk delete request received: ' . date('Y-m-d H:i:s'));
error_log('Raw input: ' . file_get_contents('php://input'));

session_start();

// Include environment settings
require_once '../../environment.php';

// Security check
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_end_clean(); // Clear any output
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Get JSON input
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

// Check if files to delete are provided
if(!isset($data['files']) || !is_array($data['files']) || empty($data['files'])) {
    ob_end_clean(); // Clear any output
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No files selected for deletion']);
    exit;
}

// Get the list of files to delete
$files_to_delete = $data['files'];

// Get document root
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
error_log("Document root: {$doc_root}");

// Determine project folder from SITE_URL
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // Should be "srms-website"
}
error_log("Project folder: {$project_folder}");
error_log("SITE_URL: " . SITE_URL);

// Validate media directories (allowed paths for deletion)
$media_directories = [
    '/assets/images/news',
    '/assets/images/events',
    '/assets/images/promotional',
    '/assets/images/facilities',
    '/assets/images/campus',
    '/assets/images/branding'
];

try {
    $results = [];
    $success_count = 0;
    $error_count = 0;
    
    // Create logs directory if it doesn't exist
    $logs_dir = dirname(__FILE__) . '/../../logs';
    if (!is_dir($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }
    
    foreach ($files_to_delete as $file_path) {
        // Original debug
        error_log("Original file path for deletion: {$file_path}");
        
        // Try multiple path extraction approaches
        $original_path = $file_path;
        
        // Create an array of possible alternate paths to check
        $possible_paths = array();
        
        // 1. Original path
        $possible_paths[] = $original_path;
        
        // 2. Extract path relative to project folder
        if (!empty($project_folder) && strpos($file_path, '/' . $project_folder . '/') === 0) {
            $stripped_path = substr($file_path, strlen('/' . $project_folder));
            $possible_paths[] = $stripped_path;
            error_log("Stripped project folder path: {$stripped_path}");
        }
        
        // 3. Path with normalized format
        $normalized_path = normalize_image_path($file_path);
        $possible_paths[] = $normalized_path;
        error_log("Normalized path: {$normalized_path}");
        
        // 4. Path with assets prefix only
        if (preg_match('#/assets/images/(.+?)/#', $file_path, $matches)) {
            $assets_path = '/assets/images/' . $matches[1] . '/' . basename($file_path);
            $possible_paths[] = $assets_path;
            error_log("Assets-only path: {$assets_path}");
        }
        
        // 5. Extract just the basename and try to match in expected directories
        $basename = basename($file_path);
        foreach ($media_directories as $media_dir) {
            $base_dir_path = '/' . ltrim($media_dir, '/') . '/' . $basename;
            $possible_paths[] = $base_dir_path;
            error_log("Base-dir path: {$base_dir_path}");
        }
        
        // Try each path format
        $file_exists = false;
        $working_path = null;
        $full_path = null;
        $is_allowed = false;
        
        foreach ($possible_paths as $test_path) {
            // Build full server path for this test path
            $test_full_path = $doc_root;
            if (!empty($project_folder)) {
                $test_full_path .= DIRECTORY_SEPARATOR . $project_folder;
            }
            $test_full_path .= str_replace('/', DIRECTORY_SEPARATOR, $test_path);
            
            error_log("Testing path: {$test_full_path}");
            
            // Verify this path is within allowed directories first
            $path_allowed = false;
            foreach ($media_directories as $dir) {
                $normalized_dir = normalize_image_path($dir);
                $normalized_test = normalize_image_path($test_path);
                if (strpos($normalized_test, $normalized_dir) === 0) {
                    $path_allowed = true;
                    break;
                }
            }
            
            if (!$path_allowed) {
                error_log("Path not allowed: {$test_path}");
                continue;
            }
            
            if (file_exists($test_full_path)) {
                $file_exists = true;
                $working_path = $test_path;
                $full_path = $test_full_path;
                $is_allowed = true;
                error_log("FOUND FILE at: {$test_full_path}");
                break;
            }
        }
        
        // If still not found, try direct paths without project folder
        if (!$file_exists) {
            foreach ($possible_paths as $test_path) {
                $direct_path = $doc_root . str_replace('/', DIRECTORY_SEPARATOR, $test_path);
                error_log("Testing direct path: {$direct_path}");
                
                // Verify this path is within allowed directories first
                $path_allowed = false;
                foreach ($media_directories as $dir) {
                    $normalized_dir = normalize_image_path($dir);
                    $normalized_test = normalize_image_path($test_path);
                    if (strpos($normalized_test, $normalized_dir) === 0) {
                        $path_allowed = true;
                        break;
                    }
                }
                
                if (!$path_allowed) {
                    continue;
                }
                
                if (file_exists($direct_path)) {
                    $file_exists = true;
                    $working_path = $test_path;
                    $full_path = $direct_path;
                    $is_allowed = true;
                    error_log("FOUND FILE at direct path: {$direct_path}");
                    break;
                }
            }
        }
        
        // If we found a valid file, try to delete it
        if ($is_allowed && $file_exists) {
            error_log("Attempting to delete: {$full_path}");
            if (unlink($full_path)) {
                $results[] = [
                    'path' => $working_path,
                    'success' => true
                ];
                $success_count++;
                error_log("Successfully deleted: {$full_path}");
            } else {
                $results[] = [
                    'path' => $working_path,
                    'success' => false,
                    'message' => 'Failed to delete the file. Check file permissions.'
                ];
                $error_count++;
                error_log("Failed to delete (permission issue): {$full_path}");
            }
        } else {
            $results[] = [
                'path' => $file_path,
                'success' => false,
                'message' => $is_allowed ? 'File not found' : 'Invalid file path'
            ];
            $error_count++;
            error_log("Failed to delete: File not found or not allowed");
        }
    }
    
    // Discard any previous output
    ob_end_clean();
    
    // Send proper JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "$success_count files deleted successfully. $error_count files failed.",
        'results' => $results,
        'success_count' => $success_count,
        'error_count' => $error_count
    ]);
    
} catch (Exception $e) {
    // Catch any exceptions
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    error_log("Exception in bulk delete: " . $e->getMessage());
}
?>