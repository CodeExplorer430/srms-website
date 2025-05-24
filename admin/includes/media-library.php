<?php
/**
 * Media Library Component for SRMS Admin Panel
 * Updated for Hostinger compatibility
 * Version: 2.0
 */
function get_media_library_assets() {
    // Get document root
    $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    
    // Enhanced environment detection
    $is_production = defined('IS_PRODUCTION') && IS_PRODUCTION;
    
    // Get project folder from SITE_URL with enhanced detection
    $project_folder = '';
    if (!$is_production && preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $project_folder = $matches[1]; // Should be "srms-website" in development
    }
    
    // Log information about the media scan
    error_log("Media Library: Scanning with environment: " . ($is_production ? "Production" : "Development"));
    error_log("Media Library: Project folder: " . ($project_folder ? $project_folder : "None (root level)"));
    
    // Scan directories for images
    $media_categories = [
        'news' => '/assets/images/news/',
        'events' => '/assets/images/events/',
        'promotional' => '/assets/images/promotional/',
        'facilities' => '/assets/images/facilities/',
        'campus' => '/assets/images/campus/',
        'branding' => '/assets/images/branding/'
    ];
    
    $all_media = [];
    
    foreach ($media_categories as $category => $path) {
        // Build the correct server path - handle production vs development environments
        $server_path = $doc_root;
        if (!$is_production && !empty($project_folder)) {
            $server_path .= DIRECTORY_SEPARATOR . $project_folder;
        }
        $server_path .= str_replace('/', DIRECTORY_SEPARATOR, $path);
        
        error_log("Media Library: Checking directory: " . $server_path);
        
        // Create directory if it doesn't exist
        if (!is_dir($server_path)) {
            error_log("Media Library: Creating directory: " . $server_path);
            // Try to create the directory with appropriate permissions
            @mkdir($server_path, 0755, true);
        }
        
        if (is_dir($server_path)) {
            $files = scandir($server_path);
            $media_files = [];
            
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') continue;
                
                // Server file path and absolute path
                $file_path = $path . $file;
                $full_path = $server_path . DIRECTORY_SEPARATOR . $file;
                
                if (is_file($full_path) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                    // Get dimensions for the image
                    $dimensions = @getimagesize($full_path) ?: [0, 0];
                    
                    // Generate web URL based on environment
                    $web_url = '';
                    if (!$is_production && !empty($project_folder)) {
                        $web_url = '/' . $project_folder . $file_path;
                    } else {
                        $web_url = $file_path;
                    }
                    
                    // Log the path mapping for debugging
                    error_log("Media Library: Mapped path {$file_path} to URL {$web_url}");
                    
                    $media_files[] = [
                        'name' => $file,
                        'path' => $file_path,  // For internal references (relative path)
                        'url' => $web_url,     // For browser URLs (with or without project folder)
                        'size' => filesize($full_path),
                        'modified' => filemtime($full_path),
                        'dimensions' => $dimensions
                    ];
                }
            }
            
            $all_media[$category] = $media_files;
            error_log("Media Library: Found " . count($media_files) . " files in category: " . $category);
        }
    }
    
    return $all_media;
}

// Output the media library modal HTML
function render_media_library($target_field = 'image') {
    global $disable_media_library_preview;
    $media = get_media_library_assets();
    
    // Enhanced environment detection for JavaScript
    $is_production = defined('IS_PRODUCTION') && IS_PRODUCTION;
?>
<div id="media-library-modal" class="media-library-modal">
    <div class="media-library-content">
        <div class="media-library-header">
            <h2><i class='bx bx-images'></i> Media Library</h2>
            <span class="media-library-close">&times;</span>
        </div>
        
        <div class="media-library-search">
            <div class="search-box">
                <i class='bx bx-search'></i>
                <input type="text" id="media-search" placeholder="Search media files...">
            </div>
            <div class="filter-dropdown">
                <select id="media-filter">
                    <option value="all">All Categories</option>
                    <option value="branding">Branding</option>
                    <option value="news">News</option>
                    <option value="events">Events</option>
                    <option value="promotional">Promotional</option>
                    <option value="facilities">Facilities</option>
                    <option value="campus">Campus</option>
                    <?php if(isset($media['legacy'])): ?>
                    <option value="legacy">Legacy Images</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        
        <div class="media-library-container">
            <div class="media-sidebar">
                <h3>Categories</h3>
                <ul>
                    <li data-category="all" class="active">All Media</li>
                    <li data-category="branding">Branding</li>
                    <?php foreach (array_keys($media) as $category): ?>
                    <?php if ($category !== 'branding'): // Skip branding as we've already added it ?>
                    <li data-category="<?php echo $category; ?>"><?php echo ucfirst($category); ?></li>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                
                <div class="upload-section">
                    <h3>Upload New Image</h3>
                    <form id="quick-upload-form" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="quick-upload">Select Image</label>
                            <input type="file" id="quick-upload" name="quick_upload" accept="image/jpeg, image/png, image/gif">
                        </div>
                        <div class="form-group">
                            <label for="quick-category">Category</label>
                            <select id="quick-category" name="quick_category">
                                <option value="branding">Branding</option>
                                <option value="news">News</option>
                                <option value="events">Events</option>
                                <option value="promotional">Promotional</option>
                                <option value="facilities">Facilities</option>
                                <option value="campus">Campus</option>
                            </select>
                        </div>
                        <button type="submit" class="quick-upload-btn">Upload</button>
                    </form>
                </div>
            </div>
            
            <div class="media-grid">
                <?php foreach ($media as $category => $files): ?>
                    <div class="category-section" data-category="<?php echo $category; ?>">
                        <?php if(empty($files)): ?>
                            <div class="no-media-message">
                                <p>No images in <?php echo ucfirst($category); ?> category.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                                <div class="media-item" data-path="<?php echo htmlspecialchars($file['path']); ?>">
                                    <div class="media-thumbnail">
                                        <img src="<?php echo htmlspecialchars($file['url']); ?>" alt="<?php echo htmlspecialchars($file['name']); ?>">
                                    </div>
                                    <div class="media-info">
                                        <div class="media-name"><?php echo htmlspecialchars($file['name']); ?></div>
                                        <div class="media-dimensions">
                                            <?php 
                                            if(isset($file['dimensions'][0]) && $file['dimensions'][0] > 0) {
                                                echo $file['dimensions'][0] . '×' . $file['dimensions'][1];
                                            } else {
                                                echo 'Unknown size';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="media-library-footer">
            <?php if (!isset($disable_media_library_preview) || $disable_media_library_preview !== true): ?>
            <div class="media-preview">
                <div class="preview-image"></div>
                <div class="preview-details"></div>
            </div>
            <?php endif; ?>
            <button class="insert-media disabled" data-target="<?php echo $target_field; ?>">Insert Selected Image</button>
        </div>
    </div>
</div>

<script>
// Add environment info for JavaScript
window.SRMS_CONFIG = window.SRMS_CONFIG || {};
window.SRMS_CONFIG.IS_PRODUCTION = <?php echo $is_production ? 'true' : 'false'; ?>;
window.SRMS_CONFIG.SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<?php
}
?>