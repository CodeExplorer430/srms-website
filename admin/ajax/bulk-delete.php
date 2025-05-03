<?php
// Start with a clean output buffer to capture any unexpected output
ob_start();

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

// Validate media directories (allowed paths for deletion)
$media_directories = [
    '/assets/images/news',
    '/assets/images/events',
    '/assets/images/promotional',
    '/assets/images/facilities',
    '/assets/images/campus'
];

error_log('Bulk delete request received: ' . date('Y-m-d H:i:s'));
error_log('POST data: ' . print_r($_POST, true));
error_log('Raw input: ' . file_get_contents('php://input'));

try {
    $results = [];
    $success_count = 0;
    $error_count = 0;
    
    foreach ($files_to_delete as $file_path) {
        // Use the normalize_image_path function from functions.php
        $file_path = normalize_image_path($file_path);
        
        // Get full server path
        $full_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
        
        // Debug logging
        error_log("Processing file for deletion: {$file_path} => {$full_path}");
        
        // Verify path is within allowed directories
        $is_allowed = false;
        foreach ($media_directories as $dir) {
            $normalized_dir = normalize_image_path($dir);
            if (strpos($file_path, $normalized_dir) === 0) {
                $is_allowed = true;
                break;
            }
        }
        
        // Check using our enhanced file existence verification
        $file_exists = verify_image_exists($file_path);
        
        if ($is_allowed && $file_exists) {
            if (unlink($full_path)) {
                $results[] = [
                    'path' => $file_path,
                    'success' => true
                ];
                $success_count++;
                error_log("Successfully deleted: {$full_path}");
            } else {
                $results[] = [
                    'path' => $file_path,
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
            error_log("Failed to delete: {$full_path} - " . 
                ($is_allowed ? "File not found (exists check: " . ($file_exists ? "true" : "false") . ")" 
                            : "Invalid path (not in allowed directories)"));
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