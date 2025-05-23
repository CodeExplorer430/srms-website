<?php
/**
 * AJAX Bulk Upload Handler
 * Updated for Hostinger compatibility
 * Version: 2.0
 */

// Start with a clean output buffer to capture any unexpected output
ob_start();

// Set up enhanced logging
ini_set('log_errors', 1);
error_log("===== BULK UPLOAD REQUEST: " . date('Y-m-d H:i:s') . " =====");

// Suppress all errors from being output (but still log them)
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

// Include environment settings
require_once '../../environment.php';

// Log environment info
error_log("Environment: " . (defined('IS_PRODUCTION') && IS_PRODUCTION ? 'Production' : 'Development'));
error_log("Server Type: " . (defined('SERVER_TYPE') ? SERVER_TYPE : 'Unknown'));

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

// Check if files are uploaded
if(!isset($_FILES['bulk_files'])) {
    ob_end_clean(); // Clear any output
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit;
}

// Get category
$category = isset($_POST['bulk_category']) ? $_POST['bulk_category'] : 'news';
// Added 'branding' to allowed categories
if(!in_array($category, ['news', 'events', 'promotional', 'facilities', 'campus', 'branding'])) {
    $category = 'news';
}

try {
    // Log for debugging
    error_log("Bulk upload: Starting file processing for category '{$category}' with " . count($_FILES['bulk_files']['name']) . " files");
    
    // Environment-specific path handling
    $is_production = defined('IS_PRODUCTION') && IS_PRODUCTION;
    
    // Determine project folder
    $project_folder = '';
    if (!$is_production && preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $project_folder = $matches[1]; // Only use in development
    }
    
    error_log("Bulk upload: Environment: " . ($is_production ? 'Production' : 'Development'));
    error_log("Bulk upload: Project folder: " . ($project_folder ? $project_folder : 'None (root level)'));
    
    $results = [];
    $success_count = 0;
    $error_count = 0;
    
    // Process each uploaded file
    $file_count = count($_FILES['bulk_files']['name']);
    
    for($i = 0; $i < $file_count; $i++) {
        // Create a temporary single file array structure that upload_image expects
        $temp_file = [
            'name' => $_FILES['bulk_files']['name'][$i],
            'type' => $_FILES['bulk_files']['type'][$i],
            'tmp_name' => $_FILES['bulk_files']['tmp_name'][$i],
            'error' => $_FILES['bulk_files']['error'][$i],
            'size' => $_FILES['bulk_files']['size'][$i]
        ];
        
        // Skip files with errors
        if($temp_file['error'] != UPLOAD_ERR_OK) {
            $error_message = get_upload_error_message($temp_file['error']);
            $results[] = [
                'filename' => $temp_file['name'],
                'success' => false,
                'message' => $error_message
            ];
            $error_count++;
            error_log("Bulk upload: File '{$temp_file['name']}' has error: {$error_message}");
            continue;
        }
        
        // Upload the image using our enhanced upload_image function
        $upload_result = upload_image($temp_file, $category);
        
        if($upload_result) {
            // Ensure the path uses forward slashes for web URLs
            $web_path = str_replace('\\', '/', $upload_result);
            
            // Generate display path based on environment
            if (!$is_production && !empty($project_folder)) {
                // In development, include project folder in the URL if not already there
                if (strpos($web_path, '/' . $project_folder) !== 0) {
                    $display_path = '/' . $project_folder . $web_path;
                } else {
                    $display_path = $web_path;
                }
            } else {
                // In production, use path as is
                $display_path = $web_path;
            }
            
            $results[] = [
                'filename' => $temp_file['name'],
                'success' => true,
                'path' => $web_path,
                'display_path' => $display_path
            ];
            $success_count++;
            error_log("Bulk upload: Successfully uploaded '{$temp_file['name']}' to '{$web_path}'");
        } else {
            $results[] = [
                'filename' => $temp_file['name'],
                'success' => false,
                'message' => 'Failed to upload file'
            ];
            $error_count++;
            error_log("Bulk upload: Failed to upload '{$temp_file['name']}'");
        }
    }
    
    // Discard any previous output
    ob_end_clean();
    
    // Send proper JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "$success_count files uploaded successfully. $error_count files failed.",
        'results' => $results,
        'success_count' => $success_count,
        'error_count' => $error_count
    ]);
    
} catch (Exception $e) {
    // Catch any exceptions
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    error_log("Bulk upload exception: " . $e->getMessage());
}

// Helper function to get error message for upload error codes
function get_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "File upload stopped by extension";
        default:
            return "Unknown upload error";
    }
}
?>