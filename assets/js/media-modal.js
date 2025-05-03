/**
 * Unified Media Library Modal Handler - Fixed Version
 * Version: 2.1 (Error-fixed enhanced version)
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Loading Media Modal v2.1 (Enhanced Error Handling)');
    
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
                if (insertButton) insertButton.classList.add('disabled');
                
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
                        const path = safeAttribute(this, 'data-path') || 
                                    (imgElement ? safeAttribute(imgElement, 'src') : '') || '';
                        
                        const nameElement = safeQuerySelector(this, '.media-name');
                        const dimensionsElement = safeQuerySelector(this, '.media-dimensions');
                        
                        // Safely get text content with fallbacks
                        const name = safeTextContent(nameElement) || 'Unknown';
                        const dimensions = safeTextContent(dimensionsElement) || '';
                        
                        // Update preview elements
                        previewImage.innerHTML = `<img src="${path}" alt="${name}">`;
                        previewDetails.innerHTML = `
                            <strong>${name}</strong><br>
                            <span>${dimensions}</span><br>
                            <code style="color: #6c757d; font-size: 12px; word-break: break-all;">${path}</code>
                        `;
                    }
                    
                    // Enable insert button
                    if (insertButton) {
                        insertButton.classList.remove('disabled');
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
                    const path = safeAttribute(selectedItem, 'data-path') || 
                                (imgElement ? safeAttribute(imgElement, 'src') : '');
                    
                    const field = document.getElementById(targetFieldId);
                    
                    if (field && path) {
                        // Ensure path is formatted correctly
                        let formattedPath = normalizePath(path);
                        
                        // Extract the part of the path starting with /assets/ or /images/
                        const match = formattedPath.match(/\/(?:assets|images)\/.*/);
                        if (match) {
                            formattedPath = match[0];
                        }
                        
                        // Set field value
                        field.value = formattedPath;
                        
                        // Trigger change event to update preview if it exists
                        try {
                            field.dispatchEvent(new Event('change'));
                        } catch (eventError) {
                            console.error('Error dispatching change event:', eventError);
                        }
                    }
                    
                    // Close modal
                    closeModal();
                } catch (error) {
                    console.error('Error inserting media:', error);
                }
            });
        }
    } catch (error) {
        console.error('Error initializing media modal:', error);
    }
});