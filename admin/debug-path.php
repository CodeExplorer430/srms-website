<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Image Path Diagnostics</h1>";

// Get project folder information
$server_root = $_SERVER['DOCUMENT_ROOT'];
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1];
}

echo "<p>Document Root: " . htmlspecialchars($server_root) . "</p>";
echo "<p>Project Folder: " . htmlspecialchars($project_folder) . "</p>";
echo "<p>SITE_URL: " . htmlspecialchars(SITE_URL) . "</p>";

// Test a specific image path
$test_path = '/assets/images/news/pexels-olly-834863-1747576084.jpg';
echo "<p>Testing image path: " . htmlspecialchars($test_path) . "</p>";

// Test different path construction methods
$fullpath1 = $server_root . $test_path;
$fullpath2 = $server_root . '/' . $project_folder . $test_path;
$fullpath3 = str_replace('/', DIRECTORY_SEPARATOR, $server_root . '/' . $project_folder . $test_path);

echo "<p>Path 1: " . htmlspecialchars($fullpath1) . " - Exists: " . (file_exists($fullpath1) ? 'Yes' : 'No') . "</p>";
echo "<p>Path 2: " . htmlspecialchars($fullpath2) . " - Exists: " . (file_exists($fullpath2) ? 'Yes' : 'No') . "</p>";
echo "<p>Path 3: " . htmlspecialchars($fullpath3) . " - Exists: " . (file_exists($fullpath3) ? 'Yes' : 'No') . "</p>";
?>