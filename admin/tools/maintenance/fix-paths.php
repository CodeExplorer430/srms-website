<?php
/**
 * Path Fixing Tool
 * Diagnoses and repairs file and directory path issues in the SRMS website
 * Updated for Hostinger compatibility
 * 
 * This tool helps administrators identify and fix common path-related problems
 * such as missing directories, incorrect file references, and path inconsistencies
 * between different operating systems and hosting environments.
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Enhanced login redirect logic for different environments
    $login_redirect = 'login.php';
    
    if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
        // Production (Hostinger) - admin is at document root
        $login_redirect = '/admin/login.php';
    } else {
        // Development - navigate up from tools/maintenance
        $login_redirect = '../../login.php';
    }
    
    header('Location: ' . $login_redirect);
    exit;
}

/**
 * Enhanced environment-aware path resolution for Hostinger
 */
function get_site_root() {
    // Check if we're in production (Hostinger)
    if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
        // On Hostinger, the site root is the document root
        return rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    }
    
    // Development environment - navigate up from admin/tools/maintenance
    $current_dir = __DIR__;
    $site_root = dirname(dirname(dirname($current_dir)));
    
    // Verify this is the correct directory by checking for key files
    $key_files = ['includes/config.php', 'includes/db.php', 'environment.php'];
    $valid_root = true;
    
    foreach ($key_files as $file) {
        if (!file_exists($site_root . DIRECTORY_SEPARATOR . $file)) {
            $valid_root = false;
            break;
        }
    }
    
    if (!$valid_root) {
        // Fallback: try to detect from document root
        $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        
        // Check if we're in a subdirectory like 'srms-website'
        $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        if (preg_match('#^/([^/]+)/#', $script_path, $matches)) {
            $potential_project = $matches[1];
            $potential_root = $doc_root . DIRECTORY_SEPARATOR . $potential_project;
            
            // Verify this potential root
            $valid_potential = true;
            foreach ($key_files as $file) {
                if (!file_exists($potential_root . DIRECTORY_SEPARATOR . $file)) {
                    $valid_potential = false;
                    break;
                }
            }
            
            if ($valid_potential) {
                return $potential_root;
            }
        }
        
        // Last fallback for development
        return $doc_root . DIRECTORY_SEPARATOR . 'srms-website';
    }
    
    return $site_root;
}

// Get the site root directory
$site_root = get_site_root();

// Include necessary files using absolute paths
$required_files = [
    'config.php' => $site_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.php',
    'db.php' => $site_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db.php',
    'functions.php' => $site_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php'
];

foreach ($required_files as $name => $path) {
    if (!file_exists($path)) {
        die("Critical Error: Required file '{$name}' not found at: {$path}");
    }
    include_once $path;
}

// Enhanced project folder detection for different environments
function get_project_folder() {
    if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
        // On Hostinger, there's no project folder - site is at document root
        return '';
    }
    
    // Development environment - extract project folder from SITE_URL
    if (defined('SITE_URL')) {
        if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
            return $matches[1]; // Should be "srms-website"
        }
    }
    
    return 'srms-website'; // Fallback for development
}

$project_folder = get_project_folder();

// Document root with consistent format
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

// Define media directories to check and fix
$media_directories = [
    'branding' => '/assets/images/branding',
    'news' => '/assets/images/news',
    'events' => '/assets/images/events',
    'promotional' => '/assets/images/promotional',
    'facilities' => '/assets/images/facilities',
    'campus' => '/assets/images/campus',
    'people' => '/assets/images/people'
];

// Initialize arrays for results
$directory_results = [];
$path_results = [];
$test_images = [];
$fixed_functions = [];

// Check if user requested a specific action
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
$category = isset($_GET['category']) ? $_GET['category'] : (isset($_POST['category']) ? $_POST['category'] : '');

