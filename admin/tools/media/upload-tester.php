<?php
/**
 * Upload Tester Tool
 * Diagnose and test file uploads across different directories
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Include necessary files
include_once '../../../includes/config.php';
include_once '../../../includes/db.php';
include_once '../../../includes/functions.php';

// Get document root information
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

// Determine project folder from SITE_URL
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // Should be "srms-website"
}

// Define upload categories
$upload_categories = [
    'news' => [
        'directory' => '/assets/images/news',
        'description' => 'News and announcement images',
        'mime_types' => ['image/jpeg', 'image/png', 'image/gif']
    ],
    'events' => [
        'directory' => '/assets/images/events',
        'description' => 'Event photos and banners',
        'mime_types' => ['image/jpeg', 'image/png', 'image/gif']
    ],
    'promotional' => [
        'directory' => '/assets/images/promotional',
        'description' => 'Marketing and promotional materials',
        'mime_types' => ['image/jpeg', 'image/png', 'image/gif']
    ],
    'facilities' => [
        'directory' => '/assets/images/facilities',
        'description' => 'School facility and infrastructure photos',
        'mime_types' => ['image/jpeg', 'image/png', 'image/gif']
    ],
    'campus' => [
        'directory' => '/assets/images/campus',
        'description' => 'Campus and location images',
        'mime_types' => ['image/jpeg', 'image/png', 'image/gif']
    ],
    'documents' => [
        'directory' => '/assets/uploads/documents',
        'description' => 'PDF documents and forms',
        'mime_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    ]
];

// Initialize results
$results = [];

// Process test upload
if (isset($_POST['action']) && $_POST['action'] === 'test_upload') {
    $category = $_POST['category'] ?? 'news';
    
    if (isset($upload_categories[$category])) {
        $category_info = $upload_categories[$category];
        
        // Check if we have an actual upload or need to generate a test file
        if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
            // Real file uploaded
            $results['upload_test'] = testUpload($_FILES['test_file'], $category, $category_info);
        } else {
            // Generate a test file
            $test_file = generateTestFile($category, $category_info);
            $results['upload_test'] = testUpload($test_file, $category, $category_info);
            
            // Clean up the temporary file
            if (isset($test_file['tmp_name']) && file_exists($test_file['tmp_name'])) {
                @unlink($test_file['tmp_name']);
            }
        }
    }
}

// Process directory check
if (isset($_POST['action']) && $_POST['action'] === 'check_directories') {
    $results['directory_check'] = checkUploadDirectories($upload_categories);
}

// Create missing directories
if (isset($_POST['action']) && $_POST['action'] === 'create_directories') {
    $results['directory_creation'] = createMissingDirectories($upload_categories);
}

// Get PHP configuration
$php_config = getPhpConfig();

// Get directory status by default
if (!isset($results['directory_check'])) {
    $results['directory_check'] = checkUploadDirectories($upload_categories);
}

/**
 * Test file upload to a specific category
 */
