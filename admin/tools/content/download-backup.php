<?php
/**
 * Database Backup Download Script
 * Allows downloading of database backup files
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Require admin level permissions
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    echo "<div class='alert alert-danger'>This feature requires administrator privileges.</div>";
    exit;
}

// Get the site root directory more reliably
$current_dir = dirname(__FILE__);
$tools_dir = dirname($current_dir);
$admin_dir = dirname($tools_dir);
$site_root = dirname($admin_dir);

// Include environment file to get constants
require_once $site_root . DIRECTORY_SEPARATOR . 'environment.php';

// Include necessary files
include_once $site_root . DS . 'includes' . DS . 'config.php';

// Check if file parameter is provided
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('HTTP/1.0 400 Bad Request');
    echo "Error: No backup file specified.";
    exit;
}

// Sanitize file parameter to prevent path traversal
$filename = basename($_GET['file']);
if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
    header('HTTP/1.0 400 Bad Request');
    echo "Error: Invalid backup filename.";
    exit;
}

// Build backup file path
$backup_dir = $site_root . DS . 'database' . DS . 'backups';
$backup_file = $backup_dir . DS . $filename;

// Check if file exists
if (!file_exists($backup_file)) {
    header('HTTP/1.0 404 Not Found');
    echo "Error: The requested backup file does not exist.";
    exit;
}

// Set headers for file download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($backup_file));

// Read file and output to browser
readfile($backup_file);
exit;
?>  