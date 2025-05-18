<?php
/**
 * Admin Dashboard Main Page
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

// Include necessary files
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php';
$db = new Database();

// Get counts for dashboard
$news_count = $db->fetch_row("SELECT COUNT(*) as count FROM news")['count'];
$users_count = $db->fetch_row("SELECT COUNT(*) as count FROM users")['count'];
$contacts_count = $db->fetch_row("SELECT COUNT(*) as count FROM contact_submissions WHERE status = 'new'")['count'];

// Get media counts
$media_counts = [
    'total' => 0,
    'news' => 0,
    'events' => 0,
    'promotional' => 0,
    'facilities' => 0,
    'campus' => 0
];

// Count images in each directory
$media_directories = [
    'news' => '/assets/images/news',
    'events' => '/assets/images/events',
    'promotional' => '/assets/images/promotional',
    'facilities' => '/assets/images/facilities',
    'campus' => '/assets/images/campus'
];

// Get document root without trailing slash
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

// Determine project folder from SITE_URL
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // Should be "srms-website"
}

foreach ($media_directories as $key => $dir) {
    // Build the full server path INCLUDING project folder
    $path = $doc_root;
    if (!empty($project_folder)) {
        $path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $path .= str_replace('/', DIRECTORY_SEPARATOR, $dir);
    
    if (is_dir($path)) {
        // Use a platform-neutral pattern for globbing
        $pattern = $path . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}";
        $files = glob($pattern, GLOB_BRACE);
        $count = count($files);
        $media_counts[$key] = $count;
        $media_counts['total'] += $count;
    }
}

// Get recent submissions
$recent_contacts = $db->fetch_all("SELECT * FROM contact_submissions ORDER BY submission_date DESC LIMIT 5");

// Get recent media uploads
$recent_media = [];
foreach ($media_directories as $key => $dir) {
    // Build the correct server path
    $path = $doc_root;
    if (!empty($project_folder)) {
        $path .= DIRECTORY_SEPARATOR . $project_folder;
    }
    $path .= str_replace('/', DIRECTORY_SEPARATOR, $dir);
    
    if (is_dir($path)) {
        $files = glob($path . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                // Convert server path to web path using improved function
                $file_web_path = filesystem_path_to_url($file, true);
                
                $recent_media[] = [
                    'path' => $file_web_path,
                    'name' => basename($file),
                    'type' => $key,
                    'modified' => filemtime($file)
                ];
            }
        }
    }
}

// Sort by modified time (newest first) and limit to 6
usort($recent_media, function($a, $b) {
    return $b['modified'] - $a['modified'];
});
$recent_media = array_slice($recent_media, 0, 6);

// Get recent news articles
$recent_news = $db->fetch_all("SELECT * FROM news ORDER BY published_date DESC LIMIT 5");

// Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'server_type' => defined('SERVER_TYPE') ? SERVER_TYPE : 'Unknown',
    'database_type' => 'MySQL/MariaDB',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

// Get the content for the layout
ob_start();
?>

<!-- Dashboard Stats Cards -->
<div class="dashboard-cards">
    <div class="card news">
        <div>
            <div class="number"><?php echo $news_count; ?></div>
            <div class="label">News Articles</div>
        </div>
        <i class='bx bxs-news'></i>
    </div>
    
    <div class="card messages">
        <div>
            <div class="number"><?php echo $contacts_count; ?></div>
            <div class="label">New Messages</div>
        </div>
        <i class='bx bxs-message-detail'></i>
    </div>
    
    <div class="card users">
        <div>
            <div class="number"><?php echo $users_count; ?></div>
            <div class="label">Admin Users</div>
        </div>
        <i class='bx bxs-user'></i>
    </div>
    
    <div class="card media">
        <div>
            <div class="number"><?php echo $media_counts['total']; ?></div>
            <div class="label">Media Files</div>
        </div>
        <i class='bx bx-images'></i>
    </div>
</div>

<div class="dashboard-panels">
    <div class="row">
        <div class="col-lg-8">
            <!-- Recent Contact Submissions Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class='bx bxs-envelope'></i> Recent Contact Submissions
                    </h3>
                    <div class="panel-actions">
                        <a href="contact-submissions.php" class="btn btn-light btn-sm">
                            View All <i class='bx bx-chevron-right'></i>
                        </a>
                    </div>
                </div>
                
                <div class="panel-body-scrollable">
                    <?php if(empty($recent_contacts)): ?>
                    <div class="empty-state">
                        <i class='bx bx-envelope-open'></i>
                        <p>No recent contact submissions.</p>
                    </div>
                    <?php else: ?>
                        <ul class="contact-list">
                            <?php foreach($recent_contacts as $contact): ?>
                            <li class="contact-item">
                                <div class="contact-card">
                                    <div class="contact-status">
                                        <div class="status-indicator status-<?php echo $contact['status'] ?: 'new'; ?>"></div>
                                    </div>
                                    <div class="contact-content">
                                        <div class="contact-header">
                                            <div class="contact-info">
                                                <div class="contact-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                                                <div class="contact-email"><?php echo htmlspecialchars($contact['email']); ?></div>
                                            </div>
                                            <div class="contact-date">
                                                <i class='bx bx-calendar'></i> <?php echo date('M j, Y g:i A', strtotime($contact['submission_date'])); ?>
                                            </div>
                                        </div>
                                        <div class="contact-subject"><?php echo htmlspecialchars($contact['subject']); ?></div>
                                        <div class="contact-message"><?php echo substr(htmlspecialchars($contact['message']), 0, 150) . (strlen($contact['message']) > 150 ? '...' : ''); ?></div>
                                        <div class="contact-actions">
                                            <a href="contact-submissions.php?action=view&id=<?php echo $contact['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class='bx bx-show'></i> View Details
                                            </a>
                                            <a href="reply.php?id=<?php echo $contact['id']; ?>" class="btn btn-success btn-sm">
                                                <i class='bx bx-reply'></i> Reply
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent News Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class='bx bxs-news'></i> Recent News
                    </h3>
                    <div class="panel-actions">
                        <a href="news-manage.php" class="btn btn-light btn-sm">
                            Manage News <i class='bx bx-chevron-right'></i>
                        </a>
                    </div>
                </div>
                
                <div class="panel-body-scrollable">
                    <?php if(empty($recent_news)): ?>
                    <div class="empty-state">
                        <i class='bx bx-news'></i>
                        <p>No news articles published yet.</p>
                    </div>
                    <?php else: ?>
                        <ul class="news-list">
                            <?php foreach($recent_news as $article): ?>
                            <li class="news-item">
                                <div class="news-card">
                                    <?php
                                    // FIXED: Use the robust image handling functions
                                    $display_image = false;
                                    $image_url = '';
                                    
                                    if (!empty($article['image'])) {
                                        // Normalize the path for consistency
                                        $normalized_path = normalize_image_path($article['image']);
                                        
                                        // Verify if image exists using robust cross-platform function
                                        if (verify_image_exists($normalized_path)) {
                                            $display_image = true;
                                            // Get correct URL with project folder consideration
                                            $image_url = get_correct_image_url($normalized_path);
                                        } else {
                                            // Log the issue for debugging
                                            error_log("Admin dashboard: News image not found: " . $normalized_path);
                                            
                                            // Try to use any available image match
                                            $alternative_path = find_best_matching_image($normalized_path);
                                            if ($alternative_path) {
                                                $display_image = true;
                                                $image_url = get_correct_image_url($alternative_path);
                                                error_log("Admin dashboard: Using alternative image: " . $alternative_path);
                                            }
                                        }
                                    }
                                    ?>
                                    <?php if($display_image): ?>
                                    <div class="news-image">
                                        <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                    </div>
                                    <?php endif; ?>
                                    <div class="news-content">
                                        <div class="news-header">
                                            <h4 class="news-title"><?php echo htmlspecialchars($article['title']); ?></h4>
                                            <div class="news-meta">
                                                <span class="news-date"><i class='bx bx-calendar'></i> <?php echo date('M j, Y', strtotime($article['published_date'])); ?></span>
                                                <span class="news-status status-<?php echo $article['status']; ?>"><?php echo ucfirst($article['status']); ?></span>
                                                <?php if($article['featured']): ?>
                                                <span class="news-featured">Featured</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="news-summary"><?php echo htmlspecialchars($article['summary']); ?></div>
                                        <div class="news-actions">
                                            <a href="news-edit.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class='bx bx-edit'></i> Edit
                                            </a>
                                            <a href="../news.php?id=<?php echo $article['id']; ?>" class="btn btn-light btn-sm" target="_blank">
                                                <i class='bx bx-link-external'></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Media Library Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class='bx bx-images'></i> Media Library
                    </h3>
                    <div class="panel-actions">
                        <a href="media-manager.php" class="btn btn-light btn-sm">
                            View All <i class='bx bx-chevron-right'></i>
                        </a>
                    </div>
                </div>
                
                <div class="panel-body">
                    <div class="media-actions">
                        <button class="btn btn-primary btn-block" id="upload-media-btn">
                            <i class='bx bx-upload'></i> Upload New Media
                        </button>
                    </div>
                    
                    <?php if (empty($recent_media)): ?>
                    <div class="empty-state">
                        <i class='bx bx-image'></i>
                        <p>No media files have been uploaded yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="media-grid">
                        <?php foreach ($recent_media as $media): ?>
                        <?php 
                            // FIXED: Use the correct image URL function
                            $media_url = get_correct_image_url($media['path']);
                        ?>
                        <div class="media-item">
                            <div class="media-thumbnail">
                                <img src="<?php echo $media_url; ?>" alt="<?php echo htmlspecialchars($media['name']); ?>">
                                <span class="media-badge badge-<?php echo $media['type']; ?>"><?php echo ucfirst($media['type']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Info Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class='bx bx-server'></i> System Information
                    </h3>
                </div>
                
                <div class="panel-body">
                    <ul class="system-info-list">
                        <li>
                            <span class="info-label">PHP Version</span>
                            <span class="info-value"><?php echo $system_info['php_version']; ?></span>
                        </li>
                        <li>
                            <span class="info-label">Server Software</span>
                            <span class="info-value"><?php echo $system_info['server_software']; ?></span>
                        </li>
                        <li>
                            <span class="info-label">Server Type</span>
                            <span class="info-value"><?php echo $system_info['server_type']; ?></span>
                        </li>
                        <li>
                            <span class="info-label">Database</span>
                            <span class="info-value"><?php echo $system_info['database_type']; ?></span>
                        </li>
                        <li>
                            <span class="info-label">Upload Max Size</span>
                            <span class="info-value"><?php echo $system_info['upload_max_filesize']; ?></span>
                        </li>
                        <li>
                            <span class="info-label">Post Max Size</span>
                            <span class="info-value"><?php echo $system_info['post_max_size']; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Quick Actions Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class='bx bx-rocket'></i> Quick Actions
                    </h3>
                </div>
                
                <div class="panel-body">
                    <div class="quick-actions">
                        <a href="news-edit.php" class="quick-action-btn">
                            <i class='bx bx-plus'></i>
                            <span>Add News</span>
                        </a>
                        <a href="maintenance/setup-directories.php" class="quick-action-btn">
                            <i class='bx bx-folder-plus'></i>
                            <span>Setup Directories</span>
                        </a>
                        <a href="settings.php" class="quick-action-btn">
                            <i class='bx bx-cog'></i>
                            <span>Settings</span>
                        </a>
                        <a href="users.php" class="quick-action-btn">
                            <i class='bx bx-user-plus'></i>
                            <span>Manage Users</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
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
                        <small>Accepted formats: JPG, PNG, GIF. Max size: 2MB</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="upload-category">Category</label>
                        <select id="upload-category" name="quick_category" required>
                            <option value="news">News</option>
                            <option value="events">Events</option>
                            <option value="promotional">Promotional</option>
                            <option value="facilities">Facilities</option>
                            <option value="campus">Campus</option>
                        </select>
                    </div>
                    
                    <div class="upload-preview">
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

<script>
    // Upload Modal Functionality
    document.addEventListener('DOMContentLoaded', function() {
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
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Show loading state
            submitButton.textContent = 'Uploading...';
            submitButton.disabled = true;
            
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
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during upload.');
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });
    });
</script>

<style>
/* Add some dashboard specific styles */
.dashboard-panels {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.row {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    margin: 0;
}

.col-lg-8 {
    flex: 0 0 66.666667%;
    max-width: 66.666667%;
    padding: 0 10px;
}

.col-lg-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
    padding: 0 10px;
}

