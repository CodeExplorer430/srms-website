<?php
/**
 * School Settings Page
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

// Include database connection and functions
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php'; // Make sure to include functions.php
$db = new Database();

// Initialize variables
$errors = [];
$warnings = [];
$success = false;

// Get school information
$school_info = $db->fetch_row("SELECT * FROM school_information LIMIT 1");

// If no school information exists, create default values
if(!$school_info) {
    $school_info = [
        'name' => 'ST. RAPHAELA MARY SCHOOL',
        'logo' => '/assets/images/branding/logo-primary.png',
        'mission' => '',
        'vision' => '',
        'philosophy' => '',
        'email' => 'srmseduc@gmail.com',
        'phone' => '8253-3801/0920 832 7705',
        'address' => '#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City'
    ];
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $logo = isset($_POST['logo']) ? trim($_POST['logo']) : '';
    $mission = isset($_POST['mission']) ? trim($_POST['mission']) : '';
    $vision = isset($_POST['vision']) ? trim($_POST['vision']) : '';
    $philosophy = isset($_POST['philosophy']) ? trim($_POST['philosophy']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    
    // Validate required fields
    if(empty($name)) {
        $errors[] = 'School name is required';
    }
    
    if(empty($email)) {
        $errors[] = 'Email is required';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address';
    }
    
    if(empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if(empty($address)) {
        $errors[] = 'Address is required';
    }
    
    // Normalize image path using our robust path handling functions
    if (!empty($logo)) {
        // Use the normalize_image_path function to ensure consistent paths
        $logo = normalize_image_path($logo);
        
        // Verify the logo path is within the allowed directories
        $valid_image_path = false;
        $allowed_paths = [
            '/assets/images/branding/', 
            '/assets/images/promotional/',
            '/assets/images/',
            '/images/'  // For backward compatibility with old paths
        ];
         
        foreach ($allowed_paths as $allowed_path) {
            if (strpos($logo, $allowed_path) === 0) {
                $valid_image_path = true;
                break;
            }
        }
         
        // If path is invalid but not empty, provide more guidance
        if (!$valid_image_path) {
            // Suggest correction to proper path
            $suggested_path = '/assets/images/branding/' . basename($logo);
            $errors[] = 'Invalid logo path. Images should be located in one of the allowed directories. Did you mean: "' . $suggested_path . '"?';
        }
         
        // Verify file exists using our robust file existence check
        if ($valid_image_path) {
            // Use the robust verify_image_exists function
            if (!verify_image_exists($logo)) {
                $warnings[] = 'Logo file not found at "' . $logo . '". Please check the path or upload the image first.';
            }
        }
    }

    $logo = $db->escape($logo);
    
    // Process if no errors
    if(empty($errors)) {
        $name = $db->escape($name);
        $mission = $db->escape($mission);
        $vision = $db->escape($vision);
        $philosophy = $db->escape($philosophy);
        $email = $db->escape($email);
        $phone = $db->escape($phone);
        $address = $db->escape($address);
        
        if(isset($school_info['id'])) {
            // Update existing record
            $sql = "UPDATE school_information SET 
                    name = '$name', 
                    logo = '$logo', 
                    mission = '$mission', 
                    vision = '$vision', 
                    philosophy = '$philosophy', 
                    email = '$email', 
                    phone = '$phone', 
                    address = '$address' 
                    WHERE id = {$school_info['id']}";
        } else {
            // Insert new record
            $sql = "INSERT INTO school_information (name, logo, mission, vision, philosophy, email, phone, address) 
                    VALUES ('$name', '$logo', '$mission', '$vision', '$philosophy', '$email', '$phone', '$address')";
        }
        
        if($db->query($sql)) {
            $success = true;
            // Refresh school info
            $school_info = $db->fetch_row("SELECT * FROM school_information LIMIT 1");
        } else {
            $errors[] = 'An error occurred while saving the settings';
        }
    }
}

// Helper function for admin panel to display the correct image URL
function get_settings_image_url($path) {
    if (empty($path)) return '';
    
    // Use the function from functions.php to get the correct URL
    return get_correct_image_url(normalize_image_path($path));
}

$disable_media_library_preview = true;

// Start output buffer for main content
ob_start();
?>

<?php if($success): ?>
    <div class="message message-success">
        <i class='bx bx-check-circle'></i>
        <span>Settings have been saved successfully.</span>
    </div>
<?php endif; ?>

<?php if(!empty($errors)): ?>
    <div class="message message-error">
        <i class='bx bx-error-circle'></i>
        <div>
            <strong>Please correct the following errors:</strong>
            <ul class="mt-2 mb-0">
                <?php foreach($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($warnings)): ?>
    <div class="message message-warning">
        <i class='bx bx-info-circle'></i>
        <div>
            <ul>
                <?php foreach ($warnings as $warning): ?>
                <li><?php echo $warning; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bxs-school'></i> School Information</h3>
    </div>
    <div class="panel-body">
        <form action="settings.php" method="post" enctype="multipart/form-data">
            <div class="form-section">
                <h4 class="section-title">Basic Information</h4>
                <div class="form-group">
                    <label for="name">School Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($school_info['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="logo">Logo Path</label>
                    <div class="image-input-group">
                        <input type="text" id="logo" name="logo" class="form-control" value="<?php echo htmlspecialchars($school_info['logo']); ?>">
                        <button type="button" class="btn btn-primary open-media-library" data-target="logo">
                            <i class='bx bx-images'></i> Browse Media Library
                        </button>
                    </div>
                    <small class="form-text">Enter logo path or use the media library to select an image</small>
                    
                    <div id="unified-image-preview" class="image-preview-container">
                        <div class="image-preview">
                            <div id="preview-placeholder" class="preview-placeholder" style="<?php echo !empty($school_info['logo']) ? 'display: none;' : ''; ?>">
                                <i class='bx bx-image'></i>
                                <span>No logo selected</span>
                                <small>Select from media library</small>
                            </div>
                            <?php
                            // FIXED: Use the get_settings_image_url function to get the correct image URL
                            $logo_url = !empty($school_info['logo']) ? get_settings_image_url($school_info['logo']) : '';
                            ?>
                            <img src="<?php echo htmlspecialchars($logo_url); ?>" 
                                alt="School Logo" 
                                id="preview-image" 
                                style="<?php echo empty($school_info['logo']) ? 'display: none;' : ''; ?>">
                        </div>
                        <div id="source-indicator" class="image-source-indicator">
                            <?php if (!empty($school_info['logo'])): ?>
                            <span><i class="bx bx-link"></i> Media Library</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($school_info['logo'])): 
                    // FIXED: Use our robust functions for image verification
                    $logo_exists = verify_image_exists($school_info['logo']);
                    $best_match = '';
                    if (!$logo_exists && function_exists('find_best_matching_image')) {
                        $best_match = find_best_matching_image($school_info['logo']);
                    }
                ?>
                    <div class="image-verification <?php echo $logo_exists ? 'success' : 'error'; ?>">
                        <?php if ($logo_exists): ?>
                            <i class='bx bx-check-circle'></i> Logo file exists at this path
                        <?php elseif ($best_match): ?>
                            <i class='bx bx-check-circle'></i> Similar logo found at: <?php echo htmlspecialchars($best_match); ?>
                        <?php else: ?>
                            <i class='bx bx-x-circle'></i> Logo file not found at this path
                            <div class="path-details">
                                <?php
                                // Get server paths for debugging
                                $server_root = $_SERVER['DOCUMENT_ROOT'];
                                $project_folder = '';
                                if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
                                    $project_folder = $matches[1]; // Should be "srms-website"
                                }
                                
                                $path1 = $server_root . ($project_folder ? DIRECTORY_SEPARATOR . $project_folder : '') . 
                                        str_replace('/', DIRECTORY_SEPARATOR, normalize_image_path($school_info['logo']));
                                $path2 = $server_root . str_replace('/', DIRECTORY_SEPARATOR, normalize_image_path($school_info['logo']));
                                ?>
                                Tried: <?php echo htmlspecialchars($path1); ?><br>
                                And: <?php echo htmlspecialchars($path2); ?>
                            </div>
                            <div class="path-suggestion">
                                <p>The logo should be placed in <code>/assets/images/branding/</code> directory.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-section">
                <h4 class="section-title">Contact Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($school_info['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($school_info['phone']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" required><?php echo htmlspecialchars($school_info['address']); ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h4 class="section-title">School Philosophy</h4>
                <div class="form-group">
                    <label for="mission">Mission</label>
                    <textarea id="mission" name="mission" rows="5"><?php echo htmlspecialchars($school_info['mission']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="vision">Vision</label>
                    <textarea id="vision" name="vision" rows="5"><?php echo htmlspecialchars($school_info['vision']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="philosophy">Philosophy</label>
                    <textarea id="philosophy" name="philosophy" rows="5"><?php echo htmlspecialchars($school_info['philosophy']); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .section-title {
        color: var(--primary-color);
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .image-input-group {
        display: flex;
        gap: 10px;
    }
    
    .image-preview-container {
        margin-top: 15px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        padding: 10px;
        background-color: #f8f9fa;
    }
    
    .image-preview {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 120px;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .image-preview img {
        max-height: 100px;
        max-width: 100%;
    }
    
    .preview-placeholder {
        text-align: center;
        color: #6c757d;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }
    
    .preview-placeholder i {
        font-size: 2rem;
        display: block;
        margin-bottom: 10px;
    }
    
    .image-source-indicator {
        margin-top: 8px;
        font-size: 0.85rem;
        color: #6c757d;
        text-align: center;
    }
    
    .image-verification {
        margin-top: 10px;
        padding: 10px;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    
    .image-verification.success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .image-verification.error {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .path-details, .path-suggestion {
        margin-top: 5px;
        font-size: 0.8rem;
        opacity: 0.8;
    }
    
    .path-suggestion code {
        background-color: rgba(0,0,0,0.1);
        padding: 2px 4px;
        border-radius: 3px;
    }
    
    .form-actions {
        margin-top: 20px;
        display: flex;
        justify-content: flex-end;
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .col-md-6 {
        flex: 0 0 50%;
        max-width: 50%;
        padding: 0 10px;
    }
    
    @media (max-width: 768px) {
        .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .image-input-group {
            flex-direction: column;
        }
    }
    
    /* Logo preview specific styles */
    #unified-image-preview.library-mode .image-preview {
        border-color: #28a745;
        border-style: solid;
    }
    
    #unified-image-preview .image-preview {
        border-width: 2px;
        border-style: dashed;
        border-color: #ced4da;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing settings page with media library integration...');
    
    // Elements
    const logoInput = document.getElementById('logo');
    const previewImage = document.getElementById('preview-image');
    const previewPlaceholder = document.getElementById('preview-placeholder');
    const sourceIndicator = document.getElementById('source-indicator');
    const previewContainer = document.getElementById('unified-image-preview');
    
    // Utility function to get correct image URL
    function getCorrectImageUrl(path) {
        if (!path || !path.trim()) return '';
        
        // Get project folder from URL
        const urlParts = window.location.pathname.split('/');
        const projectFolder = urlParts[1] ? urlParts[1] : '';
        
        // Normalize path (ensure it starts with a single slash)
        let normalizedPath = path;
        if (!normalizedPath.startsWith('/')) {
            normalizedPath = '/' + normalizedPath;
        }
        normalizedPath = normalizedPath.replace(/\/+/g, '/');
        
        // Use the origin + project folder + path for the full URL
        return window.location.origin + '/' + projectFolder + normalizedPath;
    }
    
    // Initialize with current logo path
    function updatePreview(path) {
        if (path && path.trim()) {
            // Use our URL resolution function
            const fullImageUrl = getCorrectImageUrl(path);
            
            // Show image preview
            previewImage.src = fullImageUrl;
            previewImage.style.display = 'block';
            previewPlaceholder.style.display = 'none';
            
            // Add library mode styling
            previewContainer.classList.add('library-mode');
            sourceIndicator.innerHTML = '<span><i class="bx bx-link"></i> Media Library</span>';
            
            console.log('Updated preview with path:', path, 'Full URL:', fullImageUrl);
        } else {
            // Show placeholder
            previewImage.style.display = 'none';
            previewPlaceholder.style.display = 'flex';
            
            // Reset styling
            previewContainer.classList.remove('library-mode');
            sourceIndicator.innerHTML = '';
        }
    }
    
    // Initialize with current value
    if (logoInput && logoInput.value.trim()) {
        updatePreview(logoInput.value);
    }
    
    // Handle changes to the input field directly
    if (logoInput) {
        logoInput.addEventListener('input', function() {
            updatePreview(this.value);
        });
    }
    
    // Create global function for media library integration
    window.UnifiedImageUploader = {
        selectMediaItem: function(path) {
            if (logoInput) {
                logoInput.value = path;
                updatePreview(path);
            }
        }
    };
    
    // Compatibility layer for older code
    window.selectMediaItem = function(path) {
        window.UnifiedImageUploader.selectMediaItem(path);
    };
    
    // Connect media library modal integration
    const mediaModal = document.getElementById('media-library-modal');
    if (mediaModal) {
        const insertButton = mediaModal.querySelector('.insert-media');
        if (insertButton) {
            insertButton.addEventListener('click', function() {
                const selectedItem = mediaModal.querySelector('.media-item.selected');
                if (selectedItem) {
                    const path = selectedItem.getAttribute('data-path');
                    if (path && window.UnifiedImageUploader) {
                        window.UnifiedImageUploader.selectMediaItem(path);
                        
                        // Close the modal after selection
                        mediaModal.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                }
            });
        }
    }
});
</script>

<?php
include_once '../admin/includes/media-library.php';
render_media_library('logo');

// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'School Settings';
$page_specific_css = [
    '../assets/css/image-selector.css',
    '../assets/css/media-library.css'
];
$page_specific_js = [
    '../assets/js/media-library.js',
    '../assets/js/media-modal.js'
];

// Include the layout
include 'layout.php';
?>