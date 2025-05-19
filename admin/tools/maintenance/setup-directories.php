<?php
/**
 * Setup Directories Tool
 * Creates and verifies necessary directories with proper permissions
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

// Define directory structure
$directories = [
    'assets' => [
        'description' => 'Main assets directory',
        'children' => [
            'images' => [
                'description' => 'Image storage directory',
                'children' => [
                    'news' => [
                        'description' => 'News article images',
                        'create_placeholder' => true
                    ],
                    'events' => [
                        'description' => 'Event-related images',
                        'create_placeholder' => true
                    ],
                    'campus' => [
                        'description' => 'Campus and location images',
                        'create_placeholder' => true
                    ],
                    'facilities' => [
                        'description' => 'School facilities images',
                        'create_placeholder' => true
                    ],
                    'branding' => [
                        'description' => 'Logos and branding assets',
                        'create_placeholder' => true
                    ],
                    'promotional' => [
                        'description' => 'Marketing and promotional banners',
                        'create_placeholder' => true
                    ],
                    'people' => [
                        'description' => 'Faculty and staff photos',
                        'create_placeholder' => true
                    ]
                ]
            ],
            'css' => [
                'description' => 'Stylesheets directory'
            ],
            'js' => [
                'description' => 'JavaScript files directory'
            ],
            'uploads' => [
                'description' => 'User uploads directory',
                'children' => [
                    'documents' => [
                        'description' => 'Uploaded documents',
                    ],
                    'temp' => [
                        'description' => 'Temporary upload storage'
                    ]
                ]
            ]
        ]
    ],
    'admin' => [
        'description' => 'Administration panel',
        'children' => [
            'ajax' => [
                'description' => 'AJAX handlers'
            ],
            'includes' => [
                'description' => 'Admin-specific includes'
            ],
            'maintenance' => [
                'description' => 'Maintenance scripts'
            ]
        ]
    ],
    'includes' => [
        'description' => 'Main PHP includes'
    ],
    'database' => [
        'description' => 'Database schemas and backups'
    ],
    'logs' => [
        'description' => 'Error and access logs',
        'permissions' => 0777
    ]
];

// Process setup request
$action_results = [];
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'setup_all':
            $action_results = setupAllDirectories($directories);
            break;
            
        case 'create_placeholders':
            $action_results = createPlaceholderImages();
            break;
            
        case 'verify_permissions':
            $action_results = verifyPermissions($directories);
            break;
            
        case 'fix_permissions':
            $action_results = fixPermissions($directories);
            break;
    }
}

// Check directory status
$directory_status = checkDirectories($directories);

/**
 * Recursively check directories status
 */
function checkDirectories($directories, $parent_path = '') {
    global $doc_root, $project_folder;
    
    $results = [];
    
    foreach ($directories as $name => $info) {
        $path = $parent_path . '/' . $name;
        $full_path = getFullPath($path);
        
        $exists = is_dir($full_path);
        $writable = $exists ? is_writable($full_path) : false;
        
        $result = [
            'name' => $name,
            'path' => $path,
            'full_path' => $full_path,
            'description' => $info['description'] ?? '',
            'exists' => $exists,
            'writable' => $writable,
            'permissions' => $exists ? substr(sprintf('%o', fileperms($full_path)), -4) : 'N/A',
            'create_placeholder' => isset($info['create_placeholder']) ? $info['create_placeholder'] : false,
            'placeholder_exists' => false,
            'children' => []
        ];
        
        // Check for placeholder image
        if ($result['create_placeholder'] && $exists) {
            $placeholder_path = $full_path . DIRECTORY_SEPARATOR . 'placeholder-' . $name . '.png';
            $result['placeholder_exists'] = file_exists($placeholder_path);
            $result['placeholder_path'] = $path . '/placeholder-' . $name . '.png';
        }
        
        // Check children directories
        if (isset($info['children']) && is_array($info['children'])) {
            $result['children'] = checkDirectories($info['children'], $path);
        }
        
        $results[$name] = $result;
    }
    
    return $results;
}

/**
 * Get full path from a relative path
 */
