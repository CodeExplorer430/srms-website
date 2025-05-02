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

// Check if files are uploaded
if(!isset($_FILES['bulk_files'])) {
    ob_end_clean(); // Clear any output
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit;
}

// Get category
$category = isset($_POST['bulk_category']) ? $_POST['bulk_category'] : 'news';
if(!in_array($category, ['news', 'events', 'promotional', 'facilities', 'campus'])) {
    $category = 'news';
}

try {
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
            continue;
        }
        
        // Upload the image using the cross-platform enabled upload_image function
        $upload_result = upload_image($temp_file, $category);
        
        if($upload_result) {
            // Ensure the path uses forward slashes for web URLs
            $web_path = str_replace('\\', '/', $upload_result);
            
            $results[] = [
                'filename' => $temp_file['name'],
                'success' => true,
                'path' => $web_path
            ];
            $success_count++;
        } else {
            $results[] = [
                'filename' => $temp_file['name'],
                'success' => false,
                'message' => 'Failed to upload file'
            ];
            $error_count++;
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