<?php
/**
 * Homepage Elements Management
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

// Get slideshow images
$slideshow = $db->fetch_all("SELECT * FROM slideshow ORDER BY display_order ASC");

// Get facilities
$facilities = $db->fetch_all("SELECT * FROM facilities ORDER BY display_order ASC");

// Get offer box content
$offer_box = $db->fetch_all("SELECT * FROM offer_box ORDER BY display_order ASC");

// Get hero image setting
$hero_image = '';
$hero_image_setting = $db->fetch_row("SELECT * FROM site_settings WHERE setting_key = 'hero_image'");
if ($hero_image_setting) {
    $hero_image = $hero_image_setting['setting_value'];
}

/**
 * Helper function for displaying images in admin panel
 * Uses the robust image path functions from functions.php
 */
function get_admin_image_url($image_path) {
    if (empty($image_path)) return '';
    
    // Use the normalize_image_path function from functions.php
    $normalized_path = normalize_image_path($image_path);
    
    // Log the normalized path for debugging
    error_log("Normalized image path: " . $normalized_path);
    
    // Try to get the correct URL using our helper functions
    if (function_exists('get_correct_image_url')) {
        $url = get_correct_image_url($normalized_path);
        error_log("Image URL from get_correct_image_url: " . $url);
        return $url;
    }
    
    // Build a URL with SITE_URL as fallback
    $url = SITE_URL . $normalized_path;
    error_log("Fallback image URL: " . $url);
    return $url;
}

// Start output buffer for main content
ob_start();
?>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'saved'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Homepage content has been saved successfully.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if(isset($_SESSION['admin_warning'])): ?>
    <div class="message message-warning">
        <i class='bx bx-info-circle'></i>
        <span><?php echo $_SESSION['admin_warning']; ?></span>
    </div>
    <?php unset($_SESSION['admin_warning']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['admin_message'])): ?>
    <div class="message message-success">
        <i class='bx bx-check-circle'></i>
        <span><?php echo $_SESSION['admin_message']; ?></span>
    </div>
    <?php unset($_SESSION['admin_message']); ?>
<?php endif; ?>