function getFullPath($path) {
    global $doc_root, $project_folder;
    
    $full_path = $doc_root;
    if (!empty($project_folder)) {
        $full_path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $full_path .= str_replace('/', DIRECTORY_SEPARATOR, $path);
    
    return $full_path;
}

/**
 * Create all directories recursively
 */
function setupAllDirectories($directories, $parent_path = '') {
    $results = [];
    
    foreach ($directories as $name => $info) {
        $path = $parent_path . '/' . $name;
        $full_path = getFullPath($path);
        
        // Create directory if not exists
        if (!is_dir($full_path)) {
            $permissions = isset($info['permissions']) ? $info['permissions'] : (IS_WINDOWS ? 0777 : 0755);
            $success = @mkdir($full_path, $permissions, true);
            
            $results[$path] = [
                'action' => 'create_directory',
                'path' => $path,
                'full_path' => $full_path,
                'success' => $success,
                'message' => $success ? 'Directory created successfully' : 'Failed to create directory'
            ];
            
            if (!$success) {
                $error = error_get_last();
                $results[$path]['error'] = $error ? $error['message'] : 'Unknown error';
            }
        } else {
            $results[$path] = [
                'action' => 'check_directory',
                'path' => $path,
                'full_path' => $full_path,
                'success' => true,
                'message' => 'Directory already exists'
            ];
        }
        
        // Create placeholder if needed
        if (isset($info['create_placeholder']) && $info['create_placeholder']) {
            if (is_dir($full_path)) {
                $placeholder_path = $full_path . DIRECTORY_SEPARATOR . 'placeholder-' . $name . '.png';
                
                if (!file_exists($placeholder_path)) {
                    $placeholder_result = createPlaceholder($name, $full_path);
                    $results[$path . '/placeholder'] = $placeholder_result;
                } else {
                    $results[$path . '/placeholder'] = [
                        'action' => 'check_placeholder',
                        'path' => $path . '/placeholder-' . $name . '.png',
                        'success' => true,
                        'message' => 'Placeholder already exists'
                    ];
                }
            }
        }
        
        // Process children directories
        if (isset($info['children']) && is_array($info['children'])) {
            $child_results = setupAllDirectories($info['children'], $path);
            $results = array_merge($results, $child_results);
        }
    }
    
    return $results;
}

/**
 * Create placeholder images for image directories
 */
function createPlaceholderImages() {
    global $directory_status;
    
    $results = [];
    
    // Process images directory and its children
    if (isset($directory_status['assets']['children']['images']['children'])) {
        foreach ($directory_status['assets']['children']['images']['children'] as $category => $info) {
            if ($info['exists'] && $info['create_placeholder'] && !$info['placeholder_exists']) {
                $placeholder_result = createPlaceholder($category, $info['full_path']);
                $results[$info['path'] . '/placeholder'] = $placeholder_result;
            }
        }
    }
    
    return $results;
}

/**
 * Create a placeholder image for a specific directory
 */
function createPlaceholder($category, $dir_path) {
    $result = [
        'action' => 'create_placeholder',
        'category' => $category,
        'path' => $dir_path,
        'success' => false,
        'message' => ''
    ];
    
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
        'promotional' => [217, 70, 239], // Purple
        'people' => [6, 182, 212]       // Cyan
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
        $result['web_path'] = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(getFullPath(''), '', $placeholder_path));
    } else {
        $result['message'] = "Failed to save image to {$placeholder_path}";
    }
    
    return $result;
}

/**
 * Verify permissions on directories
 */
function verifyPermissions($directories, $parent_path = '') {
    $results = [];
    
    foreach ($directories as $name => $info) {
        $path = $parent_path . '/' . $name;
        $full_path = getFullPath($path);
        
        if (is_dir($full_path)) {
            $writable = is_writable($full_path);
            $current_perms = substr(sprintf('%o', fileperms($full_path)), -4);
            $required_perms = isset($info['permissions']) ? 
                sprintf('%04o', $info['permissions']) : 
                (IS_WINDOWS ? '0777' : '0755');
            
            $results[$path] = [
                'action' => 'verify_permissions',
                'path' => $path,
                'full_path' => $full_path,
                'writable' => $writable,
                'current_permissions' => $current_perms,
                'required_permissions' => $required_perms,
                'success' => $writable,
                'message' => $writable ? 
                    'Directory is writable' : 
                    'Directory is not writable'
            ];
        }
        
        // Process children directories
        if (isset($info['children']) && is_array($info['children'])) {
            $child_results = verifyPermissions($info['children'], $path);
            $results = array_merge($results, $child_results);
        }
    }
    
    return $results;
}

