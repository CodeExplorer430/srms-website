<?php
/**
 * Admissions Content Processing
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
    
    // Get student type data
    if ($_GET['action'] === 'get_student_type' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $data = $db->fetch_row("SELECT * FROM student_types WHERE id = $id");
        
        if ($data) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['message'] = 'Student type not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Get age requirement data
    if ($_GET['action'] === 'get_age_requirement' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $data = $db->fetch_row("SELECT * FROM age_requirements WHERE id = $id");
        
        if ($data) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['message'] = 'Age requirement not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Get enrollment procedure data
    if ($_GET['action'] === 'get_enrollment_procedure' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $data = $db->fetch_row("SELECT * FROM enrollment_procedures WHERE id = $id");
        
        if ($data) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['message'] = 'Enrollment procedure not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Get non-readmission ground data
    if ($_GET['action'] === 'get_non_readmission' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $data = $db->fetch_row("SELECT * FROM non_readmission_grounds WHERE id = $id");
        
        if ($data) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['message'] = 'Non-readmission ground not found';
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Delete student type
    if ($_GET['action'] === 'delete_student_type' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $db->query("DELETE FROM student_types WHERE id = $id");
        header('Location: admissions-manage.php?msg=saved');
        exit;
    }
    
    // Delete age requirement
    if ($_GET['action'] === 'delete_age_requirement' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $db->query("DELETE FROM age_requirements WHERE id = $id");
        header('Location: admissions-manage.php?msg=saved');
        exit;
    }
    
    // Delete enrollment procedure
    if ($_GET['action'] === 'delete_enrollment_procedure' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $db->query("DELETE FROM enrollment_procedures WHERE id = $id");
        header('Location: admissions-manage.php?msg=saved');
        exit;
    }
    
    // Delete non-readmission ground
    if ($_GET['action'] === 'delete_non_readmission' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $db->query("DELETE FROM non_readmission_grounds WHERE id = $id");
        header('Location: admissions-manage.php?msg=saved');
        exit;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Save admission policies
    if ($_POST['action'] === 'save_policies') {
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        
        // Check if there's an existing policy
        $existing = $db->fetch_row("SELECT id FROM admission_policies LIMIT 1");
        
        if ($existing) {
            // Update existing policy
            $content = $db->escape($content);
            $db->query("UPDATE admission_policies SET content = '$content' WHERE id = {$existing['id']}");
        } else {
            // Insert new policy
            $content = $db->escape($content);
            $db->query("INSERT INTO admission_policies (content, display_order) VALUES ('$content', 0)");
        }
        
        header('Location: admissions-manage.php?msg=saved');
        exit;
    }
    
    // Save student type
    if ($_POST['action'] === 'save_student_type') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $requirements = isset($_POST['requirements']) ? trim($_POST['requirements']) : '';
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        if (empty($name) || empty($requirements)) {
            header('Location: admissions-manage.php?error=missing_fields');
            exit;
        }
        
        $name = $db->escape($name);
        $requirements = $db->escape($requirements);
        
        if ($id > 0) {
            // Update existing student type
            $db->query("UPDATE student_types SET 
                        name = '$name', 
                        requirements = '$requirements', 
                        display_order = $display_order 
                        WHERE id = $id");
        } else {
            // Insert new student type
            $db->query("INSERT INTO student_types (name, requirements, display_order) 
                        VALUES ('$name', '$requirements', $display_order)");
        }
        
        header('Location: admissions-manage.php?msg=saved');
        exit;
    }
    
    // Save age requirement
    if ($_POST['action'] === 'save_age_requirement') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $grade_level = isset($_POST['grade_level']) ? trim($_POST['grade_level']) : '';
        $requirements = isset($_POST['requirements']) ? trim($_POST['requirements']) : '';
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        if (empty($grade_level) || empty($requirements)) {
            header('Location: admissions-manage.php?error=missing_fields');
            exit;
        }
        
        $grade_level = $db->escape($grade_level);
        $requirements = $db->escape($requirements);
        
        if ($id > 0) {
            // Update existing age requirement
            $db->query("UPDATE age_requirements SET 
                        grade_level = '$grade_level', 
                        requirements = '$requirements', 
                        display_order = $display_order 
                        WHERE id = $id");
        } else {
            // Insert new age requirement
            $db->query("INSERT INTO age_requirements (grade_level, requirements, display_order) 
                        VALUES ('$grade_level', '$requirements', $display_order)");
        }
        
        header('Location: admissions-manage.php?msg=saved');
        exit;
    }
    
    // Save enrollment procedure
    if ($_POST['action'] === 'save_enrollment_procedure') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $student_type = isset($_POST['student_type']) ? trim($_POST['student_type']) : '';
        $steps = isset($_POST['steps']) ? trim($_POST['steps']) : '';
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        if (empty($student_type) || empty($steps)) {
            header('Location: admissions-manage.php?error=missing_fields');
            exit;
        }
        
        $student_type = $db->escape($student_type);
        $steps = $db->escape($steps);
        
        if ($id > 0) {
            // Update existing enrollment procedure
            $db->query("UPDATE enrollment_procedures SET 
                        student_type = '$student_type', 
                        steps = '$steps', 
                        display_order = $display_order 
                        WHERE id = $id");
        } else {
            // Insert new enrollment procedure
            $db->query("INSERT INTO enrollment_procedures (student_type, steps, display_order) 
                        VALUES ('$student_type', '$steps', $display_order)");
        }
        
        header('Location: admissions-manage.php?msg=saved');
        exit;
    }
    
    // Save non-readmission ground
    if ($_POST['action'] === 'save_non_readmission') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        if (empty($description)) {
            header('Location: admissions-manage.php?error=missing_fields');
            exit;
        }
        
        $description = $db->escape($description);
        
        if ($id > 0) {
            // Update existing non-readmission ground
            $db->query("UPDATE non_readmission_grounds SET 
                        description = '$description', 
                        display_order = $display_order 
                        WHERE id = $id");
        } else {
            // Insert new non-readmission ground
            $db->query("INSERT INTO non_readmission_grounds (description, display_order) 
                        VALUES ('$description', $display_order)");
        }
        
        header('Location: admissions-manage.php?msg=saved');
        exit;
    }
}

// Default redirect
header('Location: admissions-manage.php');
exit;