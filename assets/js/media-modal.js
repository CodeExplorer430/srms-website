/**
 * Unified Media Library Modal Handler - Hostinger Compatible
 * Version: 3.0 (Enhanced environment detection)
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Loading Media Modal v3.0 (Enhanced environment detection)');
    
    // Environment detection for proper path handling
    const isProduction = typeof window.SRMS_CONFIG !== 'undefined' && window.SRMS_CONFIG.IS_PRODUCTION;
    
    // Get project folder from URL for consistent handling
    function getProjectFolder() {
        // Get current URL components
        const urlParts = window.location.pathname.split('/');
        // Extract potential project folder (first segment after domain)
        const projectFolder = urlParts[1] ? urlParts[1] : '';
        
        // Don't return project folder in production if it's at root level
        if (isProduction && (projectFolder === 'admin' || projectFolder === 'assets')) {
            return '';
        }
        
        return projectFolder;
    }
    
    // Utility functions for safer DOM operations
    function safeQuerySelector(element, selector) {
        if (!element || !selector) return null;
        try {
            return element.querySelector(selector);
        } catch (error) {
            console.log(`Error querying selector "${selector}":`, error);
            return null;
        }
    }
    
    function safeTextContent(element) {
        if (!element) return '';
        try {
            return element.textContent || '';
        } catch (error) {
            console.log('Error getting text content:', error);
            return '';
        }
    }
    
    function safeAttribute(element, attribute) {
        if (!element || !attribute) return '';
        try {
            return element.getAttribute(attribute) || '';
        } catch (error) {
            console.log(`Error getting attribute "${attribute}":`, error);
            return '';
        }
    }
    
    // Path normalization utility function enhanced for production/development
    function normalizePath(path) {
        if (!path) return '';
        
        // Log for debugging
        console.log("Original path for normalization:", path);
        
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
    
    try {
        // Core selectors with safety checks
        const mediaModal = document.getElementById('media-library-modal');
        if (!mediaModal) {
            console.log('Media modal not found, skipping initialization');
            return;
        }

        // Button selectors with fallbacks
        const closeButtons = mediaModal.querySelectorAll('.media-library-close, .close-media-library') || [];
        const openButtons = document.querySelectorAll('.open-media-library, #open-media-library-btn') || [];
        const insertButton = mediaModal.querySelector('.insert-media');
        
        // Content selectors with fallbacks
        const mediaItems = mediaModal.querySelectorAll('.media-item') || [];
        const categoryLinks = mediaModal.querySelectorAll('.media-sidebar li') || [];
        const searchInput = mediaModal.querySelector('#media-search');
        const filterSelect = mediaModal.querySelector('#media-filter');
        const quickUploadForm = mediaModal.querySelector('#quick-upload-form');
        
        // Debug info
        console.log('Media Modal: Items found:', mediaItems.length);
        console.log('Media Modal: Category links found:', categoryLinks.length);
        console.log('Media Modal: Environment:', isProduction ? 'Production' : 'Development');
        console.log('Media Modal: Project folder:', getProjectFolder());
        
        // State tracking
        let selectedItem = null;
        let targetFieldId = null;
        
        // ===== MODAL OPEN/CLOSE =====
        
        // Open modal
        openButtons.forEach(button => {
            if (!button) return; // Skip if button is null
            
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get target field if specified
                targetFieldId = this.dataset.target || 'dummy-field';
                
                // Reset selection state
                if (selectedItem) {
                    selectedItem.classList.remove('selected');
                    selectedItem = null;
                }
                
                // Reset preview area
                const previewImage = mediaModal.querySelector('.preview-image');
                const previewDetails = mediaModal.querySelector('.preview-details');
                
                if (previewImage) previewImage.innerHTML = '';
                if (previewDetails) previewDetails.innerHTML = '';
                
                // Disable insert button until something is selected
                if (insertButton) {
                    insertButton.classList.add('disabled');
                    insertButton.disabled = true; // Explicitly set the disabled property
                }
                
                // Show modal
                mediaModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        });
        
        // Close modal methods
        function closeModal() {
            if (mediaModal) {
                mediaModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
        
        // Attach close handlers
        closeButtons.forEach(button => {
            if (!button) return; // Skip if button is null
            button.addEventListener('click', closeModal);
        });
        
        // Close when clicking outside content
        mediaModal.addEventListener('click', function(e) {
            if (e.target === mediaModal) {
                closeModal();
            }
        });
        
        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mediaModal.style.display === 'block') {
                closeModal();
            }
        });
        
        // ===== MEDIA ITEM SELECTION =====
        
        // Select media item with robust error handling
        mediaItems.forEach(item => {
            if (!item) return; // Skip if item is null
            
            item.addEventListener('click', function() {
                try {
                    // Remove selection from all items
                    mediaItems.forEach(i => {
                        if (i) i.classList.remove('selected');
                    });
                    
                    // Add selection to clicked item
                    this.classList.add('selected');
                    selectedItem = this;
                    
                    // Update preview with full error handling
                    const previewImage = safeQuerySelector(mediaModal, '.preview-image');
                    const previewDetails = safeQuerySelector(mediaModal, '.preview-details');
                    
                    if (previewImage && previewDetails) {
                        // Safely get attributes and properties
                        const imgElement = safeQuerySelector(this, 'img');
                        let path = safeAttribute(this, 'data-path') || '';
                        
                        // If data-path is empty, fall back to img src
                        if (!path && imgElement) {
                            path = safeAttribute(imgElement, 'src') || '';
                        }
                        
                        // Normalize path to ensure consistency
                        path = normalizePath(path);
                        
                        const nameElement = safeQuerySelector(this, '.media-name');
                        const dimensionsElement = safeQuerySelector(this, '.media-dimensions');
                        
                        // Safely get text content with fallbacks
                        const name = safeTextContent(nameElement) || 'Unknown';
                        const dimensions = safeTextContent(dimensionsElement) || '';
                        
                        // Get correct image URL for preview
                        const imageUrl = getCorrectImageUrl(path);
                        
                        // Update preview elements
                        previewImage.innerHTML = `<img src="${imageUrl}" alt="${name}">`;
                        previewDetails.innerHTML = `
                            <strong>${name}</strong><br>
                            <span>${dimensions}</span><br>
                            <code style="color: #6c757d; font-size: 12px; word-break: break-all;">${path}</code>
                        `;
                    }
                    
                    // Enable insert button
                    if (insertButton) {
                        insertButton.classList.remove('disabled');
                        insertButton.disabled = false;
                    }
                } catch (error) {
                    console.error('Error in media item selection:', error);
                }
            });
        });
        
        // ===== CATEGORY FILTERING =====
        
        // Filter by category with error handling
        categoryLinks.forEach(link => {
            if (!link) return; // Skip if link is null
            
            link.addEventListener('click', function() {
                try {
                    // Update active state
                    categoryLinks.forEach(l => {
                        if (l) l.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Get category
                    const category = this.dataset.category || 'all';
                    
                    // Update filter dropdown if it exists
                    if (filterSelect) {
                        filterSelect.value = category;
                    }
                    
                    // Apply filters
                    const searchValue = searchInput ? searchInput.value : '';
                    safeFilterMediaItems(category, searchValue);
                } catch (error) {
                    console.error('Error in category filtering:', error);
                }
            });
        });
        
        // Filter dropdown change with error handling
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                try {
                    const category = this.value || 'all';
                    
                    // Update sidebar active item
                    categoryLinks.forEach(link => {
                        if (!link) return; // Skip if link is null
                        
                        if (link.dataset.category === category) {
                            link.classList.add('active');
                        } else {
                            link.classList.remove('active');
                        }
                    });
                    
                    // Apply filters
                    const searchValue = searchInput ? searchInput.value : '';
                    safeFilterMediaItems(category, searchValue);
                } catch (error) {
                    console.error('Error in filter dropdown change:', error);
                }
            });
        }
        
        // Search input with error handling
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                try {
                    // Get current category
                    const activeCategory = mediaModal.querySelector('.media-sidebar li.active');
                    const category = activeCategory ? activeCategory.dataset.category : 'all';
                    
                    // Apply filters
                    safeFilterMediaItems(category, this.value);
                } catch (error) {
                    console.error('Error in search input:', error);
                }
            });
        }
        
        // Safe filter function with complete error handling
        function safeFilterMediaItems(category, searchTerm) {
            try {
                console.log('Filtering modal items - Category:', category, 'Search:', searchTerm);
                
                const categorySections = mediaModal.querySelectorAll('.category-section') || [];
                const items = mediaModal.querySelectorAll('.media-item') || [];
                const searchLower = (searchTerm || '').toLowerCase();
                
                // First handle category sections visibility
                if (categorySections.length > 0) {
                    categorySections.forEach(section => {
                        if (!section) return; // Skip if section is null
                        
                        if (category === 'all' || safeAttribute(section, 'data-category') === category) {
                            section.style.display = '';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                }
                
                // Then filter individual items by search term
                items.forEach(item => {
                    if (!item) return; // Skip if item is null
                    
                    try {
                        // Skip items in hidden sections
                        const parentSection = item.closest('.category-section');
                        if (parentSection && parentSection.style.display === 'none') {
                            return;
                        }
                        
                        // Safely get name element and text
                        const nameElement = safeQuerySelector(item, '.media-name');
                        const itemName = safeTextContent(nameElement).toLowerCase();
                        
                        // Check search term match
                        const nameMatch = !searchTerm || itemName.includes(searchLower);
                        
                        // Show/hide based on search
                        if (nameMatch) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    } catch (itemError) {
                        console.error('Error filtering individual item:', itemError);
                        // Default to showing the item on error
                        item.style.display = '';
                    }
                });
            } catch (error) {
                console.error('Error in filter function:', error);
            }
        }
        
        // ===== INSERT FUNCTIONALITY =====
        
        // Insert selected media with error handling
        if (insertButton) {
            insertButton.addEventListener('click', function() {
                try {
                    if (!selectedItem || this.classList.contains('disabled')) {
                        return;
                    }
                    
                    // Get selected image path and target field
                    const imgElement = safeQuerySelector(selectedItem, 'img');
                    let path = safeAttribute(selectedItem, 'data-path') || '';
                    
                    // If data-path is empty, fall back to img src
                    if (!path && imgElement) {
                        path = safeAttribute(imgElement, 'src') || '';
                    }
                    
                    const field = document.getElementById(targetFieldId);
                    
                    if (field && path) {
                        // Normalize path
                        path = normalizePath(path);
                        
                        // Extract the part of the path starting with /assets/ or /images/
                        const match = path.match(/\/(?:assets|images)\/.*/);
                        const formattedPath = match ? match[0] : path;
                        
                        console.log('Setting image path via media modal:', formattedPath);
                        
                        // Set field value
                        field.value = formattedPath;
                        
                        // ALWAYS use the global function when available
                        if (typeof window.selectMediaItem === 'function') {
                            console.log('Using global selectMediaItem function');
                            window.selectMediaItem(formattedPath);
                        } else {
                            console.warn('Global selectMediaItem function not found, using fallback');
                            // Fallback only if global function is not available
                            try {
                                const event = new Event('input', { bubbles: true });
                                field.dispatchEvent(event);
                            } catch (eventError) {
                                console.error('Error dispatching event:', eventError);
                            }
                        }
                        
                        // Clear any file upload to avoid conflicts
                        const fileInput = document.getElementById('image_upload');
                        if (fileInput) {
                            fileInput.value = '';
                        }
                    }
                    
                    // Close modal
                    closeModal();
                } catch (error) {
                    console.error('Error inserting media:', error);
                }
            });
        }
        
        // ===== QUICK UPLOAD FORM =====
        
        // Handle quick upload form if it exists
        if (quickUploadForm) {
            quickUploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                try {
                    const formData = new FormData(this);
                    
                    // Show loading state
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton ? submitButton.textContent : 'Upload';
                    
                    if (submitButton) {
                        submitButton.textContent = 'Uploading...';
                        submitButton.disabled = true;
                    }
                    
                    // Determine correct AJAX URL based on environment
                    const projectFolder = getProjectFolder();
                    let ajaxUrl = isProduction ? 
                        '/admin/ajax/upload-media.php' : 
                        (projectFolder ? `/${projectFolder}/admin/ajax/upload-media.php` : '/admin/ajax/upload-media.php');
                    
                    console.log('AJAX URL for upload:', ajaxUrl);
                    
                    // Send AJAX request
                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Refresh the page to show new media
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
                        console.error('Error during upload:', error);
                        alert('An error occurred during upload. Please try again.');
                        if (submitButton) {
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        }
                    });
                } catch (error) {
                    console.error('Error submitting upload form:', error);
                }
            });
        }
    } catch (error) {
        console.error('Error initializing media modal:', error);
    }
});