/**
 * Fix permissions on directories
 */
function fixPermissions($directories, $parent_path = '') {
    $results = [];
    
    foreach ($directories as $name => $info) {
        $path = $parent_path . '/' . $name;
        $full_path = getFullPath($path);
        
        if (is_dir($full_path)) {
            $perms = isset($info['permissions']) ? $info['permissions'] : (IS_WINDOWS ? 0777 : 0755);
            $success = @chmod($full_path, $perms);
            
            $results[$path] = [
                'action' => 'fix_permissions',
                'path' => $path,
                'full_path' => $full_path,
                'permissions' => sprintf('%04o', $perms),
                'success' => $success,
                'message' => $success ? 
                    'Permissions updated successfully' : 
                    'Failed to update permissions'
            ];
            
            if (!$success) {
                $error = error_get_last();
                $results[$path]['error'] = $error ? $error['message'] : 'Unknown error';
            }
        }
        
        // Process children directories
        if (isset($info['children']) && is_array($info['children'])) {
            $child_results = fixPermissions($info['children'], $path);
            $results = array_merge($results, $child_results);
        }
    }
    
    return $results;
}

/**
 * Count problematic directories in the status
 */
function countProblems($status) {
    $missing = 0;
    $not_writable = 0;
    $missing_placeholders = 0;
    
    foreach ($status as $key => $info) {
        if (!$info['exists']) {
            $missing++;
        } elseif (!$info['writable']) {
            $not_writable++;
        }
        
        if ($info['create_placeholder'] && $info['exists'] && !$info['placeholder_exists']) {
            $missing_placeholders++;
        }
        
        if (isset($info['children']) && !empty($info['children'])) {
            list($child_missing, $child_not_writable, $child_missing_placeholders) = countProblems($info['children']);
            $missing += $child_missing;
            $not_writable += $child_not_writable;
            $missing_placeholders += $child_missing_placeholders;
        }
    }
    
    return [$missing, $not_writable, $missing_placeholders];
}

// Count problems in the directory structure
list($missing_dirs, $not_writable_dirs, $missing_placeholders) = countProblems($directory_status);

// Start output buffer for main content
ob_start();
?>

