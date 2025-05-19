<?php
/**
 * Path Diagnostics Tool
 * A comprehensive tool for diagnosing and fixing path-related issues
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

// Initialize results container
$results = [];

// Handle path test request (AJAX or direct)
if (isset($_POST['action']) && $_POST['action'] === 'test_path') {
    $test_path = $_POST['test_path'] ?? '';
    $results['path_test'] = testImagePath($test_path);
    
    // If this is an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($results['path_test']);
        exit;
    }
}

// Handle directory creation
if (isset($_POST['action']) && $_POST['action'] === 'create_directory') {
    $dir_path = $_POST['dir_path'] ?? '';
    if (!empty($dir_path)) {
        $full_path = $doc_root;
        if (!empty($project_folder)) {
            $full_path .= DIRECTORY_SEPARATOR . $project_folder;
        }
        $full_path .= str_replace('/', DIRECTORY_SEPARATOR, $dir_path);
        
        $created = @mkdir($full_path, 0755, true);
        $results['directory_creation'] = [
            'path' => $full_path,
            'success' => $created,
            'exists' => is_dir($full_path),
            'writable' => is_writable($full_path)
        ];
    }
}

// Function to test various image path scenarios
function testImagePath($path) {
    global $doc_root, $project_folder;
    
    if (empty($path)) {
        return [
            'status' => 'error',
            'message' => 'No path provided'
        ];
    }
    
    // Normalize the path
    $normalized_path = normalize_image_path($path);
    
    // Check different path variations
    $variations = [];
    
    // Path with document root only
    $path1 = $doc_root . str_replace('/', DIRECTORY_SEPARATOR, $normalized_path);
    $variations['document_root'] = [
        'path' => $path1,
        'exists' => file_exists($path1)
    ];
    
    // Path with document root and project folder
    $path2 = $doc_root . DIRECTORY_SEPARATOR . $project_folder . str_replace('/', DIRECTORY_SEPARATOR, $normalized_path);
    $variations['project_root'] = [
        'path' => $path2,
        'exists' => file_exists($path2)
    ];
    
    // Web paths to test
    $web_paths = [
        'direct' => $normalized_path,
        'with_project' => '/' . $project_folder . $normalized_path,
        'site_url' => rtrim(SITE_URL, '/') . $normalized_path
    ];
    
    // Use verify_image_exists function
    $verified = verify_image_exists($normalized_path);
    
    return [
        'original_path' => $path,
        'normalized_path' => $normalized_path,
        'verified' => $verified,
        'variations' => $variations,
        'web_paths' => $web_paths,
        'recommended_path' => $verified ? get_correct_image_url($normalized_path) : ''
    ];
}

// Analyze directory structure
function analyzeDirectoryStructure() {
    global $doc_root, $project_folder;
    
    $directories = [
        '/assets/images/news',
        '/assets/images/events',
        '/assets/images/promotional',
        '/assets/images/facilities',
        '/assets/images/campus',
        '/assets/images/branding',
        '/assets/uploads/temp'
    ];
    
    $dir_results = [];
    
    foreach ($directories as $dir) {
        // Path with project folder
        $path_with_project = $doc_root . DIRECTORY_SEPARATOR . $project_folder . str_replace('/', DIRECTORY_SEPARATOR, $dir);
        $exists_with_project = is_dir($path_with_project);
        $writable_with_project = $exists_with_project ? is_writable($path_with_project) : false;
        
        // Path without project folder
        $path_without_project = $doc_root . str_replace('/', DIRECTORY_SEPARATOR, $dir);
        $exists_without_project = is_dir($path_without_project);
        $writable_without_project = $exists_without_project ? is_writable($path_without_project) : false;
        
        // Count files if directory exists
        $file_count = 0;
        $example_files = [];
        
        if ($exists_with_project) {
            $files = glob($path_with_project . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            $file_count = count($files);
            
            // Get up to 3 example files
            $example_files = array_slice(array_map('basename', $files), 0, 3);
        } elseif ($exists_without_project) {
            $files = glob($path_without_project . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            $file_count = count($files);
            
            // Get up to 3 example files
            $example_files = array_slice(array_map('basename', $files), 0, 3);
        }
        
        $dir_results[$dir] = [
            'path_with_project' => $path_with_project,
            'exists_with_project' => $exists_with_project,
            'writable_with_project' => $writable_with_project,
            
            'path_without_project' => $path_without_project,
            'exists_without_project' => $exists_without_project,
            'writable_without_project' => $writable_without_project,
            
            'file_count' => $file_count,
            'example_files' => $example_files,
            
            'correct_location' => $exists_with_project ? 'with_project' : ($exists_without_project ? 'without_project' : 'missing')
        ];
    }
    
    return $dir_results;
}

// Create test image for a particular directory
function createTestImage($category) {
    global $doc_root, $project_folder;
    
    // Build path
    $dir_path = $doc_root;
    if (!empty($project_folder)) {
        $dir_path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $dir_path .= DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $category;
    
    // Create directory if it doesn't exist
    if (!is_dir($dir_path)) {
        if (!@mkdir($dir_path, 0755, true)) {
            return [
                'success' => false,
                'message' => 'Failed to create directory: ' . $dir_path,
                'directory' => $dir_path
            ];
        }
    }
    
    // Create a test image
    $timestamp = time();
    $filename = 'test_' . $timestamp . '.png';
    $img_path = $dir_path . DIRECTORY_SEPARATOR . $filename;
    $web_path = '/assets/images/' . $category . '/' . $filename;
    
    // Create a 100x100 test image
    $im = @imagecreate(100, 100);
    if (!$im) {
        return [
            'success' => false,
            'message' => 'Failed to create image resource',
            'directory' => $dir_path
        ];
    }
    
    $bg = imagecolorallocate($im, 60, 145, 230);
    $text_color = imagecolorallocate($im, 255, 255, 255);
    imagestring($im, 5, 20, 40, 'Test', $text_color);
    
    $success = @imagepng($im, $img_path);
    imagedestroy($im);
    
    if (!$success) {
        return [
            'success' => false,
            'message' => 'Failed to save image to: ' . $img_path,
            'directory' => $dir_path
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Test image created successfully',
        'directory' => $dir_path,
        'file_path' => $img_path,
        'web_path' => $web_path,
        'file_exists' => file_exists($img_path)
    ];
}

// Handle test image creation
if (isset($_POST['action']) && $_POST['action'] === 'create_test_image') {
    $category = $_POST['category'] ?? 'news';
    $results['test_image'] = createTestImage($category);
    
    // If this is an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($results['test_image']);
        exit;
    }
}

// Get directory structure analysis
$directory_analysis = analyzeDirectoryStructure();

// Get system information
$system_info = [
    'document_root' => $doc_root,
    'site_url' => SITE_URL,
    'project_folder' => $project_folder,
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'php_version' => PHP_VERSION,
    'is_windows' => (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'),
    'directory_separator' => DIRECTORY_SEPARATOR,
    'server_type' => defined('SERVER_TYPE') ? SERVER_TYPE : 'Unknown'
];

// Start output buffer for main content
ob_start();
?>

<div class="path-diagnostics">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-link'></i> Path Diagnostics Tool</h2>
            <p>Diagnose and fix path-related issues in your website</p>
        </div>
        <div class="banner-actions">
            <a href="../index.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Tools
            </a>
        </div>
    </div>
    
    <div class="diagnostic-container">
        <!-- System Information Panel -->
        <div class="panel" id="system-info-panel">
            <div class="panel-header">
                <h3><i class='bx bx-server'></i> System Information</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="system-info-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="system-info-content">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Document Root:</span>
                        <span class="info-value"><?php echo htmlspecialchars($system_info['document_root']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Site URL:</span>
                        <span class="info-value"><?php echo htmlspecialchars($system_info['site_url']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Project Folder:</span>
                        <span class="info-value"><?php echo htmlspecialchars($system_info['project_folder']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Operating System:</span>
                        <span class="info-value"><?php echo htmlspecialchars($system_info['os']); ?> (<?php echo $system_info['is_windows'] ? 'Windows' : 'Unix/Linux'; ?>)</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Server Software:</span>
                        <span class="info-value"><?php echo htmlspecialchars($system_info['server_software']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">PHP Version:</span>
                        <span class="info-value"><?php echo htmlspecialchars($system_info['php_version']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Directory Separator:</span>
                        <span class="info-value"><?php echo htmlspecialchars($system_info['directory_separator']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Server Type:</span>
                        <span class="info-value"><?php echo htmlspecialchars($system_info['server_type']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Path Testing Panel -->
        <div class="panel" id="path-test-panel">
            <div class="panel-header">
                <h3><i class='bx bx-link-alt'></i> Path Testing Tool</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="path-test-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="path-test-content">
                <p class="panel-description">
                    Test any image path to see if it exists in the file system and how it should be properly referenced.
                </p>
                
                <form id="path-test-form" method="post" action="">
                    <input type="hidden" name="action" value="test_path">
                    <div class="input-group">
                        <input type="text" id="test_path" name="test_path" class="form-control" placeholder="/assets/images/news/example.jpg" value="<?php echo isset($_POST['test_path']) ? htmlspecialchars($_POST['test_path']) : ''; ?>">
                        <button type="submit" class="btn btn-primary">Test Path</button>
                    </div>
                </form>
                
                <div id="path-test-results" class="results-area">
                    <?php if (isset($results['path_test'])): ?>
                    <div class="results-content">
                        <h4>Path Test Results</h4>
                        
                        <div class="result-summary">
                            <div class="summary-item">
                                <span class="summary-label">Original Path:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($results['path_test']['original_path']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Normalized Path:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($results['path_test']['normalized_path']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Image Exists:</span>
                                <span class="summary-value <?php echo $results['path_test']['verified'] ? 'success' : 'error'; ?>">
                                    <?php echo $results['path_test']['verified'] ? 'Yes' : 'No'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="result-details">
                            <h5>File System Paths</h5>
                            <div class="detail-grid">
                                <?php foreach ($results['path_test']['variations'] as $type => $variation): ?>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo ucfirst(str_replace('_', ' ', $type)); ?>:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($variation['path']); ?></span>
                                    <span class="detail-badge <?php echo $variation['exists'] ? 'success' : 'error'; ?>">
                                        <?php echo $variation['exists'] ? 'Exists' : 'Not Found'; ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <h5>Web Reference Paths</h5>
                            <div class="detail-grid">
                                <?php foreach ($results['path_test']['web_paths'] as $type => $path): ?>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo ucfirst(str_replace('_', ' ', $type)); ?>:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($path); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <?php if ($results['path_test']['verified']): ?>
                        <div class="recommended-path">
                            <h5>Recommended Path</h5>
                            <code><?php echo htmlspecialchars($results['path_test']['recommended_path']); ?></code>
                            
                            <div class="image-preview">
                                <h5>Image Preview</h5>
                                <img src="<?php echo htmlspecialchars($results['path_test']['recommended_path']); ?>" alt="Image Preview">
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="error-message">
                            <h5>Image Not Found</h5>
                            <p>The specified image could not be found in any of the tested locations.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Directory Structure Panel -->
        <div class="panel" id="directory-structure-panel">
            <div class="panel-header">
                <h3><i class='bx bx-folder'></i> Directory Structure Analysis</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="directory-structure-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="directory-structure-content">
                <p class="panel-description">
                    Analysis of your media directories and their current status.
                </p>
                
                <div class="directory-analysis">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Directory</th>
                                    <th>Exists in Project</th>
                                    <th>Exists in Root</th>
                                    <th>Files</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($directory_analysis as $dir => $info): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dir); ?></td>
                                    <td>
                                        <span class="status-icon <?php echo $info['exists_with_project'] ? 'success' : 'error'; ?>">
                                            <i class='bx <?php echo $info['exists_with_project'] ? 'bx-check' : 'bx-x'; ?>'></i>
                                        </span>
                                        <?php if ($info['exists_with_project']): ?>
                                            <?php echo $info['writable_with_project'] ? 'Writable' : 'Not Writable'; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-icon <?php echo $info['exists_without_project'] ? 'success' : 'error'; ?>">
                                            <i class='bx <?php echo $info['exists_without_project'] ? 'bx-check' : 'bx-x'; ?>'></i>
                                        </span>
                                        <?php if ($info['exists_without_project']): ?>
                                            <?php echo $info['writable_without_project'] ? 'Writable' : 'Not Writable'; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $info['file_count']; ?> files
                                        <?php if (!empty($info['example_files'])): ?>
                                            <span class="tooltip" data-tooltip="<?php echo htmlspecialchars(implode(', ', $info['example_files'])); ?>">
                                                <i class='bx bx-info-circle'></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($info['correct_location'] === 'with_project'): ?>
                                            <span class="status-badge success">Correct Location</span>
                                        <?php elseif ($info['correct_location'] === 'without_project'): ?>
                                            <span class="status-badge warning">Wrong Location</span>
                                        <?php else: ?>
                                            <span class="status-badge error">Missing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($info['correct_location'] === 'missing'): ?>
                                            <form method="post" action="" class="inline-form">
                                                <input type="hidden" name="action" value="create_directory">
                                                <input type="hidden" name="dir_path" value="<?php echo htmlspecialchars($dir); ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">Create</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Get category name from path
                                        $category = basename($dir);
                                        ?>
                                        <form method="post" action="" class="inline-form create-test-form">
                                            <input type="hidden" name="action" value="create_test_image">
                                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Test Image</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php if (isset($results['directory_creation'])): ?>
                <div class="alert <?php echo $results['directory_creation']['success'] ? 'alert-success' : 'alert-danger'; ?> mt-20">
                    <?php if ($results['directory_creation']['success']): ?>
                        <i class='bx bx-check-circle'></i> Directory created successfully: <?php echo htmlspecialchars($results['directory_creation']['path']); ?>
                    <?php else: ?>
                        <i class='bx bx-error-circle'></i> Failed to create directory: <?php echo htmlspecialchars($results['directory_creation']['path']); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($results['test_image'])): ?>
                <div class="alert <?php echo $results['test_image']['success'] ? 'alert-success' : 'alert-danger'; ?> mt-20">
                    <?php if ($results['test_image']['success']): ?>
                        <i class='bx bx-check-circle'></i> Test image created successfully.
                        <div class="test-image-preview">
                            <div class="test-image-details">
                                <p><strong>File:</strong> <?php echo htmlspecialchars(basename($results['test_image']['file_path'])); ?></p>
                                <p><strong>Web Path:</strong> <?php echo htmlspecialchars($results['test_image']['web_path']); ?></p>
                            </div>
                            <div class="test-image-display">
                                <img src="<?php echo htmlspecialchars(SITE_URL . $results['test_image']['web_path']); ?>" alt="Test Image">
                            </div>
                        </div>
                    <?php else: ?>
                        <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($results['test_image']['message']); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recommendations Panel -->
        <div class="panel" id="recommendations-panel">
            <div class="panel-header">
                <h3><i class='bx bx-bulb'></i> Recommendations</h3>
                <div class="panel-actions">
                    <button type="button" class="panel-toggle" data-target="recommendations-content">
                        <i class='bx bx-chevron-up'></i>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="recommendations-content">
                <div class="recommendations">
                    <h4>Path Best Practices</h4>
                    <ul class="recommendations-list">
                        <li>
                            <div class="recommendation-header">
                                <i class='bx bx-check-circle'></i>
                                <h5>Use Forward Slashes in Web Paths</h5>
                            </div>
                            <p>Always use forward slashes (/) in URLs and web references, regardless of operating system.</p>
                            <div class="code-example">
                                <div class="code-header">
                                    <span class="good">Good:</span>
                                </div>
                                <code>/assets/images/news/example.jpg</code>
                                
                                <div class="code-header">
                                    <span class="bad">Bad:</span>
                                </div>
                                <code>\assets\images\news\example.jpg</code>
                            </div>
                        </li>
                        <li>
                            <div class="recommendation-header">
                                <i class='bx bx-check-circle'></i>
                                <h5>Start Paths with a Forward Slash</h5>
                            </div>
                            <p>Web paths should always start with a forward slash to ensure proper resolution.</p>
                            <div class="code-example">
                                <div class="code-header">
                                    <span class="good">Good:</span>
                                </div>
                                <code>/assets/images/news/example.jpg</code>
                                
                                <div class="code-header">
                                    <span class="bad">Bad:</span>
                                </div>
                                <code>assets/images/news/example.jpg</code>
                            </div>
                        </li>
                        <li>
                            <div class="recommendation-header">
                                <i class='bx bx-check-circle'></i>
                                <h5>Use Project Folder in PHP</h5>
                            </div>
                            <p>When working with file operations in PHP, include the project folder in your paths.</p>
                            <div class="code-example">
                                <pre><code>$doc_root = $_SERVER['DOCUMENT_ROOT'];
$project_folder = 'srms-website';
$file_path = $doc_root . DIRECTORY_SEPARATOR . $project_folder . DIRECTORY_SEPARATOR . 
             'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'news' . 
             DIRECTORY_SEPARATOR . 'example.jpg';</code></pre>
                            </div>
                        </li>
                        <li>
                            <div class="recommendation-header">
                                <i class='bx bx-check-circle'></i>
                                <h5>Use normalize_image_path() Function</h5>
                            </div>
                            <p>Always use the normalize_image_path() function to handle path formatting consistently.</p>
                            <div class="code-example">
                                <pre><code>$path = '/assets/images/news/example.jpg';
$normalized_path = normalize_image_path($path);
// Now use $normalized_path in your code</code></pre>
                            </div>
                        </li>
                    </ul>
                    
                    <h4>Directory Structure Fix</h4>
                    <p>
                        If your directories are in the wrong location (outside the project folder), 
                        you can move them to the correct location:
                    </p>
                    <ol class="fix-steps">
                        <li>Create directories in the correct location (inside the project folder)</li>
                        <li>Copy files from the incorrect location to the correct location</li>
                        <li>Update database references to point to the correct paths</li>
                    </ol>
                    <a href="../maintenance/fix-paths.php" class="btn btn-primary">
                        <i class='bx bx-wrench'></i> Run Path Fixer Tool
                    </a>
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
    
    // AJAX path testing
    const pathTestForm = document.getElementById('path-test-form');
    const pathTestResults = document.getElementById('path-test-results');
    
    if (pathTestForm) {
        pathTestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.textContent = 'Testing...';
            submitButton.disabled = true;
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                
                // Build and display results
                let resultsHTML = `
                    <div class="results-content">
                        <h4>Path Test Results</h4>
                        
                        <div class="result-summary">
                            <div class="summary-item">
                                <span class="summary-label">Original Path:</span>
                                <span class="summary-value">${data.original_path}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Normalized Path:</span>
                                <span class="summary-value">${data.normalized_path}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Image Exists:</span>
                                <span class="summary-value ${data.verified ? 'success' : 'error'}">
                                    ${data.verified ? 'Yes' : 'No'}
                                </span>
                            </div>
                        </div>
                        
                        <div class="result-details">
                            <h5>File System Paths</h5>
                            <div class="detail-grid">
                `;
                
                for (const [type, variation] of Object.entries(data.variations)) {
                    resultsHTML += `
                        <div class="detail-item">
                            <span class="detail-label">${type.replace('_', ' ').charAt(0).toUpperCase() + type.replace('_', ' ').slice(1)}:</span>
                            <span class="detail-value">${variation.path}</span>
                            <span class="detail-badge ${variation.exists ? 'success' : 'error'}">
                                ${variation.exists ? 'Exists' : 'Not Found'}
                            </span>
                        </div>
                    `;
                }
                
                resultsHTML += `
                            </div>
                            
                            <h5>Web Reference Paths</h5>
                            <div class="detail-grid">
                `;
                
                for (const [type, path] of Object.entries(data.web_paths)) {
                    resultsHTML += `
                        <div class="detail-item">
                            <span class="detail-label">${type.replace('_', ' ').charAt(0).toUpperCase() + type.replace('_', ' ').slice(1)}:</span>
                            <span class="detail-value">${path}</span>
                        </div>
                    `;
                }
                
                resultsHTML += `
                            </div>
                        </div>
                `;
                
                if (data.verified) {
                    resultsHTML += `
                        <div class="recommended-path">
                            <h5>Recommended Path</h5>
                            <code>${data.recommended_path}</code>
                            
                            <div class="image-preview">
                                <h5>Image Preview</h5>
                                <img src="${data.recommended_path}" alt="Image Preview">
                            </div>
                        </div>
                    `;
                } else {
                    resultsHTML += `
                        <div class="error-message">
                            <h5>Image Not Found</h5>
                            <p>The specified image could not be found in any of the tested locations.</p>
                        </div>
                    `;
                }
                
                resultsHTML += `</div>`;
                
                pathTestResults.innerHTML = resultsHTML;
            })
            .catch(error => {
                console.error('Error:', error);
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                pathTestResults.innerHTML = `
                    <div class="alert alert-danger">
                        <i class='bx bx-error-circle'></i> An error occurred while testing the path.
                    </div>
                `;
            });
        });
    }
    
    // AJAX test image creation
    const testImageForms = document.querySelectorAll('.create-test-form');
    
    testImageForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.textContent = 'Creating...';
            submitButton.disabled = true;
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                
                // Create alert element
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert ${data.success ? 'alert-success' : 'alert-danger'} mt-20`;
                
                if (data.success) {
                    alertDiv.innerHTML = `
                        <i class='bx bx-check-circle'></i> Test image created successfully.
                        <div class="test-image-preview">
                            <div class="test-image-details">
                                <p><strong>File:</strong> ${basename(data.file_path)}</p>
                                <p><strong>Web Path:</strong> ${data.web_path}</p>
                            </div>
                            <div class="test-image-display">
                                <img src="${window.location.origin}/srms-website${data.web_path}" alt="Test Image">
                            </div>
                        </div>
                    `;
                } else {
                    alertDiv.innerHTML = `
                        <i class='bx bx-error-circle'></i> ${data.message}
                    `;
                }
                
                // Find panel content and append alert
                const panel = this.closest('.panel');
                const panelContent = panel.querySelector('.panel-content');
                
                // Remove any existing alerts
                const existingAlerts = panelContent.querySelectorAll('.alert');
                existingAlerts.forEach(alert => alert.remove());
                
                // Add new alert
                panelContent.appendChild(alertDiv);
                
                // Scroll to the alert
                alertDiv.scrollIntoView({ behavior: 'smooth' });
            })
            .catch(error => {
                console.error('Error:', error);
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                
                // Show error alert
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger mt-20';
                alertDiv.innerHTML = `
                    <i class='bx bx-error-circle'></i> An error occurred while creating the test image.
                `;
                
                // Find panel content and append alert
                const panel = this.closest('.panel');
                const panelContent = panel.querySelector('.panel-content');
                
                // Remove any existing alerts
                const existingAlerts = panelContent.querySelectorAll('.alert');
                existingAlerts.forEach(alert => alert.remove());
                
                // Add new alert
                panelContent.appendChild(alertDiv);
            });
        });
    });
    
    // Helper function to get the basename of a path
    function basename(path) {
        return path.split('\\').pop().split('/').pop();
    }
});
</script>

<style>
.path-diagnostics {
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

.diagnostic-container {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.panel {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.info-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 5px;
}

.info-value {
    font-family: monospace;
    overflow-wrap: break-word;
    word-break: break-all;
}

.input-group {
    display: flex;
    gap: 10px;
}

.form-control {
    flex-grow: 1;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 5px;
    font-size: 14px;
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

.btn-primary {
    background-color: #3C91E6;
    color: white;
}

.btn-light {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.results-area {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.results-content {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 20px;
}

.results-content h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #0a3060;
}

.result-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.summary-item {
    flex: 1;
    min-width: 200px;
    background-color: white;
    padding: 10px;
    border-radius: 5px;
    border-left: 3px solid #3C91E6;
}

.summary-label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
    color: #495057;
}

.summary-value {
    font-family: monospace;
    word-break: break-all;
}

.summary-value.success {
    color: #28a745;
}

.summary-value.error {
    color: #dc3545;
}

.result-details {
    margin-bottom: 20px;
}

.result-details h5 {
    margin: 15px 0 10px 0;
    color: #0a3060;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 5px;
}

.detail-grid {
    display: grid;
    gap: 10px;
}

.detail-item {
    display: grid;
    grid-template-columns: 150px 1fr auto;
    background-color: white;
    padding: 8px 10px;
    border-radius: 5px;
    align-items: center;
}

.detail-label {
    font-weight: 500;
    color: #495057;
}

.detail-value {
    font-family: monospace;
    overflow-wrap: break-word;
    word-break: break-all;
}

.detail-badge {
    padding: 3px 8px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
}

.detail-badge.success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.detail-badge.error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.recommended-path {
    margin-top: 20px;
    padding: 15px;
    background-color: rgba(40, 167, 69, 0.05);
    border-radius: 5px;
    border-left: 3px solid #28a745;
}

.recommended-path h5 {
    margin-top: 0;
    color: #28a745;
}

.recommended-path code {
    display: block;
    padding: 10px;
    background-color: white;
    border-radius: 5px;
    font-family: monospace;
    margin: 10px 0;
    overflow-wrap: break-word;
    word-break: break-all;
}

.image-preview {
    margin-top: 15px;
    text-align: center;
}

.image-preview h5 {
    margin: 0 0 10px 0;
}

.image-preview img {
    max-width: 100%;
    max-height: 200px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    background-color: white;
}

.error-message {
    margin-top: 20px;
    padding: 15px;
    background-color: rgba(220, 53, 69, 0.05);
    border-radius: 5px;
    border-left: 3px solid #dc3545;
}

.error-message h5 {
    margin-top: 0;
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
    color: #495057;
}

.status-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin-right: 5px;
}

.status-icon.success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-icon.error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
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

.inline-form {
    display: inline-block;
    margin-right: 5px;
}

.tooltip {
    position: relative;
    display: inline-block;
    cursor: help;
}

.tooltip:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 10px;
    background-color: #333;
    color: white;
    border-radius: 5px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
}

.alert i {
    margin-right: 10px;
    font-size: 20px;
    flex-shrink: 0;
}

.alert-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.2);
}

.mt-20 {
    margin-top: 20px;
}

.test-image-preview {
    margin-top: 15px;
    display: flex;
    gap: 20px;
    background-color: white;
    padding: 15px;
    border-radius: 5px;
}

.test-image-details {
    flex: 1;
}

.test-image-details p {
    margin: 5px 0;
}

.test-image-display {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
}

.test-image-display img {
    max-width: 100%;
    max-height: 150px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}

.recommendations {
    padding: 10px;
}

.recommendations h4 {
    margin: 0 0 15px 0;
    color: #0a3060;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 8px;
}

.recommendations-list {
    list-style: none;
    padding: 0;
    margin: 0 0 30px 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.recommendation-header {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.recommendation-header i {
    font-size: 20px;
    margin-right: 10px;
    color: #28a745;
}

.recommendation-header h5 {
    margin: 0;
    color: #0a3060;
}

.code-example {
    margin: 10px 0;
    border-radius: 5px;
    overflow: hidden;
    border: 1px solid #dee2e6;
}

.code-header {
    background-color: #f8f9fa;
    padding: 8px 15px;
    border-bottom: 1px solid #dee2e6;
}

.good {
    color: #28a745;
    font-weight: 500;
}

.bad {
    color: #dc3545;
    font-weight: 500;
}

.code-example code {
    display: block;
    padding: 10px 15px;
    font-family: monospace;
    background-color: white;
}

.code-example pre {
    margin: 0;
    padding: 10px 15px;
    font-family: monospace;
    background-color: white;
    overflow-x: auto;
}

.fix-steps {
    margin: 0 0 20px 0;
    padding-left: 20px;
}

.fix-steps li {
    margin-bottom: 8px;
}

@media (max-width: 768px) {
    .header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .banner-actions {
        margin-top: 15px;
    }
    
    .detail-item {
        grid-template-columns: 100px 1fr;
    }
    
    .detail-badge {
        grid-column: span 2;
        justify-self: start;
        margin-top: 5px;
    }
    
    .test-image-preview {
        flex-direction: column;
    }
}
</style>

<?php
$content = ob_get_clean();

// Define the page title and specific CSS/JS
$page_title = 'Path Diagnostics Tool';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout - need to use a different path for the layout
include '../../layout.php';
?>