// Process create directory action
if ($action === 'create_directory' && !empty($category)) {
    if (isset($media_directories[$category])) {
        $dir_path = $site_root; // Use site_root instead of building path with doc_root + project_folder
        $dir_path .= str_replace('/', DIRECTORY_SEPARATOR, $media_directories[$category]);
        
        if (!is_dir($dir_path)) {
            $created = @mkdir($dir_path, 0755, true);
            $status_message = $created ? 
                "Successfully created directory: $category" : 
                "Failed to create directory: $category. Check server permissions.";
        } else {
            $status_message = "Directory already exists: $category";
        }
    }
}

// Process create test image action
if ($action === 'create_test_image' && !empty($category)) {
    if (isset($media_directories[$category])) {
        $dir_path = $site_root; // Use site_root instead of building path
        $dir_path .= str_replace('/', DIRECTORY_SEPARATOR, $media_directories[$category]);
        
        // Ensure directory exists
        if (!is_dir($dir_path)) {
            @mkdir($dir_path, 0755, true);
        }
        
        // Create a simple test image
        $timestamp = time();
        $file_name = "test_{$category}_{$timestamp}.png";
        $img_path = $dir_path . DIRECTORY_SEPARATOR . $file_name;
        
        // Generate a simple colored image with text
        $width = 200;
        $height = 150;
        $image = imagecreatetruecolor($width, $height);
        
        // Set colors based on category
        switch ($category) {
            case 'branding':
                $bg_color = imagecolorallocate($image, 0, 51, 153); // Dark blue
                break;
            case 'news':
                $bg_color = imagecolorallocate($image, 204, 0, 0); // Red
                break;
            case 'events':
                $bg_color = imagecolorallocate($image, 0, 153, 51); // Green
                break;
            case 'facilities':
                $bg_color = imagecolorallocate($image, 153, 102, 0); // Brown
                break;
            case 'campus':
                $bg_color = imagecolorallocate($image, 102, 0, 204); // Purple
                break;
            default:
                $bg_color = imagecolorallocate($image, 51, 51, 51); // Dark gray
        }
        
        $text_color = imagecolorallocate($image, 255, 255, 255); // White
        
        // Fill background
        imagefill($image, 0, 0, $bg_color);
        
        // Add text
        $text = "Test Image";
        $category_text = ucfirst($category);
        $timestamp_text = date('Y-m-d H:i:s');
        
        // Center text
        $font_size = 5; // Built-in font size (1-5)
        imagestring($image, $font_size, ($width - imagefontwidth($font_size) * strlen($text)) / 2, 
                   $height / 2 - 20, $text, $text_color);
        imagestring($image, 3, ($width - imagefontwidth(3) * strlen($category_text)) / 2, 
                   $height / 2, $category_text, $text_color);
        imagestring($image, 2, ($width - imagefontwidth(2) * strlen($timestamp_text)) / 2, 
                   $height / 2 + 20, $timestamp_text, $text_color);
        
        // Save image
        $success = imagepng($image, $img_path);
        imagedestroy($image);
        
        if ($success) {
            // Generate web URL for the image
            $web_path = $media_directories[$category] . '/' . $file_name;
            
            // Convert to proper URL
            $url = SITE_URL . $web_path;
            
            $test_images[] = [
                'category' => $category,
                'path' => $img_path,
                'url' => $url,
                'filename' => $file_name
            ];
            
            $status_message = "Successfully created test image for $category category.";
        } else {
            $status_message = "Failed to create test image for $category category. Check directory permissions.";
        }
    }
}

// Scan directories and check status
foreach ($media_directories as $category => $dir) {
    // Build correct path using site_root
    $correct_path = $site_root . str_replace('/', DIRECTORY_SEPARATOR, $dir);
    
    $exists = is_dir($correct_path);
    $writable = $exists ? is_writable($correct_path) : false;
    
    // Count files if directory exists
    $file_count = 0;
    $files_list = [];
    
    if ($exists) {
        // Use pattern matching to find image files
        $pattern = $correct_path . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}";
        $files = glob($pattern, GLOB_BRACE);
        
        if ($files !== false) {
            $file_count = count($files);
            // Get sample of files (up to 5)
            $sample_files = array_slice($files, 0, 5);
            
            foreach ($sample_files as $file) {
                // Convert to web URL
                $rel_path = str_replace($site_root, '', $file);
                $rel_path = str_replace(DIRECTORY_SEPARATOR, '/', $rel_path);
                
                if (substr($rel_path, 0, 1) !== '/') {
                    $rel_path = '/' . $rel_path;
                }
                
                $url = SITE_URL . $rel_path;
                
                $files_list[] = [
                    'filename' => basename($file),
                    'url' => $url
                ];
            }
        }
    }
    
    // Store results
    $directory_results[$category] = [
        'path' => [
            'correct' => $correct_path
        ],
        'exists' => $exists,
        'writable' => $writable,
        'file_count' => $file_count,
        'files' => $files_list
    ];
}