<div class="setup-directories">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-folder-plus'></i> Setup Directories Tool</h2>
            <p>Create and manage directories required for the website</p>
        </div>
        <div class="banner-actions">
            <a href="../index.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Tools
            </a>
        </div>
    </div>
    
    <!-- Status Overview -->
    <div class="status-overview">
        <div class="status-card <?php echo $missing_dirs > 0 ? 'warning' : 'success'; ?>">
            <div class="status-icon">
                <i class='bx bx-folder'></i>
            </div>
            <div class="status-content">
                <div class="status-title">Directories</div>
                <div class="status-value"><?php echo $missing_dirs; ?> missing</div>
                <div class="status-text">
                    <?php echo $missing_dirs === 0 ? 'All required directories exist' : $missing_dirs . ' directories need to be created'; ?>
                </div>
            </div>
        </div>
        
        <div class="status-card <?php echo $not_writable_dirs > 0 ? 'warning' : 'success'; ?>">
            <div class="status-icon">
                <i class='bx bx-lock-open-alt'></i>
            </div>
            <div class="status-content">
                <div class="status-title">Permissions</div>
                <div class="status-value"><?php echo $not_writable_dirs; ?> not writable</div>
                <div class="status-text">
                    <?php echo $not_writable_dirs === 0 ? 'All directories have correct permissions' : $not_writable_dirs . ' directories have permission issues'; ?>
                </div>
            </div>
        </div>
        
        <div class="status-card <?php echo $missing_placeholders > 0 ? 'warning' : 'success'; ?>">
            <div class="status-icon">
                <i class='bx bx-image'></i>
            </div>
            <div class="status-content">
                <div class="status-title">Placeholders</div>
                <div class="status-value"><?php echo $missing_placeholders; ?> missing</div>
                <div class="status-text">
                    <?php echo $missing_placeholders === 0 ? 'All placeholder images exist' : $missing_placeholders . ' placeholder images need to be created'; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <form method="post" action="" class="action-form">
            <input type="hidden" name="action" value="setup_all">
            <button type="submit" class="btn btn-primary">
                <i class='bx bx-layer-plus'></i> Setup All Directories
            </button>
        </form>
        
        <?php if ($missing_placeholders > 0): ?>
        <form method="post" action="" class="action-form">
            <input type="hidden" name="action" value="create_placeholders">
            <button type="submit" class="btn btn-success">
                <i class='bx bx-image-add'></i> Create Missing Placeholders
            </button>
        </form>
        <?php endif; ?>
        
        <?php if ($not_writable_dirs > 0): ?>
        <form method="post" action="" class="action-form">
            <input type="hidden" name="action" value="fix_permissions">
            <button type="submit" class="btn btn-warning">
                <i class='bx bx-lock-open'></i> Fix Permissions
            </button>
        </form>
        <?php endif; ?>
        
        <form method="post" action="" class="action-form">
            <input type="hidden" name="action" value="verify_permissions">
            <button type="submit" class="btn btn-secondary">
                <i class='bx bx-check-shield'></i> Verify Permissions
            </button>
        </form>
    </div>
    
    <!-- Action Results -->
    <?php if (!empty($action_results)): ?>
    <div class="results-panel">
        <div class="panel-header">
            <h3>
                <i class='bx bx-list-check'></i>
                <?php
                $action_type = '';
                if (isset($_POST['action'])) {
                    switch ($_POST['action']) {
                        case 'setup_all':
                            $action_type = 'Directory Setup';
                            break;
                        case 'create_placeholders':
                            $action_type = 'Placeholder Creation';
                            break;
                        case 'verify_permissions':
                            $action_type = 'Permission Verification';
                            break;
                        case 'fix_permissions':
                            $action_type = 'Permission Fix';
                            break;
                    }
                }
                echo $action_type . ' Results';
                ?>
            </h3>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Path</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($action_results as $path => $result): ?>
                        <tr>
                            <td>
                                <span class="path-display"><?php echo htmlspecialchars($path); ?></span>
                            </td>
                            <td>
                                <?php 
                                $action_name = '';
                                switch ($result['action']) {
                                    case 'create_directory':
                                        $action_name = 'Create Directory';
                                        break;
                                    case 'check_directory':
                                        $action_name = 'Check Directory';
                                        break;
                                    case 'create_placeholder':
                                        $action_name = 'Create Placeholder';
                                        break;
                                    case 'check_placeholder':
                                        $action_name = 'Check Placeholder';
                                        break;
                                    case 'verify_permissions':
                                        $action_name = 'Verify Permissions';
                                        break;
                                    case 'fix_permissions':
                                        $action_name = 'Fix Permissions';
                                        break;
                                    default:
                                        $action_name = ucfirst($result['action']);
                                }
                                echo $action_name;
                                ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo isset($result['success']) && $result['success'] ? 'success' : 'error'; ?>">
                                    <?php echo isset($result['success']) && $result['success'] ? 'Success' : 'Failed'; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($result['message']); ?>
                                <?php if (isset($result['error'])): ?>
                                <div class="error-message">
                                    <?php echo htmlspecialchars($result['error']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($result['web_path'])): ?>
                                <div class="placeholder-preview">
                                    <a href="<?php echo SITE_URL . $result['web_path']; ?>" target="_blank">
                                        <img src="<?php echo SITE_URL . $result['web_path']; ?>" alt="Placeholder Image">
                                    </a>
                                </div>
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
    
    <!-- Directory Structure -->
    <div class="structure-panel">
        <div class="panel-header">
            <h3>
                <i class='bx bx-folder-open'></i> Directory Structure
            </h3>
        </div>
        <div class="panel-body">
            <div class="directory-tree">
                <?php 
                function renderDirectoryTree($directories, $level = 0) {
                    foreach ($directories as $name => $info) {
                        $indent = str_repeat('    ', $level);
                        $statusClass = !$info['exists'] ? 'missing' : (!$info['writable'] ? 'not-writable' : 'ok');
                        $hasChildren = isset($info['children']) && !empty($info['children']);
                        
                        echo '<div class="tree-item level-' . $level . '">';
                        echo '<div class="tree-content ' . $statusClass . '">';
                        
                        // Show expand/collapse if has children
                        if ($hasChildren) {
                            echo '<span class="toggle-icon"><i class="bx bx-plus"></i></span>';
                        } else {
                            echo '<span class="toggle-placeholder"></span>';
                        }
                        
                        // Directory icon and name
                        echo '<span class="tree-icon"><i class="bx bx-folder"></i></span>';
                        echo '<span class="tree-name">' . htmlspecialchars($name) . '</span>';
                        
                        // Path display
                        echo '<span class="tree-path">' . htmlspecialchars($info['path']) . '</span>';
                        
                        // Status indicators
                        echo '<div class="tree-status">';
                        if (!$info['exists']) {
                            echo '<span class="status-badge error">Missing</span>';
                        } elseif (!$info['writable']) {
                            echo '<span class="status-badge warning">Not Writable</span>';
                        } else {
                            echo '<span class="status-badge success">OK</span>';
                        }
                        
                        if ($info['exists'] && isset($info['permissions'])) {
                            echo '<span class="permissions-badge">' . $info['permissions'] . '</span>';
                        }
                        
                        if ($info['create_placeholder']) {
                            if ($info['exists'] && $info['placeholder_exists']) {
                                echo '<span class="status-badge success">Placeholder</span>';
                            } elseif ($info['exists']) {
                                echo '<span class="status-badge warning">No Placeholder</span>';
                            }
                        }
                        echo '</div>';
                        
                        // Description
                        echo '<div class="tree-description">' . htmlspecialchars($info['description']) . '</div>';
                        echo '</div>';
                        
                        // Render children
                        if ($hasChildren) {
                            echo '<div class="tree-children" style="display: none;">';
                            renderDirectoryTree($info['children'], $level + 1);
                            echo '</div>';
                        }
                        
                        echo '</div>';
                    }
                }
                
                renderDirectoryTree($directory_status);
                ?>
            </div>
        </div>
    </div>
    
    <!-- Troubleshooting Guide -->
    <div class="panel">
        <div class="panel-header">
            <h3>
                <i class='bx bx-help-circle'></i> Troubleshooting Guide
            </h3>
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
                        <h4>Directory Cannot Be Created</h4>
                        <i class='bx bx-chevron-down accordion-toggle'></i>
                    </div>
                    <div class="accordion-content" style="display: none;">
                        <p>If directories can't be created, try the following:</p>
                        <ol>
                            <li>
                                <strong>Check Permissions:</strong> Ensure the web server has write permissions to the parent directory.
                                <ul>
                                    <li>On Linux: <code>chmod -R 755 /path/to/parent</code></li>
                                    <li>On Windows: Right-click folder > Properties > Security > Edit permissions</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Create Manually:</strong> Use FTP or file manager to create the directories manually.
                            </li>
                            <li>
                                <strong>Ownership Issues:</strong> On Linux servers, make sure the directory is owned by the web server user.
                                <ul>
                                    <li><code>chown -R www-data:www-data /path/to/directory</code></li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <i class='bx bx-error-circle'></i>
                        <h4>Permission Issues</h4>
                        <i class='bx bx-chevron-down accordion-toggle'></i>
                    </div>
                    <div class="accordion-content" style="display: none;">
                        <p>If directories exist but permissions are incorrect:</p>
                        <ol>
                            <li>
                                <strong>Change Permissions:</strong> Set the appropriate permissions based on your OS.
                                <ul>
                                    <li>On Linux: <code>chmod -R 755 /path/to/directory</code></li>
                                    <li>For upload directories: <code>chmod -R 777 /path/to/uploads</code></li>
                                    <li>On Windows: Right-click folder > Properties > Security > Edit > Add Everyone > Full Control</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Web Server Restart:</strong> Sometimes permission changes require a web server restart to take effect.
                            </li>
                            <li>
                                <strong>SELinux Issues:</strong> If using SELinux (CentOS/RHEL), you may need to change contexts:
                                <ul>
                                    <li><code>setenforce 0</code> to temporarily disable SELinux</li>
                                    <li><code>chcon -R -t httpd_sys_rw_content_t /path/to/directory</code></li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <i class='bx bx-error-circle'></i>
                        <h4>Placeholder Image Creation Fails</h4>
                        <i class='bx bx-chevron-down accordion-toggle'></i>
                    </div>
                    <div class="accordion-content" style="display: none;">
                        <p>If placeholder image creation fails:</p>
                        <ol>
                            <li>
                                <strong>Check GD Library:</strong> Make sure PHP GD extension is installed.
                                <ul>
                                    <li>Run <code>php -m | grep gd</code> to check if GD is installed</li>
                                    <li>Install via <code>apt-get install php-gd</code> (Debian/Ubuntu) or similar for your OS</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Memory Limits:</strong> PHP might need more memory to create images.
                                <ul>
                                    <li>Check <code>memory_limit</code> in php.ini</li>
                                    <li>Increase it to at least 128M if needed</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Manual Creation:</strong> You can manually upload placeholder images to each directory.
                            </li>
                        </ol>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <div class="accordion-header">
                        <i class='bx bx-error-circle'></i>
                        <h4>Cross-Platform Directory Issues</h4>
                        <i class='bx bx-chevron-down accordion-toggle'></i>
                    </div>
                    <div class="accordion-content" style="display: none;">
                        <p>For cross-platform compatibility:</p>
                        <ol>
                            <li>
                                <strong>Use DIRECTORY_SEPARATOR:</strong> Always use PHP's <code>DIRECTORY_SEPARATOR</code> constant for file paths.
                            </li>
                            <li>
                                <strong>Normalize Paths:</strong> Convert slashes to be consistent with the platform.
                                <pre><code>$path = str_replace('/', DIRECTORY_SEPARATOR, $path);</code></pre>
                            </li>
                            <li>
                                <strong>Project Folder Detection:</strong> Use the project folder detection to build correct paths.
                                <pre><code>$full_path = $doc_root;