function testUpload($file, $category, $category_info) {
    $result = [
        'category' => $category,
        'directory' => $category_info['directory'],
        'file' => $file['name'],
        'file_size' => $file['size'],
        'file_type' => $file['type'],
        'success' => false,
        'message' => '',
        'errors' => [],
        'phases' => []
    ];
    
    // Check file size
    $max_size = getMaxUploadSize();
    $result['phases']['size_check'] = [
        'name' => 'File Size Check',
        'success' => $file['size'] <= $max_size,
        'message' => $file['size'] <= $max_size ? 
            'File size is within limits' : 
            'File exceeds maximum upload size of ' . formatBytes($max_size)
    ];
    
    if (!$result['phases']['size_check']['success']) {
        $result['errors'][] = $result['phases']['size_check']['message'];
    }
    
    // Check file type
    $valid_type = in_array($file['type'], $category_info['mime_types']);
    $result['phases']['type_check'] = [
        'name' => 'File Type Check',
        'success' => $valid_type,
        'message' => $valid_type ? 
            'File type is allowed' : 
            'File type ' . $file['type'] . ' is not allowed for this category'
    ];
    
    if (!$result['phases']['type_check']['success']) {
        $result['errors'][] = $result['phases']['type_check']['message'];
    }
    
    // Check directory exists
    $dir_path = getDirectoryPath($category_info['directory']);
    $dir_exists = is_dir($dir_path);
    $result['phases']['directory_check'] = [
        'name' => 'Directory Check',
        'success' => $dir_exists,
        'message' => $dir_exists ? 
            'Directory exists' : 
            'Directory does not exist: ' . $dir_path
    ];
    
    if (!$dir_exists) {
        $result['errors'][] = $result['phases']['directory_check']['message'];
        
        // Try to create the directory
        $created = @mkdir($dir_path, 0755, true);
        $result['phases']['directory_creation'] = [
            'name' => 'Directory Creation',
            'success' => $created,
            'message' => $created ? 
                'Directory created successfully' : 
                'Failed to create directory'
        ];
        
        if (!$created) {
            $result['errors'][] = $result['phases']['directory_creation']['message'];
        }
    }
    
    // Check directory permissions
    if ($dir_exists || (isset($created) && $created)) {
        $writable = is_writable($dir_path);
        $result['phases']['permission_check'] = [
            'name' => 'Permission Check',
            'success' => $writable,
            'message' => $writable ? 
                'Directory is writable' : 
                'Directory is not writable: ' . $dir_path
        ];
        
        if (!$writable) {
            $result['errors'][] = $result['phases']['permission_check']['message'];
        }
    }
    
    // Proceed with upload if everything is OK so far
    if (empty($result['errors'])) {
        // Generate unique filename
        $filename = pathinfo($file['name'], PATHINFO_FILENAME);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
        if (empty($filename)) {
            $filename = 'test_file';
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_name = $filename . '-' . time() . '.' . $extension;
        
        // Full target path
        $target_path = $dir_path . DIRECTORY_SEPARATOR . $unique_name;
        
        // Web path for display
        $web_path = $category_info['directory'] . '/' . $unique_name;
        
        // Attempt file move
        $moved = @move_uploaded_file($file['tmp_name'], $target_path);
        
        $result['phases']['file_move'] = [
            'name' => 'File Upload',
            'success' => $moved,
            'message' => $moved ? 
                'File uploaded successfully' : 
                'Failed to move uploaded file to target location'
        ];
        
        if (!$moved) {
            $error = error_get_last();
            $result['errors'][] = $result['phases']['file_move']['message'] . 
                                 ': ' . ($error ? $error['message'] : 'Unknown error');
        } else {
            // Set permissions on the file
            @chmod($target_path, 0644);
            
            // Verify file exists and is readable
            $file_exists = file_exists($target_path);
            $file_readable = is_readable($target_path);
            
            $result['phases']['file_verification'] = [
                'name' => 'File Verification',
                'success' => $file_exists && $file_readable,
                'message' => $file_exists && $file_readable ? 
                    'File verified successfully' : 
                    'File verification failed'
            ];
            
            if (!$file_exists || !$file_readable) {
                $result['errors'][] = $result['phases']['file_verification']['message'];
            } else {
                // File uploaded successfully!
                $result['success'] = true;
                $result['message'] = 'File uploaded successfully';
                $result['file_path'] = $target_path;
                $result['web_path'] = $web_path;
                $result['web_url'] = SITE_URL . $web_path;
            }
        }
    }
    
    return $result;
}

/**
 * Generate a test file based on category
 */
function generateTestFile($category, $category_info) {
    $result = [
        'name' => 'test_' . $category . '_' . time() . '.png',
        'type' => 'image/png',
        'error' => UPLOAD_ERR_OK,
        'size' => 0
    ];
    
    // Create a temporary file
    $tmp_file = tempnam(sys_get_temp_dir(), 'test_upload');
    $result['tmp_name'] = $tmp_file;
    
    // Create a simple test image
    $width = 800;
    $height = 600;
    
    $im = @imagecreatetruecolor($width, $height);
    if (!$im) {
        $result['error'] = 'Failed to create image resource';
        return $result;
    }
    
    // Pick a color based on category
    $colors = [
        'news' => [59, 130, 246],       // Blue
        'events' => [16, 185, 129],     // Green
        'promotional' => [217, 70, 239], // Purple
        'facilities' => [245, 158, 11], // Amber
        'campus' => [99, 102, 241],     // Indigo
        'documents' => [75, 85, 99]     // Gray
    ];
    
    $color = $colors[$category] ?? [75, 85, 99]; // Gray default
    
    // Create background
    $bg_color = imagecolorallocate($im, $color[0], $color[1], $color[2]);
    imagefilledrectangle($im, 0, 0, $width, $height, $bg_color);
    
    // Add text
    $text_color = imagecolorallocate($im, 255, 255, 255);
    $font_size = 5; // Largest built-in font
    
    // Center the text
    $text = "TEST UPLOAD - " . strtoupper($category);
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    // Draw text
    imagestring($im, $font_size, $x, $y, $text, $text_color);
    
    // Add timestamp
    $timestamp = date('Y-m-d H:i:s');
    $timestamp_width = imagefontwidth(2) * strlen($timestamp);
    imagestring($im, 2, ($width - $timestamp_width) / 2, $height - 30, $timestamp, $text_color);
    
    // Save the image
    imagepng($im, $tmp_file);
    imagedestroy($im);
    
    // Update file size
    $result['size'] = filesize($tmp_file);
    
    return $result;
}

/**
 * Check upload directories
 */
function checkUploadDirectories($upload_categories) {
    $results = [];
    
    foreach ($upload_categories as $category => $info) {
        $dir_path = getDirectoryPath($info['directory']);
        
        $exists = is_dir($dir_path);
        $writable = $exists ? is_writable($dir_path) : false;
        
        // Count files if directory exists
        $file_count = 0;
        $total_size = 0;
        
        if ($exists) {
            $files = glob($dir_path . DIRECTORY_SEPARATOR . "*.*");
            $file_count = count($files);
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $total_size += filesize($file);
                }
            }
        }
        
        $results[$category] = [
            'directory' => $info['directory'],
            'path' => $dir_path,
            'description' => $info['description'],
            'exists' => $exists,
            'writable' => $writable,
            'file_count' => $file_count,
            'total_size' => $total_size,
            'total_size_formatted' => formatBytes($total_size),
            'mime_types' => $info['mime_types']
        ];
    }
    
    return $results;
}

