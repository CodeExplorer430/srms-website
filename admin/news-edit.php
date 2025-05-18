<?php
/**
 * News Article Editor
 * Allows admin users to add or edit news articles
 */

// Start session and check login
session_start();

// Check if user is logged in
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
$title = '';
$slug = '';
$content = '';
$summary = '';
$image = '';
$published_date = date('Y-m-d H:i:s');
$status = 'draft';
$featured = 0;
$errors = [];
$warnings = [];
$success = false;
$upload_result = false;

// Load article data if editing
if ($id > 0) {
    $article = $db->fetch_row("SELECT * FROM news WHERE id = $id");
    if ($article) {
        $title = $article['title'];
        $slug = $article['slug'];
        $content = $article['content'];
        $summary = $article['summary'];
        $image = $article['image'];
        $published_date = $article['published_date'];
        $status = $article['status'];
        $featured = $article['featured'];
    } else {
        $errors[] = 'Article not found';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $summary = isset($_POST['summary']) ? trim($_POST['summary']) : '';
    $image = isset($_POST['image']) ? trim($_POST['image']) : '';
    $published_date = isset($_POST['published_date']) ? trim($_POST['published_date']) : date('Y-m-d H:i:s');
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'draft';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate inputs
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($slug)) {
        // Generate slug from title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }
    
    if (empty($content)) {
        $errors[] = 'Content is required';
    }

    // Determine proper destination folder based on context
    $destination_folder = 'news';
    if (strpos(strtolower($title), 'event') !== false || 
        strpos(strtolower($summary), 'event') !== false) {
        $destination_folder = 'events';
    }
    
    // Process image upload first
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] != UPLOAD_ERR_NO_FILE) {
        // Log the upload attempt
        error_log('News Edit: Processing image upload for file: ' . $_FILES['image_upload']['name']);
        
        $upload_result = upload_image($_FILES['image_upload'], $destination_folder);
        if ($upload_result) {
            // Use the new image path immediately
            $image = $upload_result;
            
            // Normalize the path for display/storage
            $image = normalize_image_path($image);
            
            // Add a small delay to ensure file is written to disk
            usleep(500000); // 0.5 second delay
            
            // Clear file caches
            clearstatcache();
            
            // Verify the uploaded file exists
            if (verify_image_exists($image)) {
                error_log("News Edit: Verified uploaded image exists at path: {$image}");
            } else {
                error_log("News Edit: WARNING - Uploaded image not found at path: {$image}");
                $warnings[] = "Image was uploaded successfully but verification check failed. The image may not display correctly.";
            }
        } else {
            $errors[] = 'Image upload failed. Please check file type, size, and server permissions.';
        }
    }

    // Normalize the manually entered image path if no upload
    if (empty($upload_result) && isset($_POST['image'])) {
        $original_path = $_POST['image'];
        $image = normalize_image_path($original_path);
        error_log("News Edit: Normalized image path from '{$original_path}' to '{$image}'");
    }
    
    // Verify image path exists
    if (!empty($image) && !verify_image_exists($image)) {
        // Add warning but don't prevent saving
        $warnings[] = 'The specified image path could not be verified. Please check that the file exists.';
    }

    // Only use the text input if no file was uploaded
    if (!$upload_result) {
        $image = isset($_POST['image']) ? trim($_POST['image']) : '';
    }

    // Now standardize the path - this preserves the upload result if it exists
    $image = standardize_image_path($image);

    // Add after successful file upload in news-edit.php
    if ($upload_result) {
        // Verify file exists immediately after upload
        $full_upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_result;
        if (!file_exists($full_upload_path)) {
            error_log("File upload indicated success but file not found at: " . $full_upload_path);
            // Try flushing file cache
            clearstatcache(true, $full_upload_path);
        }
    }
    
    // Convert legacy paths to new asset structure
    if (!empty($image) && strpos($image, '/images/') === 0) {
        // Determine appropriate directory based on filename pattern
        if (strpos($image, 'School_Events') !== false) {
            $suggested_path = '/assets/images/events/' . basename($image);
            $image = $suggested_path;
        } else if (strpos($image, 'School_Announcement') !== false) {
            $suggested_path = '/assets/images/news/' . basename($image);
            $image = $suggested_path;
        }
    }

    // Make sure image path starts with a slash if it's not empty
    if (!empty($image) && strpos($image, '/') !== 0) {
        $image = '/' . $image;
    }

    // Normalize path: remove double slashes and ensure proper directory structure
    $image = preg_replace('#/+#', '/', $image);

    // Verify the image path is within the allowed directories
    $valid_image_path = false;
    $allowed_paths = [
        '/assets/images/news/', 
        '/assets/images/events/', 
        '/assets/images/promotional/',
        '/assets/images/campus/',
        '/assets/images/facilities/',
        '/images/'  // For backward compatibility with old paths
    ];
     
    foreach ($allowed_paths as $allowed_path) {
        if (strpos($image, $allowed_path) === 0) {
            $valid_image_path = true;
            break;
        }
    }
     
    // If path is invalid but not empty, provide more guidance
    if (!empty($image) && !$valid_image_path) {
        // Suggest correction to proper path
        $suggested_path = '/assets/images/news/' . basename($image);
        $errors[] = 'Invalid image path. Images should be located in one of the allowed directories. Did you mean: "' . $suggested_path . '"?';
    }
     
    // Verify file exists (if path is valid and not empty)
    if (!empty($image) && $valid_image_path) {
        if (!file_exists_with_alternatives($image)) {
            $errors[] = 'Image file not found at "' . $image . '". Please check the path or upload the image first.';
        }
    }

    $image = $db->escape($image);
    
    // Process if no errors
    if (empty($errors)) {
        $title = $db->escape($title);
        $slug = $db->escape($slug);
        $content = $db->escape($content);
        $summary = $db->escape($summary);
        $image = $db->escape($image);
        $published_date = $db->escape($published_date);
        $author_id = $_SESSION['admin_user_id'];
        
        if ($id > 0) {
            // Update existing article
            $sql = "UPDATE news SET 
                    title = '$title', 
                    slug = '$slug', 
                    content = '$content', 
                    summary = '$summary', 
                    image = '$image', 
                    published_date = '$published_date', 
                    status = '$status', 
                    featured = $featured 
                    WHERE id = $id";
                    
            if ($db->query($sql)) {
                $success = true;
            } else {
                $errors[] = 'An error occurred while updating the article';
            }
        } else {
            // Insert new article
            $sql = "INSERT INTO news (title, slug, content, summary, image, published_date, author_id, status, featured) 
                    VALUES ('$title', '$slug', '$content', '$summary', '$image', '$published_date', $author_id, '$status', $featured)";
                    
            if ($db->query($sql)) {
                $id = $db->insert_id();
                $success = true;
            } else {
                $errors[] = 'An error occurred while creating the article';
            }
        }
    }
}