if (!empty($project_folder)) {
    $full_path .= DIRECTORY_SEPARATOR . $project_folder;
}
$full_path .= str_replace('/', DIRECTORY_SEPARATOR, $path);</code></pre>
                            </li>
                        </ol>
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
    
    // Directory tree toggle functionality
    const treeToggles = document.querySelectorAll('.toggle-icon');
    
    treeToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const treeItem = this.closest('.tree-item');
            const treeChildren = treeItem.querySelector('.tree-children');
            const icon = this.querySelector('i');
            
            if (treeChildren.style.display === 'none') {
                treeChildren.style.display = 'block';
                icon.classList.remove('bx-plus');
                icon.classList.add('bx-minus');
            } else {
                treeChildren.style.display = 'none';
                icon.classList.remove('bx-minus');
                icon.classList.add('bx-plus');
            }
        });
    });
});
</script>

<style>
.setup-directories {
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

.btn-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-light {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.status-overview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.status-card {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 20px;
    display: flex;
    gap: 15px;
    position: relative;
    overflow: hidden;
}

.status-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}

.status-card.success::before {
    background-color: #28a745;
}

.status-card.warning::before {
    background-color: #ffc107;
}

.status-card.error::before {
    background-color: #dc3545;
}

.status-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 10px;
    font-size: 24px;
    flex-shrink: 0;
}

