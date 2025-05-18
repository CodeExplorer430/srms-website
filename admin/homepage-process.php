<?php
/**
 * Homepage Elements Processing
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
include_once '../includes/functions.php';
$db = new Database();

// Handle AJAX GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    // Get slide data
    if ($_GET['action'] === 'get_slide' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $data = $db->fetch_row("SELECT * FROM slideshow WHERE id = $id");
        
        if ($data) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['message'] = 'Slide not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Get facility data
    if ($_GET['action'] === 'get_facility' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $data = $db->fetch_row("SELECT * FROM facilities WHERE id = $id");
        
        if ($data) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['message'] = 'Facility not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Delete slide
    if ($_GET['action'] === 'delete_slide' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $db->query("DELETE FROM slideshow WHERE id = $id");
        header('Location: homepage-manage.php?msg=saved');
        exit;
    }
    
    // Delete facility
    if ($_GET['action'] === 'delete_facility' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $db->query("DELETE FROM facilities WHERE id = $id");
        header('Location: homepage-manage.php?msg=saved');
        exit;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Save slideshow
    if ($_POST['action'] === 'save_slideshow') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $image = isset($_POST['image']) ? trim($_POST['image']) : '';
        $caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
        $link = isset($_POST['link']) ? trim($_POST['link']) : '';
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Process image upload if provided
        if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['image_upload'], 'promotional');
            if ($upload_result) {
                $image = $upload_result;
            } else {
                header('Location: homepage-manage.php?error=upload_failed');
                exit;
            }
        }
        
        if (empty($image)) {
            header('Location: homepage-manage.php?error=missing_image');
            exit;
        }
        
        // Normalize image path
        $image = normalize_image_path($image);
        
        // Prepare data for database
        $image = $db->escape($image);
        $caption = $db->escape($caption);
        $link = $db->escape($link);
        
        if ($id > 0) {
            // Update existing slide
            $db->query("UPDATE slideshow SET 
                        image = '$image', 
                        caption = '$caption', 
                        link = '$link', 
                        display_order = $display_order, 
                        is_active = $is_active 
                        WHERE id = $id");
        } else {
            // Insert new slide
            $db->query("INSERT INTO slideshow (image, caption, link, display_order, is_active) 
                        VALUES ('$image', '$caption', '$link', $display_order, $is_active)");
        }
        
        header('Location: homepage-manage.php?msg=saved');
        exit;
    }
    
    // Save facility
    if ($_POST['action'] === 'save_facility') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $image = isset($_POST['image']) ? trim($_POST['image']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        // Process image upload if provided
        if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['image_upload'], 'facilities');
            if ($upload_result) {
                $image = $upload_result;
            } else {
                header('Location: homepage-manage.php?error=upload_failed');
                exit;
            }
        }
        
        if (empty($name)) {
            header('Location: homepage-manage.php?error=missing_name');
            exit;
        }
        
        // Normalize image path if provided
        if (!empty($image)) {
            $image = normalize_image_path($image);
        }
        
        // Prepare data for database
        $name = $db->escape($name);
        $image = $db->escape($image);
        $description = $db->escape($description);
        
        if ($id > 0) {
            // Update existing facility
            $db->query("UPDATE facilities SET 
                        name = '$name', 
                        image = '$image', 
                        description = '$description', 
                        display_order = $display_order 
                        WHERE id = $id");
        } else {
            // Insert new facility
            $db->query("INSERT INTO facilities (name, image, description, display_order) 
                        VALUES ('$name', '$image', '$description', $display_order)");
        }
        
        header('Location: homepage-manage.php?msg=saved');
        exit;
    }
    
    // Save offer box
    if ($_POST['action'] === 'save_offer_box') {
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        
        if (!empty($content)) {
            // Clear existing offer box entries
            $db->query("DELETE FROM offer_box");
            
            // Insert new entries
            $order = 0;
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $line = $db->escape($line);
                    $db->query("INSERT INTO offer_box (content, display_order) VALUES ('$line', $order)");
                    $order++;
                }
            }
        }
        
        header('Location: homepage-manage.php?msg=saved');
        exit;
    }
}

// Default redirect
header('Location: homepage-manage.php');
exit;