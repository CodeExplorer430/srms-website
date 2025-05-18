<?php
/**
 * Media Manager Page
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

// Get media statistics
$media_counts = [
    'total' => 0,
    'branding' => 0, // Added branding category
    'news' => 0,
    'events' => 0,
    'promotional' => 0,
    'facilities' => 0,
    'campus' => 0
];

// Initialize status variables
$delete_success = false;
$delete_error = null;

// Count images in each directory
$media_directories = [
    'branding' => '/assets/images/branding',
    'news' => '/assets/images/news',
    'events' => '/assets/images/events',
    'promotional' => '/assets/images/promotional',
    'facilities' => '/assets/images/facilities',
    'campus' => '/assets/images/campus'
];

// Helper function to get category icon
function get_category_icon($category) {
    switch ($category) {
        case 'branding':
            return 'bxs-palette';
        case 'news':
            return 'bxs-news';
        case 'events':
            return 'bx-calendar-event';
        case 'promotional':
            return 'bx-bullhorn';
        case 'facilities':
            return 'bx-building-house';
        case 'campus':
            return 'bx-landscape';
        default:
            return 'bx-image';
    }
}

foreach ($media_directories as $key => $dir) {
    // Normalize directory path for cross-platform compatibility
    $dir_path = str_replace(['\\', '/'], DS, $dir);
    $path = $_SERVER['DOCUMENT_ROOT'] . $dir_path;
    
    if (is_dir($path)) {
        // Use a platform-neutral pattern for globbing
        $pattern = $path . DS . "*.{jpg,jpeg,png,gif}";
        $files = glob($pattern, GLOB_BRACE);
        $count = count($files);
        $media_counts[$key] = $count;
        $media_counts['total'] += $count;
    }
}

// Handle file deletion if requested
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    $file_path = urldecode($_GET['file']);
    // Normalize file path for cross-platform compatibility
    $file_path = str_replace(['\\', '/'], DS, $file_path);
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
    
    // Verify path is within allowed directories
    $is_allowed = false;
    foreach ($media_directories as $dir) {
        $normalized_dir = str_replace(['\\', '/'], DS, $dir);
        if (strpos($file_path, $normalized_dir) === 0) {
            $is_allowed = true;
            break;
        }
    }
    
    if ($is_allowed && file_exists($full_path) && is_file($full_path)) {
        if (unlink($full_path)) {
            $delete_success = true;
        } else {
            $delete_error = 'Failed to delete the file. Check file permissions.';
        }
    } else {
        $delete_error = 'Invalid file path or file not found.';
    }
}

// Start output buffer for main content
ob_start();
?>

<?php if ($delete_success): ?>
    <div class="message message-success">
        <i class='bx bx-check-circle'></i>
        <span>File has been deleted successfully.</span>
    </div>
<?php endif; ?>

<?php if ($delete_error): ?>
    <div class="message message-error">
        <i class='bx bx-error-circle'></i>
        <span><?php echo $delete_error; ?></span>
    </div>
<?php endif; ?>

<!-- Media Stats Cards -->
<div class="dashboard-cards">
    <div class="card">
        <div>
            <div class="number"><?php echo $media_counts['total']; ?></div>
            <div class="label">Total Media Files</div>
        </div>
        <i class='bx bx-images'></i>
    </div>
    
    <div class="card">
        <div>
            <div class="number"><?php echo $media_counts['branding']; ?></div>
            <div class="label">Branding Assets</div>
        </div>
        <i class='bx bxs-palette'></i>
    </div>
    
    <div class="card">
        <div>
            <div class="number"><?php echo $media_counts['news']; ?></div>
            <div class="label">News Images</div>
        </div>
        <i class='bx bxs-news'></i>
    </div>
    
    <div class="card">
        <div>
            <div class="number"><?php echo $media_counts['events']; ?></div>
            <div class="label">Event Images</div>
        </div>
        <i class='bx bx-calendar-event'></i>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bx-images'></i> Media Library</h3>
        <div class="panel-actions">
            <button type="button" class="btn btn-primary" id="upload-media-btn">
                <i class='bx bx-upload'></i> Upload New Media
            </button>
            <button type="button" class="btn btn-success" id="bulk-upload-btn">
                <i class='bx bx-upload'></i> Bulk Upload
            </button>
            <a href="maintenance/setup-directories.php" class="btn btn-secondary">
                <i class='bx bx-folder-plus'></i> Setup Directories
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <!-- Category Filter Tabs -->
        <div class="filter-tabs">
            <button type="button" class="filter-tab active" data-category="all">
                <i class='bx bx-images'></i> All Media <span class="count">(<?php echo $media_counts['total']; ?>)</span>
            </button>
            <?php foreach ($media_directories as $category => $dir): ?>
            <button type="button" class="filter-tab" data-category="<?php echo $category; ?>">
                <i class='bx <?php echo get_category_icon($category); ?>'></i>
                <?php echo ucfirst($category); ?> <span class="count">(<?php echo $media_counts[$category]; ?>)</span>
            </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Bulk Actions Bar -->
        <div class="bulk-actions-bar" style="display: none;">
            <div class="selected-count">0 items selected</div>
            <div class="bulk-actions">
                <button type="button" class="btn btn-light select-all">
                    <i class='bx bx-select-multiple'></i> Select All
                </button>
                <button type="button" class="btn btn-light deselect-all">
                    <i class='bx bx-list-check'></i> Deselect All
                </button>
                <button type="button" class="btn btn-danger delete-selected">
                    <i class='bx bx-trash'></i> Delete Selected
                </button>
            </div>
        </div>
        
        <?php
        // Generate media sections by category
        foreach ($media_directories as $category => $dir):
            // Normalize directory path for cross-platform compatibility
            $dir_path = str_replace(['\\', '/'], DS, $dir);
            $path = $_SERVER['DOCUMENT_ROOT'] . $dir_path;
            
            if (is_dir($path)):
                // Use a platform-neutral pattern for globbing
                $pattern = $path . DS . "*.{jpg,jpeg,png,gif}";
                $files = glob($pattern, GLOB_BRACE);
                
                // Sort files by modification time (newest first)
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                
                // Get category icon
                $icon_class = get_category_icon($category);
        ?>
        <div class="media-section" data-category="<?php echo $category; ?>">
            <h4 class="section-title">
                <i class='bx <?php echo $icon_class; ?>'></i> <?php echo ucfirst($category); ?> Images
            </h4>
            
            <div class="media-grid">
                <?php if (empty($files)): ?>
                <div class="empty-state">
                    <i class='bx bx-image'></i>
                    <p>No images found in the <?php echo ucfirst($category); ?> category.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($files as $file): 
                        $file_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
                        $file_name = basename($file);
                        $file_time = filemtime($file);
                    ?>
                    <div class="media-item" data-category="<?php echo $category; ?>">
                        <div class="media-select">
                            <input type="checkbox" class="media-checkbox" data-path="<?php echo htmlspecialchars($file_path); ?>">
                        </div>
                        <div class="media-thumbnail">
                            <img src="<?php echo $file_path; ?>" alt="<?php echo htmlspecialchars($file_name); ?>">
                            <div class="media-actions">
                                <button type="button" class="media-action view" onclick="viewMedia('<?php echo htmlspecialchars(addslashes($file_path)); ?>', '<?php echo htmlspecialchars(addslashes($file_name)); ?>')">
                                    <i class='bx bx-fullscreen'></i>
                                </button>
                                <button type="button" class="media-action copy" onclick="copyPath('<?php echo htmlspecialchars(addslashes($file_path)); ?>')">
                                    <i class='bx bx-copy'></i>
                                </button>
                                <button type="button" class="media-action delete" onclick="confirmDelete('<?php echo htmlspecialchars(addslashes($file_path)); ?>')">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </div>
                        </div>
                        <div class="media-info">
                            <div class="media-name" title="<?php echo htmlspecialchars($file_name); ?>"><?php echo htmlspecialchars($file_name); ?></div>
                            <div class="media-date"><?php echo date('M j, Y', $file_time); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php 
            endif;
        endforeach; 
        ?>
    </div>
</div>

<!-- Media Upload Modal -->
<div id="upload-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class='bx bx-upload'></i> Upload Media</h3>
                <button type="button" class="modal-close" id="close-upload-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="media-upload-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="upload-file">Select Image File</label>
                        <input type="file" id="upload-file" name="quick_upload" accept="image/jpeg, image/png, image/gif" required>
                        <small class="form-text">Accepted formats: JPG, PNG, GIF. Max size: 2MB</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="upload-category">Category</label>
                        <select id="upload-category" name="quick_category" required>
                            <option value="branding">Branding</option>
                            <option value="news">News</option>
                            <option value="events">Events</option>
                            <option value="promotional">Promotional</option>
                            <option value="facilities">Facilities</option>
                            <option value="campus">Campus</option>
                        </select>
                    </div>
                    
                    <div class="upload-preview" style="display: none;">
                        <h4>Preview</h4>
                        <div class="image-preview">
                            <img src="" alt="Preview">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancel-upload">Cancel</button>
                <button type="submit" form="media-upload-form" class="btn btn-primary">Upload File</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Upload Modal -->
<div id="bulk-upload-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class='bx bx-upload'></i> Bulk Upload Media</h3>
                <button type="button" class="modal-close" id="close-bulk-upload-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="bulk-upload-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="bulk-upload-files">Select Multiple Image Files</label>
                        <input type="file" id="bulk-upload-files" name="bulk_files[]" accept="image/jpeg, image/png, image/gif" multiple required>
                        <small class="form-text">Accepted formats: JPG, PNG, GIF. Max size per file: 2MB</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk-upload-category">Target Category</label>
                        <select id="bulk-upload-category" name="bulk_category" required>
                            <option value="branding">Branding</option>
                            <option value="news">News</option>
                            <option value="events">Events</option>
                            <option value="promotional">Promotional</option>
                            <option value="facilities">Facilities</option>
                            <option value="campus">Campus</option>
                        </select>
                    </div>
                    
                    <div class="bulk-preview" style="display: none;">
                        <h4>Files to Upload (<span id="file-count">0</span>)</h4>
                        <div class="file-list">
                            <!-- File preview items will be added here dynamically -->
                        </div>
                    </div>
                    
                    <div id="upload-progress" style="display: none;">
                        <h4>Upload Progress</h4>
                        <div class="progress">
                            <div class="progress-bar" style="width: 0%;"></div>
                        </div>
                        <div class="progress-text">0%</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancel-bulk-upload">Cancel</button>
                <button type="submit" form="bulk-upload-form" class="btn btn-primary">Upload Files</button>
            </div>
        </div>
    </div>
</div>

<!-- Media Viewer Modal -->
<div id="media-viewer-modal" class="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="viewer-title">Image Title</h3>
                <button type="button" class="modal-close" id="close-media-viewer">&times;</button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="Image Preview" class="img-fluid" id="viewer-image">
                <div class="mt-3">
                    <div class="media-path" id="viewer-path">/path/to/image.jpg</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="copy-path-btn">
                    <i class='bx bx-copy'></i> Copy Path
                </button>
                <button type="button" class="btn btn-secondary" id="view-full-btn">
                    <i class='bx bx-link-external'></i> View Full Size
                </button>
                <button type="button" class="btn btn-danger" id="delete-btn">
                    <i class='bx bx-trash'></i> Delete Image
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-confirm-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class='bx bx-trash'></i> Confirm Deletion</h3>
                <button type="button" class="modal-close" id="close-delete-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="delete-count">0</span> selected files?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancel-delete">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">Delete Files</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Media Manager specific styles */
    .filter-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .filter-tab {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        color: #495057;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }
    
    .filter-tab i {
        margin-right: 8px;
        font-size: 18px;
    }
    
    .filter-tab .count {
        margin-left: 5px;
        font-size: 12px;
        color: #6c757d;
    }
    
    .filter-tab:hover {
        background-color: #e9ecef;
    }
    
    .filter-tab.active {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .filter-tab.active .count {
        color: rgba(255, 255, 255, 0.8);
    }
    
    .section-title {
        margin: 20px 0 15px;
        color: var(--primary-color);
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .section-title i {
        margin-right: 10px;
    }
    
    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
    }
    
    .media-item {
        position: relative;
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: var(--box-shadow);
        transition: all 0.2s;
    }
    
    .media-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .media-select {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .media-item:hover .media-select,
    .media-item.selected .media-select {
        opacity: 1;
    }
    
    .media-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .media-item.selected {
        border: 2px solid var(--primary-light);
    }
    
    .media-thumbnail {
        position: relative;
        padding-top: 100%;
        background-color: #f8f9fa;
    }
    
    .media-thumbnail img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .media-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .media-item:hover .media-actions {
        opacity: 1;
    }
    
    .media-action {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(255, 255, 255, 0.8);
        border-radius: 4px;
        border: none;
        color: var(--text-color);
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .media-action:hover {
        background-color: white;
    }
    
    .media-action.view:hover {
        color: var(--primary-light);
    }
    
    .media-action.copy:hover {
        color: var(--success-color);
    }
    
    .media-action.delete:hover {
        color: var(--danger-color);
    }
    
    .media-info {
        padding: 10px;
    }
    
    .media-name {
        font-weight: 500;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .media-date {
        font-size: 12px;
        color: #6c757d;
    }
    
    .bulk-actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .selected-count {
        font-weight: 500;
        color: var(--primary-color);
    }
    
    .bulk-actions {
        display: flex;
        gap: 10px;
    }
    
    .upload-preview, .bulk-preview {
        margin-top: 20px;
    }
    
    .image-preview {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        height: 200px;
    }
    
    .image-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .file-list {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 10px;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .file-item {
        display: flex;
        align-items: center;
        padding: 5px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .file-item:last-child {
        border-bottom: none;
    }
    
    .progress {
        height: 20px;
        background-color: #e9ecef;
        border-radius: 4px;
        margin: 10px 0;
        overflow: hidden;
    }
    
    .progress-bar {
        height: 100%;
        background-color: var(--primary-light);
        color: white;
        text-align: center;
        line-height: 20px;
        transition: width 0.3s;
    }
    
    .progress-text {
        text-align: center;
        font-size: 12px;
        color: #6c757d;
    }
    
    .media-path {
        font-family: monospace;
        font-size: 14px;
        padding: 8px;
        background-color: #f8f9fa;
        border-radius: 4px;
        margin-top: 10px;
        word-break: break-all;
    }
    
    @media (max-width: 768px) {
        .panel-actions {
            flex-direction: column;
            align-items: stretch;
        }
        
        .panel-actions .btn {
            margin-bottom: 5px;
        }
        
        .media-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
        
        .bulk-actions-bar {
            flex-direction: column;
        }
        
        .bulk-actions {
            margin-top: 10px;
            flex-direction: column;
            width: 100%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Upload Modal Functionality
        const uploadModal = document.getElementById('upload-modal');
        const uploadBtn = document.getElementById('upload-media-btn');
        const closeUploadModal = document.getElementById('close-upload-modal');
        const cancelUpload = document.getElementById('cancel-upload');
        const uploadForm = document.getElementById('media-upload-form');
        const uploadFile = document.getElementById('upload-file');
        const uploadPreview = document.querySelector('.upload-preview');
        const previewImg = uploadPreview.querySelector('img');
        
        // Open Upload Modal
        uploadBtn.addEventListener('click', function() {
            uploadModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
        
        // Close Upload Modal
        function closeUploadModalFn() {
            uploadModal.style.display = 'none';
            document.body.style.overflow = '';
            uploadForm.reset();
            uploadPreview.style.display = 'none';
        }
        
        closeUploadModal.addEventListener('click', closeUploadModalFn);
        cancelUpload.addEventListener('click', closeUploadModalFn);
        uploadModal.addEventListener('click', function(e) {
            if (e.target === uploadModal) {
                closeUploadModalFn();
            }
        });
        
        // Preview uploaded image
        uploadFile.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    uploadPreview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                uploadPreview.style.display = 'none';
            }
        });
        
        // Handle form submission
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            // Find submit button using a document-wide selector with the form attribute
            const submitButton = document.querySelector('button[type="submit"][form="media-upload-form"]');
            const originalText = submitButton ? submitButton.textContent : 'Upload File';
            
            // Only proceed with button modifications if the button was found
            if (submitButton) {
                // Show loading state
                submitButton.textContent = 'Uploading...';
                submitButton.disabled = true;
            }
            
            // Send AJAX request
            fetch('ajax/upload-media.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and close modal
                    alert('File uploaded successfully!');
                    closeUploadModalFn();
                    // Reload page to reflect changes
                    window.location.reload();
                } else {
                    alert('Upload failed: ' + (data.message || 'Unknown error'));
                    if (submitButton) {
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during upload.');
                if (submitButton) {
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                }
            });
        });
        
        // Media Viewer Modal
        const viewerModal = document.getElementById('media-viewer-modal');
        const viewerTitle = document.getElementById('viewer-title');
        const viewerImage = document.getElementById('viewer-image');
        const viewerPath = document.getElementById('viewer-path');
        const closeViewer = document.getElementById('close-media-viewer');
        const copyPathBtn = document.getElementById('copy-path-btn');
        const viewFullBtn = document.getElementById('view-full-btn');
        const deleteBtn = document.getElementById('delete-btn');
        let currentImagePath = '';
        
        // Close viewer
        function closeViewerFn() {
            viewerModal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        closeViewer.addEventListener('click', closeViewerFn);
        viewerModal.addEventListener('click', function(e) {
            if (e.target === viewerModal) {
                closeViewerFn();
            }
        });
        
        // Copy path button
        copyPathBtn.addEventListener('click', function() {
            const path = viewerPath.textContent;
            navigator.clipboard.writeText(path).then(() => {
                alert('Image path copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        });
        
        // View full size button
        viewFullBtn.addEventListener('click', function() {
            window.open(viewerImage.src, '_blank');
        });
        
        // Delete button
        deleteBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this image?')) {
                window.location.href = 'media-manager.php?action=delete&file=' + encodeURIComponent(currentImagePath);
            }
        });
        
        // Category Filter Tabs
        const filterTabs = document.querySelectorAll('.filter-tab');
        const mediaSections = document.querySelectorAll('.media-section');
        
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const category = this.getAttribute('data-category');
                
                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide sections
                if (category === 'all') {
                    mediaSections.forEach(section => {
                        section.style.display = 'block';
                    });
                } else {
                    mediaSections.forEach(section => {
                        if (section.getAttribute('data-category') === category) {
                            section.style.display = 'block';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                }
                
                // Reset selection
                resetSelection();
            });
        });
        
        // Bulk selection functionality
        const mediaCheckboxes = document.querySelectorAll('.media-checkbox');
        const bulkActionsBar = document.querySelector('.bulk-actions-bar');
        const selectedCountDisplay = document.querySelector('.selected-count');
        const selectAllBtn = document.querySelector('.select-all');
        const deselectAllBtn = document.querySelector('.deselect-all');
        const deleteSelectedBtn = document.querySelector('.delete-selected');
        const deleteConfirmModal = document.getElementById('delete-confirm-modal');
        const deleteCountDisplay = document.getElementById('delete-count');
        const confirmDeleteBtn = document.getElementById('confirm-delete');
        const cancelDeleteBtn = document.getElementById('cancel-delete');
        const closeDeleteModalBtn = document.getElementById('close-delete-modal');
        let selectedFiles = [];
        
        // Media item selection
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('media-checkbox')) {
                const checkbox = e.target;
                const mediaItem = checkbox.closest('.media-item');
                
                if (checkbox.checked) {
                    mediaItem.classList.add('selected');
                    selectedFiles.push(checkbox.getAttribute('data-path'));
                } else {
                    mediaItem.classList.remove('selected');
                    const index = selectedFiles.indexOf(checkbox.getAttribute('data-path'));
                    if (index > -1) {
                        selectedFiles.splice(index, 1);
                    }
                }
                
                updateBulkActionsBar();
            }
        });
        
        // Select All Button
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                const activeCategory = document.querySelector('.filter-tab.active').getAttribute('data-category');
                
                document.querySelectorAll('.media-checkbox').forEach(checkbox => {
                    const mediaItem = checkbox.closest('.media-item');
                    
                    // Only select visible items based on active category
                    if (activeCategory === 'all' || mediaItem.getAttribute('data-category') === activeCategory) {
                        checkbox.checked = true;
                        mediaItem.classList.add('selected');
                        
                        const path = checkbox.getAttribute('data-path');
                        if (!selectedFiles.includes(path)) {
                            selectedFiles.push(path);
                        }
                    }
                });
                
                updateBulkActionsBar();
            });
        }
        
        // Deselect All Button
        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', function() {
                resetSelection();
            });
        }
        
        // Delete Selected Button
        if (deleteSelectedBtn) {
            deleteSelectedBtn.addEventListener('click', function() {
                if (selectedFiles.length === 0) return;
                
                // Show confirmation modal
                deleteCountDisplay.textContent = selectedFiles.length;
                deleteConfirmModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        }
        
        // Confirm Delete Button
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                if (selectedFiles.length === 0) {
                    closeDeleteModal();
                    return;
                }
                
                // Disable button and show loading state
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Deleting...';
                
                // Fix paths before sending
                const fixedPaths = selectedFiles.map(path => normalizePath(path));
                
                // Send AJAX request to delete files
                fetch('ajax/bulk-delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ files: fixedPaths })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message with appropriate detail
                        if (data.error_count > 0) {
                            alert(data.message + '\n\nClick OK to refresh the page.');
                        } else {
                            alert('Successfully deleted ' + data.success_count + ' files.');
                        }
                        // Reload page to reflect changes
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                        closeDeleteModal();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during deletion: ' + error.message);
                    closeDeleteModal();
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = 'Delete Files';
                });
            });
        }
        
        // Cancel and Close Delete Modal Buttons
        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', closeDeleteModal);
        }
        
        if (closeDeleteModalBtn) {
            closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
        }
        
        // Close modal when clicking outside
        if (deleteConfirmModal) {
            deleteConfirmModal.addEventListener('click', function(e) {
                if (e.target === deleteConfirmModal) {
                    closeDeleteModal();
                }
            });
        }
        
        // Bulk Upload Modal Functionality
        const bulkUploadModal = document.getElementById('bulk-upload-modal');
        const bulkUploadBtn = document.getElementById('bulk-upload-btn');
        const closeBulkUploadModal = document.getElementById('close-bulk-upload-modal');
        const cancelBulkUpload = document.getElementById('cancel-bulk-upload');
        const bulkUploadForm = document.getElementById('bulk-upload-form');
        const bulkUploadFiles = document.getElementById('bulk-upload-files');
        const bulkUploadCategory = document.getElementById('bulk-upload-category');
        const bulkPreview = document.querySelector('.bulk-preview');
        const fileList = document.querySelector('.file-list');
        const fileCount = document.getElementById('file-count');
        const uploadProgress = document.getElementById('upload-progress');
        const progressBar = document.querySelector('.progress-bar');
        const progressText = document.querySelector('.progress-text');
        
        // Open Bulk Upload Modal
        if (bulkUploadBtn) {
            bulkUploadBtn.addEventListener('click', function() {
                bulkUploadModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        }
        
        // Close Bulk Upload Modal
        function closeBulkUploadModalFn() {
            bulkUploadModal.style.display = 'none';
            document.body.style.overflow = '';
            bulkUploadForm.reset();
            bulkPreview.style.display = 'none';
            fileList.innerHTML = '';
            fileCount.textContent = '0';
            uploadProgress.style.display = 'none';
            progressBar.style.width = '0%';
            progressText.textContent = '0%';
        }
        
        if (closeBulkUploadModal) {
            closeBulkUploadModal.addEventListener('click', closeBulkUploadModalFn);
        }
        
        if (cancelBulkUpload) {
            cancelBulkUpload.addEventListener('click', closeBulkUploadModalFn);
        }
        
        if (bulkUploadModal) {
            bulkUploadModal.addEventListener('click', function(e) {
                if (e.target === bulkUploadModal) {
                    closeBulkUploadModalFn();
                }
            });
        }
        
        // Preview selected files
        if (bulkUploadFiles) {
            bulkUploadFiles.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    bulkPreview.style.display = 'block';
                    fileList.innerHTML = '';
                    fileCount.textContent = this.files.length;
                    
                    // Display preview for each file
                    for (let i = 0; i < this.files.length; i++) {
                        const file = this.files[i];
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const fileItem = document.createElement('div');
                            fileItem.className = 'file-item';
                            fileItem.innerHTML = `
                                <img src="${e.target.result}" alt="${file.name}" style="width: 40px; height: 40px; object-fit: cover; margin-right: 10px;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 500; font-size: 14px;">${file.name}</div>
                                    <div style="font-size: 12px; color: #6c757d;">${(file.size / 1024).toFixed(2)} KB</div>
                                </div>
                            `;
                            
                            fileList.appendChild(fileItem);
                        };
                        
                        reader.readAsDataURL(file);
                    }
                } else {
                    bulkPreview.style.display = 'none';
                    fileList.innerHTML = '';
                    fileCount.textContent = '0';
                }
            });
        }
        
        // Handle bulk upload form submission
        if (bulkUploadForm) {
            bulkUploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!bulkUploadFiles.files || bulkUploadFiles.files.length === 0) {
                    alert('Please select files to upload');
                    return;
                }
                
                const formData = new FormData(this);
                // Find submit button using a document-wide selector with the form attribute
                const submitButton = document.querySelector('button[type="submit"][form="bulk-upload-form"]');
                const originalText = submitButton ? submitButton.textContent : 'Upload Files';
                
                // Only proceed with button modifications if the button was found
                if (submitButton) {
                    // Show loading state
                    submitButton.textContent = 'Uploading...';
                    submitButton.disabled = true;
                }
                
                uploadProgress.style.display = 'block';
                
                // Upload files with progress tracking
                const xhr = new XMLHttpRequest();
                
                xhr.open('POST', 'ajax/bulk-upload.php', true);
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        progressText.textContent = percentComplete + '%';
                    }
                });
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                alert(response.message);
                                // Reload page to reflect changes
                                window.location.reload();
                            } else {
                                alert('Upload failed: ' + response.message);
                                if (submitButton) {
                                    submitButton.textContent = originalText;
                                    submitButton.disabled = false;
                                }
                            }
                        } catch (error) {
                            console.error('Error parsing response:', error);
                            alert('Error processing server response. Please try again.');
                            if (submitButton) {
                                submitButton.textContent = originalText;
                                submitButton.disabled = false;
                            }
                        }
                    } else {
                        alert('Upload failed with status: ' + xhr.status);
                        if (submitButton) {
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        }
                    }
                };
                
                xhr.onerror = function() {
                    alert('Upload failed. Please check your connection and try again.');
                    if (submitButton) {
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                    }
                };
                
                xhr.send(formData);
            });
        }
        
        // Helper Functions
        function updateBulkActionsBar() {
            if (selectedFiles.length > 0) {
                bulkActionsBar.style.display = 'flex';
                selectedCountDisplay.textContent = selectedFiles.length + ' items selected';
            } else {
                bulkActionsBar.style.display = 'none';
            }
        }
        
        function resetSelection() {
            selectedFiles = [];
            document.querySelectorAll('.media-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.media-item').classList.remove('selected');
            });
            bulkActionsBar.style.display = 'none';
        }
        
        function closeDeleteModal() {
            deleteConfirmModal.style.display = 'none';
            document.body.style.overflow = '';
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = 'Delete Files';
        }
    });
    
    // Function to view media in modal
    function viewMedia(path, name) {
        const viewerModal = document.getElementById('media-viewer-modal');
        const viewerTitle = document.getElementById('viewer-title');
        const viewerImage = document.getElementById('viewer-image');
        const viewerPath = document.getElementById('viewer-path');
        
        viewerTitle.textContent = name;
        viewerImage.src = path;
        viewerPath.textContent = path;
        window.currentImagePath = path;
        
        viewerModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    // Function to copy image path
    function copyPath(path) {
        navigator.clipboard.writeText(path).then(() => {
            alert('Image path copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }
    
    // Function to confirm and delete image
    function confirmDelete(path) {
        if (confirm('Are you sure you want to delete this image?')) {
            window.location.href = 'media-manager.php?action=delete&file=' + encodeURIComponent(normalizePath(path));
        }
    }
    
    // Path normalization utility function
    function normalizePath(path) {
        if (!path) return '';
        // Replace all backslashes with forward slashes
        let normalized = path.replace(/\\/g, '/');
        // Remove any double slashes
        normalized = normalized.replace(/\/+/g, '/');
        // Ensure path starts with a single slash
        normalized = '/' + normalized.replace(/^\/+/, '');
        return normalized;
    }
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Media Library';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>