.status-card.success .status-icon {
    color: #28a745;
}

.status-card.warning .status-icon {
    color: #ffc107;
}

.status-card.error .status-icon {
    color: #dc3545;
}

.status-content {
    flex-grow: 1;
}

.status-title {
    font-weight: 500;
    color: #0a3060;
    margin-bottom: 5px;
}

.status-value {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 5px;
}

.status-card.success .status-value {
    color: #28a745;
}

.status-card.warning .status-value {
    color: #ffc107;
}

.status-card.error .status-value {
    color: #dc3545;
}

.status-text {
    font-size: 14px;
    color: #6c757d;
}

.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 30px;
}

.action-form {
    margin: 0;
}

.results-panel, .structure-panel, .panel {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
    overflow: hidden;
}

.panel-header, .results-panel .panel-header, .structure-panel .panel-header {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header h3, .results-panel .panel-header h3, .structure-panel .panel-header h3 {
    margin: 0;
    font-size: 18px;
    color: #0a3060;
    display: flex;
    align-items: center;
}

.panel-header h3 i, .results-panel .panel-header h3 i, .structure-panel .panel-header h3 i {
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

.panel-body, .results-panel .panel-body, .structure-panel .panel-body {
    padding: 20px;
}

.panel-content {
    padding: 20px;
}

.table-responsive {
    overflow-x: auto;
}

.results-table {
    width: 100%;
    border-collapse: collapse;
}

.results-table th, .results-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.results-table th {
    background-color: #f8f9fa;
    font-weight: 500;
    color: #0a3060;
}

.path-display {
    font-family: monospace;
    font-size: 14px;
    word-break: break-all;
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

.permissions-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 50px;
    font-size: 12px;
    background-color: rgba(108, 117, 125, 0.1);
    color: #6c757d;
    margin-left: 5px;
}

.error-message {
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
}

.placeholder-preview {
    margin-top: 10px;
    text-align: center;
}

.placeholder-preview img {
    max-width: 100%;
    max-height: 150px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}

.directory-tree {
    font-size: 14px;
}

.tree-item {
    margin-bottom: 5px;
}

.tree-content {
    display: grid;
    grid-template-columns: auto auto 1fr auto;
    grid-template-rows: auto auto;
    align-items: center;
    gap: 5px 10px;
    padding: 8px;
    border-radius: 5px;
    cursor: pointer;
}

.tree-content:hover {
    background-color: #f8f9fa;
}

.tree-content.ok {
    border-left: 3px solid #28a745;
}

.tree-content.not-writable {
    border-left: 3px solid #ffc107;
}

.tree-content.missing {
    border-left: 3px solid #dc3545;
}

.toggle-icon, .toggle-placeholder {
    grid-column: 1;
    grid-row: 1 / span 2;
    width: 24px;
    text-align: center;
    cursor: pointer;
}

.tree-icon {
    grid-column: 2;
    grid-row: 1 / span 2;
    width: 24px;
    text-align: center;
}

.tree-name {
    grid-column: 3;
    grid-row: 1;
    font-weight: 500;
    color: #0a3060;
}

.tree-path {
    grid-column: 3;
    grid-row: 2;
    font-family: monospace;
    font-size: 12px;
    color: #6c757d;
}

.tree-status {
    grid-column: 4;
    grid-row: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: flex-end;
}

.tree-description {
    grid-column: 4;
    grid-row: 2;
    font-size: 12px;
    color: #6c757d;
    text-align: right;
}

.level-0 {
    /* No indentation for root level */
}

.level-1 {
    margin-left: 20px;
}

.level-2 {
    margin-left: 40px;
}

.level-3 {
    margin-left: 60px;
}

.tree-children {
    margin-left: 20px;
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

@media (max-width: 768px) {
    .header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .banner-actions {
        margin-top: 15px;
    }
    
    .status-overview {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        flex-direction: column;
    }
    
    .quick-actions .btn {
        width: 100%;
    }
    
    .tree-content {
        grid-template-columns: auto auto 1fr;
        grid-template-rows: auto auto auto;
    }
    
    .tree-status {
        grid-column: 1 / span 3;
        grid-row: 3;
        justify-content: flex-start;
        margin-top: 5px;
    }
    
    .tree-description {
        grid-column: 3;
        grid-row: 2;
        text-align: left;
    }
}
</style>

<?php
$content = ob_get_clean();

// Define the page title and specific CSS/JS
$page_title = 'Setup Directories Tool';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout - need to use a different path for the layout
include '../../layout.php';
?>