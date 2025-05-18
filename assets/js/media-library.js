/**
 * Media Library functionality for St. Raphaela Mary School Admin
 * Version: 3.3 (Enhanced path handling)
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Loading Media Library v3.3 (Enhanced path handling)');
    
    // Helper function to ensure path is correctly formatted
    function formatImagePath(path) {
        if (!path) return '';
        
        // Remove any leading slashes
        path = path.replace(/^\/+/, '');
        
        // Ensure path starts with a single slash
        return '/' + path;
    }
    
    // Utility function to get correct image URL with project folder
    function getCorrectImageUrl(path) {
        if (!path) return '';
        
        // Get current URL components
        const origin = window.location.origin;
        const urlParts = window.location.pathname.split('/');
        const projectFolder = urlParts[1] ? urlParts[1] : '';
        
        // Make sure path starts with a slash
        if (!path.startsWith('/')) {
            path = '/' + path;
        }
        
        // Don't add project folder if it's already in the path
        if (projectFolder && path.toLowerCase().startsWith('/' + projectFolder.toLowerCase() + '/')) {
            return origin + path;
        }
        
        // Add project folder to path
        if (projectFolder) {
            return origin + '/' + projectFolder + path;
        }
        
        // No project folder
        return origin + path;
    }
    
    // Override the selectMediaItem global function if it exists
    if (typeof window.originalSelectMediaItem === 'undefined' && typeof window.selectMediaItem === 'function') {
        // Store the original function
        window.originalSelectMediaItem = window.selectMediaItem;
        
        // Replace with our enhanced version
        window.selectMediaItem = function(path, displayUrl) {
            // Format the path correctly before passing to original function
            const formattedPath = formatImagePath(path);
            console.log('Media Library: Using formatted path:', formattedPath);
            
            // Generate display URL for preview (correctly handles project folder)
            const displayImageUrl = displayUrl || getCorrectImageUrl(formattedPath);
            console.log('Media Library: Display URL for preview:', displayImageUrl);
            
            // Call the original function with the formatted path (for database storage)
            if (typeof window.originalSelectMediaItem === 'function') {
                // Pass both paths to original handler - the formatted path for storage
                // and the display URL for immediate preview
                window.originalSelectMediaItem(formattedPath, displayImageUrl);
            }
        };
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
            insertButton.classList.add('disabled');
        }
        
        // Open media library modal
        openButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const targetField = this.getAttribute('data-target') || 'image';
                if (insertButton) {
                    insertButton.setAttribute('data-target', targetField);
                    insertButton.disabled = true;
                    insertButton.classList.add('disabled');
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
                    
                    // Get the img element for direct src if needed
                    const imgElement = this.querySelector('img');
                    const imgSrc = imgElement ? imgElement.getAttribute('src') : '';
                    
                    // Use path or fallback to img src
                    const finalPath = path || imgSrc;
                    
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
                        // Use correct URL with project folder
                        const displayUrl = getCorrectImageUrl(finalPath);
                        previewImage.innerHTML = `<img src="${displayUrl}" alt="${name}">`;
                    }
                    
                    if (previewDetails) {
                        previewDetails.innerHTML = `
                            <strong>${name}</strong><br>
                            <span>${dimensions}</span><br>
                            <code style="color: #6c757d; font-size: 12px; word-break: break-all;">${finalPath}</code>
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
            
            // Add double-click for faster selection
            item.addEventListener('dblclick', function() {
                try {
                    // Get path from data attribute or img src as fallback
                    let path = safeAttribute(this, 'data-path') || '';
                    const imgElement = this.querySelector('img');
                    if (!path && imgElement) {
                        path = imgElement.getAttribute('src') || '';
                    }
                    
                    const targetField = insertButton ? insertButton.getAttribute('data-target') : 'image';
                    const inputField = document.getElementById(targetField);
                    
                    if (inputField && path) {
                        // Update input field value with formatted path
                        inputField.value = formatImagePath(path);
                        
                        // Use global function if available
                        if (typeof window.selectMediaItem === 'function') {
                            window.selectMediaItem(path);
                        } else {
                            // Fallback: Trigger change event to update preview
                            const event = new Event('input');
                            inputField.dispatchEvent(event);
                        }
                        
                        // Close modal
                        mediaModal.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                } catch (error) {
                    console.error('Error handling double-click:', error);
                }
            });
        });
        
        // Insert selected media - with error handling
        if (insertButton) {
            insertButton.addEventListener('click', function() {
                try {
                    const selectedItem = mediaModal.querySelector('.media-item.selected');
                    
                    if (selectedItem) {
                        // Get path from data attribute or img src as fallback
                        let path = selectedItem.getAttribute('data-path') || '';
                        const imgElement = selectedItem.querySelector('img');
                        if (!path && imgElement) {
                            path = imgElement.getAttribute('src') || '';
                        }
                        
                        if (path) {
                            // Format the path properly
                            path = formatImagePath(path);
                            
                            const targetField = this.getAttribute('data-target');
                            const inputField = document.getElementById(targetField);
                            
                            if (inputField) {
                                inputField.value = path;
                                
                                // Use global function if available
                                if (typeof window.selectMediaItem === 'function') {
                                    window.selectMediaItem(path);
                                } else {
                                    // Fallback: Trigger input event to update preview
                                    const event = new Event('input');
                                    inputField.dispatchEvent(event);
                                }
                                
                                // Clear any file upload to avoid conflicts
                                const fileInput = document.getElementById('image_upload');
                                if (fileInput) {
                                    fileInput.value = '';
                                }
                                
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
                // Get all category sections with fallback
                const categorySections = mediaModal.querySelectorAll('.category-section') || [];
                const searchLower = (searchTerm || '').toLowerCase();
                
                // First hide/show entire category sections
                categorySections.forEach(section => {
                    if (!section) return; // Skip if section is null
                    
                    try {
                        const sectionCategory = safeAttribute(section, 'data-category') || 'unknown';
                        
                        if (category === 'all' || sectionCategory === category) {
                            section.style.display = '';
                        } else {
                            section.style.display = 'none';
                        }
                    } catch (sectionError) {
                        console.error('Error processing section for filter:', sectionError);
                    }
                });
                
                // Then filter items within visible sections by search term
                if (searchTerm) {
                    const items = mediaModal.querySelectorAll('.media-item') || [];
                    
                    items.forEach(item => {
                        if (!item) return; // Skip if item is null
                        
                        try {
                            // Check if the item's section is visible
                            const section = item.closest('.category-section');
                            if (section && section.style.display !== 'none') {
                                // Safely get name
                                let itemName = '';
                                const nameElement = safeQuerySelector(item, '.media-name');
                                if (nameElement) {
                                    itemName = safeTextContent(nameElement).toLowerCase();
                                }
                                
                                // Show/hide based on search
                                if (itemName.includes(searchLower)) {
                                    item.style.display = '';
                                } else {
                                    item.style.display = 'none';
                                }
                            }
                        } catch (itemError) {
                            console.error('Error processing filter for item:', itemError);
                            // Default to showing the item on error
                            item.style.display = '';
                        }
                    });
                    
                    // Check if any visible sections have no visible items - show "no results" message
                    categorySections.forEach(section => {
                        if (!section || section.style.display === 'none') return;
                        
                        const visibleItems = section.querySelectorAll('.media-item[style="display: none;"]');
                        const totalItems = section.querySelectorAll('.media-item');
                        
                        // If all items are hidden, show a "no results" message
                        if (visibleItems.length === totalItems.length) {
                            // Check if a no-results message already exists
                            let noResultsMsg = section.querySelector('.no-search-results');
                            
                            if (!noResultsMsg) {
                                // Create one if it doesn't exist
                                noResultsMsg = document.createElement('div');
                                noResultsMsg.className = 'no-search-results';
                                noResultsMsg.style.padding = '20px';
                                noResultsMsg.style.textAlign = 'center';
                                noResultsMsg.style.color = '#6c757d';
                                section.appendChild(noResultsMsg);
                            }
                            
                            noResultsMsg.innerHTML = `No results found for "${searchTerm}" in this category.`;
                            noResultsMsg.style.display = 'block';
                        } else {
                            // Hide the no-results message if it exists
                            const noResultsMsg = section.querySelector('.no-search-results');
                            if (noResultsMsg) {
                                noResultsMsg.style.display = 'none';
                            }
                        }
                    });
                }
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
});

// Fix insert button behavior
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const insertButton = document.querySelector('.insert-media');
        if (insertButton) {
            const originalClick = insertButton.onclick;
            insertButton.onclick = function(e) {
                try {
                    // Prevent default action
                    e.preventDefault();
                    
                    // Get selected item
                    const mediaModal = document.getElementById('media-library-modal');
                    const selectedItem = mediaModal.querySelector('.media-item.selected');
                    
                    if (selectedItem) {
                        // Get path from data attribute or img src as fallback
                        let path = selectedItem.getAttribute('data-path') || '';
                        const imgElement = selectedItem.querySelector('img');
                        if (!path && imgElement) {
                            path = imgElement.getAttribute('src') || '';
                        }
                        
                        if (path) {
                            // Format path correctly
                            path = path.replace(/^\/+/, '');
                            path = '/' + path;
                            
                            // Update the input field
                            const targetField = this.getAttribute('data-target') || 'image';
                            const inputField = document.getElementById(targetField);
                            
                            if (inputField) {
                                // Update input field
                                inputField.value = path;
                                
                                // Use our global function
                                if (typeof window.selectMediaItem === 'function') {
                                    window.selectMediaItem(path);
                                }
                            }
                            
                            // Close the modal
                            mediaModal.style.display = 'none';
                            document.body.style.overflow = '';
                        }
                    }
                } catch (error) {
                    console.error('Error in media library insert button handler:', error);
                }
            };
        }
    }, 500);
});

// Make sure our global selectMediaItem function is only called once
// This prevents double-triggering when media library selects an item
document.addEventListener('DOMContentLoaded', function() {
    // Store the original function if it exists
    const originalSelectMediaItem = window.selectMediaItem;
    
    // Debounce wrapper to prevent multiple rapid calls
    window.selectMediaItem = function(path, displayUrl) {
        if (typeof originalSelectMediaItem === 'function') {
            // Clear any pending calls
            if (window.selectMediaItemTimeout) {
                clearTimeout(window.selectMediaItemTimeout);
            }
            
            // Set up new delayed call
            window.selectMediaItemTimeout = setTimeout(function() {
                originalSelectMediaItem(path, displayUrl);
            }, 50);
        }
    };
});