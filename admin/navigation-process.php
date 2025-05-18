<?php
/**
 * Navigation Processing
 * Handles AJAX requests and form submissions for navigation management
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    } else {
        header('Location: ../admin/login.php');
    }
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
$db = new Database();

// Handle AJAX GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    // Get navigation item data
    if ($_GET['action'] === 'get' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $item = $db->fetch_row("SELECT * FROM navigation WHERE id = $id");
        
        if ($item) {
            $response['success'] = true;
            $response['item'] = $item;
        } else {
            $response['message'] = 'Navigation item not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Save navigation item
    if ($_POST['action'] === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $url = isset($_POST['url']) ? trim($_POST['url']) : '';
        $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 'NULL';
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Basic validation
        if (empty($name) || $url === '') {
            $_SESSION['message'] = 'Name and URL are required fields.';
            $_SESSION['message_type'] = 'error';
            header('Location: navigation-manage.php');
            exit;
        }
        
        // Check if URL starts with / for internal links (except #)
        if ($url !== '#' && strpos($url, 'http') !== 0 && strpos($url, '/') !== 0) {
            $url = '/' . $url;
        }
        
        // Prepare data for database
        $name = $db->escape($name);
        $url = $db->escape($url);
        
        // Check for circular references
        if ($id > 0 && $parent_id !== 'NULL') {
            // Make sure we're not setting an item as its own parent or descendant
            $current = $parent_id;
            while ($current !== 'NULL') {
                if ($current == $id) {
                    $_SESSION['message'] = 'Cannot set an item as its own parent or descendant.';
                    $_SESSION['message_type'] = 'error';
                    header('Location: navigation-manage.php');
                    exit;
                }
                
                $parent = $db->fetch_row("SELECT parent_id FROM navigation WHERE id = $current");
                $current = $parent ? ($parent['parent_id'] ?: 'NULL') : 'NULL';
            }
        }
        
        if ($id > 0) {
            // Update existing item
            $sql = "UPDATE navigation SET 
                    name = '$name', 
                    url = '$url', 
                    parent_id = $parent_id, 
                    display_order = $display_order, 
                    is_active = $is_active 
                    WHERE id = $id";
                    
            if ($db->query($sql)) {
                $_SESSION['message'] = 'Navigation item updated successfully.';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'An error occurred while updating the navigation item.';
                $_SESSION['message_type'] = 'error';
            }
        } else {
            // Insert new item
            $sql = "INSERT INTO navigation (name, url, parent_id, display_order, is_active) 
                    VALUES ('$name', '$url', $parent_id, $display_order, $is_active)";
                    
            if ($db->query($sql)) {
                $_SESSION['message'] = 'Navigation item added successfully.';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'An error occurred while adding the navigation item.';
                $_SESSION['message_type'] = 'error';
            }
        }
        
        header('Location: navigation-manage.php');
        exit;
    }
}

// Default redirect
header('Location: navigation-manage.php');
exit;