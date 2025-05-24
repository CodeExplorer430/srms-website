<?php
/**
 * School Settings Page
 * Updated for Hostinger compatibility
 * Version: 2.0 - Works with existing CSS files
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . (defined('IS_PRODUCTION') && IS_PRODUCTION ? '/admin/login.php' : 'login.php'));
    exit;
}

// Include database connection and functions
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php';
$db = new Database();

// Enhanced environment detection
$is_production = defined('IS_PRODUCTION') && IS_PRODUCTION;

// Initialize variables
$errors = [];
$warnings = [];
$success = false;
$upload_result = false;

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
    
    // Process image upload if a new file is uploaded
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] != UPLOAD_ERR_NO_FILE) {
        error_log('Settings: Processing logo upload for file: ' . $_FILES['logo_upload']['name']);
        
        $upload_result = upload_image($_FILES['logo_upload'], 'branding');
        if ($upload_result) {
            // Use the new image path immediately
            $logo = $upload_result;
            error_log("Settings: Successfully uploaded logo to path: {$logo}");
            
            // Add a small delay to ensure file is written to disk
            usleep(500000); // 0.5 second delay
            clearstatcache();
            
            // Log verification
            if (verify_image_exists($logo)) {
                error_log("Settings: Verified uploaded logo exists at path: {$logo}");
            } else {
                error_log("Settings: WARNING - Uploaded logo not found at path: {$logo}");
                $warnings[] = "Logo was uploaded successfully but verification check failed. The logo may not display correctly.";
            }
        } else {
            $errors[] = 'Logo upload failed. Please check file type, size, and server permissions.';
        }
    }
    
    // Normalize image path using enhanced path handling
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
         
        // If path is invalid but not empty, provide guidance
        if (!$valid_image_path) {
            $suggested_path = '/assets/images/branding/' . basename($logo);
            $errors[] = 'Invalid logo path. Logo should be in the branding directory. Did you mean: "' . $suggested_path . '"?';
        }
         
        // Verify file exists using enhanced verification
        if ($valid_image_path && !verify_image_exists($logo)) {
            $warnings[] = 'Logo file not found at "' . $logo . '". Please check the path or upload the image first.';
        }
    }
    
    // Process if no errors
    if(empty($errors)) {
        // Escape all values for database
        $name = $db->escape($name);
        $logo = $db->escape($logo);
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
                    <li><?php echo htmlspecialchars($error); ?></li>
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
                <li><?php echo htmlspecialchars($warning); ?></li>
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
                <h4 class="section-title"><i class='bx bxs-info-circle'></i> Basic Information</h4>
                
                <div class="form-group">
                    <label for="name">School Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($school_info['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="logo">School Logo</label>
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
                                <small>Select from media library or upload new logo</small>
                            </div>
                            <img src="<?php echo !empty($school_info['logo']) ? htmlspecialchars(get_correct_image_url($school_info['logo'])) : ''; ?>" 
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

                    <div class="form-group">
                        <label for="logo_upload">Upload New Logo</label>
                        <input type="file" id="logo_upload" name="logo_upload" accept="image/jpeg, image/png, image/gif">
                        <small class="form-text">Max file size: 2MB. Accepted formats: JPEG, PNG, GIF</small>
                    </div>
                </div>
                
                <?php if (!empty($school_info['logo'])): 
                    $logo_exists = verify_image_exists($school_info['logo']);
                    $best_match = '';
                    if (!$logo_exists && function_exists('find_best_matching_image')) {
                        $best_match = find_best_matching_image($school_info['logo']);
                    }
                ?>
                    <div class="image-verification <?php echo ($logo_exists || $best_match) ? 'success' : 'error'; ?>">
                        <?php if ($logo_exists): ?>
                            <i class='bx bx-check-circle'></i> Logo file exists and is accessible
                        <?php elseif ($best_match): ?>
                            <i class='bx bx-check-circle'></i> Similar logo found at: <?php echo htmlspecialchars($best_match); ?>
                        <?php else: ?>
                            <i class='bx bx-x-circle'></i> Logo file not found at this path
                            <div class="path-suggestion">
                                <p>The logo should be placed in <code>/assets/images/branding/</code> directory.</p>
                                <p>Use the media library to browse available images or upload a new logo.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-section">
                <h4 class="section-title"><i class='bx bxs-contact'></i> Contact Information</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($school_info['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($school_info['phone']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">School Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($school_info['address']); ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h4 class="section-title"><i class='bx bxs-graduation'></i> School Philosophy</h4>
                
                <div class="form-group">
                    <label for="mission">Mission Statement</label>
                    <textarea id="mission" name="mission" class="form-control" rows="5"><?php echo htmlspecialchars($school_info['mission']); ?></textarea>
                    <small class="form-text">Describe the school's purpose and primary objectives.</small>
                </div>
                
                <div class="form-group">
                    <label for="vision">Vision Statement</label>
                    <textarea id="vision" name="vision" class="form-control" rows="5"><?php echo htmlspecialchars($school_info['vision']); ?></textarea>
                    <small class="form-text">Describe the school's aspirations and future goals.</small>
                </div>
                
                <div class="form-group">
                    <label for="philosophy">Educational Philosophy</label>
                    <textarea id="philosophy" name="philosophy" class="form-control" rows="5"><?php echo htmlspecialchars($school_info['philosophy']); ?></textarea>
                    <small class="form-text">Describe the school's approach to education and core beliefs.</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Save Settings
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.reload();">
                    <i class='bx bx-refresh'></i> Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Settings Page JavaScript
 * Enhanced for Hostinger compatibility
 * Version: 2.0 - Works with existing CSS
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing Settings page with enhanced media library integration...');
    
    // Environment configuration
    const isProduction = typeof window.SRMS_CONFIG !== 'undefined' && window.SRMS_CONFIG.IS_PRODUCTION;
    
    // Get project folder from URL for consistent handling
    function getProjectFolder() {
        const urlParts = window.location.pathname.split('/');
        const projectFolder = urlParts[1] ? urlParts[1] : '';
        
        // Don't return project folder in production if it's at root level
        if (isProduction && (projectFolder === 'admin' || projectFolder === 'assets')) {
            return '';
        }
        
        return projectFolder;
    }
    
    // Elements
    const logoInput = document.getElementById('logo');
    const logoUpload = document.getElementById('logo_upload');
    const previewImage = document.getElementById('preview-image');
    const previewPlaceholder = document.getElementById('preview-placeholder');
    const sourceIndicator = document.getElementById('source-indicator');
    const previewContainer = document.getElementById('unified-image-preview');
    
    // State management
    let currentMode = null; // 'library', 'upload', or null
    let uploadDataUrl = null;
    
    // Path normalization utility function
    function normalizePath(path) {
        if (!path) return '';
        
        console.log("Normalizing path:", path);
        
        // Replace all backslashes with forward slashes
        let normalized = path.replace(/\\/g, '/');
        
        // Remove any double slashes
        normalized = normalized.replace(/\/+/g, '/');
        
        // Get project folder
        const projectFolder = getProjectFolder();
        
        // Remove project folder if it's in the path (to avoid duplication)
        if (projectFolder && normalized.toLowerCase().startsWith('/' + projectFolder.toLowerCase() + '/')) {
            normalized = normalized.substring(('/' + projectFolder).length);
        }
        
        // Ensure path starts with a single slash
        normalized = '/' + normalized.replace(/^\/+/, '');
        
        console.log("Normalized path:", normalized);
        return normalized;
    }
    
    // Function to get correct image URL based on environment
    function getCorrectImageUrl(path) {
        if (!path) return '';
        
        // Normalize the path first
        const normalizedPath = normalizePath(path);
        
        // Get origin and project folder
        const origin = window.location.origin;
        const projectFolder = getProjectFolder();
        
        // Build URL based on environment
        if (isProduction) {
            // In production (Hostinger), don't add project folder
            return origin + normalizedPath;
        } else {
            // In development, include project folder if available
            if (projectFolder) {
                return origin + '/' + projectFolder + normalizedPath;
            } else {
                return origin + normalizedPath;
            }
        }
    }
    
    // Reset all state
    function resetState() {
        console.log('Resetting logo state');
        
        // Reset UI elements
        if (previewImage) {
            previewImage.src = '';
            previewImage.style.display = 'none';
        }
        
        if (previewPlaceholder) {
            previewPlaceholder.style.display = 'flex';
        }
        
        if (sourceIndicator) {
            sourceIndicator.innerHTML = '';
        }
        
        if (previewContainer) {
            previewContainer.className = 'image-preview-container';
        }
        
        // Reset state variables
        currentMode = null;
        uploadDataUrl = null;
    }
    
    // Activate library mode (for media library selections)
    function activateLibraryMode(path, displayUrl) {
        if (!path || path.trim() === '') {
            resetState();
            return;
        }
        
        console.log('Activating LIBRARY mode with path:', path);
        
        // Format path (for storage in input field)
        const formattedPath = normalizePath(path);
        currentMode = 'library';
        
        // Update UI for library mode using existing CSS classes
        if (previewContainer) {
            previewContainer.className = 'image-preview-container library-mode';
        }
        
        if (sourceIndicator) {
            sourceIndicator.innerHTML = '<span><i class="bx bx-link"></i> Media Library</span>';
        }
        
        // Get absolute URL for image display
        const fullImageUrl = displayUrl || getCorrectImageUrl(formattedPath);
        console.log('Using full image URL:', fullImageUrl);
        
        // Update preview with full URL
        if (previewImage) {
            previewImage.src = fullImageUrl;
            previewImage.style.display = 'block';
        }
        
        if (previewPlaceholder) {
            previewPlaceholder.style.display = 'none';
        }
        
        // Set input value as relative path (for database storage)
        if (logoInput && logoInput.value !== formattedPath) {
            logoInput.value = formattedPath;
        }
        
        // Clear file upload input to avoid conflicts
        if (logoUpload) {
            logoUpload.value = '';
        }
    }
    
    // Activate upload mode (for local file uploads)
    function activateUploadMode(dataUrl, file) {
        if (!dataUrl) {
            resetState();
            return;
        }
        
        console.log('Activating UPLOAD mode with file:', file ? file.name : 'unknown');
        
        // Store file reference and data
        uploadDataUrl = dataUrl;
        currentMode = 'upload';
        
        // Update UI for upload mode using existing CSS classes
        if (previewContainer) {
            previewContainer.className = 'image-preview-container upload-mode';
        }
        
        if (sourceIndicator) {
            sourceIndicator.innerHTML = `<span><i class="bx bx-upload"></i> Local upload: <strong>${file ? file.name : ''}</strong> (not saved until you submit)</span>`;
        }
        
        // Update preview
        if (previewImage) {
            previewImage.src = dataUrl;
            previewImage.style.display = 'block';
        }
        
        if (previewPlaceholder) {
            previewPlaceholder.style.display = 'none';
        }
        
        // Clear the text input since we're using upload
        if (logoInput) {
            logoInput.value = '';
        }
    }
    
    // Handle changes to the input field directly
    if (logoInput) {
        logoInput.addEventListener('input', function() {
            const path = this.value.trim();
            if (path) {
                activateLibraryMode(path);
            } else {
                resetState();
            }
        });
    }
    
    // Handle file upload change
    if (logoUpload) {
        logoUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    activateUploadMode(e.target.result, file);
                };
                
                reader.onerror = function() {
                    resetState();
                    alert('Failed to read file');
                };
                
                reader.readAsDataURL(file);
            } else if (currentMode === 'upload') {
                resetState();
            }
        });
    }
    
    // Initialize with current logo path
    if (logoInput && logoInput.value.trim()) {
        activateLibraryMode(logoInput.value);
    }
    
    // Create global function for media library integration
    window.UnifiedImageUploader = {
        selectMediaItem: function(path, displayUrl) {
            activateLibraryMode(path, displayUrl);
        },
        reset: function() {
            resetState();
        },
        getState: function() {
            return {
                mode: currentMode,
                uploadDataUrl: uploadDataUrl
            };
        }
    };
    
    // Legacy compatibility layer
    window.selectMediaItem = function(path, displayUrl) {
        window.UnifiedImageUploader.selectMediaItem(path, displayUrl);
    };
    
    // Form validation before submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Basic validation
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!name) {
                alert('School name is required');
                e.preventDefault();
                return false;
            }
            
            if (!email) {
                alert('Email address is required');
                e.preventDefault();
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }
    
    console.log('Settings page initialized successfully');
    console.log('Environment:', isProduction ? 'Production' : 'Development');
    console.log('Project folder:', getProjectFolder());
});
</script>

<?php
// Include the media library (disable preview to avoid conflicts)
$disable_media_library_preview = true;
include_once 'includes/media-library.php';
render_media_library('logo');

// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files - Updated to use your existing CSS files
$page_title = 'School Settings';
$page_specific_css = [
    'css/image-selector.css',      // Uses your existing CSS
    'css/media-library.css'        // Uses your existing CSS
];
$page_specific_js = [
    'js/unified-image-uploader.js',
    'js/media-library.js',
    'js/media-modal.js'
];

// Additional inline styles for components not covered by existing CSS
$additional_styles = '
<style>
/* Additional styles for settings page components */
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
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 10px;
}

.image-verification {
    margin-top: 15px;
    padding: 12px;
    border-radius: 6px;
    font-size: 0.9rem;
    display: flex;
    align-items: flex-start;
}

.image-verification i {
    margin-right: 8px;
    font-size: 1.1rem;
    margin-top: 2px;
}

.image-verification.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.image-verification.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.path-suggestion {
    margin-top: 10px;
}

.path-suggestion code {
    background-color: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
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
        margin-bottom: 15px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>';

// Include additional styles in the content
$content = $additional_styles . $content;

// Include the layout
include 'layout.php';
?>