.contact-list, .news-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.contact-item, .news-item {
    border-bottom: 1px solid var(--border-color);
    padding: 15px 0;
}

.contact-item:last-child, .news-item:last-child {
    border-bottom: none;
}

.contact-card, .news-card {
    display: flex;
}

.contact-status {
    flex-shrink: 0;
    width: 40px;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 10px;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.status-new {
    background-color: var(--danger-color);
}

.status-read {
    background-color: var(--primary-light);
}

.status-replied {
    background-color: var(--success-color);
}

.contact-content {
    flex-grow: 1;
}

.contact-header, .news-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.contact-name {
    font-weight: 600;
    color: var(--primary-color);
}

.contact-email {
    color: #6c757d;
    font-size: 13px;
}

.contact-date, .news-date {
    font-size: 12px;
    color: #6c757d;
}

.contact-subject {
    font-weight: 500;
    margin-bottom: 5px;
}

.contact-message, .news-summary {
    color: #6c757d;
    margin-bottom: 10px;
    line-height: 1.4;
}

.contact-actions, .news-actions {
    display: flex;
    gap: 10px;
}

.news-image {
    width: 120px;
    height: 80px;
    flex-shrink: 0;
    margin-right: 15px;
    border-radius: 4px;
    overflow: hidden;
}

.news-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.news-title {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.news-meta {
    display: flex;
    gap: 10px;
    font-size: 12px;
    color: #6c757d;
}

.news-status, .news-featured {
    padding: 2px 8px;
    border-radius: 50px;
    font-size: 11px;
}

.status-published {
    background-color: rgba(25, 135, 84, 0.1);
    color: var(--success-color);
}

.status-draft {
    background-color: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.news-featured {
    background-color: rgba(255, 193, 7, 0.1);
    color: #664d03;
}

.media-actions {
    margin-bottom: 15px;
}

.btn-block {
    display: block;
    width: 100%;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.media-thumbnail {
    position: relative;
    padding-top: 100%;
    border-radius: 4px;
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.media-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    padding: 2px 5px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 500;
    color: white;
}

.badge-news {
    background-color: var(--primary-light);
}

.badge-events {
    background-color: var(--success-color);
}

.badge-promotional {
    background-color: var(--danger-color);
}

.badge-facilities {
    background-color: var(--warning-color);
}

.badge-campus {
    background-color: #6f42c1;
}

.system-info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.system-info-list li {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color);
}

.system-info-list li:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: var(--text-color);
}

.info-value {
    color: #6c757d;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: var(--primary-color);
    transition: all 0.2s;
}

.quick-action-btn:hover {
    background-color: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

.quick-action-btn i {
    font-size: 24px;
    margin-bottom: 5px;
}

.empty-state {
    text-align: center;
    padding: 30px;
    color: #6c757d;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 10px;
    opacity: 0.5;
}

/* Card Colors */
.card.news::before {
    background-color: var(--primary-light);
}

.card.news i {
    color: rgba(60, 145, 230, 0.2);
}

.card.messages::before {
    background-color: var(--danger-color);
}

.card.messages i {
    color: rgba(220, 53, 69, 0.2);
}

.card.users::before {
    background-color: var(--success-color);
}

.card.users i {
    color: rgba(25, 135, 84, 0.2);
}

.card.media::before {
    background-color: #6f42c1;
}

.card.media i {
    color: rgba(111, 66, 193, 0.2);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    overflow-y: auto;
    padding: 30px;
}

.modal-dialog {
    max-width: 600px;
    margin: 30px auto;
}

.modal-content {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
}

.modal-title {
    margin: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
}

.modal-title i {
    margin-right: 10px;
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
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.upload-preview {
    margin-top: 20px;
    display: none;
}

.image-preview {
    width: 100%;
    height: 200px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
}

.image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Media Queries */
@media (max-width: 992px) {
    .col-lg-8, .col-lg-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .contact-card, .news-card {
        flex-direction: column;
    }
    
    .news-image {
        width: 100%;
        height: 150px;
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .contact-header, .news-header {
        flex-direction: column;
    }
    
    .contact-date, .news-meta {
        margin-top: 10px;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();

// Define the page title and specific CSS/JS
$page_title = 'Admin Dashboard';
$page_specific_css = []; // Add any page-specific CSS files
$page_specific_js = []; // Add any page-specific JS files

// Include the layout
include 'layout.php';
?>