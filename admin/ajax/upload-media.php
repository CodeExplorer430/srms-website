<?php
// Start with a clean output buffer to capture any unexpected output
ob_start();

// Suppress all errors from being output (but still log them)
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

// Log detailed information for debugging
error_log("Upload media: Processing request");

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
    
    // Upload the image using our enhanced upload_image function
    $upload_result = upload_image($_FILES['quick_upload'], $category);
    
    // Discard any previous output
    ob_end_clean();
    
    // Send proper JSON response
    header('Content-Type: application/json');
    
    if($upload_result) {
        // Ensure the path uses forward slashes for web URLs
        $web_path = str_replace('\\', '/', $upload_result);
        
        // Get project folder from SITE_URL
        $project_folder = '';
        if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
            $project_folder = $matches[1]; 
        }
        
        // Verify file exists immediately after upload
        $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        $with_project = $doc_root . DIRECTORY_SEPARATOR . $project_folder . str_replace('/', DIRECTORY_SEPARATOR, $web_path);
        $without_project = $doc_root . str_replace('/', DIRECTORY_SEPARATOR, $web_path);
        
        // Log verification results
        if (file_exists($with_project)) {
            error_log("Upload media: Verified image exists at: " . $with_project);
        } else if (file_exists($without_project)) {
            error_log("Upload media: Verified image exists at: " . $without_project);
        } else {
            error_log("Upload media: WARNING - File uploaded but verification failed. Tried paths:");
            error_log("With project: " . $with_project);
            error_log("Without project: " . $without_project);
        }
        
        error_log("Upload media: Successfully uploaded to '{$web_path}'");
        echo json_encode([
            'success' => true, 
            'message' => 'File uploaded successfully',
            'path' => $web_path
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