// Start output buffer for main content
ob_start();
?>

<?php if ($success): ?>
<div class="message message-success">
    <i class='bx bx-check-circle'></i>
    <span>News article has been successfully <?php echo $id > 0 ? 'updated' : 'created'; ?>.</span>
    <a href="news-manage.php">Return to News Management</a>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="message message-error">
    <i class='bx bx-error-circle'></i>
    <div>
        <ul>
            <?php foreach ($errors as $error): ?>
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
        <h3 class="panel-title">
            <i class='bx bx-edit'></i> <?php echo $id > 0 ? 'Edit' : 'Add'; ?> News Article
        </h3>
    </div>
    
    <div class="panel-body">
        <form action="<?php echo $_SERVER['PHP_SELF'] . ($id > 0 ? '?id=' . $id : ''); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="slug">Slug (URL-friendly version of title)</label>
                <input type="text" id="slug" name="slug" class="form-control" value="<?php echo htmlspecialchars($slug); ?>">
                <small class="form-text">If left empty, a slug will be generated from the title.</small>
            </div>
            
            <div class="form-group">
                <label for="summary">Summary</label>
                <textarea id="summary" name="summary" class="form-control"><?php echo htmlspecialchars($summary); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($content); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">Image Path</label>
                <div class="image-input-group">
                    <input type="text" id="image" name="image" class="form-control" value="<?php echo htmlspecialchars($image); ?>">
                    <button type="button" class="btn btn-primary open-media-library" data-target="image">
                        <i class='bx bx-images'></i> Browse Media Library
                    </button>
                </div>
                <small class="form-text">Enter image path or use the media library to select an image</small>
                
                <div id="unified-image-preview" class="image-preview-container">
                    <div class="image-preview">
                        <div id="preview-placeholder" class="preview-placeholder">
                            <i class='bx bx-image'></i>
                            <span>No image selected</span>
                            <small>Select from media library or upload a new image</small>
                        </div>
                        <img src="<?php echo !empty($image) ? htmlspecialchars(get_display_url($image)) : ''; ?>" 
                            alt="Preview" 
                            id="preview-image" 
                            style="<?php echo empty($image) ? 'display: none;' : ''; ?>">
                    </div>
                    <div id="source-indicator" class="image-source-indicator"></div>
                </div>

                <div class="form-group">
                    <label for="image_upload">Upload New Image</label>
                    <input type="file" id="image_upload" name="image_upload" accept="image/jpeg, image/png, image/gif">
                    <small class="form-text">Max file size: 2MB. Accepted formats: JPEG, PNG, GIF</small>
                </div>
            </div>
            
            <?php if (!empty($image)): 
                 // More comprehensive image verification
                $server_root = $_SERVER['DOCUMENT_ROOT'];
                $image_full_path = $server_root . $image;
                
                // Get project folder
                $project_folder = '';
                if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
                    $project_folder = $matches[1]; // "srms-website"
                }
                
                // Try with project folder
                $path_with_project = $server_root . DIRECTORY_SEPARATOR . $project_folder . str_replace('/', DIRECTORY_SEPARATOR, $image);
                
                // Check both paths
                $image_exists = file_exists($image_full_path) || file_exists($path_with_project);
                $best_match = $image_exists ? '' : find_best_matching_image($image);
            ?>
                <div class="image-verification <?php echo ($image_exists || $best_match) ? 'success' : 'error'; ?>">
                    <?php if ($image_exists): ?>
                        <i class='bx bx-check-circle'></i> Image file exists at this path
                    <?php elseif ($best_match): ?>
                        <i class='bx bx-check-circle'></i> Similar image found at: <?php echo htmlspecialchars($best_match); ?>
                    <?php else: ?>
                        <i class='bx bx-x-circle'></i> Image file not found at this path
                        <div class="path-details">
                            Tried paths:
                            <ul>
                                <li><?php echo htmlspecialchars($image_full_path); ?></li>
                                <li><?php echo htmlspecialchars($path_with_project); ?></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="published_date">Published Date</label>
                <input type="datetime-local" id="published_date" name="published_date" class="form-control" 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($published_date)); ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Article Options</label>
                <div class="checkbox-styled">
                    <input type="checkbox" id="featured" name="featured" value="1" <?php echo $featured ? 'checked' : ''; ?>>
                    <label for="featured">Featured Article</label>
                    <small class="form-text">Featured articles appear prominently on the homepage.</small>
                </div>
            </div>

            <div class="form-actions">
                <a href="news-manage.php" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Save Article
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Disable any preview conflicts
$disable_media_library_preview = true;

