<?php
/**
 * Image Diagnostics Tool
 * Comprehensive tool for diagnosing and fixing image display and path issues
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

// Initialize database connection
$db = new Database();

// Get document root and project folder
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // Should be "srms-website"
}

// Define image categories
$image_categories = [
    'news' => [
        'directory' => '/assets/images/news',
        'db_table' => 'news',
        'image_field' => 'image',
        'description' => 'News images used for articles and announcements'
    ],
    'events' => [
        'directory' => '/assets/images/events',
        'db_table' => null,
        'image_field' => null,
        'description' => 'Event-related images and photos'
    ],
    'facilities' => [
        'directory' => '/assets/images/facilities',
        'db_table' => 'facilities',
        'image_field' => 'image',
        'description' => 'School facility images'
    ],
    'campus' => [
        'directory' => '/assets/images/campus',
        'db_table' => null,
        'image_field' => null,
        'description' => 'Campus photos and location images'
    ],
    'branding' => [
        'directory' => '/assets/images/branding',
        'db_table' => 'school_information',
        'image_field' => 'logo',
        'description' => 'Logos and branding assets'
    ],
    'promotional' => [
        'directory' => '/assets/images/promotional',
        'db_table' => null,
        'image_field' => null,
        'description' => 'Promotional banners and marketing assets'
    ]
];

// Initialize results array
$results = [];

// Process scan request
if (isset($_POST['action']) && $_POST['action'] === 'scan_directory') {
    $category = $_POST['category'] ?? 'news';
    
    if (isset($image_categories[$category])) {
        $results['directory_scan'] = scanImageDirectory($category, $image_categories[$category]);
    }
}

// Process database check request
if (isset($_POST['action']) && $_POST['action'] === 'check_database') {
    $category = $_POST['category'] ?? 'news';
    
    if (isset($image_categories[$category]) && $image_categories[$category]['db_table'] !== null) {
        $results['database_check'] = checkDatabaseImages($category, $image_categories[$category]);
    }
}

// Process image path test
if (isset($_POST['action']) && $_POST['action'] === 'test_image') {
    $image_path = $_POST['image_path'] ?? '';
    
    if (!empty($image_path)) {
        $results['image_test'] = testImagePath($image_path);
    }
}

// Process placeholder image creation
if (isset($_POST['action']) && $_POST['action'] === 'create_placeholder') {
    $category = $_POST['category'] ?? 'news';
    
    if (isset($image_categories[$category])) {
        $results['placeholder_creation'] = createPlaceholder($category, $image_categories[$category]);
    }
}

// Process directory creation
if (isset($_POST['action']) && $_POST['action'] === 'create_directory') {
    $category = $_POST['category'] ?? 'news';
    
    if (isset($image_categories[$category])) {
        $results['directory_creation'] = createDirectory($category, $image_categories[$category]);
    }
}

// Generate directory statistics
$directory_stats = getDirectoryStats($image_categories);

// Get database statistics
$database_stats = getDatabaseStats($image_categories);

/**
 * Scan a specific image directory
 */