/**
 * Create missing directories
 */
function createMissingDirectories($upload_categories) {
    $results = [];
    
    foreach ($upload_categories as $category => $info) {
        $dir_path = getDirectoryPath($info['directory']);
        
        if (!is_dir($dir_path)) {
            $success = @mkdir($dir_path, 0755, true);
            
            $results[$category] = [
                'directory' => $info['directory'],
                'path' => $dir_path,
                'success' => $success,
                'message' => $success ? 'Directory created successfully' : 'Failed to create directory'
            ];
            
            if (!$success) {
                $error = error_get_last();
                $results[$category]['error'] = $error ? $error['message'] : 'Unknown error';
            }
        } else {
            $results[$category] = [
                'directory' => $info['directory'],
                'path' => $dir_path,
                'success' => true,
                'message' => 'Directory already exists'
            ];
        }
    }
    
    return $results;
}

/**
 * Get PHP configuration for uploads
 */
function getPhpConfig() {
    return [
        'file_uploads' => [
            'name' => 'file_uploads',
            'value' => ini_get('file_uploads') ? 'On' : 'Off',
            'status' => ini_get('file_uploads') ? 'success' : 'error',
            'description' => 'Enables or disables file uploads'
        ],
        'upload_max_filesize' => [
            'name' => 'upload_max_filesize',
            'value' => ini_get('upload_max_filesize'),
            'status' => 'info',
            'description' => 'Maximum allowed file size'
        ],
        'post_max_size' => [
            'name' => 'post_max_size',
            'value' => ini_get('post_max_size'),
            'status' => 'info',
            'description' => 'Maximum allowed POST data size'
        ],
        'max_file_uploads' => [
            'name' => 'max_file_uploads',
            'value' => ini_get('max_file_uploads'),
            'status' => 'info',
            'description' => 'Maximum number of files allowed in a single upload'
        ],
        'memory_limit' => [
            'name' => 'memory_limit',
            'value' => ini_get('memory_limit'),
            'status' => 'info',
            'description' => 'Maximum amount of memory a script can use'
        ],
        'max_execution_time' => [
            'name' => 'max_execution_time',
            'value' => ini_get('max_execution_time') . ' seconds',
            'status' => 'info',
            'description' => 'Maximum execution time of a script'
        ],
        'upload_tmp_dir' => [
            'name' => 'upload_tmp_dir',
            'value' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
            'status' => is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) ? 'success' : 'error',
            'description' => 'Temporary directory for file uploads'
        ]
    ];
}

/**
 * Get the full directory path for a web path
 */
function getDirectoryPath($web_path) {
    global $doc_root, $project_folder;
    
    $dir_path = $doc_root;
    if (!empty($project_folder)) {
        $dir_path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $dir_path .= str_replace('/', DIRECTORY_SEPARATOR, $web_path);
    
    return $dir_path;
}

/**
 * Get the maximum upload file size in bytes
 */
function getMaxUploadSize() {
    $max_upload = convertToBytes(ini_get('upload_max_filesize'));
    $max_post = convertToBytes(ini_get('post_max_size'));
    $memory_limit = convertToBytes(ini_get('memory_limit'));
    
    return min($max_upload, $max_post, $memory_limit);
}

/**
 * Convert PHP ini value to bytes
 */
function convertToBytes($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int)$value;
    
    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    
    return $value;
}