<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bxs-home'></i> Homepage Elements</h3>
        <div class="panel-actions">
            <a href="../index.php" class="btn btn-light" target="_blank">
                <i class='bx bx-link-external'></i> View Homepage
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="tabs">
            <div class="tab-header">
                <div class="tab active" data-tab="slideshow">Slideshow</div>
                <div class="tab" data-tab="facilities">Facilities</div>
                <div class="tab" data-tab="offer-box">Offer Box</div>
                <div class="tab" data-tab="hero-image">Hero Image</div>
            </div>
            
            <div class="tab-content">
                <!-- Slideshow Tab -->
                <div class="tab-pane active" id="slideshow">
                    <div class="slideshow-management">
                        <div class="action-row">
                            <button type="button" class="btn btn-primary" id="add-slide-btn">
                                <i class='bx bx-plus'></i> Add Slideshow Image
                            </button>
                        </div>
                        
                        <div class="slideshow-list">
                            <?php if(empty($slideshow)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-images'></i>
                                    <p>No slideshow images found. Add your first slideshow image.</p>
                                </div>
                            <?php else: ?>
                                <div class="slides-grid">
                                    <?php foreach($slideshow as $slide): ?>
                                        <div class="slide-card" data-id="<?php echo $slide['id']; ?>">
                                            <div class="slide-image">
                                                <!-- FIXED: Use the get_admin_image_url function to properly resolve image path -->
                                                <img src="<?php echo get_admin_image_url($slide['image']); ?>" alt="<?php echo htmlspecialchars($slide['caption']); ?>">
                                                <?php if(!$slide['is_active']): ?>
                                                <div class="inactive-overlay">
                                                    <span>Inactive</span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="slide-info">
                                                <?php if(!empty($slide['caption'])): ?>
                                                <div class="slide-caption"><?php echo htmlspecialchars($slide['caption']); ?></div>
                                                <?php endif; ?>
                                                <div class="slide-order">Order: <?php echo $slide['display_order']; ?></div>
                                            </div>
                                            <div class="slide-actions">
                                                <button type="button" class="btn btn-primary btn-sm edit-slide-btn">
                                                    <i class='bx bxs-edit'></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm delete-slide-btn">
                                                    <i class='bx bxs-trash'></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Facilities Tab -->
                <div class="tab-pane" id="facilities">
                    <div class="facilities-management">
                        <div class="action-row">
                            <button type="button" class="btn btn-primary" id="add-facility-btn">
                                <i class='bx bx-plus'></i> Add Facility
                            </button>
                        </div>
                        
                        <div class="facilities-list">
                            <?php if(empty($facilities)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-building-house'></i>
                                    <p>No facilities found. Add your first facility.</p>
                                </div>
                            <?php else: ?>
                                <div class="facilities-grid">
                                    <?php foreach($facilities as $facility): ?>
                                        <div class="facility-card" data-id="<?php echo $facility['id']; ?>">
                                            <div class="facility-image">
                                                <!-- FIXED: Use the get_admin_image_url function to properly resolve image path -->
                                                <img src="<?php echo get_admin_image_url($facility['image']); ?>" alt="<?php echo htmlspecialchars($facility['name']); ?>">
                                            </div>
                                            <div class="facility-info">
                                                <h4><?php echo htmlspecialchars($facility['name']); ?></h4>
                                                <div class="facility-order">Order: <?php echo $facility['display_order']; ?></div>
                                                <p class="facility-description"><?php echo htmlspecialchars($facility['description']); ?></p>
                                            </div>
                                            <div class="facility-actions">
                                                <button type="button" class="btn btn-primary btn-sm edit-facility-btn">
                                                    <i class='bx bxs-edit'></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm delete-facility-btn">
                                                    <i class='bx bxs-trash'></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Offer Box Tab -->
                <div class="tab-pane" id="offer-box">
                    <div class="offer-box-management">
                        <form action="homepage-process.php" method="post">
                            <input type="hidden" name="action" value="save_offer_box">
                            
                            <div class="form-group">
                                <label>Offer Box Content</label>
                                <p class="form-text">Enter each offering on a new line. These will be displayed in the offer box on the homepage.</p>
                                <textarea name="content" class="form-control" rows="6" required><?php 
                                if(!empty($offer_box)) {
                                    foreach($offer_box as $offer) {
                                        echo htmlspecialchars($offer['content']) . "\n";
                                    }
                                }
                                ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Offer Box</button>
                        </form>
                    </div>
                </div>
                
                <!-- Hero Image Tab -->
                <div class="tab-pane" id="hero-image">
                    <div class="hero-image-management">
                        <form action="homepage-process.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_hero_image">
                            
                            <div class="form-group">
                                <label for="heroImage">Hero Background Image</label>
                                <div class="image-input-group">
                                    <input type="text" id="heroImage" name="hero_image" class="form-control" value="<?php echo htmlspecialchars($hero_image); ?>">
                                    <button type="button" class="btn btn-primary open-media-library" data-target="heroImage">
                                        <i class='bx bx-images'></i> Browse Media Library
                                    </button>
                                </div>
                                <small class="form-text">Enter image path or use the media library to select an image</small>
                                
                                <div id="unified-image-preview" class="image-preview-container">
                                    <div class="image-preview">
                                        <div id="hero-preview-placeholder" class="preview-placeholder" style="<?php echo !empty($hero_image) ? 'display: none;' : ''; ?>">
                                            <i class='bx bx-image'></i>
                                            <span>No hero image selected</span>
                                            <small>Select from media library or upload a new image</small>
                                        </div>
                                        <?php if (!empty($hero_image)): ?>
                                        <img src="<?php echo get_admin_image_url($hero_image); ?>" 
                                            alt="Hero Image Preview" 
                                            id="hero-preview-image">
                                        <?php else: ?>
                                        <img src="" alt="Hero Image Preview" id="hero-preview-image" style="display: none;">
                                        <?php endif; ?>
                                    </div>
                                    <div id="source-indicator" class="image-source-indicator"></div>
                                </div>

                                <div class="form-group">
                                    <label for="heroImageUpload">Or Upload New Hero Image</label>
                                    <input type="file" id="heroImageUpload" name="image_upload" accept="image/jpeg, image/png, image/gif">
                                    <small class="form-text">Max file size: 2MB. Recommended dimensions: 1920x1080px or similar wide format.</small>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-save'></i> Save Hero Image
                                </button>
                            </div>
                            
                            <div class="notes mt-4">
                                <div class="note">
                                    <i class='bx bx-info-circle'></i>
                                    <div>
                                        <p><strong>About the Hero Image</strong></p>
                                        <p>The hero image appears at the top of the homepage with a blue overlay. Choose a high-quality, wide image that represents your school's values and aesthetics.</p>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slideshow Modal -->
<div class="modal" id="slideshowModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="slideshowModalTitle">Add Slideshow Image</h3>
                <button type="button" class="modal-close" id="closeSlideshowModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="slideshowForm" action="homepage-process.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_slideshow">
                    <input type="hidden" id="slideId" name="id" value="0">
                    
                    <div class="form-group">
                        <label for="slideImage">Image</label>
                        <div class="image-input-group">
                            <input type="text" id="slideImage" name="image" class="form-control">
                            <button type="button" class="btn btn-primary open-media-library" data-target="slideImage">
                                <i class='bx bx-images'></i> Browse Media Library
                            </button>
                        </div>
                        <small class="form-text">Enter image path or use the media library to select an image</small>
                        
                        <div id="slide-image-preview" class="image-preview-container">
                            <div class="image-preview">
                                <div id="slide-preview-placeholder" class="preview-placeholder">
                                    <i class='bx bx-image'></i>
                                    <span>No image selected</span>
                                    <small>Select from media library or upload a new image</small>
                                </div>
                                <img src="" alt="Preview" id="slide-preview-image" style="display: none;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="slideImageUpload">Or Upload New Image</label>
                            <input type="file" id="slideImageUpload" name="image_upload" accept="image/jpeg, image/png, image/gif">
                            <small class="form-text">Max file size: 2MB. Accepted formats: JPEG, PNG, GIF</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="slideCaption">Caption (optional)</label>
                        <input type="text" id="slideCaption" name="caption" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="slideLink">Link (optional)</label>
                        <input type="text" id="slideLink" name="link" class="form-control">
                        <small class="form-text">URL to navigate to when the slide is clicked.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="slideOrder">Display Order</label>
                        <input type="number" id="slideOrder" name="display_order" class="form-control" value="0" min="0">
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="slideActive" name="is_active" value="1" checked>
                        <label for="slideActive">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelSlideshowBtn">Cancel</button>
                <button type="submit" form="slideshowForm" class="btn btn-primary">Save Slide</button>
            </div>
        </div>
    </div>
</div>

<!-- Facility Modal -->
<div class="modal" id="facilityModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="facilityModalTitle">Add Facility</h3>
                <button type="button" class="modal-close" id="closeFacilityModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="facilityForm" action="homepage-process.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_facility">
                    <input type="hidden" id="facilityId" name="id" value="0">
                    
                    <div class="form-group">
                        <label for="facilityName">Name</label>
                        <input type="text" id="facilityName" name="name" class="form-control" required>
                        <small class="form-text">Example: Library, Gymnasium, Computer Laboratory</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="facilityImage">Image</label>
                        <div class="image-input-group">
                            <input type="text" id="facilityImage" name="image" class="form-control">
                            <button type="button" class="btn btn-primary open-media-library" data-target="facilityImage">
                                <i class='bx bx-images'></i> Browse Media Library
                            </button>
                        </div>
                        <small class="form-text">Enter image path or use the media library to select an image</small>
                        
                        <div id="facility-image-preview" class="image-preview-container">
                            <div class="image-preview">
                                <div id="facility-preview-placeholder" class="preview-placeholder">
                                    <i class='bx bx-image'></i>
                                    <span>No image selected</span>
                                    <small>Select from media library or upload a new image</small>
                                </div>
                                <img src="" alt="Preview" id="facility-preview-image" style="display: none;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="facilityImageUpload">Or Upload New Image</label>
                            <input type="file" id="facilityImageUpload" name="image_upload" accept="image/jpeg, image/png, image/gif">
                            <small class="form-text">Max file size: 2MB. Accepted formats: JPEG, PNG, GIF</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="facilityDescription">Description</label>
                        <textarea id="facilityDescription" name="description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="facilityOrder">Display Order</label>
                        <input type="number" id="facilityOrder" name="display_order" class="form-control" value="0" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelFacilityBtn">Cancel</button>
                <button type="submit" form="facilityForm" class="btn btn-primary">Save Facility</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Enhanced Image Preview Styles */
    .image-source-indicator {
        text-align: right;
        font-size: 13px;
        margin-top: 8px;
        padding: 2px 8px;
        border-radius: 4px;
        background-color: #f8f9fa;
        display: inline-block;
        float: right;
    }
    
    .image-preview-container.library-mode .image-preview {
        border-color: #28a745;
        border-style: solid;
        background-color: rgba(40, 167, 69, 0.05);
    }
    
    .image-preview-container.library-mode .image-source-indicator span {
        color: #28a745;
    }
    
    .image-preview-container.upload-mode .image-preview {
        border-color: #007bff;
        border-style: solid;
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    .image-preview-container.upload-mode .image-source-indicator span {
        color: #007bff;
    }
    
    .image-preview-container.error-mode .image-preview {
        border-color: #dc3545;
        border-style: solid;
        background-color: rgba(220, 53, 69, 0.05);
    }
    
    .image-preview-container.error-mode .image-source-indicator span {
        color: #dc3545;
    }

    /* Tab Styles - Same as in admissions-manage.php */
    .tabs {
        margin-top: 20px;
    }
    
    .tab-header {
        display: flex;
        overflow-x: auto;
        border-bottom: 1px solid var(--border-color);
    }
    
    .tab {
        padding: 15px 20px;
        cursor: pointer;
        white-space: nowrap;
        position: relative;
        transition: all 0.3s;
    }
    
    .tab:hover {
        background-color: #f8f9fa;
    }
    
    .tab.active {
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .tab.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 3px;
        background-color: var(--primary-color);
    }
    
    .tab-content {
        padding: 20px 0;
    }
    
    .tab-pane {
        display: none;
    }
    
    .tab-pane.active {
        display: block;
    }
    
    /* Slideshow Styles */
    .action-row {
        margin-bottom: 20px;
    }
    
    .slides-grid, .facilities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .slide-card, .facility-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .slide-image, .facility-image {
        position: relative;
        height: 200px;
        overflow: hidden;
    }
    
    .slide-image img, .facility-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .inactive-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .inactive-overlay span {
        background-color: var(--danger-color);
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .slide-info, .facility-info {
        padding: 15px;
    }
    
    .slide-caption, .facility-name {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .slide-order, .facility-order {
        font-size: 14px;
        color: #6c757d;
    }
    
    .facility-description {
        margin-top: 10px;
        color: #6c757d;
    }
    
    .slide-actions, .facility-actions {
        padding: 10px 15px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    
    /* Hero Image styles */
    .hero-image-management .form-actions {
        margin-top: 20px;
    }
    
    .notes {
        margin-top: 30px;
    }
    
    .note {
        background-color: #f8f9fa;
        border-left: 4px solid var(--primary-color);
        padding: 15px;
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }
    
    .note i {
        font-size: 24px;
        color: var(--primary-color);
    }
    
    .note p {
        margin: 0 0 10px 0;
    }
    
    .note p:last-child {
        margin-bottom: 0;
    }
    
    .mt-4 {
        margin-top: 1.5rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .slides-grid, .facilities-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and panes
                tabs.forEach(t => t.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                // Add active class to current tab and pane
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Slideshow Modal
        const slideshowModal = document.getElementById('slideshowModal');
        const slideshowModalTitle = document.getElementById('slideshowModalTitle');
        const slideshowForm = document.getElementById('slideshowForm');
        const slideId = document.getElementById('slideId');
        const slideImage = document.getElementById('slideImage');
        const slideCaption = document.getElementById('slideCaption');
        const slideLink = document.getElementById('slideLink');
        const slideOrder = document.getElementById('slideOrder');
        const slideActive = document.getElementById('slideActive');
        const slidePreviewImage = document.getElementById('slide-preview-image');
        const slidePreviewPlaceholder = document.getElementById('slide-preview-placeholder');
        const closeSlideshowModal = document.getElementById('closeSlideshowModal');
        const cancelSlideshowBtn = document.getElementById('cancelSlideshowBtn');
        
        // Facility Modal
        const facilityModal = document.getElementById('facilityModal');
        const facilityModalTitle = document.getElementById('facilityModalTitle');
        const facilityForm = document.getElementById('facilityForm');
        const facilityId = document.getElementById('facilityId');
        const facilityName = document.getElementById('facilityName');
        const facilityImage = document.getElementById('facilityImage');
        const facilityDescription = document.getElementById('facilityDescription');
        const facilityOrder = document.getElementById('facilityOrder');
        const facilityPreviewImage = document.getElementById('facility-preview-image');
        const facilityPreviewPlaceholder = document.getElementById('facility-preview-placeholder');
        const closeFacilityModal = document.getElementById('closeFacilityModal');
        const cancelFacilityBtn = document.getElementById('cancelFacilityBtn');
        
        // Hero image elements
        const heroImage = document.getElementById('heroImage');
        const heroPreviewImage = document.getElementById('hero-preview-image');
        const heroPreviewPlaceholder = document.getElementById('hero-preview-placeholder');
        const sourceIndicator = document.getElementById('source-indicator');
        const heroImageUpload = document.getElementById('heroImageUpload');
        const unifiedPreview = document.getElementById('unified-image-preview');
        
        // Add Slide Button
        const addSlideBtn = document.getElementById('add-slide-btn');
        if (addSlideBtn) {
            addSlideBtn.addEventListener('click', function() {
                slideshowModalTitle.textContent = 'Add Slideshow Image';
                slideId.value = '0';
                slideshowForm.reset();
                
                // Reset preview
                slidePreviewImage.style.display = 'none';
                slidePreviewPlaceholder.style.display = 'flex';
                
                openModal(slideshowModal);
            });
        }
        
        // Edit Slide Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-slide-btn') || 
                e.target.parentElement.classList.contains('edit-slide-btn')) {
                
                const btn = e.target.classList.contains('edit-slide-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.slide-card');
                const id = card.dataset.id;
                
                // Fetch slide data via AJAX
                fetch(`homepage-process.php?action=get_slide&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            slideshowModalTitle.textContent = 'Edit Slideshow Image';
                            slideId.value = data.data.id;
                            slideImage.value = data.data.image;
                            slideCaption.value = data.data.caption;
                            slideLink.value = data.data.link;
                            slideOrder.value = data.data.display_order;
                            slideActive.checked = data.data.is_active == 1;
                            
                            // Update preview - use get_admin_image_url equivalent in JavaScript
                            if (data.data.image) {
                                // Use the SITE_URL to ensure proper path resolution
                                const siteUrl = window.location.origin + '/srms-website';
                                const imagePath = data.data.image.startsWith('/') ? 
                                                 data.data.image : '/' + data.data.image;
                                                 
                                slidePreviewImage.src = data.data.image.includes('http') ? 
                                                      data.data.image : siteUrl + imagePath;
                                slidePreviewImage.style.display = 'block';
                                slidePreviewPlaceholder.style.display = 'none';
                            } else {
                                slidePreviewImage.style.display = 'none';
                                slidePreviewPlaceholder.style.display = 'flex';
                            }
                            
                            openModal(slideshowModal);
                        } else {
                            alert('Failed to load slide data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading slide data');
                    });
            }
        });
        
        // Delete Slide Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-slide-btn') || 
                e.target.parentElement.classList.contains('delete-slide-btn')) {
                
                const btn = e.target.classList.contains('delete-slide-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.slide-card');
                const id = card.dataset.id;
                
                if (confirm('Are you sure you want to delete this slideshow image?')) {
                    window.location.href = `homepage-process.php?action=delete_slide&id=${id}`;
                }
            }
        });
        
        // Add Facility Button
        const addFacilityBtn = document.getElementById('add-facility-btn');
        if (addFacilityBtn) {
            addFacilityBtn.addEventListener('click', function() {
                facilityModalTitle.textContent = 'Add Facility';
                facilityId.value = '0';
                facilityForm.reset();
                
                // Reset preview
                facilityPreviewImage.style.display = 'none';
                facilityPreviewPlaceholder.style.display = 'flex';
                
                openModal(facilityModal);
            });
        }
        
        // Edit Facility Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-facility-btn') || 
                e.target.parentElement.classList.contains('edit-facility-btn')) {
                
                const btn = e.target.classList.contains('edit-facility-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.facility-card');
                const id = card.dataset.id;
                
                // Fetch facility data via AJAX
                fetch(`homepage-process.php?action=get_facility&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            facilityModalTitle.textContent = 'Edit Facility';
                            facilityId.value = data.data.id;
                            facilityName.value = data.data.name;
                            facilityImage.value = data.data.image;
                            facilityDescription.value = data.data.description;
                            facilityOrder.value = data.data.display_order;
                            
                            // Update preview - use get_admin_image_url equivalent in JavaScript
                            if (data.data.image) {
                                // Use the SITE_URL to ensure proper path resolution
                                const siteUrl = window.location.origin + '/srms-website';
                                const imagePath = data.data.image.startsWith('/') ? 
                                                 data.data.image : '/' + data.data.image;
                                                 
                                facilityPreviewImage.src = data.data.image.includes('http') ? 
                                                        data.data.image : siteUrl + imagePath;
                                facilityPreviewImage.style.display = 'block';
                                facilityPreviewPlaceholder.style.display = 'none';
                            } else {
                                facilityPreviewImage.style.display = 'none';
                                facilityPreviewPlaceholder.style.display = 'flex';
                            }
                            
                            openModal(facilityModal);
                        } else {
                            alert('Failed to load facility data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading facility data');
                    });
            }
        });
        
        // Delete Facility Buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-facility-btn') || 
                e.target.parentElement.classList.contains('delete-facility-btn')) {
                
                const btn = e.target.classList.contains('delete-facility-btn') ? 
                            e.target : e.target.parentElement;
                const card = btn.closest('.facility-card');
                const id = card.dataset.id;
                
                if (confirm('Are you sure you want to delete this facility?')) {
                    window.location.href = `homepage-process.php?action=delete_facility&id=${id}`;
                }
            }
        });
        
        // Enhanced updateImagePreview function with better path handling
        function updateImagePreview(path, previewImage, previewPlaceholder) {
            if (path && path.trim()) {
                console.log('Updating preview with path:', path);
                
                // Reset preview container classes
                if (unifiedPreview) {
                    unifiedPreview.className = 'image-preview-container library-mode';
                }
                
                // Normalize path
                let normalizedPath = path;
                // Ensure path starts with a slash
                if (!normalizedPath.startsWith('/')) {
                    normalizedPath = '/' + normalizedPath;
                }
                
                // Get site URL with project folder
                const siteUrl = window.location.origin + '/srms-website';
                
                // Check if the path already contains the full URL
                if (!path.includes('http')) {
                    // Add site URL
                    previewImage.src = siteUrl + normalizedPath;
                } else {
                    previewImage.src = path;
                }
                
                // Update source indicator
                if (sourceIndicator) {
                    sourceIndicator.innerHTML = '<span><i class="bx bx-link"></i> Media Library</span>';
                }
                
                previewImage.style.display = 'block';
                previewPlaceholder.style.display = 'none';
                
                // Handle image loading errors
                previewImage.onerror = function() {
                    console.log('Image failed to load, trying alternative paths');
                    
                    // Try alternative paths
                    const pathWithoutProject = normalizedPath.replace(/^\/srms-website\//, '/');
                    const filename = normalizedPath.split('/').pop();
                    const pathVariations = [
                        siteUrl + pathWithoutProject,
                        siteUrl + '/assets/images/campus/' + filename,
                        siteUrl + '/assets/images/promotional/' + filename
                    ];
                    
                    // Try each variation
                    tryNextPath(pathVariations, 0, previewImage);
                };
            } else {
                previewImage.style.display = 'none';
                previewPlaceholder.style.display = 'flex';
                
                // Reset source indicator
                if (sourceIndicator) {
                    sourceIndicator.innerHTML = '';
                }
                
                // Reset preview container classes
                if (unifiedPreview) {
                    unifiedPreview.className = 'image-preview-container';
                }
            }
        }

        // Helper function to try alternative paths
        function tryNextPath(paths, index, imgElement) {
            if (index >= paths.length) {
                console.log('All alternative paths failed');
                if (unifiedPreview) {
                    unifiedPreview.className = 'image-preview-container error-mode';
                }
                if (sourceIndicator) {
                    sourceIndicator.innerHTML = '<span><i class="bx bx-error-circle"></i> Image not found</span>';
                }
                return;
            }
            
            console.log('Trying path:', paths[index]);
            imgElement.src = paths[index];
            
            imgElement.onerror = function() {
                // Try next path
                tryNextPath(paths, index + 1, imgElement);
            };
        }
        
        // Image preview functionality for slideshow
        if (slideImage) {
            slideImage.addEventListener('input', function() {
                updateImagePreview(this.value, slidePreviewImage, slidePreviewPlaceholder);
            });
        }
        
        // Image preview functionality for facility
        if (facilityImage) {
            facilityImage.addEventListener('input', function() {
                updateImagePreview(this.value, facilityPreviewImage, facilityPreviewPlaceholder);
            });
        }
        
        // Image preview functionality for hero image
        if (heroImage) {
            heroImage.addEventListener('input', function() {
                updateImagePreview(this.value, heroPreviewImage, heroPreviewPlaceholder);
            });
        }

        // File upload preview
        if (heroImageUpload) {
            heroImageUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    
                    // Update UI to show we're processing
                    if (sourceIndicator) {
                        sourceIndicator.innerHTML = '<span><i class="bx bx-loader-alt bx-spin"></i> Processing...</span>';
                    }
                    
                    reader.onload = function(e) {
                        // Display file preview
                        heroPreviewImage.src = e.target.result;
                        heroPreviewImage.style.display = 'block';
                        heroPreviewPlaceholder.style.display = 'none';
                        
                        // Update preview container classes
                        if (unifiedPreview) {
                            unifiedPreview.className = 'image-preview-container upload-mode';
                        }
                        
                        // Update source indicator
                        if (sourceIndicator) {
                            sourceIndicator.innerHTML = `<span><i class="bx bx-upload"></i> Local upload: <strong>${file.name}</strong> (not saved until you submit)</span>`;
                        }
                        
                        // Verify file size
                        if (file.size > 2 * 1024 * 1024) { // 2MB
                            if (sourceIndicator) {
                                sourceIndicator.innerHTML += `<br><span style="color: #dc3545;"><i class="bx bx-error-circle"></i> Warning: File size (${(file.size/1024/1024).toFixed(2)}MB) exceeds recommended 2MB limit.</span>`;
                            }
                        }
                    };
                    
                    reader.onerror = function() {
                        // Show error
                        if (unifiedPreview) {
                            unifiedPreview.className = 'image-preview-container error-mode';
                        }
                        if (sourceIndicator) {
                            sourceIndicator.innerHTML = '<span><i class="bx bx-error-circle"></i> Error reading file</span>';
                        }
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                    
                    // Clear the text input to avoid conflicts
                    if (heroImage) {
                        heroImage.value = '';
                    }
                }
            });
        }

        // Define global selectMediaItem function for media library integration
        window.selectMediaItem = function(path, displayUrl) {
            console.log('Media item selected:', path);
            
            // If we have a target input field
            const targetField = document.querySelector('[data-target]') ? 
                                document.querySelector('[data-target]').getAttribute('data-target') : 
                                'heroImage';
            
            const inputField = document.getElementById(targetField);
            if (inputField) {
                // Normalize the path
                let normalizedPath = path;
                if (!normalizedPath.startsWith('/')) {
                    normalizedPath = '/' + normalizedPath;
                }
                
                // Update the input field
                inputField.value = normalizedPath;
                
                // Update the preview
                if (targetField === 'heroImage') {
                    updateImagePreview(normalizedPath, heroPreviewImage, heroPreviewPlaceholder);
                    
                    // Clear any file upload
                    if (heroImageUpload) {
                        heroImageUpload.value = '';
                    }
                } else if (targetField === 'slideImage') {
                    updateImagePreview(normalizedPath, slidePreviewImage, slidePreviewPlaceholder);
                } else if (targetField === 'facilityImage') {
                    updateImagePreview(normalizedPath, facilityPreviewImage, facilityPreviewPlaceholder);
                }
            }
        };
        
        // Close Modal Buttons
        [closeSlideshowModal, cancelSlideshowBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', function() {
                    closeModal(slideshowModal);
                });
            }
        });
        
        [closeFacilityModal, cancelFacilityBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', function() {
                    closeModal(facilityModal);
                });
            }
        });
        
        // Utility Functions
        function openModal(modal) {
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeModal(modal) {
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === slideshowModal) closeModal(slideshowModal);
            if (e.target === facilityModal) closeModal(facilityModal);
        });
        
        // Function to verify image exists (more comprehensive)
        function verifyImagePath(path) {
            if (!path || path.trim() === '') return false;
            
            // Normalize path
            path = path.startsWith('/') ? path : '/' + path;
            
            // Make AJAX call to verify image exists (this is async, but better than nothing)
            fetch(getImageUrl(path), { method: 'HEAD' })
                .then(response => {
                    if (response.ok) {
                        console.log('Image verified at:', path);
                        return true;
                    } else {
                        console.warn('Image not found at:', path);
                        return false;
                    }
                })
                .catch(error => {
                    console.error('Error verifying image:', error);
                    return false;
                });
        }
        
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
        
        // Initialize the preview from existing path if any
        if (heroImage && heroImage.value.trim()) {
            console.log('Initializing hero image preview with:', heroImage.value);
            // Use a slight delay to ensure DOM is fully loaded
            setTimeout(function() {
                updateImagePreview(heroImage.value, heroPreviewImage, heroPreviewPlaceholder);
                
                // Add library mode class for initial load
                if (unifiedPreview) {
                    unifiedPreview.className = 'image-preview-container library-mode';
                }
                
                // Set source indicator for initial state
                if (sourceIndicator) {
                    sourceIndicator.innerHTML = '<span><i class="bx bx-link"></i> Media Library</span>';
                }
            }, 200);
        }
        
        // Add image verification info if path changes
        if (heroImage) {
            heroImage.addEventListener('change', function() {
                const path = this.value.trim();
                if (path) {
                    // Attempt to verify the image
                    verifyImagePath(path);
                }
            });
        }
    });

    // Add warning for large viewports
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1920) {
            const noteElement = document.querySelector('.note');
            if (noteElement) {
                // Check if we already added the warning
                if (!document.getElementById('responsive-warning')) {
                    const warningDiv = document.createElement('div');
                    warningDiv.id = 'responsive-warning';
                    warningDiv.innerHTML = '<strong>Screen Size Note:</strong> Your screen is very wide. For best results with hero images, choose images with a 16:9 or similar widescreen aspect ratio.';
                    warningDiv.style.marginTop = '10px';
                    warningDiv.style.color = '#856404';
                    warningDiv.style.backgroundColor = '#fff3cd';
                    warningDiv.style.padding = '8px';
                    warningDiv.style.borderRadius = '4px';
                    
                    noteElement.appendChild(warningDiv);
                }
            }
        } else {
            const warningDiv = document.getElementById('responsive-warning');
            if (warningDiv) {
                warningDiv.remove();
            }
        }
    });
</script>

<?php
// Include the media library for image selection
include_once '../admin/includes/media-library.php';
render_media_library();

// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Homepage Elements';
$page_specific_css = [
    '../assets/css/image-selector.css',
    '../assets/css/media-library.css'
];
$page_specific_js = [
    '../assets/js/media-library.js'
];

// Include the layout
include 'layout.php';
?>