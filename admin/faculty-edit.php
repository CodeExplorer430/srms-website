<?php
/**
 * Faculty Member Editor
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php';
$db = new Database();

// Initialize variables
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$name = '';
$position = '';
$qualifications = '';
$bio = '';
$photo = '';
$email = '';
$category_id = 0;
$display_order = 0;
$errors = [];
$success = false;

// Get faculty categories
$categories = $db->fetch_all("SELECT * FROM faculty_categories ORDER BY display_order");

// Load faculty data if editing
if ($id > 0) {
    $faculty = $db->fetch_row("SELECT * FROM faculty WHERE id = $id");
    if ($faculty) {
        $name = $faculty['name'];
        $position = $faculty['position'];
        $qualifications = $faculty['qualifications'];
        $bio = $faculty['bio'];
        $photo = $faculty['photo'];
        $email = $faculty['email'];
        $category_id = $faculty['category_id'];
        $display_order = $faculty['display_order'];
    } else {
        $errors[] = 'Faculty member not found';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';
    $qualifications = isset($_POST['qualifications']) ? trim($_POST['qualifications']) : '';
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    $photo = isset($_POST['photo']) ? trim($_POST['photo']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($position)) {
        $errors[] = 'Position is required';
    }
    
    // Process image upload if provided
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_result = upload_image($_FILES['image_upload'], 'people');
        if ($upload_result) {
            $photo = $upload_result;
        } else {
            $errors[] = 'Image upload failed. Please check file type and size.';
        }
    }
    
    // Process if no errors
    if (empty($errors)) {
        $name = $db->escape($name);
        $position = $db->escape($position);
        $qualifications = $db->escape($qualifications);
        $bio = $db->escape($bio);
        $photo = $db->escape($photo);
        $email = $db->escape($email);
        
        if ($id > 0) {
            // Update existing faculty member
            $sql = "UPDATE faculty SET 
                    name = '$name', 
                    position = '$position', 
                    qualifications = '$qualifications', 
                    bio = '$bio', 
                    photo = '$photo', 
                    email = '$email', 
                    category_id = $category_id, 
                    display_order = $display_order 
                    WHERE id = $id";
                    
            if ($db->query($sql)) {
                $success = true;
            } else {
                $errors[] = 'An error occurred while updating the faculty member';
            }
        } else {
            // Insert new faculty member
            $sql = "INSERT INTO faculty (name, position, qualifications, bio, photo, email, category_id, display_order) 
                    VALUES ('$name', '$position', '$qualifications', '$bio', '$photo', '$email', $category_id, $display_order)";
                    
            if ($db->query($sql)) {
                $id = $db->insert_id();
                $success = true;
            } else {
                $errors[] = 'An error occurred while creating the faculty member';
            }
        }
    }
}

$disable_media_library_preview = true;

// Start output buffer for main content
ob_start();
?>

<?php if($success): ?>
    <div class="message message-success">
        <i class='bx bx-check-circle'></i>
        <span>Faculty member has been <?php echo $id > 0 ? 'updated' : 'created'; ?> successfully.</span>
        <a href="faculty-manage.php">Return to Faculty Management</a>
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

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">
            <i class='bx bx-user-plus'></i> <?php echo $id > 0 ? 'Edit' : 'Add'; ?> Faculty Member
        </h3>
    </div>
    
    <div class="panel-body">
        <form action="<?php echo $_SERVER['PHP_SELF'] . ($id > 0 ? '?id=' . $id : ''); ?>" method="post" enctype="multipart/form-data">
            <div class="form-section">
                <h4 class="section-title">Basic Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($position); ?>" required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="0">-- Select Category --</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" id="display_order" name="display_order" value="<?php echo $display_order; ?>" min="0" class="form-control">
                            <small class="form-text">Lower numbers will appear first within their category.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4 class="section-title">Profile Photo</h4>
                <div class="form-group">
                    <label for="photo">Photo Path</label>
                    <div class="image-input-group">
                        <input type="text" id="photo" name="photo" class="form-control" value="<?php echo htmlspecialchars($photo); ?>">
                        <button type="button" class="btn btn-primary open-media-library" data-target="photo">
                            <i class='bx bx-images'></i> Browse Media Library
                        </button>
                    </div>
                    <small class="form-text">Enter image path or use the media library to select an image</small>
                    
                    <div id="unified-image-preview" class="image-preview-container">
                        <div class="image-preview">
                            <div id="preview-placeholder" class="preview-placeholder" style="<?php echo !empty($photo) ? 'display: none;' : ''; ?>">
                                <i class='bx bx-image'></i>
                                <span>No image selected</span>
                                <small>Select from media library or upload a new image</small>
                            </div>
                            <img src="<?php echo !empty($photo) ? htmlspecialchars($photo) : ''; ?>" 
                                alt="Preview" 
                                id="preview-image" 
                                style="<?php echo empty($photo) ? 'display: none;' : ''; ?>">
                        </div>
                        <div id="source-indicator" class="image-source-indicator"></div>
                    </div>

                    <div class="form-group">
                        <label for="image_upload">Upload New Image</label>
                        <input type="file" id="image_upload" name="image_upload" accept="image/jpeg, image/png, image/gif">
                        <small class="form-text">Max file size: 2MB. Accepted formats: JPEG, PNG, GIF</small>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4 class="section-title">Additional Information</h4>
                <div class="form-group">
                    <label for="qualifications">Qualifications</label>
                    <textarea id="qualifications" name="qualifications" class="form-control" rows="3"><?php echo htmlspecialchars($qualifications); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="bio">Biography</label>
                    <textarea id="bio" name="bio" class="form-control rich-editor" rows="6"><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="faculty-manage.php" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Save Faculty Member
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TinyMCE Rich Text Editor -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Rich Text Editor
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '.rich-editor',
                height: 300,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
            });
        }
        
        // Initialize the image preview system
        const photoInput = document.getElementById('photo');
        const previewImage = document.getElementById('preview-image');
        const previewPlaceholder = document.getElementById('preview-placeholder');
        const sourceIndicator = document.getElementById('source-indicator');
        const previewContainer = document.getElementById('unified-image-preview');
        
        function updatePreview(path) {
            if (path && path.trim()) {
                // Show image preview
                previewImage.src = path;
                previewImage.style.display = 'block';
                previewPlaceholder.style.display = 'none';
                
                // Add library mode styling
                previewContainer.classList.add('library-mode');
                sourceIndicator.innerHTML = '<span><i class="bx bx-link"></i> Media Library</span>';
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
        if (photoInput && photoInput.value.trim()) {
            updatePreview(photoInput.value);
        }
        
        // Handle changes to the input field directly
        if (photoInput) {
            photoInput.addEventListener('input', function() {
                updatePreview(this.value);
            });
        }
        
        // Create global function for media library integration
        window.UnifiedImageUploader = {
            selectMediaItem: function(path) {
                if (photoInput) {
                    photoInput.value = path;
                    updatePreview(path);
                }
            }
        };
        
        // Compatibility layer for older code
        window.selectMediaItem = function(path) {
            window.UnifiedImageUploader.selectMediaItem(path);
        };
        
        // Image upload preview
        const imageUpload = document.getElementById('image_upload');
        if (imageUpload) {
            imageUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                        previewPlaceholder.style.display = 'none';
                        
                        // Update styling for upload mode
                        previewContainer.classList.remove('library-mode');
                        previewContainer.classList.add('upload-mode');
                        sourceIndicator.innerHTML = `<span><i class="bx bx-upload"></i> Upload: <strong>${imageUpload.files[0].name}</strong></span>`;
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    });
</script>

<?php
// Include the media library
include_once '../admin/includes/media-library.php';
render_media_library('photo');

// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = ($id > 0 ? 'Edit' : 'Add') . ' Faculty Member';
$page_specific_css = [
    '../assets/css/image-selector.css',
    '../assets/css/media-library.css'
];
$page_specific_js = [
    '../assets/js/media-library.js',
    '../assets/js/unified-image-uploader.js'
];

// Include the layout
include 'layout.php';
?>