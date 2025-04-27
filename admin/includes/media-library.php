<?php
/**
 * Media Library Component for SRMS Admin Panel
 */
function get_media_library_assets() {
    // Scan directories for images
    $media_categories = [
        'news' => '/assets/images/news/',
        'events' => '/assets/images/events/',
        'promotional' => '/assets/images/promotional/',
        'facilities' => '/assets/images/facilities/',
        'campus' => '/assets/images/campus/'
    ];
    
    $all_media = [];
    
    foreach ($media_categories as $category => $path) {
        $server_path = $_SERVER['DOCUMENT_ROOT'] . $path;
        
        if (is_dir($server_path)) {
            $files = scandir($server_path);
            $media_files = [];
            
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') continue;
                
                $file_path = $path . $file;
                $full_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
                
                if (is_file($full_path) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $media_files[] = [
                        'name' => $file,
                        'path' => $file_path,
                        'url' => $file_path,
                        'size' => filesize($full_path),
                        'modified' => filemtime($full_path),
                        'dimensions' => @getimagesize($full_path) ?: [0, 0]
                    ];
                }
            }
            
            $all_media[$category] = $media_files;
        }
    }
    
    // Include legacy paths if they exist
    $legacy_path = $_SERVER['DOCUMENT_ROOT'] . '/images/';
    if (is_dir($legacy_path)) {
        $files = scandir($legacy_path);
        $legacy_files = [];
        
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            
            $file_path = '/images/' . $file;
            $full_path = $legacy_path . $file;
            
            if (is_file($full_path) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                $legacy_files[] = [
                    'name' => $file,
                    'path' => $file_path,
                    'url' => $file_path,
                    'size' => filesize($full_path),
                    'modified' => filemtime($full_path),
                    'dimensions' => @getimagesize($full_path) ?: [0, 0]
                ];
            }
        }
        
        $all_media['legacy'] = $legacy_files;
    }
    
    return $all_media;
}

// Output the media library modal HTML
function render_media_library($target_field = 'image') {
    $media = get_media_library_assets();
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
                    <?php foreach (array_keys($media) as $category): ?>
                    <li data-category="<?php echo $category; ?>"><?php echo ucfirst($category); ?></li>
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
            <div class="media-preview">
                <div class="preview-image"></div>
                <div class="preview-details"></div>
            </div>
            <button class="insert-media disabled" data-target="<?php echo $target_field; ?>">Insert Selected Image</button>
        </div>
    </div>
</div>
<?php
}
?>