function scanImageDirectory($category, $category_info) {
    global $doc_root, $project_folder;
    
    $dir_path = $doc_root;
    if (!empty($project_folder)) {
        $dir_path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $dir_path .= str_replace('/', DIRECTORY_SEPARATOR, $category_info['directory']);
    
    $result = [
        'category' => $category,
        'directory' => $category_info['directory'],
        'full_path' => $dir_path,
        'exists' => is_dir($dir_path),
        'writable' => is_dir($dir_path) ? is_writable($dir_path) : false,
        'images' => [],
        'total_count' => 0,
        'total_size' => 0
    ];
    
    if (is_dir($dir_path)) {
        $files = glob($dir_path . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        
        $result['images'] = [];
        $result['total_count'] = count($files);
        $result['total_size'] = 0;
        
        foreach ($files as $file) {
            $size = filesize($file);
            $result['total_size'] += $size;
            
            // Only include details for the first 20 files to avoid excessive output
            if (count($result['images']) < 20) {
                $filename = basename($file);
                $web_path = $category_info['directory'] . '/' . $filename;
                
                // Test if this image is properly accessible
                $image_exists = verify_image_exists($web_path);
                
                $result['images'][] = [
                    'filename' => $filename,
                    'path' => $file,
                    'web_path' => $web_path,
                    'size' => $size,
                    'size_formatted' => formatBytes($size),
                    'modified' => filemtime($file),
                    'modified_formatted' => date('Y-m-d H:i:s', filemtime($file)),
                    'accessible' => $image_exists
                ];
            }
        }
        
        $result['total_size_formatted'] = formatBytes($result['total_size']);
    }
    
    return $result;
}

/**
 * Check database entries for a specific category
 */
function checkDatabaseImages($category, $category_info) {
    global $db;
    
    $result = [
        'category' => $category,
        'table' => $category_info['db_table'],
        'field' => $category_info['image_field'],
        'entries' => [],
        'total_count' => 0,
        'valid_count' => 0,
        'invalid_count' => 0
    ];
    
    if ($category_info['db_table'] !== null && $category_info['image_field'] !== null) {
        $entries = $db->fetch_all("SELECT * FROM {$category_info['db_table']} LIMIT 50");
        
        $result['total_count'] = count($entries);
        $result['valid_count'] = 0;
        $result['invalid_count'] = 0;
        
        foreach ($entries as $entry) {
            if (isset($entry[$category_info['image_field']]) && !empty($entry[$category_info['image_field']])) {
                $image_path = $entry[$category_info['image_field']];
                $exists = verify_image_exists($image_path);
                
                if ($exists) {
                    $result['valid_count']++;
                } else {
                    $result['invalid_count']++;
                }
                
                $result['entries'][] = [
                    'id' => $entry['id'] ?? 'unknown',
                    'name' => isset($entry['name']) ? $entry['name'] : (isset($entry['title']) ? $entry['title'] : 'Unknown'),
                    'image_path' => $image_path,
                    'exists' => $exists,
                    'web_url' => get_correct_image_url($image_path)
                ];
            }
        }
    }
    
    return $result;
}

/**
 * Test a specific image path
 */
function testImagePath($image_path) {
    global $doc_root, $project_folder;
    
    $result = [
        'original_path' => $image_path,
        'normalized_path' => normalize_image_exists($image_path),
        'paths_checked' => [],
        'exists' => false,
        'recommended_url' => ''
    ];
    
    // Check different path combinations
    $paths_to_check = [
        // Path with document root only
        'document_root' => $doc_root . str_replace('/', DIRECTORY_SEPARATOR, $image_path),
        
        // Path with document root and project folder
        'project_path' => $doc_root . DIRECTORY_SEPARATOR . $project_folder . 
                         str_replace('/', DIRECTORY_SEPARATOR, $image_path)
    ];
    
    foreach ($paths_to_check as $type => $path) {
        $exists = file_exists($path);
        
        $result['paths_checked'][$type] = [
            'path' => $path,
            'exists' => $exists
        ];
        
        if ($exists) {
            $result['exists'] = true;
        }
    }
    
    // Use built-in functions for verification
    $verified = verify_image_exists($image_path);
    $result['verified'] = $verified;
    
    if ($verified) {
        $result['recommended_url'] = get_correct_image_url($image_path);
    }
    
    return $result;
}

/**
 * Create a placeholder image for a category
 */
function createPlaceholder($category, $category_info) {
    global $doc_root, $project_folder;
    
    $dir_path = $doc_root;
    if (!empty($project_folder)) {
        $dir_path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $dir_path .= str_replace('/', DIRECTORY_SEPARATOR, $category_info['directory']);
    
    $result = [
        'category' => $category,
        'directory' => $category_info['directory'],
        'full_path' => $dir_path,
        'success' => false,
        'message' => ''
    ];
    
    // Create directory if it doesn't exist
    if (!is_dir($dir_path)) {
        if (!mkdir($dir_path, 0755, true)) {
            $result['message'] = "Failed to create directory: {$dir_path}";
            return $result;
        }
    }
    
    // Create placeholder image
    $placeholder_path = $dir_path . DIRECTORY_SEPARATOR . 'placeholder-' . $category . '.png';
    $width = 800;
    $height = 600;
    
    // Create image resource
    $im = @imagecreatetruecolor($width, $height);
    if (!$im) {
        $result['message'] = "Failed to create image resource";
        return $result;
    }
    
    // Set colors based on category
    $colors = [
        'news' => [59, 130, 246],       // Blue
        'events' => [16, 185, 129],     // Green
        'facilities' => [245, 158, 11], // Amber
        'campus' => [99, 102, 241],     // Indigo
        'branding' => [239, 68, 68],    // Red
        'promotional' => [217, 70, 239] // Purple
    ];
    
    $color = $colors[$category] ?? [75, 85, 99]; // Gray default
    
    // Create background gradient
    $bg_color = imagecolorallocate($im, $color[0], $color[1], $color[2]);
    $bg_color_light = imagecolorallocate($im, 
        min($color[0] + 40, 255), 
        min($color[1] + 40, 255), 
        min($color[2] + 40, 255)
    );
    
    // Fill with gradient-like background
    imagefill($im, 0, 0, $bg_color);
    
    // Draw a pattern
    for ($i = 0; $i < $width; $i += 20) {
        for ($j = 0; $j < $height; $j += 20) {
            if (($i + $j) % 40 == 0) {
                imagefilledrectangle($im, $i, $j, $i + 10, $j + 10, $bg_color_light);
            }
        }
    }
    
    // Add text
    $text_color = imagecolorallocate($im, 255, 255, 255);
    $font_size = 5; // Largest built-in font
    
    // Center the text
    $text = strtoupper($category) . " PLACEHOLDER";
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    // Add white rectangle behind text for better readability
    imagefilledrectangle($im, 
        $x - 10, 
        $y - 10, 
        $x + $text_width + 10, 
        $y + $text_height + 10, 
        imagecolorallocatealpha($im, 255, 255, 255, 80)
    );
    
    // Draw text
    imagestring($im, $font_size, $x, $y, $text, $text_color);
    
    // Save the image
    $success = imagepng($im, $placeholder_path);
    imagedestroy($im);
    
    if ($success) {
        $result['success'] = true;
        $result['message'] = "Successfully created placeholder image";
        $result['image_path'] = $placeholder_path;
        $result['web_path'] = $category_info['directory'] . '/placeholder-' . $category . '.png';
    } else {
        $result['message'] = "Failed to save image to {$placeholder_path}";
    }
    
    return $result;
}

/**
 * Create a directory for a category
 */
function createDirectory($category, $category_info) {
    global $doc_root, $project_folder;
    
    $dir_path = $doc_root;
    if (!empty($project_folder)) {
        $dir_path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $dir_path .= str_replace('/', DIRECTORY_SEPARATOR, $category_info['directory']);
    
    $result = [
        'category' => $category,
        'directory' => $category_info['directory'],
        'full_path' => $dir_path,
        'success' => false,
        'message' => '',
        'existed' => is_dir($dir_path)
    ];
    
    if ($result['existed']) {
        $result['success'] = true;
        $result['message'] = "Directory already exists";
    } else {
        $success = @mkdir($dir_path, 0755, true);
        
        if ($success) {
            $result['success'] = true;
            $result['message'] = "Successfully created directory";
        } else {
            $result['message'] = "Failed to create directory: {$dir_path}";
        }
    }
    
    return $result;
}

/**
 * Get statistics for all image directories
 */
function getDirectoryStats($image_categories) {
    global $doc_root, $project_folder;
    
    $stats = [];
    
    foreach ($image_categories as $category => $info) {
        $dir_path = $doc_root;
        if (!empty($project_folder)) {
            $dir_path .= DIRECTORY_SEPARATOR . $project_folder;
        }
        $dir_path .= str_replace('/', DIRECTORY_SEPARATOR, $info['directory']);
        
        $stats[$category] = [
            'directory' => $info['directory'],
            'description' => $info['description'],
            'exists' => is_dir($dir_path),
            'writable' => is_dir($dir_path) ? is_writable($dir_path) : false,
            'file_count' => 0,
            'total_size' => 0,
            'has_placeholder' => false
        ];
        
        if (is_dir($dir_path)) {
            $files = glob($dir_path . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            $stats[$category]['file_count'] = count($files);
            
            $total_size = 0;
            foreach ($files as $file) {
                $total_size += filesize($file);
                
                // Check if a placeholder image exists
                if (strpos(basename($file), 'placeholder') !== false) {
                    $stats[$category]['has_placeholder'] = true;
                }
            }
            
            $stats[$category]['total_size'] = $total_size;
            $stats[$category]['total_size_formatted'] = formatBytes($total_size);
        }
    }
    
    return $stats;
}

/**
 * Get statistics for image fields in database tables
 */
function getDatabaseStats($image_categories) {
    global $db;
    
    $stats = [];
    
    foreach ($image_categories as $category => $info) {
        if ($info['db_table'] !== null && $info['image_field'] !== null) {
            try {
                $total_count = $db->fetch_row("SELECT COUNT(*) as count FROM {$info['db_table']}")['count'];
                
                $image_count = $db->fetch_row("SELECT COUNT(*) as count FROM {$info['db_table']} 
                                              WHERE {$info['image_field']} IS NOT NULL 
                                              AND {$info['image_field']} != ''")['count'];
                
                $stats[$category] = [
                    'table' => $info['db_table'],
                    'field' => $info['image_field'],
                    'total_entries' => $total_count,
                    'entries_with_images' => $image_count,
                    'percentage' => $total_count > 0 ? round(($image_count / $total_count) * 100) : 0
                ];
            } catch (Exception $e) {
                $stats[$category] = [
                    'table' => $info['db_table'],
                    'field' => $info['image_field'],
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    return $stats;
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

<div class="image-diagnostics">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-image-check'></i> Image Diagnostics Tool</h2>
            <p>Diagnose and fix image-related issues across your website</p>
        </div>
        <div class="banner-actions">
            <a href="../index.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Tools
            </a>
        </div>
    </div>
    
    <!-- Dashboard Summary -->
    <div class="dashboard-summary">
        <div class="summary-header">
            <h3>Image Categories Overview</h3>
            <p>Status of image directories and database references</p>
        </div>
        
        <div class="summary-grid">
            <?php foreach ($directory_stats as $category => $stats): ?>
                <div class="summary-card <?php echo $stats['exists'] ? ($stats['writable'] ? 'success' : 'warning') : 'error'; ?>">
                    <div class="card-icon">
                        <i class='bx bx-folder-open'></i>
                    </div>
                    <div class="card-content">
                        <h4><?php echo ucfirst($category); ?></h4>
                        <p class="description"><?php echo $stats['description']; ?></p>
                        
                        <?php if ($stats['exists']): ?>
                            <div class="stats">
                                <div class="stat-item">
                                    <span class="stat-label">Files:</span>
                                    <span class="stat-value"><?php echo $stats['file_count']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Size:</span>
                                    <span class="stat-value"><?php echo $stats['total_size_formatted']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Placeholder:</span>
                                    <span class="stat-value"><?php echo $stats['has_placeholder'] ? 'Yes' : 'No'; ?></span>
                                </div>
                            </div>
                            
                            <div class="quick-actions">
                                <form method="post" action="" class="inline-form">
                                    <input type="hidden" name="action" value="scan_directory">
                                    <input type="hidden" name="category" value="<?php echo $category; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class='bx bx-search'></i> Scan
                                    </button>
                                </form>
                                
                                <?php if (isset($image_categories[$category]['db_table'])): ?>
                                <form method="post" action="" class="inline-form">
                                    <input type="hidden" name="action" value="check_database">
                                    <input type="hidden" name="category" value="<?php echo $category; ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary">
                                        <i class='bx bx-data'></i> Check DB
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if (!$stats['has_placeholder']): ?>
                                <form method="post" action="" class="inline-form">
                                    <input type="hidden" name="action" value="create_placeholder">
                                    <input type="hidden" name="category" value="<?php echo $category; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class='bx bx-image-add'></i> Add Placeholder
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="error-message">
                                <i class='bx bx-error-circle'></i> Directory doesn't exist
                            </div>
                            
                            <div class="quick-actions">
                                <form method="post" action="" class="inline-form">
                                    <input type="hidden" name="action" value="create_directory">
                                    <input type="hidden" name="category" value="<?php echo $category; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class='bx bx-folder-plus'></i> Create Directory
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Image Path Tester -->
    <div class="panel" id="image-tester-panel">
        <div class="panel-header">
            <h3><i class='bx bx-link-alt'></i> Image Path Tester</h3>
            <div class="panel-actions">
                <button type="button" class="panel-toggle" data-target="image-tester-content">
                    <i class='bx bx-chevron-up'></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="image-tester-content">
            <p class="panel-description">
                Test if an image path is correctly formatted and accessible
            </p>
            
            <form method="post" action="" id="image-test-form" class="test-form">
                <input type="hidden" name="action" value="test_image">
                <div class="form-row">
                    <div class="form-group">
                        <label for="image_path">Image Path:</label>
                        <input type="text" name="image_path" id="image_path" class="form-control" 
                               placeholder="/assets/images/category/image.jpg" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-check'></i> Test Path
                        </button>
                    </div>
                </div>
                
                <div class="form-help">
                    <p>Examples:</p>
                    <ul>
                        <li><code>/assets/images/news/example.jpg</code></li>
                        <li><code>/assets/images/branding/logo-primary.png</code></li>
                    </ul>
                </div>
            </form>
            
            <?php if (isset($results['image_test'])): ?>
            <div class="test-results">
                <h4>Path Test Results</h4>
                
                <div class="result-overview">
                    <div class="result-status <?php echo $results['image_test']['verified'] ? 'success' : 'error'; ?>">
                        <i class='bx <?php echo $results['image_test']['verified'] ? 'bx-check-circle' : 'bx-x-circle'; ?>'></i>
                        <span><?php echo $results['image_test']['verified'] ? 'Image Exists' : 'Image Not Found'; ?></span>
                    </div>
                    
                    <div class="path-details">
                        <div class="detail-item">
                            <span class="detail-label">Original Path:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($results['image_test']['original_path']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Normalized Path:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($results['image_test']['normalized_path']); ?></span>
                        </div>
                        
                        <?php if ($results['image_test']['verified']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Recommended URL:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($results['image_test']['recommended_url']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($results['image_test']['verified']): ?>
                <div class="image-preview">
                    <h5>Image Preview</h5>
                    <img src="<?php echo htmlspecialchars($results['image_test']['recommended_url']); ?>" 
                         alt="Image preview">
                </div>
                <?php endif; ?>
                
                <div class="paths-checked">
                    <h5>Paths Checked</h5>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Path Type</th>
                                <th>Full Path</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['image_test']['paths_checked'] as $type => $path_info): ?>
                            <tr>
                                <td><?php echo ucfirst(str_replace('_', ' ', $type)); ?></td>
                                <td>
                                    <div class="path-display"><?php echo htmlspecialchars($path_info['path']); ?></div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $path_info['exists'] ? 'success' : 'error'; ?>">
                                        <?php echo $path_info['exists'] ? 'Found' : 'Not Found'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!$results['image_test']['verified']): ?>
                <div class="recommendations">
                    <h5>Recommendations</h5>
                    <ul>
                        <li>Check the path spelling and make sure it's correct</li>
                        <li>Ensure the image exists in one of the checked locations</li>
                        <li>Try with a leading slash: <code>/assets/images/...</code></li>
                        <li>Check directory permissions</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Directory Scan Results -->
    <?php if (isset($results['directory_scan'])): ?>
    <div class="panel" id="scan-results-panel">
        <div class="panel-header">
            <h3><i class='bx bx-folder'></i> Directory Scan Results: <?php echo ucfirst($results['directory_scan']['category']); ?></h3>
            <div class="panel-actions">
                <button type="button" class="panel-toggle" data-target="scan-results-content">
                    <i class='bx bx-chevron-up'></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="scan-results-content">
            <div class="scan-overview">
                <div class="overview-item">
                    <span class="overview-label">Directory:</span>
                    <span class="overview-value"><?php echo htmlspecialchars($results['directory_scan']['directory']); ?></span>
                </div>
                <div class="overview-item">
                    <span class="overview-label">Full Path:</span>
                    <span class="overview-value path-display"><?php echo htmlspecialchars($results['directory_scan']['full_path']); ?></span>
                </div>
                <div class="overview-item">
                    <span class="overview-label">Status:</span>
                    <span class="overview-value">
                        <?php if ($results['directory_scan']['exists']): ?>
                            <span class="status-badge success">Directory Exists</span>
                        <?php else: ?>
                            <span class="status-badge error">Directory Not Found</span>
                        <?php endif; ?>
                        
                        <?php if ($results['directory_scan']['exists']): ?>
                            <?php if ($results['directory_scan']['writable']): ?>
                                <span class="status-badge success">Writable</span>
                            <?php else: ?>
                                <span class="status-badge warning">Not Writable</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="overview-item">
                    <span class="overview-label">Total Files:</span>
                    <span class="overview-value"><?php echo $results['directory_scan']['total_count']; ?> images</span>
                </div>
                <div class="overview-item">
                    <span class="overview-label">Total Size:</span>
                    <span class="overview-value"><?php echo $results['directory_scan']['total_size_formatted']; ?></span>
                </div>
            </div>
            
            <?php if (!empty($results['directory_scan']['images'])): ?>
            <div class="image-list">
                <h4>Images in Directory</h4>
                <p class="note">Showing <?php echo count($results['directory_scan']['images']); ?> of <?php echo $results['directory_scan']['total_count']; ?> images</p>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Modified</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['directory_scan']['images'] as $image): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($image['filename']); ?></td>
                                <td><?php echo $image['size_formatted']; ?></td>
                                <td><?php echo $image['modified_formatted']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $image['accessible'] ? 'success' : 'error'; ?>">
                                        <?php echo $image['accessible'] ? 'Accessible' : 'Not Accessible'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo htmlspecialchars(SITE_URL . $image['web_path']); ?>" target="_blank" class="btn btn-sm btn-light">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                        
                                        <form method="post" action="" class="inline-form">
                                            <input type="hidden" name="action" value="test_image">
                                            <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($image['web_path']); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class='bx bx-check'></i> Test
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php elseif ($results['directory_scan']['exists']): ?>
            <div class="empty-state">
                <i class='bx bx-image'></i>
                <p>No images found in this directory</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Database Check Results -->
    <?php if (isset($results['database_check'])): ?>
    <div class="panel" id="db-results-panel">
        <div class="panel-header">
            <h3><i class='bx bx-data'></i> Database Image References: <?php echo ucfirst($results['database_check']['category']); ?></h3>
            <div class="panel-actions">
                <button type="button" class="panel-toggle" data-target="db-results-content">
                    <i class='bx bx-chevron-up'></i>
                </button>
            </div>
        </div>
        <div class="panel-content" id="db-results-content">
            <div class="db-overview">
                <div class="overview-item">
                    <span class="overview-label">Table:</span>
                    <span class="overview-value"><?php echo htmlspecialchars($results['database_check']['table']); ?></span>
                </div>
                <div class="overview-item">
                    <span class="overview-label">Image Field:</span>
                    <span class="overview-value"><?php echo htmlspecialchars($results['database_check']['field']); ?></span>
                </div>
                <div class="overview-item">
                    <span class="overview-label">Total Entries:</span>
                    <span class="overview-value"><?php echo $results['database_check']['total_count']; ?></span>
                </div>
                <div class="overview-item">
                    <span class="overview-label">Valid Images:</span>
                    <span class="overview-value"><?php echo $results['database_check']['valid_count']; ?></span>
                </div>
                <div class="overview-item">
                    <span class="overview-label">Invalid Images:</span>
                    <span class="overview-value"><?php echo $results['database_check']['invalid_count']; ?></span>
                </div>
            </div>
            
            <?php if (!empty($results['database_check']['entries'])): ?>
            <div class="image-references">
                <h4>Image References in Database</h4>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Image Path</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['database_check']['entries'] as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entry['id']); ?></td>
                                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                <td>
                                    <div class="path-display"><?php echo htmlspecialchars($entry['image_path']); ?></div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $entry['exists'] ? 'success' : 'error'; ?>">
                                        <?php echo $entry['exists'] ? 'Valid' : 'Invalid'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($entry['exists']): ?>
                                        <a href="<?php echo htmlspecialchars($entry['web_url']); ?>" target="_blank" class="btn btn-sm btn-light">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                        <?php endif; ?>
                                        
                                        <form method="post" action="" class="inline-form">
                                            <input type="hidden" name="action" value="test_image">
                                            <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($entry['image_path']); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class='bx bx-check'></i> Test
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class='bx bx-image'></i>
                <p>No image references found in the database</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Placeholder Creation Results -->
    <?php if (isset($results['placeholder_creation'])): ?>
    <div class="alert <?php echo $results['placeholder_creation']['success'] ? 'alert-success' : 'alert-danger'; ?>">
        <i class='bx <?php echo $results['placeholder_creation']['success'] ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
        <div class="alert-content">
            <div class="alert-message">
                <?php echo htmlspecialchars($results['placeholder_creation']['message']); ?>
            </div>
            
            <?php if ($results['placeholder_creation']['success']): ?>
            <div class="placeholder-preview">
                <img src="<?php echo htmlspecialchars(SITE_URL . $results['placeholder_creation']['web_path']); ?>" 
                     alt="Placeholder Image">
                <div class="image-info">
                    <div class="info-item">
                        <span class="info-label">Path:</span>
                        <span class="info-value"><?php echo htmlspecialchars($results['placeholder_creation']['web_path']); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Directory Creation Results -->
    <?php if (isset($results['directory_creation'])): ?>
    <div class="alert <?php echo $results['directory_creation']['success'] ? 'alert-success' : 'alert-danger'; ?>">
        <i class='bx <?php echo $results['directory_creation']['success'] ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
        <div class="alert-content">
            <div class="alert-title">
                <?php echo $results['directory_creation']['success'] ? 'Directory Created' : 'Creation Failed'; ?>
            </div>
            <div class="alert-message">
                <?php echo htmlspecialchars($results['directory_creation']['message']); ?>: 
                <?php echo htmlspecialchars($results['directory_creation']['full_path']); ?>
            </div>
            
            <?php if ($results['directory_creation']['success'] && !$results['directory_creation']['existed']): ?>
            <div class="alert-actions">
                <form method="post" action="" class="inline-form">
                    <input type="hidden" name="action" value="create_placeholder">
                    <input type="hidden" name="category" value="<?php echo $results['directory_creation']['category']; ?>">
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class='bx bx-image-add'></i> Add Placeholder Image
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
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
                <h4>Best Practices for Images</h4>
                
                <div class="recommendation-group">
                    <h5>1. Directory Structure</h5>
                    <ul>
                        <li>Maintain a consistent directory structure for all images</li>
                        <li>Organize images by category (news, events, facilities, etc.)</li>
                        <li>Always place images inside <code>/assets/images/[category]/</code></li>
                        <li>Create placeholder images for each directory</li>
                    </ul>
                </div>
                
                <div class="recommendation-group">
                    <h5>2. Image Path Format</h5>
                    <ul>
                        <li>Always use forward slashes (/) in image paths, regardless of operating system</li>
                        <li>Start paths with a leading slash: <code>/assets/images/category/image.jpg</code></li>
                        <li>Avoid using absolute URLs (http://...) in the database</li>
                        <li>Use lowercase filenames and avoid spaces (use hyphens instead)</li>
                    </ul>
                </div>
                
                <div class="recommendation-group">
                    <h5>3. Cross-Platform Compatibility</h5>
                    <ul>
                        <li>Use the <code>normalize_image_path()</code> function to standardize paths</li>
                        <li>Use <code>verify_image_exists()</code> to check if an image exists</li>
                        <li>Use <code>get_correct_image_url()</code> to get the proper URL for display</li>
                        <li>Always handle folder structure in a platform-neutral way</li>
                    </ul>
                </div>
                
                <div class="recommendation-group">
                    <h5>4. Performance Optimization</h5>
                    <ul>
                        <li>Optimize images for web (compress JPG and PNG files)</li>
                        <li>Keep image dimensions reasonable (max 1920px width for full-size photos)</li>
                        <li>Use appropriate formats: JPEG for photos, PNG for logos and icons</li>
                        <li>Consider using WebP format for better compression</li>
                    </ul>
                </div>
                
                <div class="code-example">
                    <h5>Recommended PHP Code</h5>
                    <pre><code>// Always normalize paths before use
$image_path = normalize_image_path($input_path);

// Check if the image exists
if (verify_image_exists($image_path)) {
    // Get the correct URL for display
    $image_url = get_correct_image_url($image_path);
    echo '&lt;img src="' . $image_url . '" alt="Image"&gt;';
} else {
    // Use a fallback placeholder
    $placeholder = '/assets/images/' . $category . '/placeholder-' . $category . '.png';
    $placeholder_url = get_correct_image_url($placeholder);
    echo '&lt;img src="' . $placeholder_url . '" alt="Placeholder"&gt;';
}</code></pre>
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
    
    // Path display hover functionality
    const pathDisplays = document.querySelectorAll('.path-display');
    
    pathDisplays.forEach(display => {
        const fullPath = display.textContent;
        
        if (fullPath.length > 40) {
            const shortPath = fullPath.substring(0, 20) + '...' + fullPath.substring(fullPath.length - 20);
            display.setAttribute('data-full-path', fullPath);
            display.textContent = shortPath;
            
            display.addEventListener('mouseenter', function() {
                this.textContent = this.getAttribute('data-full-path');
            });
            
            display.addEventListener('mouseleave', function() {
                this.textContent = shortPath;
            });
        }
    });
});
</script>

<style>
.image-diagnostics {
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

.dashboard-summary {
    margin-bottom: 30px;
}

.summary-header {
    margin-bottom: 20px;
}

.summary-header h3 {
    margin: 0 0 5px 0;
    color: #0a3060;
    font-size: 24px;
}

.summary-header p {
    margin: 0;
    color: #6c757d;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.summary-card {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 20px;
    display: flex;
    gap: 15px;
    border-left: 5px solid;
}

.summary-card.success {
    border-left-color: #28a745;
}

.summary-card.warning {
    border-left-color: #ffc107;
}

.summary-card.error {
    border-left-color: #dc3545;
}

.card-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background-color: #f8f9fa;
    border-radius: 10px;
    font-size: 24px;
    color: #3C91E6;
    flex-shrink: 0;
}

.card-content {
    flex-grow: 1;
}

.card-content h4 {
    margin: 0 0 5px 0;
    color: #0a3060;
    font-size: 18px;
}

.description {
    color: #6c757d;
    font-size: 14px;
    margin: 0 0 10px 0;
}

.stats {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.stat-item {
    background-color: #f8f9fa;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat-label {
    color: #495057;
    font-weight: 500;
}

.stat-value {
    color: #0a3060;
}

.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.inline-form {
    display: inline-block;
}

.error-message {
    color: #dc3545;
    font-size: 14px;
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 5px;
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

.test-form {
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.form-group {
    flex-grow: 1;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 5px;
    font-size: 14px;
}

.form-help {
    margin-top: 10px;
    font-size: 13px;
    color: #6c757d;
}

.form-help p {
    margin: 0 0 5px 0;
}

.form-help ul {
    margin: 0;
    padding-left: 20px;
}

.form-help code {
    background-color: #f8f9fa;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}

.test-results {
    margin-top: 30px;
    border-top: 1px solid #dee2e6;
    padding-top: 20px;
}

.test-results h4 {
    margin: 0 0 15px 0;
    color: #0a3060;
    font-size: 18px;
}

.result-overview {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.result-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 10px 15px;
    border-radius: 5px;
    font-weight: 500;
}

.result-status.success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.result-status.error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.path-details {
    display: flex;
    flex-direction: column;
    gap: 10px;
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 2px;
}

.detail-value {
    font-family: monospace;
    word-break: break-all;
}

.paths-checked {
    margin-top: 20px;
}

.paths-checked h5 {
    margin: 0 0 10px 0;
    color: #0a3060;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.data-table th {
    font-weight: 500;
    color: #495057;
    background-color: #f8f9fa;
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

.path-display {
    font-family: monospace;
    word-break: break-all;
    cursor: pointer;
}

.recommendations {
    margin-top: 20px;
}

.recommendations h5 {
    margin: 0 0 10px 0;
    color: #0a3060;
}

.recommendations ul {
    margin: 0 0 20px 0;
    padding-left: 20px;
}

.recommendations li {
    margin-bottom: 5px;
}

.image-preview {
    margin-top: 20px;
    text-align: center;
}

.image-preview h5 {
    margin: 0 0 10px 0;
    color: #0a3060;
}

.image-preview img {
    max-width: 100%;
    max-height: 300px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}

.scan-overview, .db-overview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.overview-item {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
}

.overview-label {
    display: block;
    font-weight: 500;
    color: #495057;
    margin-bottom: 5px;
}

.overview-value {
    font-family: monospace;
    word-break: break-all;
}

.image-list h4, .image-references h4 {
    margin: 0 0 10px 0;
    color: #0a3060;
    font-size: 18px;
}

.note {
    font-size: 13px;
    color: #6c757d;
    margin: 0 0 15px 0;
}

.table-responsive {
    overflow-x: auto;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.3;
}

.empty-state p {
    margin: 0;
}

.alert {
    display: flex;
    align-items: flex-start;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.alert i {
    font-size: 24px;
    margin-right: 15px;
}

.alert-content {
    flex-grow: 1;
}

.alert-title {
    font-weight: 500;
    font-size: 16px;
    margin-bottom: 5px;
}

.alert-message {
    color: inherit;
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

.alert-actions {
    margin-top: 15px;
}

.placeholder-preview {
    margin-top: 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.placeholder-preview img {
    max-width: 100%;
    max-height: 200px;
    border: 1px solid currentColor;
    border-radius: 5px;
    margin-bottom: 10px;
}

.image-info {
    width: 100%;
    max-width: 400px;
    font-size: 14px;
}

.info-item {
    display: flex;
    margin-bottom: 5px;
}

.info-label {
    font-weight: 500;
    width: 100px;
    flex-shrink: 0;
}

.recommendation-group {
    margin-bottom: 25px;
}

.recommendation-group h5 {
    margin: 0 0 10px 0;
    color: #0a3060;
    font-size: 16px;
}

.code-example {
    margin-top: 30px;
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}

.code-example h5 {
    margin: 0 0 10px 0;
    color: #0a3060;
}

.code-example pre {
    margin: 0;
    background-color: white;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}

.code-example code {
    font-family: monospace;
    white-space: pre-wrap;
    word-break: break-all;
}

@media (max-width: 768px) {
    .header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .banner-actions {
        margin-top: 15px;
    }
    
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .scan-overview, .db-overview {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();

// Define the page title and specific CSS/JS
$page_title = 'Image Diagnostics Tool';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout - need to use a different path for the layout
include '../../layout.php';
?>