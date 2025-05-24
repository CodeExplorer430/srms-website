<?php
/**
 * AJAX Upload Media Handler
 * Updated for Hostinger compatibility
 * Version: 2.0
 */

// Start with a clean output buffer to capture any unexpected output
ob_start();

// Set up enhanced logging
ini_set('log_errors', 1);
error_log("===== UPLOAD MEDIA REQUEST: " . date('Y-m-d H:i:s') . " =====");

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

// Check if file is uploaded
if(!isset($_FILES['quick_upload']) || $_FILES['quick_upload']['error'] != UPLOAD_ERR_OK) {
    $error_code = isset($_FILES['quick_upload']) ? $_FILES['quick_upload']['error'] : 'No file';
    ob_end_clean(); // Clear any output
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error: ' . $error_code]);
    error_log("Upload media: File upload error - " . $error_code);
    exit;
}

// Get category - Added 'branding' to allowed categories
$category = isset($_POST['quick_category']) ? $_POST['quick_category'] : 'news';
if(!in_array($category, ['news', 'events', 'promotional', 'facilities', 'campus', 'branding'])) {
    $category = 'news';
}

try {
    // Log detailed info about the uploaded file
    error_log("Upload media: Processing file '{$_FILES['quick_upload']['name']}' for category '{$category}'");
    error_log("Upload media: File size: {$_FILES['quick_upload']['size']} bytes, type: {$_FILES['quick_upload']['type']}");
    
    // Environment-specific path handling
    $is_production = defined('IS_PRODUCTION') && IS_PRODUCTION;
    
    // Determine project folder
    $project_folder = '';
    if (!$is_production && preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $project_folder = $matches[1]; // Only use in development
    }
    
    error_log("Upload media: Environment: " . ($is_production ? 'Production' : 'Development'));
    error_log("Upload media: Project folder: " . ($project_folder ? $project_folder : 'None (root level)'));
    
    // Upload the image using our enhanced upload_image function with environment context
    $upload_result = upload_image($_FILES['quick_upload'], $category);
    
    // Discard any previous output
    ob_end_clean();
    
    // Send proper JSON response
    header('Content-Type: application/json');
    
    if($upload_result) {
        // Ensure the path uses forward slashes for web URLs
        $web_path = str_replace('\\', '/', $upload_result);
        
        // Verify file exists immediately after upload
        $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        
        // Build verification paths based on environment
        if ($is_production) {
            // In production, the file should be directly in document root
            $verification_path = $doc_root . str_replace('/', DIRECTORY_SEPARATOR, $web_path);
            
            error_log("Upload media: Verifying production path: " . $verification_path);
            $exists = file_exists($verification_path);
        } else {
            // In development, we might need to check with and without project folder
            $with_project = $doc_root . DIRECTORY_SEPARATOR . $project_folder . str_replace('/', DIRECTORY_SEPARATOR, $web_path);
            $without_project = $doc_root . str_replace('/', DIRECTORY_SEPARATOR, $web_path);
            
            error_log("Upload media: Verifying development paths:");
            error_log("With project: " . $with_project);
            error_log("Without project: " . $without_project);
            
            $exists = file_exists($with_project) || file_exists($without_project);
        }
        
        // Log verification result
        error_log("Upload media: File verification: " . ($exists ? "Successful" : "Failed"));
        
        // Generate web URL for response based on environment
        if (!$is_production && !empty($project_folder)) {
            // In development, include project folder in the URL if not already there
            if (strpos($web_path, '/' . $project_folder) !== 0) {
                $display_path = '/' . $project_folder . $web_path;
                error_log("Upload media: Added project folder to path: " . $display_path);
            } else {
                $display_path = $web_path;
            }
        } else {
            // In production, use path as is
            $display_path = $web_path;
        }
        
        error_log("Upload media: Successfully uploaded to '{$web_path}', display path: '{$display_path}'");
        echo json_encode([
            'success' => true, 
            'message' => 'File uploaded successfully',
            'path' => $web_path,
            'display_path' => $display_path
        ]);
    } else {
        error_log("Upload media: Failed to upload file");
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
} catch (Exception $e) {
    // Catch any exceptions
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    error_log("Upload media exception: " . $e->getMessage());
}
?>