<?php
/**
 * AJAX Handler for retrieving submission details
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection
include_once '../../includes/config.php';
include_once '../../includes/db.php';
$db = new Database();

// Get submission ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
    exit;
}

// Get submission details
$submission = $db->fetch_row("SELECT * FROM contact_submissions WHERE id = $id");

if (!$submission) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Submission not found']);
    exit;
}

// Return submission data
header('Content-Type: application/json');
echo json_encode(['success' => true, 'submission' => $submission]);