/**
 * Format bytes to human-readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Start output buffer for main content
ob_start();
?>

<div class="upload-tester">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-upload'></i> Upload Tester Tool</h2>
            <p>Test file uploads to ensure proper configuration and permissions</p>
        </div>
        <div class="banner-actions">
            <a href="../index.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Tools
            </a>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="panel" id="system-panel">
        <div class="panel-header">
            <h3><i class='bx bx-cog'></i> PHP Upload Configuration</h3>
            <div class="panel-actions">
                <button type="button" class="panel-toggle" data-target="system-content">
                    <i class='bx bx-chevron-up'></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="system-content">
            <div class="config-grid">
                <?php foreach ($php_config as $key => $config): ?>
                <div class="config-card <?php echo $config['status']; ?>">
                    <div class="config-name"><?php echo $config['name']; ?></div>
                    <div class="config-value"><?php echo $config['value']; ?></div>
                    <div class="config-description"><?php echo $config['description']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="config-summary">
                <div class="summary-item">
                    <span class="summary-label">Max Upload Size:</span>
                    <span class="summary-value"><?php echo formatBytes(getMaxUploadSize()); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Temp Directory:</span>
                    <span class="summary-value">
                        <?php 
                        $tmp_dir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
                        echo $tmp_dir . ' ';
                        if (is_writable($tmp_dir)) {
                            echo '<span class="status-badge success">Writable</span>';
                        } else {
                            echo '<span class="status-badge error">Not Writable</span>';
                        }
                        ?>
                    </span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">File Uploads:</span>
                    <span class="summary-value">
                        <?php if (ini_get('file_uploads')): ?>
                            <span class="status-badge success">Enabled</span>
                        <?php else: ?>
                            <span class="status-badge error">Disabled</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Directory Status -->
    <div class="panel" id="directories-panel">
        <div class="panel-header">
            <h3><i class='bx bx-folder'></i> Upload Directories</h3>
            <div class="panel-actions">
                <button type="button" class="panel-toggle" data-target="directories-content">
                    <i class='bx bx-chevron-up'></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="directories-content">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Directory</th>
                            <th>Status</th>
                            <th>Files</th>
                            <th>Size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['directory_check'] as $category => $info): ?>
                        <tr>
                            <td>
                                <div class="category-name"><?php echo ucfirst($category); ?></div>
                                <div class="category-description"><?php echo $info['description']; ?></div>
                            </td>
                            <td>
                                <div class="path-display" title="<?php echo htmlspecialchars($info['path']); ?>">
                                    <?php echo htmlspecialchars($info['directory']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($info['exists']): ?>
                                    <span class="status-badge success">Exists</span>
                                    <?php if ($info['writable']): ?>
                                        <span class="status-badge success">Writable</span>
                                    <?php else: ?>
                                        <span class="status-badge error">Not Writable</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="status-badge error">Not Found</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $info['file_count']; ?> files</td>
                            <td><?php echo $info['total_size_formatted']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="post" action="" class="inline-form">
                                        <input type="hidden" name="action" value="test_upload">
                                        <input type="hidden" name="category" value="<?php echo $category; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class='bx bx-upload'></i> Test Upload
                                        </button>
                                    </form>
                                    
                                    <?php if (!$info['exists']): ?>
                                    <form method="post" action="" class="inline-form">
                                        <input type="hidden" name="action" value="create_directories">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class='bx bx-folder-plus'></i> Create All Directories
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="directory-actions">
                <form method="post" action="" class="inline-form">
                    <input type="hidden" name="action" value="check_directories">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-refresh'></i> Refresh Directory Status
                    </button>
                </form>
                
                <?php
                // Check if any directories are missing
                $missing_directories = false;
                foreach ($results['directory_check'] as $info) {
                    if (!$info['exists']) {
                        $missing_directories = true;
                        break;
                    }
                }
                
                if ($missing_directories):
                ?>
                <form method="post" action="" class="inline-form">
                    <input type="hidden" name="action" value="create_directories">
                    <button type="submit" class="btn btn-success">
                        <i class='bx bx-folder-plus'></i> Create All Missing Directories
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Test Upload Form -->
    <div class="panel" id="upload-panel">
        <div class="panel-header">
            <h3><i class='bx bx-cloud-upload'></i> Test File Upload</h3>
            <div class="panel-actions">
                <button type="button" class="panel-toggle" data-target="upload-content">
                    <i class='bx bx-chevron-up'></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="upload-content">
            <p class="panel-description">
                Test upload functionality by either uploading your own file or generating a test file automatically.
            </p>
            
            <form method="post" action="" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="action" value="test_upload">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Target Category:</label>
                        <select id="category" name="category" class="form-control">
                            <?php foreach ($upload_categories as $key => $info): ?>
                            <option value="<?php echo $key; ?>"><?php echo ucfirst($key); ?> - <?php echo $info['description']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="test_file">File to Upload (Optional):</label>
                        <input type="file" id="test_file" name="test_file" class="form-control">
                        <div class="form-text">
                            If no file is selected, a test image will be generated automatically.
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-upload'></i> Run Test Upload
                    </button>
                </div>
            </form>
            
            <div class="allowed-types">
                <h4>Allowed File Types by Category</h4>
                <div class="types-grid">
                    <?php foreach ($upload_categories as $category => $info): ?>
                    <div class="type-card">
                        <div class="type-header"><?php echo ucfirst($category); ?></div>
                        <div class="type-list">
                            <?php foreach ($info['mime_types'] as $mime): ?>
                            <div class="type-item">
                                <i class='bx bx-file'></i> <?php echo $mime; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Results -->
    <?php if (isset($results['upload_test'])): ?>
    <div class="panel" id="results-panel">
        <div class="panel-header">
            <h3>
                <i class='bx <?php echo $results['upload_test']['success'] ? 'bx-check-circle' : 'bx-x-circle'; ?>'></i>
                Upload Test Results
            </h3>
            <div class="panel-actions">
                <button type="button" class="panel-toggle" data-target="results-content">
                    <i class='bx bx-chevron-up'></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="results-content">
            <div class="result-summary <?php echo $results['upload_test']['success'] ? 'success' : 'error'; ?>">
                <div class="summary-icon">
                    <i class='bx <?php echo $results['upload_test']['success'] ? 'bx-check-circle' : 'bx-x-circle'; ?>'></i>
                </div>
                <div class="summary-content">
                    <h4><?php echo $results['upload_test']['success'] ? 'Upload Successful' : 'Upload Failed'; ?></h4>
                    <p><?php echo $results['upload_test']['message']; ?></p>
                    
                    <?php if (!empty($results['upload_test']['errors'])): ?>
                    <div class="error-list">
                        <p><strong>Errors:</strong></p>
                        <ul>
                            <?php foreach ($results['upload_test']['errors'] as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="file-details">
                <h4>File Details</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Filename:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($results['upload_test']['file']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">File Size:</span>
                        <span class="detail-value"><?php echo formatBytes($results['upload_test']['file_size']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">File Type:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($results['upload_test']['file_type']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Target Directory:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($results['upload_test']['directory']); ?></span>
                    </div>
                    
                    <?php if ($results['upload_test']['success']): ?>
                    <div class="detail-item">
                        <span class="detail-label">File Path:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($results['upload_test']['file_path']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Web Path:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($results['upload_test']['web_path']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($results['upload_test']['success']): ?>
                <div class="upload-preview">
                    <h4>File Preview</h4>
                    <?php
                    $file_extension = strtolower(pathinfo($results['upload_test']['file'], PATHINFO_EXTENSION));
                    $is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif']);
                    ?>
                    
                    <?php if ($is_image): ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($results['upload_test']['web_url']); ?>" alt="Uploaded Image">
                    </div>
                    <?php else: ?>
                    <div class="file-icon">
                        <i class='bx bx-file'></i>
                        <p>Non-image file uploaded successfully</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="preview-actions">
                        <a href="<?php echo htmlspecialchars($results['upload_test']['web_url']); ?>" target="_blank" class="btn btn-primary">
                            <i class='bx bx-link-external'></i> Open File
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="upload-phases">
                <h4>Upload Process Phases</h4>
                <div class="phases-list">
                    <?php foreach ($results['upload_test']['phases'] as $phase): ?>
                    <div class="phase-item <?php echo $phase['success'] ? 'success' : 'error'; ?>">
                        <div class="phase-icon">
                            <i class='bx <?php echo $phase['success'] ? 'bx-check' : 'bx-x'; ?>'></i>
                        </div>
                        <div class="phase-details">
                            <div class="phase-name"><?php echo $phase['name']; ?></div>
                            <div class="phase-message"><?php echo $phase['message']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Directory Creation Results -->
    <?php if (isset($results['directory_creation']) && !empty($results['directory_creation'])): ?>
    <div class="panel" id="creation-results-panel">
        <div class="panel-header">
            <h3><i class='bx bx-folder-plus'></i> Directory Creation Results</h3>
            <div class="panel-actions">
                <button type="button" class="panel-toggle" data-target="creation-results-content">
                    <i class='bx bx-chevron-up'></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="creation-results-content">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Directory</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['directory_creation'] as $dir => $info): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($info['directory']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $info['success'] ? 'success' : 'error'; ?>">
                                    <?php echo $info['success'] ? 'Success' : 'Failed'; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($info['message']); ?>
                                <?php if (isset($info['error'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($info['error']); ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Troubleshooting Guide -->
    <div class="panel" id="troubleshooting-panel">
        <div class="panel-header">
            <h3><i class='bx bx-help-circle'></i> Troubleshooting Guide</h3>
            <div class="panel-actions">
                <button type="button" class="panel-toggle" data-target="troubleshooting-content">
                    <i class='bx bx-chevron-up'></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="troubleshooting-content">
            <div class="troubleshooting-accordion">
                <div class="accordion-item">
                    <div class="accordion-header">
                        <i class='bx bx-error-circle'></i>
                        <h4>Directory Does Not Exist or Cannot Be Created</h4>
                        <i class='bx bx-chevron-down accordion-toggle'></i>
                    </div>
                    <div class="accordion-content" style="display: none;">
                        <p>If directories don't exist or can't be created, try the following:</p>
                        <ol>
                            <li>
                                <strong>Check Permissions:</strong> Make sure the web server has write permissions to the parent directory.
                                <ul>
                                    <li>On Linux: <code>chmod -R 755 /path/to/parent</code></li>
                                    <li>On Windows: Right-click folder > Properties > Security > Edit permissions</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Create Manually:</strong> Create the directory structure manually using FTP or SSH.
                            </li>
                            <li>
                                <strong>Check Path:</strong> Ensure the path doesn't contain special characters or spaces.
                            </li>
                        </ol>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <i class='bx bx-error-circle'></i>
                        <h4>Directory Exists But Is Not Writable</h4>
                        <i class='bx bx-chevron-down accordion-toggle'></i>
                    </div>
                    <div class="accordion-content" style="display: none;">
                        <p>If a directory exists but isn't writable, try:</p>
                        <ol>
                            <li>
                                <strong>Change Permissions:</strong>
                                <ul>
                                    <li>On Linux: <code>chmod 755 /path/to/directory</code></li>
                                    <li>On Windows: Right-click folder > Properties > Security > Edit permissions</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Change Ownership:</strong> Make sure the directory is owned by the web server user.
                                <ul>
                                    <li>On Linux: <code>chown www-data:www-data /path/to/directory</code></li>
                                </ul>
                            </li>
                            <li>
                                <strong>Check SELinux:</strong> If using SELinux, check if it's blocking write access.
                                <ul>
                                    <li><code>setenforce 0</code> to temporarily disable SELinux</li>
                                    <li><code>chcon -R -t httpd_sys_rw_content_t /path/to/directory</code> to set proper context</li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <i class='bx bx-error-circle'></i>
                        <h4>Failed to Move Uploaded File</h4>
                        <i class='bx bx-chevron-down accordion-toggle'></i>
                    </div>
                    <div class="accordion-content" style="display: none;">
                        <p>If the file upload fails during the move operation:</p>
                        <ol>
                            <li>
                                <strong>Check Temporary Directory:</strong> Make sure the temporary directory is writable.
                                <ul>
                                    <li>Check the 'upload_tmp_dir' PHP setting</li>
                                    <li>Verify permissions on the system's temporary directory</li>
                                </ul>
                            </li>
                            <li>
                                <strong>File Size Limits:</strong> Verify the file doesn't exceed PHP's limits.
                                <ul>
                                    <li>Check 'upload_max_filesize' and 'post_max_size' in php.ini</li>
                                    <li>Remember that 'post_max_size' must be larger than 'upload_max_filesize'</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Disk Space:</strong> Ensure there's enough disk space available.
                            </li>
                            <li>
                                <strong>Safe Mode:</strong> If PHP is running in safe mode, it might restrict uploads.
                            </li>
                        </ol>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <i class='bx bx-error-circle'></i>
                        <h4>File Uploads Disabled in PHP</h4>
                        <i class='bx bx-chevron-down accordion-toggle'></i>
                    </div>
                    <div class="accordion-content" style="display: none;">
                        <p>If file uploads are disabled in PHP:</p>
                        <ol>
                            <li>
                                <strong>Edit php.ini:</strong> Set <code>file_uploads = On</code> in your php.ini file.
                            </li>
                            <li>
                                <strong>Check .htaccess:</strong> See if any .htaccess file is disabling uploads with <code>php_flag file_uploads off</code>.
                            </li>
                            <li>
                                <strong>Restart Web Server:</strong> After making changes to php.ini, restart the web server.
                                <ul>
                                    <li>Apache: <code>sudo service apache2 restart</code></li>
                                    <li>Nginx: <code>sudo service nginx restart</code></li>
                                    <li>Windows: Restart XAMPP/WAMP from control panel</li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <i class='bx bx-error-circle'></i>
                        <h4>Cross-Platform Path Issues</h4>
                        <i class='bx bx-chevron-down accordion-toggle'></i>
                    </div>
                    <div class="accordion-content" style="display: none;">
                        <p>For cross-platform compatibility issues:</p>
                        <ol>
                            <li>
                                <strong>Use DIRECTORY_SEPARATOR:</strong> Always use PHP's <code>DIRECTORY_SEPARATOR</code> constant for file paths.
                            </li>
                            <li>
                                <strong>Normalize Paths:</strong> Use the <code>normalize_image_path()</code> function in functions.php.
                            </li>
                            <li>
                                <strong>Consistent Slashes:</strong> Use forward slashes (/) for web URLs, regardless of OS.
                            </li>
                            <li>
                                <strong>Path Detection:</strong> Use the project directory detection to build correct paths.
                            </li>
                            <li>
                                <strong>Verify Paths:</strong> Use <code>verify_image_exists()</code> to check if a file exists.
                            </li>
                        </ol>
                        <p>Example code:</p>
                        <pre><code>// Building a file path
$dir_path = $doc_root;
if (!empty($project_folder)) {
    $dir_path .= DIRECTORY_SEPARATOR . $project_folder;
}
$dir_path .= str_replace('/', DIRECTORY_SEPARATOR, $web_path);

// Normalize a web path
$normalized_path = normalize_image_path($input_path);</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Panel toggle functionality
    const toggleButtons = document.querySelectorAll('.panel-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetContent = document.getElementById(targetId);
            
            if (targetContent.style.display === 'none') {
                targetContent.style.display = 'block';
                this.querySelector('i').classList.remove('bx-chevron-down');
                this.querySelector('i').classList.add('bx-chevron-up');
            } else {
                targetContent.style.display = 'none';
                this.querySelector('i').classList.remove('bx-chevron-up');
                this.querySelector('i').classList.add('bx-chevron-down');
            }
        });
    });
    
    // Accordion functionality
    const accordionToggles = document.querySelectorAll('.accordion-toggle');
    
    accordionToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const accordionItem = this.closest('.accordion-item');
            const accordionContent = accordionItem.querySelector('.accordion-content');
            
            if (accordionContent.style.display === 'none') {
                accordionContent.style.display = 'block';
                this.classList.remove('bx-chevron-down');
                this.classList.add('bx-chevron-up');
            } else {
                accordionContent.style.display = 'none';
                this.classList.remove('bx-chevron-up');
                this.classList.add('bx-chevron-down');
            }
        });
    });
    
    // File input preview
    const fileInput = document.getElementById('test_file');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            const fileText = this.nextElementSibling;
            
            if (this.files[0]) {
                fileText.textContent = `Selected file: ${fileName} (${formatBytes(this.files[0].size)})`;
            } else {
                fileText.textContent = 'If no file is selected, a test image will be generated automatically.';
            }
        });
    }
    
    // Helper function to format bytes
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
});
</script>

<style>
.upload-tester {
    max-width: 1200px;
    margin: 0 auto;
}

.header-banner {
    background: linear-gradient(120deg, #3C91E6, #0a3060);
    border-radius: 10px;
    color: white;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.banner-content h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
    display: flex;
    align-items: center;
}

.banner-content h2 i {
    margin-right: 10px;
    font-size: 32px;
}

.banner-content p {
    margin: 0;
    opacity: 0.8;
    font-size: 16px;
}

.btn {
    padding: 8px 15px;
    border-radius: 5px;
    border: none;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn i {
    font-size: 16px;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-primary {
    background-color: #3C91E6;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-light {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.panel {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
    overflow: hidden;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.panel-header h3 {
    margin: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    color: #0a3060;
}

.panel-header h3 i {
    margin-right: 10px;
    font-size: 20px;
    color: #3C91E6;
}

.panel-toggle {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.panel-toggle:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.panel-content {
    padding: 20px;
}

.panel-description {
    margin-top: 0;
    margin-bottom: 20px;
    color: #6c757d;
}

.config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.config-card {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid;
}

.config-card.success {
    border-left-color: #28a745;
}

.config-card.error {
    border-left-color: #dc3545;
}

.config-card.info {
    border-left-color: #3C91E6;
}

.config-name {
    font-weight: 500;
    color: #0a3060;
    margin-bottom: 5px;
}

.config-value {
    font-family: monospace;
    background-color: white;
    padding: 5px 10px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.config-description {
    font-size: 13px;
    color: #6c757d;
}

.config-summary {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.summary-item {
    display: flex;
    flex-direction: column;
}

.summary-label {
    font-weight: 500;
    color: #0a3060;
    margin-bottom: 5px;
}

.summary-value {
    display: flex;
    align-items: center;
    gap: 5px;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-badge.warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.status-badge.error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.data-table th {
    background-color: #f8f9fa;
    font-weight: 500;
    color: #0a3060;
}

.category-name {
    font-weight: 500;
    color: #0a3060;
}

.category-description {
    font-size: 13px;
    color: #6c757d;
    margin-top: 3px;
}

.path-display {
    font-family: monospace;
    font-size: 13px;
    background-color: #f8f9fa;
    padding: 5px 8px;
    border-radius: 4px;
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.directory-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.upload-form {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.form-row {
    margin-bottom: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 500;
    color: #0a3060;
    margin-bottom: 5px;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 5px;
    font-size: 14px;
}

.form-text {
    font-size: 13px;
    color: #6c757d;
    margin-top: 5px;
}

.form-actions {
    margin-top: 20px;
}

.allowed-types {
    margin-top: 30px;
}

.allowed-types h4 {
    margin: 0 0 15px 0;
    color: #0a3060;
    font-size: 16px;
}

.types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.type-card {
    background-color: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
}

.type-header {
    background-color: #0a3060;
    color: white;
    padding: 10px 15px;
    font-weight: 500;
}

.type-list {
    padding: 10px 15px;
}

.type-item {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 0;
    font-size: 13px;
    color: #495057;
}

.type-item i {
    color: #3C91E6;
}

.result-summary {
    display: flex;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.result-summary.success {
    background-color: rgba(40, 167, 69, 0.1);
}

.result-summary.error {
    background-color: rgba(220, 53, 69, 0.1);
}

.summary-icon {
    font-size: 32px;
    margin-right: 20px;
}

.result-summary.success .summary-icon {
    color: #28a745;
}

.result-summary.error .summary-icon {
    color: #dc3545;
}

.summary-content h4 {
    margin: 0 0 10px 0;
    color: #0a3060;
}

.summary-content p {
    margin: 0 0 10px 0;
}

.error-list {
    margin-top: 10px;
}

.error-list p {
    margin: 0 0 5px 0;
}

.error-list ul {
    margin: 0;
    padding-left: 20px;
}

.error-list li {
    margin-bottom: 5px;
    color: #dc3545;
}

.file-details {
    margin-bottom: 30px;
}

.file-details h4 {
    margin: 0 0 15px 0;
    color: #0a3060;
    font-size: 16px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}

.detail-item {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
}

.detail-label {
    font-weight: 500;
    color: #0a3060;
    margin-bottom: 5px;
    display: block;
}

.detail-value {
    font-family: monospace;
    word-break: break-all;
}

.upload-preview {
    margin-top: 30px;
    text-align: center;
}

.upload-preview h4 {
    margin-bottom: 15px;
}

.image-preview {
    margin-bottom: 15px;
}

.image-preview img {
    max-width: 100%;
    max-height: 300px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}

.file-icon {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 10px;
}

.file-icon p {
    font-size: 14px;
    margin: 5px 0 0 0;
}

.preview-actions {
    margin-top: 15px;
}

.upload-phases {
    margin-top: 30px;
}

.upload-phases h4 {
    margin: 0 0 15px 0;
    color: #0a3060;
    font-size: 16px;
}

.phases-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.phase-item {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border-radius: 8px;
}

.phase-item.success {
    background-color: rgba(40, 167, 69, 0.1);
}

.phase-item.error {
    background-color: rgba(220, 53, 69, 0.1);
}

.phase-icon {
    font-size: 24px;
    margin-right: 15px;
}

.phase-item.success .phase-icon {
    color: #28a745;
}

.phase-item.error .phase-icon {
    color: #dc3545;
}

.phase-name {
    font-weight: 500;
    margin-bottom: 5px;
}

.phase-item.success .phase-name {
    color: #0a3060;
}

.phase-item.error .phase-name {
    color: #721c24;
}

.phase-message {
    font-size: 14px;
}

.troubleshooting-accordion {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.accordion-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.accordion-header {
    display: flex;
    align-items: center;
    padding: 15px;
    background-color: #f8f9fa;
    cursor: pointer;
}

.accordion-header i:first-child {
    color: #dc3545;
    margin-right: 10px;
    font-size: 20px;
}

.accordion-header h4 {
    margin: 0;
    flex-grow: 1;
    font-size: 16px;
    color: #0a3060;
}

.accordion-toggle {
    font-size: 20px;
    color: #6c757d;
}

.accordion-content {
    padding: 15px;
    border-top: 1px solid #dee2e6;
}

.accordion-content p {
    margin-top: 0;
}

.accordion-content code {
    background-color: #f8f9fa;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}

.accordion-content pre {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
    margin: 15px 0;
}

.accordion-content pre code {
    background-color: transparent;
    padding: 0;
    border-radius: 0;
    display: block;
    white-space: pre;
}

.error-message {
    color: #dc3545;
    font-size: 13px;
    margin-top: 5px;
}

.inline-form {
    display: inline-block;
}

@media (max-width: 768px) {
    .header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .banner-actions {
        margin-top: 15px;
    }
    
    .config-grid, .types-grid, .details-grid {
        grid-template-columns: 1fr;
    }
    
    .directory-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php
$content = ob_get_clean();

// Define the page title and specific CSS/JS
$page_title = 'Upload Tester Tool';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout - need to use a different path for the layout
include '../../layout.php';
?>