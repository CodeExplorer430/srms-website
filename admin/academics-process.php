<?php
/**
 * Academic Programs Processing
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php';
$db = new Database();

// Handle AJAX GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    // Get level data
    if ($_GET['action'] === 'get_level' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $level = $db->fetch_row("SELECT * FROM academic_levels WHERE id = $id");
        
        if ($level) {
            $response['success'] = true;
            $response['level'] = $level;
        } else {
            $response['message'] = 'Level not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Get program data
    if ($_GET['action'] === 'get_program' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $program = $db->fetch_row("SELECT * FROM academic_programs WHERE id = $id");
        
        if ($program) {
            $response['success'] = true;
            $response['program'] = $program;
        } else {
            $response['message'] = 'Program not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Get track data
    if ($_GET['action'] === 'get_track' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $track = $db->fetch_row("SELECT * FROM academic_tracks WHERE id = $id");
        
        if ($track) {
            $response['success'] = true;
            $response['track'] = $track;
        } else {
            $response['message'] = 'Track not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Save academic level
    if ($_POST['action'] === 'save_level') {
        $id = isset($_POST['level_id']) ? (int)$_POST['level_id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        // Validate inputs
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Level name is required';
        }
        
        if (empty($slug)) {
            $errors[] = 'Level slug is required';
        } else {
            // Check if slug is valid (alphanumeric + dash)
            if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
                $errors[] = 'Slug can only contain lowercase letters, numbers, and dashes';
            }
            
            // Check if slug is unique
            $check_sql = "SELECT id FROM academic_levels WHERE slug = '{$db->escape($slug)}' AND id != $id";
            $existing = $db->fetch_row($check_sql);
            
            if ($existing) {
                $errors[] = 'Slug is already in use by another level';
            }
        }
        
        if (!empty($errors)) {
            set_error_messages($errors);
            header('Location: academics-manage.php');
            exit;
        }
        
        // Process if no errors
        $name = $db->escape($name);
        $slug = $db->escape($slug);
        $description = $db->escape($description);
        
        if ($id > 0) {
            // Update existing level
            $sql = "UPDATE academic_levels SET 
                    name = '$name', 
                    slug = '$slug', 
                    description = '$description', 
                    display_order = $display_order 
                    WHERE id = $id";
                    
            if ($db->query($sql)) {
                header('Location: academics-manage.php?msg=level_updated');
            } else {
                set_error_messages(['An error occurred while updating the level']);
                header('Location: academics-manage.php');
            }
        } else {
            // Insert new level
            $sql = "INSERT INTO academic_levels (name, slug, description, display_order) 
                    VALUES ('$name', '$slug', '$description', $display_order)";
                    
            if ($db->query($sql)) {
                header('Location: academics-manage.php?msg=level_added');
            } else {
                set_error_messages(['An error occurred while creating the level']);
                header('Location: academics-manage.php');
            }
        }
        
        exit;
    }
    
    // Delete academic level
    if ($_POST['action'] === 'delete_level') {
        $id = isset($_POST['level_id']) ? (int)$_POST['level_id'] : 0;
        
        // Check if level has programs
        $program_count = $db->fetch_row("SELECT COUNT(*) as count FROM academic_programs WHERE level_id = $id")['count'];
        
        if ($program_count > 0) {
            set_error_messages(['Cannot delete this level because it has programs associated with it']);
            header('Location: academics-manage.php');
            exit;
        }
        
        // Delete the level
        $sql = "DELETE FROM academic_levels WHERE id = $id";
        
        if ($db->query($sql)) {
            header('Location: academics-manage.php?msg=level_deleted');
        } else {
            set_error_messages(['An error occurred while deleting the level']);
            header('Location: academics-manage.php');
        }
        
        exit;
    }
    
    // Save academic program
    if ($_POST['action'] === 'save_program') {
        $id = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
        $level_id = isset($_POST['level_id']) ? (int)$_POST['level_id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        
        // Validate inputs
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Program name is required';
        }
        
        if ($level_id <= 0) {
            $errors[] = 'Academic level is required';
        }
        
        if (!empty($errors)) {
            set_error_messages($errors);
            header('Location: academics-manage.php');
            exit;
        }
        
        // Process if no errors
        $name = $db->escape($name);
        $description = $db->escape($description);
        
        if ($id > 0) {
            // Update existing program
            $sql = "UPDATE academic_programs SET 
                    level_id = $level_id, 
                    name = '$name', 
                    description = '$description' 
                    WHERE id = $id";
                    
            if ($db->query($sql)) {
                header('Location: academics-manage.php?msg=program_updated');
            } else {
                set_error_messages(['An error occurred while updating the program']);
                header('Location: academics-manage.php');
            }
        } else {
            // Insert new program
            $sql = "INSERT INTO academic_programs (level_id, name, description) 
                    VALUES ($level_id, '$name', '$description')";
                    
            if ($db->query($sql)) {
                header('Location: academics-manage.php?msg=program_added');
            } else {
                set_error_messages(['An error occurred while creating the program']);
                header('Location: academics-manage.php');
            }
        }
        
        exit;
    }
    
    // Delete academic program
    if ($_POST['action'] === 'delete_program') {
        $id = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
        
        // Check if program has tracks
        $track_count = $db->fetch_row("SELECT COUNT(*) as count FROM academic_tracks WHERE program_id = $id")['count'];
        
        if ($track_count > 0) {
            set_error_messages(['Cannot delete this program because it has tracks associated with it']);
            header('Location: academics-manage.php');
            exit;
        }
        
        // Delete the program
        $sql = "DELETE FROM academic_programs WHERE id = $id";
        
        if ($db->query($sql)) {
            header('Location: academics-manage.php?msg=program_deleted');
        } else {
            set_error_messages(['An error occurred while deleting the program']);
            header('Location: academics-manage.php');
        }
        
        exit;
    }
    
    // Save academic track
    if ($_POST['action'] === 'save_track') {
        $id = isset($_POST['track_id']) ? (int)$_POST['track_id'] : 0;
        $program_id = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        // Validate inputs
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Track name is required';
        }
        
        if ($program_id <= 0) {
            $errors[] = 'Academic program is required';
        }
        
        if (!empty($errors)) {
            set_error_messages($errors);
            header('Location: academics-manage.php');
            exit;
        }
        
        // Process if no errors
        $name = $db->escape($name);
        $code = $db->escape($code);
        $description = $db->escape($description);
        
        if ($id > 0) {
            // Update existing track
            $sql = "UPDATE academic_tracks SET 
                    program_id = $program_id, 
                    name = '$name', 
                    code = '$code', 
                    description = '$description', 
                    display_order = $display_order 
                    WHERE id = $id";
                    
            if ($db->query($sql)) {
                header('Location: academics-manage.php?msg=track_updated');
            } else {
                set_error_messages(['An error occurred while updating the track']);
                header('Location: academics-manage.php');
            }
        } else {
            // Insert new track
            $sql = "INSERT INTO academic_tracks (program_id, name, code, description, display_order) 
                    VALUES ($program_id, '$name', '$code', '$description', $display_order)";
                    
            if ($db->query($sql)) {
                header('Location: academics-manage.php?msg=track_added');
            } else {
                set_error_messages(['An error occurred while creating the track']);
                header('Location: academics-manage.php');
            }
        }
        
        exit;
    }
    
    // Delete academic track
    if ($_POST['action'] === 'delete_track') {
        $id = isset($_POST['track_id']) ? (int)$_POST['track_id'] : 0;
        
        // Delete the track
        $sql = "DELETE FROM academic_tracks WHERE id = $id";
        
        if ($db->query($sql)) {
            header('Location: academics-manage.php?msg=track_deleted');
        } else {
            set_error_messages(['An error occurred while deleting the track']);
            header('Location: academics-manage.php');
        }
        
        exit;
    }
}

// Helper function to set error messages
function set_error_messages($errors) {
    $_SESSION['error_messages'] = $errors;
}

// Redirect to academics page if no action specified
header('Location: academics-manage.php');
exit;