<?php
session_start();

// Security check
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Get path parameter
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Check if file exists using our enhanced file verification function
$exists = false;
$debug_info = [];

if (!empty($path)) {
    // Normalize the path
    $path = normalize_image_path($path);
    
    // Check if file exists using our helper function
    $exists = verify_image_exists($path);
    
    // Get additional debug info
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    $full_path = $server_root . $path;
    $alt_path = $server_root . DIRECTORY_SEPARATOR . ltrim($path, '/');
    
    $debug_info = [
        'requested_path' => $path,
        'full_server_path' => $full_path,
        'alt_server_path' => $alt_path,
        'full_path_exists' => file_exists($full_path),
        'alt_path_exists' => file_exists($alt_path)
    ];
    
    // Try to find best matching image if exact match not found
    if (!$exists) {
        $best_match = find_best_matching_image($path);
        if ($best_match) {
            $debug_info['best_match'] = $best_match;
        }
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'exists' => (bool)$exists,
    'path' => $path,
    'debug' => $debug_info
]);