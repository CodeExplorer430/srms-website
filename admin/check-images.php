<?php
/**
 * Image Path Diagnostic Tool
 * Checks and repairs image directories and paths
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../admin/login.php');
    exit;
}

// Include database connection
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
$db = new Database();

// Define image directories to check
$image_directories = [
    'assets/images',
    'assets/images/branding',
    'assets/images/news',
    'assets/images/events',
    'assets/images/promotional',
    'assets/images/facilities',
    'assets/images/campus',
    'assets/images/people'
];

// Get document root and project path
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // Should be "srms-website"
}

// Results storage
$results = [];

// Check each directory
foreach ($image_directories as $dir) {
    // Build the full server path INCLUDING project folder
    $path = $doc_root;
    if (!empty($project_folder)) {
        $path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $path .= DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
    
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    $created = false;
    
    // Try to create if doesn't exist
    if (!$exists) {
        $created = @mkdir($path, 0755, true);
        $writable = $created ? is_writable($path) : false;
    }
    
    // Add placeholder image if directory is writable
    $placeholder_added = false;
    if ($writable && ($dir === 'assets/images/branding' || $dir === 'assets/images')) {
        $logo_path = $path . DIRECTORY_SEPARATOR . 'logo-primary.png';
        if (!file_exists($logo_path)) {
            // Create a simple placeholder logo
            $placeholder_added = @copy(__DIR__ . '/placeholder-logo.png', $logo_path);
            if (!$placeholder_added && function_exists('imagecreate')) {
                // Create a placeholder image programmatically if copy fails
                $img = imagecreate(200, 80);
                $bg = imagecolorallocate($img, 0, 51, 153); // Dark blue
                $text_color = imagecolorallocate($img, 255, 255, 255); // White
                imagestring($img, 5, 40, 30, 'SRMS Logo', $text_color);
                $placeholder_added = imagepng($img, $logo_path);
                imagedestroy($img);
            }
        } else {
            $placeholder_added = true;
        }
    }
    
    $results[] = [
        'directory' => $dir,
        'path' => $path,
        'exists' => $exists,
        'writable' => $writable,
        'created' => $created,
        'placeholder_added' => $placeholder_added
    ];
}

// Get logo path from database
$logo_path = '';
$school_info = $db->fetch_row("SELECT logo FROM school_information LIMIT 1");
if ($school_info && !empty($school_info['logo'])) {
    $logo_path = $school_info['logo'];
    
    // Check if logo exists
    $logo_exists = verify_image_exists($logo_path);
    
    // If logo doesn't exist, attempt to update it
    if (!$logo_exists) {
        $default_logo = '/assets/images/branding/logo-primary.png';
        $update_result = $db->query("UPDATE school_information SET logo = '{$db->escape($default_logo)}'");
        
        $results[] = [
            'action' => 'update_logo_path',
            'old_path' => $logo_path,
            'new_path' => $default_logo,
            'success' => $update_result ? true : false
        ];
    }
}

// Start output buffer for main content
ob_start();
?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bx-image-check'></i> Image Path Diagnostic Tool</h3>
        <div class="panel-actions">
            <a href="../media-manager.php" class="btn btn-primary">
                <i class='bx bx-images'></i> Media Manager
            </a>
            <a href="setup-directories.php" class="btn btn-secondary">
                <i class='bx bx-folder-plus'></i> Setup Directories
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="alert alert-info">
            <p><strong>Server Information:</strong></p>
            <ul>
                <li><strong>Server OS:</strong> <?php echo IS_WINDOWS ? 'Windows' : 'Linux'; ?></li>
                <li><strong>Server Type:</strong> <?php echo SERVER_TYPE; ?></li>
                <li><strong>Document Root:</strong> <?php echo $doc_root; ?></li>
                <li><strong>Project Folder:</strong> <?php echo $project_folder; ?></li>
                <li><strong>Site URL:</strong> <?php echo SITE_URL; ?></li>
            </ul>
        </div>
        
        <h4>Image Directory Status</h4>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Directory</th>
                        <th>Status</th>
                        <th>Path</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <?php if (isset($result['directory'])): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($result['directory']); ?></td>
                            <td>
                                <?php if ($result['exists'] || $result['created']): ?>
                                    <span class="badge badge-success">
                                        <i class='bx bx-check'></i> 
                                        <?php echo $result['created'] ? 'Created' : 'Exists'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger">
                                        <i class='bx bx-x'></i> Missing
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($result['writable']): ?>
                                    <span class="badge badge-info">
                                        <i class='bx bx-pencil'></i> Writable
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class='bx bx-lock'></i> Not Writable
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($result['placeholder_added']): ?>
                                    <span class="badge badge-success">
                                        <i class='bx bx-image'></i> Placeholder Added
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($result['path']); ?></code>
                            </td>
                            <td>
                                <a href="?repair=<?php echo urlencode($result['directory']); ?>" class="btn btn-sm btn-primary">
                                    <i class='bx bx-wrench'></i> Repair
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <h4>Logo Path Information</h4>
        <div class="card">
            <div class="card-body">
                <p><strong>Current Logo Path in Database:</strong> <code><?php echo htmlspecialchars($logo_path); ?></code></p>
                
                <?php if (verify_image_exists($logo_path)): ?>
                    <div class="alert alert-success">
                        <i class='bx bx-check-circle'></i> Logo file exists at this path.
                    </div>
                    <div class="image-preview">
                        <img src="<?php echo get_correct_image_url($logo_path); ?>" alt="Current Logo" style="max-height: 100px;">
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class='bx bx-error-circle'></i> Logo file does not exist at this path.
                    </div>
                    <div class="form-actions">
                        <a href="?reset_logo=1" class="btn btn-warning">
                            <i class='bx bx-reset'></i> Reset Logo Path
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <h4>Test Image Display</h4>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="test_path">Test Image Path:</label>
                    <input type="text" id="test_path" class="form-control" value="<?php echo htmlspecialchars($logo_path); ?>">
                </div>
                <button type="button" id="test_image_btn" class="btn btn-primary">
                    <i class='bx bx-test-tube'></i> Test Image
                </button>
            </div>
            <div class="col-md-6">
                <div id="test_image_container" class="image-preview" style="min-height: 100px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;">
                    <p class="text-muted">Image preview will appear here</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Test image display functionality
document.addEventListener('DOMContentLoaded', function() {
    const testPathInput = document.getElementById('test_path');
    const testButton = document.getElementById('test_image_btn');
    const imageContainer = document.getElementById('test_image_container');
    
    if (testButton) {
        testButton.addEventListener('click', function() {
            const path = testPathInput.value.trim();
            if (path) {
                imageContainer.innerHTML = `
                    <div style="text-align: center;">
                        <img src="${path}" alt="Test Image" style="max-width: 100%; max-height: 200px;" 
                             onerror="this.onerror=null; this.style.display='none'; document.getElementById('error_msg').style.display='block';">
                        <div id="error_msg" style="display: none; color: #dc3545;">
                            <i class='bx bx-error-circle'></i> Image failed to load
                        </div>
                    </div>
                `;
            }
        });
    }
});
</script>

<style>
    .badge {
        display: inline-block;
        padding: 0.25em 0.6em;
        font-size: 75%;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
        margin-right: 5px;
    }
    
    .badge-success {
        background-color: #28a745;
        color: white;
    }
    
    .badge-danger {
        background-color: #dc3545;
        color: white;
    }
    
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    .badge-info {
        background-color: #17a2b8;
        color: white;
    }
    
    .alert {
        position: relative;
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: 0.25rem;
    }
    
    .alert-info {
        color: #0c5460;
        background-color: #d1ecf1;
        border-color: #bee5eb;
    }
    
    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
    
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
    
    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 1px solid rgba(0,0,0,.125);
        border-radius: 0.25rem;
        margin-bottom: 20px;
    }
    
    .card-body {
        flex: 1 1 auto;
        padding: 1.25rem;
    }
    
    .image-preview {
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
    }
</style>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Image Path Diagnostic Tool';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include '../layout.php';