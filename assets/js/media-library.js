/**
 * Media Library functionality for St. Raphaela Mary School Admin
 * Version: 2.1 (Error-fixed enhanced version)
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Loading Media Library v2.1 (Enhanced Error Handling)');
    
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
    
    // Initialize media library
    function initMediaLibrary() {
        // Safely get elements with error checking
        const mediaModal = document.getElementById('media-library-modal');
        if (!mediaModal) {
            console.log('Media modal not found, skipping initialization');
            return;
        }
        
        // Get all required elements with safety checks
        const openButtons = document.querySelectorAll('.open-media-library') || [];
        const closeButton = mediaModal.querySelector('.media-library-close');
        const mediaItems = mediaModal.querySelectorAll('.media-item') || [];
        const insertButton = mediaModal.querySelector('.insert-media');
        const categoryLinks = mediaModal.querySelectorAll('.media-sidebar li') || [];
        const searchInput = document.getElementById('media-search');
        const filterSelect = document.getElementById('media-filter');
        const quickUploadForm = mediaModal.querySelector('#quick-upload-form');
        
        // Debug info
        console.log('Media items found:', mediaItems.length);
        console.log('Category links found:', categoryLinks.length);
        
        // Disable insert button by default (no selection)
        if (insertButton) {
            insertButton.disabled = true;
        }
        
        // Open media library modal
        openButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const targetField = this.getAttribute('data-target') || 'image';
                if (insertButton) {
                    insertButton.setAttribute('data-target', targetField);
                    insertButton.disabled = true;
                }
                mediaModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        });
        
        // Close media library modal
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                mediaModal.style.display = 'none';
                document.body.style.overflow = '';
            });
        }
        
        // Close modal if clicking outside of content
        window.addEventListener('click', function(e) {
            if (e.target === mediaModal) {
                mediaModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
        
        // Select media item - with enhanced error handling
        mediaItems.forEach(item => {
            if (!item) return; // Skip if item is null
            
            item.addEventListener('click', function(e) {
                try {
                    // Remove selection from other items
                    mediaItems.forEach(i => {
                        if (i) i.classList.remove('selected');
                    });
                    
                    // Add selection to clicked item
                    this.classList.add('selected');
                    
                    // Safely get path attribute with fallback
                    const path = safeAttribute(this, 'data-path') || '';
                    
                    // Safely get elements and their content
                    const nameElement = safeQuerySelector(this, '.media-name');
                    const dimensionsElement = safeQuerySelector(this, '.media-dimensions');
                    const previewImage = safeQuerySelector(mediaModal, '.preview-image');
                    const previewDetails = safeQuerySelector(mediaModal, '.preview-details');
                    
                    // Get content with fallbacks
                    const name = safeTextContent(nameElement) || 'Unknown';
                    const dimensions = safeTextContent(dimensionsElement) || '';
                    
                    // Update preview if elements exist
                    if (previewImage) {
                        previewImage.innerHTML = `<img src="${path}" alt="${name}">`;
                    }
                    
                    if (previewDetails) {
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
                    console.error('Error selecting media item:', error);
                }
            });
        });
        
        // Insert selected media - with error handling
        if (insertButton) {
            insertButton.addEventListener('click', function() {
                try {
                    const selectedItem = mediaModal.querySelector('.media-item.selected');
                    
                    if (selectedItem) {
                        // Ensure path always starts with a slash and normalize
                        let path = selectedItem.getAttribute('data-path') || '';
                        if (path) {
                            path = '/' + path.replace(/^\/+/, '');
                            
                            const targetField = this.getAttribute('data-target');
                            const inputField = document.getElementById(targetField);
                            
                            if (inputField) {
                                inputField.value = path;
                                
                                // Trigger change event to update preview
                                const event = new Event('change');
                                inputField.dispatchEvent(event);
                                
                                // Close modal
                                mediaModal.style.display = 'none';
                                document.body.style.overflow = '';
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error inserting media:', error);
                }
            });
        }
        
        // Filter by category - with error handling
        categoryLinks.forEach(link => {
            if (!link) return; // Skip if link is null
            
            link.addEventListener('click', function() {
                try {
                    // Remove active class from other links
                    categoryLinks.forEach(l => {
                        if (l) l.classList.remove('active');
                    });
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Get selected category
                    const category = this.getAttribute('data-category') || 'all';
                    
                    // Update filter dropdown to match
                    if (filterSelect) {
                        filterSelect.value = category;
                    }
                    
                    // Filter items
                    const searchValue = searchInput ? searchInput.value : '';
                    safeFilterMediaItems(category, searchValue);
                } catch (error) {
                    console.error('Error filtering by category:', error);
                }
            });
        });
        
        // Search media items - with error handling
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                try {
                    const activeCategory = mediaModal.querySelector('.media-sidebar li.active');
                    const category = activeCategory ? activeCategory.getAttribute('data-category') : 'all';
                    safeFilterMediaItems(category, this.value);
                } catch (error) {
                    console.error('Error searching media items:', error);
                }
            });
        }
        
        // Filter dropdown - with error handling
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                try {
                    const category = this.value || 'all';
                    
                    // Update sidebar active link
                    categoryLinks.forEach(link => {
                        if (!link) return; // Skip if link is null
                        
                        if (link.getAttribute('data-category') === category) {
                            link.classList.add('active');
                        } else {
                            link.classList.remove('active');
                        }
                    });
                    
                    // Filter items
                    const searchValue = searchInput ? searchInput.value : '';
                    safeFilterMediaItems(category, searchValue);
                } catch (error) {
                    console.error('Error filtering by dropdown:', error);
                }
            });
        }
        
        // Completely redesigned safe filter function
        function safeFilterMediaItems(category, searchTerm) {
            console.log('Filtering items - Category:', category, 'Search:', searchTerm);
            
            try {
                // Get all items with fallback
                const items = mediaModal.querySelectorAll('.media-item') || [];
                const searchLower = (searchTerm || '').toLowerCase();
                
                // Process each item
                items.forEach(item => {
                    if (!item) return; // Skip if item is null
                    
                    try {
                        // Safely get category
                        let itemCategory = 'unknown';
                        const categorySection = item.closest('.category-section');
                        if (categorySection) {
                            itemCategory = safeAttribute(categorySection, 'data-category') || 'unknown';
                        }
                        
                        // Safely get name
                        let itemName = '';
                        const nameElement = safeQuerySelector(item, '.media-name');
                        if (nameElement) {
                            itemName = safeTextContent(nameElement).toLowerCase();
                        }
                        
                        // Check category
                        const categoryMatch = !category || category === 'all' || itemCategory === category;
                        
                        // Check search term
                        const searchMatch = !searchTerm || itemName.includes(searchLower);
                        
                        // Show/hide based on filters
                        if (categoryMatch && searchMatch) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    } catch (itemError) {
                        console.error('Error processing filter for item:', itemError);
                        // Default to showing the item on error
                        item.style.display = '';
                    }
                });
            } catch (filterError) {
                console.error('Error in filter function:', filterError);
            }
        }
        
        // Quick upload form - with error handling
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
                    
                    // Send AJAX request
                    fetch('../admin/ajax/upload-media.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Refresh media library
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
                        alert('An error occurred during upload.');
                        if (submitButton) {
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        }
                    });
                } catch (error) {
                    console.error('Error preparing upload:', error);
                    alert('An error occurred while preparing the upload.');
                }
            });
        }
    }
    
    // Initialize if media library exists on the page
    try {
        initMediaLibrary();
    } catch (error) {
        console.error('Error initializing media library:', error);
    }
    
    // Image preview functionality with error handling
    function initImagePreview() {
        try {
            const imageInputs = document.querySelectorAll('input[type="text"][id$="image"]') || [];
            
            imageInputs.forEach(input => {
                if (!input) return; // Skip if input is null
                
                try {
                    const previewContainer = document.createElement('div');
                    previewContainer.className = 'image-preview-container';
                    previewContainer.innerHTML = `
                        <div class="image-preview">
                            <div class="preview-placeholder">
                                <i class='bx bx-image'></i>
                                <span>No image selected</span>
                            </div>
                            <img src="" alt="Preview" style="display: none;">
                        </div>
                    `;
                    
                    // Insert preview after parent element of input (usually a form-group)
                    const parent = input.closest('.form-group');
                    if (parent) {
                        parent.appendChild(previewContainer);
                    } else if (input.parentNode) {
                        input.parentNode.insertBefore(previewContainer, input.nextSibling);
                    }
                    
                    // Update preview when path changes
                    input.addEventListener('change', function() {
                        safeUpdateImagePreview(this, previewContainer);
                    });
                    
                    // Initial preview
                    if (input.value) {
                        safeUpdateImagePreview(input, previewContainer);
                    }
                } catch (inputError) {
                    console.error('Error setting up preview for input:', inputError);
                }
            });
        } catch (error) {
            console.error('Error initializing image previews:', error);
        }
        
        // Update preview function with complete error handling
        function safeUpdateImagePreview(input, container) {
            try {
                if (!input || !container) return;
                
                const path = input.value || '';
                const previewImg = container.querySelector('img');
                const placeholder = container.querySelector('.preview-placeholder');
                
                if (!previewImg || !placeholder) return;
                
                if (!path) {
                    previewImg.style.display = 'none';
                    placeholder.style.display = 'flex';
                    return;
                }
                
                // Set image source
                previewImg.src = path.startsWith('/') ? path : '/' + path;
                
                // Show loading state
                previewImg.style.opacity = '0.5';
                placeholder.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i><span>Loading...</span>';
                placeholder.style.display = 'flex';
                
                // Check if image loads
                previewImg.onload = function() {
                    previewImg.style.opacity = '1';
                    previewImg.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                
                previewImg.onerror = function() {
                    previewImg.style.display = 'none';
                    placeholder.innerHTML = `
                        <i class='bx bx-error-circle'></i>
                        <span>Image not found:<br>${path}</span>
                    `;
                    placeholder.style.display = 'flex';
                };
            } catch (error) {
                console.error('Error updating image preview:', error);
            }
        }
    }
    
    // Initialize image previews
    try {
        initImagePreview();
    } catch (error) {
        console.error('Error initializing image previews:', error);
    }
});