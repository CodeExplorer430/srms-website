<?php
/**
 * Upload Debug Tool
 * This file helps diagnose upload issues by testing file permissions and paths
 */
session_start();

// Check if user is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Include dependencies
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php';

$results = [];
$categories = ['news', 'events', 'promotional', 'facilities', 'campus'];

// Test directory structure
$results['directories'] = [];
foreach ($categories as $category) {
    $dir_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $category;
    $normalized_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dir_path);
    
    $dir_exists = is_dir($normalized_path);
    
    $results['directories'][$category] = [
        'path' => $normalized_path,
        'exists' => $dir_exists,
        'writable' => $dir_exists ? is_writable($normalized_path) : false
    ];
    
    // Create directory if it doesn't exist
    if (!$dir_exists) {
        $created = mkdir($normalized_path, 0755, true);
        $results['directories'][$category]['created'] = $created;
        $results['directories'][$category]['writable'] = $created ? is_writable($normalized_path) : false;
    }
}

// Test file uploads
$results['test_upload'] = [];
if (isset($_POST['test_upload'])) {
    $category = $_POST['category'] ?? 'news';
    
    // Create a test image if none uploaded
    if (!isset($_FILES['test_file']) || $_FILES['test_file']['error'] !== UPLOAD_ERR_OK) {
        // Create a 1x1 transparent PNG
        $img = imagecreatetruecolor(1, 1);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 0, 0, 1, 1, $transparent);
        
        $temp_file = tempnam(sys_get_temp_dir(), 'test_image');
        imagepng($img, $temp_file);
        
        $_FILES['test_file'] = [
            'name' => 'test-image.png',
            'type' => 'image/png',
            'tmp_name' => $temp_file,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($temp_file)
        ];
    }
    
    // Attempt upload
    $upload_result = upload_image($_FILES['test_file'], $category);
    
    $results['test_upload'] = [
        'category' => $category,
        'success' => $upload_result !== false,
        'result_path' => $upload_result,
        'file_exists' => $upload_result ? file_exists($_SERVER['DOCUMENT_ROOT'] . $upload_result) : false,
        'error' => $upload_result === false ? 'Upload failed' : null
    ];
    
    // Check if the uploaded file is accessible
    if ($upload_result) {
        $full_path = $_SERVER['DOCUMENT_ROOT'] . $upload_result;
        $alt_path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim($upload_result, '/');
        
        $results['test_upload']['full_path'] = $full_path;
        $results['test_upload']['alt_path'] = $alt_path;
        $results['test_upload']['full_path_exists'] = file_exists($full_path);
        $results['test_upload']['alt_path_exists'] = file_exists($alt_path);
    }
}

// Get server info
$results['server_info'] = [
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'server_type' => defined('SERVER_TYPE') ? SERVER_TYPE : 'Unknown', 
    'php_version' => PHP_VERSION,
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'tmp_dir' => sys_get_temp_dir(),
    'tmp_dir_writable' => is_writable(sys_get_temp_dir())
];