// Generate fixed functions for common path issues (updated for Hostinger compatibility)
$fixed_functions = [
    'normalize_image_path' => '
// Enhanced normalize_image_path function with Hostinger compatibility
function normalize_image_path($path) {
    if (empty($path)) return \'\';
    
    // If it\'s a URL, extract just the path part
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $path = parse_url($path, PHP_URL_PATH);
    }
    
    // Remove project folder prefix if present in development
    if (defined(\'IS_PRODUCTION\') && IS_PRODUCTION) {
        // In production (Hostinger), no project folder to remove
    } else {
        // In development, get project folder from SITE_URL
        $project_folder = \'\';
        if (preg_match(\'#/([^/]+)$#\', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
            $project_folder = $matches[1];
        }
        
        // Remove project folder prefix if present
        if (!empty($project_folder) && stripos($path, \'/\' . $project_folder . \'/\') === 0) {
            $path = substr($path, strlen(\'/\' . $project_folder));
        }
    }
    
    // Ensure path starts with slash and uses forward slashes
    $path = \'/\' . ltrim(str_replace(\'\\\\\', \'/\', $path), \'/\');
    
    // Clean up double slashes
    $path = preg_replace(\'#/+#\', \'/\', $path);
    
    return $path;
}',

    'get_correct_image_url' => '
// Get correct image URL with Hostinger compatibility  
function get_correct_image_url($image_path) {
    if (empty($image_path)) {
        return SITE_URL . \'/assets/images/branding/logo-primary.png\'; // Default fallback
    }
    
    // Normalize path (handle slashes, etc.)
    $path = normalize_image_path($image_path);
    
    // Check if path is already a full URL
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }
    
    // Build the complete URL (simpler for Hostinger)
    return SITE_URL . $path;
}',

    'verify_image_exists' => '
// Improved cross-platform image existence verification with Hostinger support
function verify_image_exists($image_path) {
    if (empty($image_path)) return false;
    
    // Normalize path
    $image_path = normalize_image_path($image_path);
    
    // Get server root
    $server_root = rtrim($_SERVER[\'DOCUMENT_ROOT\'], \'/\\\\\');
    
    // Build path to check
    $full_path = $server_root . str_replace(\'/\', DIRECTORY_SEPARATOR, $image_path);
    
    return file_exists($full_path);
}'
];

// Start output buffer for main content
ob_start();
?>

<div class="path-fix-tool">
    <div class="tool-header">
        <div class="header-icon">
            <i class='bx bx-wrench'></i>
        </div>
        <div class="header-title">
            <h2>Path Fix Tool</h2>
            <p>Diagnose and repair image path issues across your website</p>
        </div>
    </div>
    
    <?php if(isset($status_message)): ?>
    <div class="alert <?php echo strpos($status_message, 'Successfully') !== false ? 'alert-success' : 'alert-warning'; ?>">
        <i class='bx bx-<?php echo strpos($status_message, 'Successfully') !== false ? 'check-circle' : 'error-circle'; ?>'></i>
        <span><?php echo $status_message; ?></span>
    </div>
    <?php endif; ?>
    
    <div class="system-info">
        <h3><i class='bx bx-server'></i> System Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Document Root:</div>
                <div class="info-value"><?php echo htmlspecialchars($doc_root); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Site Root:</div>
                <div class="info-value"><?php echo htmlspecialchars($site_root); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Project Folder:</div>
                <div class="info-value"><?php echo htmlspecialchars($project_folder ?: 'None (Production)'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Site URL:</div>
                <div class="info-value"><?php echo htmlspecialchars(SITE_URL); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Environment:</div>
                <div class="info-value"><?php echo (defined('IS_PRODUCTION') && IS_PRODUCTION) ? 'Production (Hostinger)' : 'Development'; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Operating System:</div>
                <div class="info-value"><?php echo htmlspecialchars(PHP_OS); ?> (<?php echo IS_WINDOWS ? 'Windows' : 'Linux/Unix'; ?>)</div>
            </div>
            <div class="info-item">
                <div class="info-label">Directory Separator:</div>
                <div class="info-value"><?php echo DIRECTORY_SEPARATOR; ?></div>
            </div>
        </div>
    </div>
    
    <div class="directory-status">
        <h3><i class='bx bx-folder'></i> Directory Status</h3>
        
        <div class="status-table">
            <div class="table-header">
                <div class="col-category">Category</div>
                <div class="col-path">Path</div>
                <div class="col-exists">Exists</div>
                <div class="col-writable">Writable</div>
                <div class="col-files">Files</div>
                <div class="col-actions">Actions</div>
            </div>
            
            <?php foreach($directory_results as $category => $info): ?>
            <div class="table-row">
                <div class="col-category"><?php echo ucfirst($category); ?></div>
                <div class="col-path">
                    <div class="path-display"><?php echo htmlspecialchars($info['path']['correct']); ?></div>
                </div>
                <div class="col-exists">
                    <?php if($info['exists']): ?>
                    <span class="status-badge success">Yes</span>
                    <?php else: ?>
                    <span class="status-badge error">No</span>
                    <?php endif; ?>
                </div>
                <div class="col-writable">
                    <?php if($info['exists']): ?>
                        <?php if($info['writable']): ?>
                        <span class="status-badge success">Yes</span>
                        <?php else: ?>
                        <span class="status-badge error">No</span>
                        <?php endif; ?>
                    <?php else: ?>
                    <span class="status-badge neutral">N/A</span>
                    <?php endif; ?>
                </div>
                <div class="col-files">
                    <span class="file-count"><?php echo $info['file_count']; ?></span>
                    <?php if($info['file_count'] > 0): ?>
                    <button type="button" class="btn-view-files" data-category="<?php echo $category; ?>">
                        <i class='bx bx-images'></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="col-actions">
                    <?php if(!$info['exists']): ?>
                    <a href="?action=create_directory&category=<?php echo $category; ?>" class="btn btn-sm btn-primary">
                        <i class='bx bx-folder-plus'></i> Create
                    </a>
                    <?php else: ?>
                    <a href="?action=create_test_image&category=<?php echo $category; ?>" class="btn btn-sm btn-secondary">
                        <i class='bx bx-image-add'></i> Test Image
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php if(!empty($test_images)): ?>
    <div class="test-images">
        <h3><i class='bx bx-image'></i> Generated Test Images</h3>
        <div class="image-grid">
            <?php foreach($test_images as $image): ?>
            <div class="image-card">
                <div class="image-preview">
                    <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="Test Image">
                </div>
                <div class="image-info">
                    <div class="image-category"><?php echo ucfirst($image['category']); ?></div>
                    <div class="image-filename"><?php echo htmlspecialchars($image['filename']); ?></div>
                </div>
                <div class="image-path">
                    <?php echo htmlspecialchars($image['url']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="fixed-functions">
        <h3><i class='bx bx-code-alt'></i> Fixed Path Helper Functions</h3>
        <p class="section-description">
            Copy these improved functions to your <code>functions.php</code> file to fix common path-related issues.
            These functions are updated for Hostinger compatibility.
        </p>
        
        <div class="function-list">
            <?php foreach($fixed_functions as $name => $code): ?>
            <div class="function-item">
                <div class="function-header">
                    <h4><?php echo $name; ?></h4>
                    <button class="btn-copy" data-code="<?php echo htmlspecialchars($name); ?>">
                        <i class='bx bx-copy'></i> Copy
                    </button>
                </div>
                <div class="function-code">
                    <pre><code class="language-php"><?php echo htmlspecialchars($code); ?></code></pre>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="recommendations">
        <h3><i class='bx bx-bulb'></i> Path Recommendations for <?php echo (defined('IS_PRODUCTION') && IS_PRODUCTION) ? 'Hostinger Production' : 'Development'; ?></h3>
        
        <?php if (defined('IS_PRODUCTION') && IS_PRODUCTION): ?>
        <!-- Production (Hostinger) Recommendations -->
        <div class="recommendation-item">
            <h4>1. Hostinger Production Environment</h4>
            <p>Your site is running on Hostinger production. All files are located directly in the document root without a project folder.</p>
        </div>
        
        <div class="recommendation-item">
            <h4>2. Simplified Path Structure</h4>
            <p>In production, use direct paths:</p>
            <pre><code class="language-php">$path = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/news/image.jpg';</code></pre>
        </div>
        
        <div class="recommendation-item">
            <h4>3. URL Generation</h4>
            <p>Generate URLs directly without project folder consideration:</p>
            <pre><code class="language-php">$url = SITE_URL . '/assets/images/news/image.jpg';</code></pre>
        </div>
        
        <?php else: ?>
        <!-- Development Recommendations -->
        <div class="recommendation-item">
            <h4>1. Development Environment</h4>
            <p>You're in development mode. Always include the project folder (<code><?php echo $project_folder; ?></code>) when constructing file paths.</p>
        </div>
        
        <div class="recommendation-item">
            <h4>2. Include Project Folder Check</h4>
            <p>When constructing paths, always check if the project folder needs to be included:</p>
            <pre><code class="language-php">$path = $_SERVER['DOCUMENT_ROOT'];
if (!empty($project_folder)) {
    $path .= DIRECTORY_SEPARATOR . $project_folder;
}
$path .= str_replace('/', DIRECTORY_SEPARATOR, $dir);</code></pre>
        </div>
        <?php endif; ?>
        
        <div class="recommendation-item">
            <h4><?php echo (defined('IS_PRODUCTION') && IS_PRODUCTION) ? '4' : '3'; ?>. Use Directory Separator Constant</h4>
            <p>Always use PHP's <code>DIRECTORY_SEPARATOR</code> constant when working with filesystem paths to ensure cross-platform compatibility.</p>
        </div>
        
        <div class="recommendation-item">
            <h4><?php echo (defined('IS_PRODUCTION') && IS_PRODUCTION) ? '5' : '4'; ?>. Verify Image Existence</h4>
            <p>Use the improved <code>verify_image_exists()</code> function that checks the correct paths for your environment.</p>
        </div>
        
        <div class="recommendation-item">
            <h4><?php echo (defined('IS_PRODUCTION') && IS_PRODUCTION) ? '6' : '5'; ?>. Use Forward Slashes in URLs</h4>
            <p>Always convert backslashes to forward slashes when generating URLs:</p>
            <pre><code class="language-php">$url_path = str_replace(DIRECTORY_SEPARATOR, '/', $file_path);</code></pre>
        </div>
    </div>
</div>

<!-- Modal for viewing files -->
<div class="modal" id="filesModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Files in <span id="modalCategoryName"></span> Directory</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- File previews will be loaded here -->
        </div>
    </div>
</div>

<style>
.path-fix-tool {
    font-family: 'Poppins', sans-serif;
    max-width: 1200px;
    margin: 0 auto;
}

.tool-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.header-icon {
    background-color: rgba(60, 145, 230, 0.1);
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
}

.header-icon i {
    font-size: 30px;
    color: #3C91E6;
}

.header-title h2 {
    margin: 0 0 5px 0;
    font-size: 24px;
    color: #0a3060;
}

.header-title p {
    margin: 0;
    color: #6c757d;
}

.alert {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert i {
    font-size: 20px;
    margin-right: 10px;
    flex-shrink: 0;
}

.alert-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #856404;
    border-left: 4px solid #ffc107;
}

.system-info, .directory-status, .test-images, .fixed-functions, .recommendations {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 20px;
    margin-bottom: 30px;
}

.system-info h3, .directory-status h3, .test-images h3, .fixed-functions h3, .recommendations h3 {
    margin: 0 0 20px 0;
    color: #0a3060;
    font-size: 18px;
    display: flex;
    align-items: center;
}

.system-info h3 i, .directory-status h3 i, .test-images h3 i, .fixed-functions h3 i, .recommendations h3 i {
    margin-right: 10px;
    font-size: 20px;
    color: #3C91E6;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.info-label {
    font-weight: 500;
    color: #495057;
    width: 150px;
    flex-shrink: 0;
}

.info-value {
    font-family: monospace;
    color: #6c757d;
    word-break: break-all;
}

.status-table {
    border: 1px solid #dee2e6;
    border-radius: 5px;
    overflow: hidden;
}

.table-header {
    display: grid;
    grid-template-columns: 100px 1fr 80px 80px 100px 120px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-weight: 500;
    color: #495057;
}

.table-row {
    display: grid;
    grid-template-columns: 100px 1fr 80px 80px 100px 120px;
    border-bottom: 1px solid #dee2e6;
}

.table-row:last-child {
    border-bottom: none;
}

.table-header > div, .table-row > div {
    padding: 10px;
    display: flex;
    align-items: center;
}

.path-display {
    font-family: monospace;
    font-size: 12px;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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

.status-badge.error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.status-badge.neutral {
    background-color: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.file-count {
    font-weight: 500;
    margin-right: 5px;
}

.btn-view-files {
    background: none;
    border: none;
    color: #3C91E6;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 5px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    border-radius: 5px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-primary {
    background-color: #3C91E6;
    color: white;
}

.btn-primary:hover {
    background-color: #2e73b8;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.image-card {
    background-color: #f8f9fa;
    border-radius: 5px;
    overflow: hidden;
}

.image-preview {
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-bottom: 1px solid #dee2e6;
}

.image-preview img {
    max-width: 100%;
    max-height: 100%;
}

.image-info {
    padding: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.image-category {
    font-weight: 500;
    color: #0a3060;
    background-color: rgba(60, 145, 230, 0.1);
    padding: 3px 8px;
    border-radius: 50px;
    font-size: 12px;
}

.image-filename {
    font-size: 12px;
    color: #6c757d;
}

.image-path {
    padding: 10px;
    font-family: monospace;
    font-size: 12px;
    color: #6c757d;
    background-color: rgba(0, 0, 0, 0.03);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.section-description {
    color: #6c757d;
    margin-bottom: 20px;
}

.function-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.function-item {
    background-color: #f8f9fa;
    border-radius: 5px;
    overflow: hidden;
}

.function-header {
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: rgba(60, 145, 230, 0.1);
}

.function-header h4 {
    margin: 0;
    color: #0a3060;
    font-size: 16px;
}

.btn-copy {
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 500;
    color: #495057;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.function-code {
    padding: 15px;
    overflow-x: auto;
}

.function-code pre {
    margin: 0;
}

.function-code code {
    font-family: monospace;
    font-size: 13px;
    line-height: 1.5;
    color: #495057;
}

.recommendation-item {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
    border-left: 4px solid #3C91E6;
}

.recommendation-item h4 {
    margin: 0 0 10px 0;
    color: #0a3060;
    font-size: 16px;
}

.recommendation-item p {
    margin: 0 0 15px 0;
    color: #495057;
}

.recommendation-item pre {
    margin: 0;
    background-color: white;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}

.recommendation-item code {
    font-family: monospace;
    font-size: 13px;
    line-height: 1.5;
    color: #495057;
}

.recommendation-item:last-child p {
    margin-bottom: 0;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: white;
    border-radius: 10px;
    width: 80%;
    max-width: 800px;
    max-height: 80%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #dee2e6;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #0a3060;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
}

.file-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
}

.file-item {
    display: flex;
    flex-direction: column;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    overflow: hidden;
}

.file-preview {
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
}

.file-preview img {
    max-width: 100%;
    max-height: 100%;
}

.file-name {
    padding: 8px;
    font-size: 12px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border-top: 1px solid #dee2e6;
}

@media (max-width: 992px) {
    .table-header, .table-row {
        grid-template-columns: 100px 1fr 80px 80px 100px;
    }
    
    .col-path {
        grid-column: 1 / -1;
        border-bottom: 1px solid #dee2e6;
    }
    
    .col-actions {
        grid-column: span 2;
    }
}

@media (max-width: 768px) {
    .table-header, .table-row {
        display: flex;
        flex-wrap: wrap;
    }
    
    .table-header > div, .table-row > div {
        width: 50%;
    }
    
    .col-path {
        width: 100%;
    }
    
    .image-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 576px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .image-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File Modal Functionality
    const modal = document.getElementById('filesModal');
    const modalClose = document.querySelector('.modal-close');
    const modalCategoryName = document.getElementById('modalCategoryName');
    const modalBody = document.getElementById('modalBody');
    const viewFileButtons = document.querySelectorAll('.btn-view-files');
    
    // Show modal when a "View Files" button is clicked
    viewFileButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            showFilesModal(category);
        });
    });
    
    // Close modal
    modalClose.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Show files in modal
    function showFilesModal(category) {
        modalCategoryName.textContent = category.charAt(0).toUpperCase() + category.slice(1);
        
        // Create HTML for files grid
        let filesHTML = '<div class="file-grid">';
        
        <?php foreach($directory_results as $cat => $info): ?>
        if ('<?php echo $cat; ?>' === category) {
            <?php foreach($info['files'] as $file): ?>
            filesHTML += `
                <div class="file-item">
                    <div class="file-preview">
                        <img src="<?php echo htmlspecialchars($file['url']); ?>" alt="<?php echo htmlspecialchars($file['filename']); ?>">
                    </div>
                    <div class="file-name"><?php echo htmlspecialchars($file['filename']); ?></div>
                </div>
            `;
            <?php endforeach; ?>
        }
        <?php endforeach; ?>
        
        filesHTML += '</div>';
        
        modalBody.innerHTML = filesHTML;
        modal.style.display = 'flex';
    }
    
    // Copy function code
    const copyButtons = document.querySelectorAll('.btn-copy');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const funcName = this.getAttribute('data-code');
            let codeText = '';
            
            <?php foreach($fixed_functions as $name => $code): ?>
            if (funcName === '<?php echo $name; ?>') {
                codeText = `<?php echo str_replace('`', '\`', $code); ?>`;
            }
            <?php endforeach; ?>
            
            navigator.clipboard.writeText(codeText)
                .then(() => {
                    // Change button text temporarily
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="bx bx-check"></i> Copied!';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy text: ', err);
                });
        });
    });
});
</script>

<?php
$content = ob_get_clean();

// Set page title and specific CSS/JS
$page_title = 'Path Fix Tool';
$page_specific_css = []; 
$page_specific_js = [];

// Enhanced layout include logic for different environments
if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
    // Production (Hostinger) - layout is in admin folder
    $layout_path = $_SERVER['DOCUMENT_ROOT'] . '/admin/layout.php';
} else {
    // Development - navigate up from tools/maintenance to admin
    $layout_path = '../../layout.php';
}

// Verify layout file exists before including
if (!file_exists($layout_path)) {
    // Fallback attempts
    $fallback_paths = [
        __DIR__ . '/../../layout.php',
        $_SERVER['DOCUMENT_ROOT'] . '/admin/layout.php',
        $_SERVER['DOCUMENT_ROOT'] . '/srms-website/admin/layout.php'
    ];
    
    foreach ($fallback_paths as $fallback) {
        if (file_exists($fallback)) {
            $layout_path = $fallback;
            break;
        }
    }
}

// Final check and include
if (file_exists($layout_path)) {
    include $layout_path;
} else {
    // Emergency fallback - render without layout
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title; ?> | SRMS Admin</title>
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    </head>
    <body>
        <div style="padding: 20px;">
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <strong>Warning:</strong> Layout file not found. Running in emergency mode.
                <br><small>Attempted paths: <?php echo implode(', ', array_merge([$layout_path], $fallback_paths ?? [])); ?></small>
            </div>
            <?php echo $content; ?>
        </div>
    </body>
    </html>
    <?php
}
?>