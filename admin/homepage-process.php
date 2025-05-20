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

/**
 * Process and save an image path consistently for cross-platform compatibility
 * 
 * @param string $image_path Image path to process
 * @return string Normalized image path for database storage
 */
function process_image_path_for_storage($image_path) {
    if (empty($image_path)) return '';
    
    // First, normalize the path using the common function
    $normalized_path = normalize_image_path($image_path);
    
    // Make sure it's properly formatted for storage
    // Paths should always start with /assets/ to ensure consistency
    if (strpos($normalized_path, '/assets/') !== 0) {
        // Try to extract the /assets/ part
        if (preg_match('#(/assets/.*)#', $normalized_path, $matches)) {
            $normalized_path = $matches[1];
        }
    }
    
    // Log the path transformation for debugging
    error_log("Image path transformation: Original: {$image_path}, Normalized: {$normalized_path}");
    
    return $normalized_path;
}

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
        
        // IMPROVED: Process image path consistently before storage
        $image = process_image_path_for_storage($image);
        
        // Prepare data for database
        $image = $db->escape($image);
        $caption = $db->escape($caption);
        $link = $db->escape($link);
        
        // Log the final path for debugging
        error_log("Slideshow image path to be stored: {$image}");
        
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
        
        // IMPROVED: Process image path consistently before storage if image exists
        if (!empty($image)) {
            $image = process_image_path_for_storage($image);
        }
        
        // Prepare data for database
        $name = $db->escape($name);
        $image = $db->escape($image);
        $description = $db->escape($description);
        
        // Log the final path for debugging
        error_log("Facility image path to be stored: {$image}");
        
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

    // Save hero image
    if ($_POST['action'] === 'save_hero_image') {
        $hero_image = isset($_POST['hero_image']) ? trim($_POST['hero_image']) : '';
        
        // Process image upload if provided
        if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_image($_FILES['image_upload'], 'campus');
            if ($upload_result) {
                $hero_image = $upload_result;
            } else {
                header('Location: homepage-manage.php?error=upload_failed');
                exit;
            }
        }
        
        // Process image path consistently before storage
        if (!empty($hero_image)) {
            $hero_image = process_image_path_for_storage($hero_image);
        }
        
        // Prepare data for database
        $hero_image = $db->escape($hero_image);
        
        // Check if setting already exists
        $existing = $db->fetch_row("SELECT * FROM site_settings WHERE setting_key = 'hero_image'");
        
        if ($existing) {
            // Update existing setting
            $db->query("UPDATE site_settings SET setting_value = '$hero_image' WHERE setting_key = 'hero_image'");
        } else {
            // Insert new setting
            $db->query("INSERT INTO site_settings (setting_key, setting_value) VALUES ('hero_image', '$hero_image')");
        }
        
        header('Location: homepage-manage.php?msg=saved');
        exit;
    }
}

// Default redirect
header('Location: homepage-manage.php');
exit;