// Include the media library
include_once '../admin/includes/media-library.php';
render_media_library('image');
?>

<script>
// Connect the unified image uploader with the media library
document.addEventListener('DOMContentLoaded', function() {
    console.log('Connecting unified uploader with media library...');
    
    // Initialize the image from existing path if any
    const imageInput = document.getElementById('image');
    if (imageInput && imageInput.value.trim()) {
        setTimeout(function() {
            if (window.UnifiedImageUploader) {
                window.UnifiedImageUploader.selectMediaItem(imageInput.value);
            }
        }, 200);
    }
    
    // Connect insert button from media library to unified uploader
    const mediaModal = document.getElementById('media-library-modal');
    if (mediaModal) {
        const insertButton = mediaModal.querySelector('.insert-media');
        if (insertButton) {
            insertButton.addEventListener('click', function() {
                try {
                    const selectedItem = mediaModal.querySelector('.media-item.selected');
                    if (selectedItem) {
                        const path = selectedItem.getAttribute('data-path');
                        if (window.UnifiedImageUploader && path) {
                            window.UnifiedImageUploader.selectMediaItem(path);
                        }
                    }
                } catch (error) {
                    console.error('Error connecting media library to unified uploader:', error);
                }
            });
        }
    }
});

// Function to convert relative path to full URL
function getImageUrl(path) {
    // If already a full URL, return as is
    if (path.startsWith('http')) return path;
    
    // Get current URL components
    const baseUrl = window.location.origin;
    const projectFolder = window.location.pathname.split('/')[1];
    
    // Ensure path starts with a slash
    path = path.startsWith('/') ? path : '/' + path;
    
    // Return full URL
    return baseUrl + '/' + projectFolder + path;
}

// Patch the global UnifiedImageUploader.selectMediaItem
if (window.UnifiedImageUploader) {
    const originalSelectMediaItem = window.UnifiedImageUploader.selectMediaItem;
    
    window.UnifiedImageUploader.selectMediaItem = function(path) {
        // Call original function for database storage
        // But pass a second parameter with the display URL
        originalSelectMediaItem(path, getImageUrl(path));
    };
}
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Define the page title and specific CSS/JS
$page_title = ($id > 0 ? 'Edit' : 'Add') . ' News Article';
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