// Check if error logging is working
error_log("Test error log message from upload-debug.php at " . date('Y-m-d H:i:s'));
$results['error_log'] = [
    'error_log_path' => ini_get('error_log'),
    'error_reporting' => ini_get('error_reporting'),
    'display_errors' => ini_get('display_errors'),
    'log_errors' => ini_get('log_errors')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Debug Tool | SRMS Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f3f3;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        .header {
            background-color: #0a3060;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header i {
            margin-right: 15px;
            font-size: 28px;
        }
        .section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .section-title {
            margin-top: 0;
            color: #0a3060;
            font-size: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
        }
        .info-label {
            font-weight: 500;
            color: #495057;
        }
        .info-value {
            font-family: monospace;
            padding: 2px 5px;
            background-color: #f8f9fa;
            border-radius: 3px;
        }
        .result-success {
            color: #198754;
            font-weight: 500;
        }
        .result-error {
            color: #dc3545;
            font-weight: 500;
        }
        .test-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group label {
            font-weight: 500;
            color: #495057;
        }
        .form-group select, .form-group input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-primary {
            background-color: #3C91E6;
            color: white;
        }
        .btn-success {
            background-color: #198754;
            color: white;
        }
        .btn-back {
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 500;
            margin-top: 20px;
        }
        .btn-back i {
            margin-right: 5px;
        }
        .directory-grid {
            display: grid;
            grid-template-columns: 1fr 100px 100px 100px;
            gap: 10px;
            align-items: center;
        }
        .directory-grid div {
            padding: 8px;
        }
        .directory-grid .grid-header {
            font-weight: 500;
            background-color: #f8f9fa;
            padding: 10px 8px;
            border-radius: 4px;
        }
        .directory-status {
            text-align: center;
            border-radius: 4px;
            padding: 3px 5px;
            font-weight: 500;
            font-size: 12px;
        }
        .status-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        .status-error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .image-preview {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        .path-info {
            font-family: monospace;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            width: 100%;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class='bx bx-bug'></i>
            <h1>Upload Debug Tool</h1>
        </div>
        
        <div class="section">
            <h2 class="section-title">Server Information</h2>
            <div class="info-grid">
                <div class="info-label">Operating System</div>
                <div class="info-value"><?php echo $results['server_info']['os']; ?></div>
                
                <div class="info-label">Server Software</div>
                <div class="info-value"><?php echo $results['server_info']['server_software']; ?></div>
                
                <div class="info-label">Document Root</div>
                <div class="info-value"><?php echo $results['server_info']['document_root']; ?></div>
                
                <div class="info-label">Server Type</div>
                <div class="info-value"><?php echo $results['server_info']['server_type']; ?></div>
                
                <div class="info-label">PHP Version</div>
                <div class="info-value"><?php echo $results['server_info']['php_version']; ?></div>
                
                <div class="info-label">File Uploads Enabled</div>
                <div class="info-value"><?php echo $results['server_info']['file_uploads'] ? 'Yes' : 'No'; ?></div>
                
                <div class="info-label">Upload Max Filesize</div>
                <div class="info-value"><?php echo $results['server_info']['upload_max_filesize']; ?></div>
                
                <div class="info-label">Post Max Size</div>
                <div class="info-value"><?php echo $results['server_info']['post_max_size']; ?></div>
                
                <div class="info-label">Temp Directory</div>
                <div class="info-value"><?php echo $results['server_info']['tmp_dir']; ?></div>
                
                <div class="info-label">Temp Directory Writable</div>
                <div class="info-value"><?php echo $results['server_info']['tmp_dir_writable'] ? 'Yes' : 'No'; ?></div>
            </div>
        </div>
        
        <div class="section">
            <h2 class="section-title">Directory Structure</h2>
            <div class="directory-grid">
                <div class="grid-header">Directory Path</div>
                <div class="grid-header">Exists</div>
                <div class="grid-header">Writable</div>
                <div class="grid-header">Created</div>
                
                <?php foreach ($results['directories'] as $category => $dir): ?>
                <div><?php echo $dir['path']; ?></div>
                <div class="directory-status <?php echo $dir['exists'] ? 'status-success' : 'status-error'; ?>">
                    <?php echo $dir['exists'] ? 'Yes' : 'No'; ?>
                </div>
                <div class="directory-status <?php echo $dir['writable'] ? 'status-success' : 'status-error'; ?>">
                    <?php echo $dir['writable'] ? 'Yes' : 'No'; ?>
                </div>
                <div class="directory-status <?php echo isset($dir['created']) ? ($dir['created'] ? 'status-success' : 'status-error') : ''; ?>">
                    <?php echo isset($dir['created']) ? ($dir['created'] ? 'Yes' : 'No') : 'N/A'; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="section">
            <h2 class="section-title">Test File Upload</h2>
            <form class="test-form" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category">Target Category</label>
                    <select id="category" name="category">
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category; ?>"><?php echo ucfirst($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="test_file">Test Image (Optional)</label>
                    <input type="file" id="test_file" name="test_file" accept="image/jpeg, image/png, image/gif">
                    <small>If no file is selected, a test image will be generated automatically.</small>
                </div>
                
                <button type="submit" name="test_upload" value="1" class="btn btn-primary">
                    <i class='bx bx-upload'></i> Run Test Upload
                </button>
            </form>
            
            <?php if (isset($results['test_upload']) && !empty($results['test_upload'])): ?>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                <h3 style="margin-top: 0;">Test Results</h3>
                
                <div class="info-grid">
                    <div class="info-label">Upload Success</div>
                    <div class="<?php echo $results['test_upload']['success'] ? 'result-success' : 'result-error'; ?>">
                        <?php echo $results['test_upload']['success'] ? 'Success' : 'Failed'; ?>
                    </div>
                    
                    <?php if ($results['test_upload']['success']): ?>
                    <div class="info-label">Result Path</div>
                    <div class="info-value"><?php echo $results['test_upload']['result_path']; ?></div>
                    
                    <div class="info-label">File Exists</div>
                    <div class="<?php echo $results['test_upload']['file_exists'] ? 'result-success' : 'result-error'; ?>">
                        <?php echo $results['test_upload']['file_exists'] ? 'Yes' : 'No'; ?>
                    </div>
                    
                    <div class="info-label">Full Path Check</div>
                    <div class="<?php echo $results['test_upload']['full_path_exists'] ? 'result-success' : 'result-error'; ?>">
                        <?php echo $results['test_upload']['full_path_exists'] ? 'Exists' : 'Not Found'; ?>
                    </div>
                    
                    <div class="info-label">Alt Path Check</div>
                    <div class="<?php echo $results['test_upload']['alt_path_exists'] ? 'result-success' : 'result-error'; ?>">
                        <?php echo $results['test_upload']['alt_path_exists'] ? 'Exists' : 'Not Found'; ?>
                    </div>
                    <?php else: ?>
                    <div class="info-label">Error</div>
                    <div class="result-error"><?php echo $results['test_upload']['error']; ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if ($results['test_upload']['success']): ?>
                <div class="image-preview">
                    <h4>Preview</h4>
                    <img src="<?php echo $results['test_upload']['result_path']; ?>" alt="Uploaded Image">
                    <div class="path-info">
Full Path: <?php echo $results['test_upload']['full_path']; ?>
Alt Path: <?php echo $results['test_upload']['alt_path']; ?>
Web Path: <?php echo $results['test_upload']['result_path']; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2 class="section-title">Error Logging</h2>
            <div class="info-grid">
                <div class="info-label">Error Log Path</div>
                <div class="info-value"><?php echo $results['error_log']['error_log_path'] ?: 'Using system default'; ?></div>
                
                <div class="info-label">Error Reporting</div>
                <div class="info-value"><?php echo $results['error_log']['error_reporting']; ?></div>
                
                <div class="info-label">Display Errors</div>
                <div class="info-value"><?php echo $results['error_log']['display_errors'] ? 'On' : 'Off'; ?></div>
                
                <div class="info-label">Log Errors</div>
                <div class="info-value"><?php echo $results['error_log']['log_errors'] ? 'On' : 'Off'; ?></div>
            </div>
            <p style="margin-top: 15px;">A test message has been written to the error log.</p>
        </div>
        
        <a href="../admin/index.php" class="btn-back">
            <i class='bx bx-arrow-back'></i> Back to Admin Dashboard
        </a>
    </div>
</body>
</html>
