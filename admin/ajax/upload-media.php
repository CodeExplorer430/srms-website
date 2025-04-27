<?php
// Start with a clean output buffer to capture any unexpected output
ob_start();

// Suppress all errors from being output (but still log them)
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

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
    ob_end_clean(); // Clear any output
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error: ' . ($_FILES['quick_upload']['error'] ?? 'Unknown error')]);
    exit;
}

// Get category
$category = isset($_POST['quick_category']) ? $_POST['quick_category'] : 'news';
if(!in_array($category, ['news', 'events', 'promotional', 'facilities', 'campus'])) {
    $category = 'news';
}

try {
    // Upload the image
    $upload_result = upload_image($_FILES['quick_upload'], $category);
    
    // Discard any previous output
    ob_end_clean();
    
    // Send proper JSON response
    header('Content-Type: application/json');
    
    if($upload_result) {
        echo json_encode([
            'success' => true, 
            'message' => 'File uploaded successfully',
            'path' => $upload_result
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
} catch (Exception $e) {
    // Catch any exceptions
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>