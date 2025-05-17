<?php
session_start();

// Security check
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['exists' => false, 'error' => 'Unauthorized']);
    exit;
}

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Get path parameter and normalize it
$path = isset($_GET['path']) ? $_GET['path'] : '';
$path = normalize_image_path($path);

// Check if file exists using our enhanced file verification function
$exists = !empty($path) ? verify_image_exists($path) : false;

// Get additional information for detailed response
$debug_info = [];
if (!empty($path)) {
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
}

// Send response with detailed information
header('Content-Type: application/json');
echo json_encode([
    'exists' => (bool)$exists, 
    'path' => $path,
    'debug' => $debug_info
]);