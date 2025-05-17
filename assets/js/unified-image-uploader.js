/**
 * Unified Image Uploader
 * Provides a single, consistent interface for image uploads and media library integration
 * Version: 1.0.0
 */
(function() {
    // Main configuration
    const config = {
        imagePathInput: 'image',              // ID of the input field for image path
        imageUploadInput: 'image_upload',     // ID of the file input for image upload
        previewContainer: 'unified-image-preview', // ID of the preview container
        previewImage: 'preview-image',        // ID of the preview image element
        previewPlaceholder: 'preview-placeholder', // ID of the placeholder element
        sourceIndicator: 'source-indicator',  // ID of the source indicator element
        mediaLibraryModal: 'media-library-modal', // ID of the media library modal
        mediaLibraryItems: '.media-item',     // Selector for media library items
        mediaInsertButton: '.insert-media'    // Selector for media insert button
    };

    // State management
    let state = {
        mode: null,              // 'library' or 'upload'
        currentPath: null,       // Current selected image path
        uploadDataUrl: null,     // Data URL for uploaded image preview
        selectedFile: null       // Currently selected File object
    };

    // DOM elements cache
    let elements = {};

    // Initialize when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔄 Initializing Unified Image Uploader...');
        
        // Cache DOM elements
        elements = {
            imageInput: document.getElementById(config.imagePathInput),
            imageUpload: document.getElementById(config.imageUploadInput),
            previewContainer: document.getElementById(config.previewContainer),
            previewImage: document.getElementById(config.previewImage),
            previewPlaceholder: document.getElementById(config.previewPlaceholder),
            sourceIndicator: document.getElementById(config.sourceIndicator),
            mediaModal: document.getElementById(config.mediaLibraryModal),
            mediaItems: document.querySelectorAll(config.mediaLibraryItems),
            mediaInsertButton: document.querySelector(config.mediaInsertButton),
            openMediaButtons: document.querySelectorAll('.open-media-library')
        };
        
        // Check if required elements exist
        if (!elements.imageInput || !elements.previewContainer) {
            console.error('🔄 Required elements not found, aborting initialization');
            return;
        }
        
        // Initialize custom file upload UI
        initCustomFileUpload();
        
        // Set up event listeners
        bindEvents();
        
        // Initialize based on existing path
        if (elements.imageInput.value.trim()) {
            activateLibraryMode(elements.imageInput.value);
        }
        
        console.log('🔄 Unified Image Uploader initialized successfully');
    });
    
    // Initialize custom file upload UI
    function initCustomFileUpload() {
        if (!elements.imageUpload) return;
        
        // Create custom file input UI
        const customContainer = document.createElement('div');
        customContainer.className = 'custom-file-upload';
        customContainer.style.display = 'flex';
        customContainer.style.alignItems = 'center';
        customContainer.style.marginBottom = '10px';
        
        const customButton = document.createElement('button');
        customButton.type = 'button';
        customButton.className = 'custom-file-button';
        customButton.innerHTML = '<i class="bx bx-upload"></i> Choose File';
        customButton.style.backgroundColor = '#3C91E6';
        customButton.style.color = 'white';
        customButton.style.border = 'none';
        customButton.style.padding = '8px 15px';
        customButton.style.borderRadius = '4px';
        customButton.style.cursor = 'pointer';
        customButton.style.marginRight = '10px';
        
        const fileNameDisplay = document.createElement('div');
        fileNameDisplay.className = 'file-name-display';
        fileNameDisplay.textContent = 'No file selected';
        fileNameDisplay.style.flexGrow = '1';
        fileNameDisplay.style.padding = '8px 12px';
        fileNameDisplay.style.backgroundColor = '#f8f9fa';
        fileNameDisplay.style.border = '1px solid #ced4da';
        fileNameDisplay.style.borderRadius = '4px';
        fileNameDisplay.style.color = '#6c757d';
        
        customContainer.appendChild(customButton);
        customContainer.appendChild(fileNameDisplay);
        
        // Insert custom UI before original input
        elements.imageUpload.parentNode.insertBefore(customContainer, elements.imageUpload);
        
        // Hide the original input
        elements.imageUpload.style.display = 'none';
        
        // Add click handler to trigger the original input
        customButton.addEventListener('click', function() {
            elements.imageUpload.click();
        });
        
        // Add to elements cache
        elements.customButton = customButton;
        elements.fileNameDisplay = fileNameDisplay;
    }
    
    // Bind all event listeners
    function bindEvents() {
        // Image path input change
        if (elements.imageInput) {
            elements.imageInput.addEventListener('input', function() {
                const path = this.value.trim();
                if (path) {
                    activateLibraryMode(path);
                } else {
                    resetState();
                }
            });
        }
        
        // File upload change
        if (elements.imageUpload) {
            elements.imageUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        activateUploadMode(e.target.result, file);
                    };
                    
                    reader.onerror = function() {
                        resetState();
                        showError('Failed to read file');
                    };
                    
                    reader.readAsDataURL(file);
                } else if (state.mode === 'upload') {
                    resetState();
                }
            });
        }
        
        // Preview image error handling
        if (elements.previewImage) {
            elements.previewImage.addEventListener('error', function() {
                if (state.mode === 'library') {
                    showError('Image failed to load');
                }
            });
        }
        
        // Media Library Integration
        if (elements.mediaModal) {
            // Override media item selection
            elements.mediaItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Remove selection from all items
                    elements.mediaItems.forEach(i => i.classList.remove('selected'));
                    
                    // Add selection to clicked item
                    this.classList.add('selected');
                    
                    // Enable insert button
                    if (elements.mediaInsertButton) {
                        elements.mediaInsertButton.classList.remove('disabled');
                        elements.mediaInsertButton.disabled = false;
                    }
                });
                
                // Double-click to select immediately
                item.addEventListener('dblclick', function() {
                    const path = this.getAttribute('data-path');
                    if (path) {
                        activateLibraryMode(path);
                        elements.mediaModal.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                });
            });
            
            // Override insert button
            if (elements.mediaInsertButton) {
                elements.mediaInsertButton.addEventListener('click', function() {
                    const selectedItem = elements.mediaModal.querySelector('.media-item.selected');
                    if (selectedItem) {
                        const path = selectedItem.getAttribute('data-path');
                        if (path) {
                            activateLibraryMode(path);
                            elements.mediaModal.style.display = 'none';
                            document.body.style.overflow = '';
                        }
                    }
                });
            }
            
            // Handle open media library buttons
            elements.openMediaButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    elements.mediaModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
            });
        }
    }
    
    // Reset all state
    function resetState() {
        console.log('🔄 Resetting image state');
        
        // Reset UI elements
        elements.previewImage.src = '';
        elements.previewImage.style.display = 'none';
        elements.previewPlaceholder.style.display = 'flex';
        elements.sourceIndicator.innerHTML = '';
        elements.previewContainer.className = 'image-preview-container';
        
        // Reset file name display
        if (elements.fileNameDisplay) {
            elements.fileNameDisplay.textContent = 'No file selected';
            elements.fileNameDisplay.style.color = '#6c757d';
            elements.fileNameDisplay.style.fontWeight = 'normal';
        }
        
        // Reset state variables
        state = {
            mode: null,
            currentPath: null,
            uploadDataUrl: null,
            selectedFile: null
        };
    }
    
    // Activate library mode (for media library selections)
    function activateLibraryMode(path) {
        if (!path || path.trim() === '') {
            resetState();
            return;
        }
        
        console.log('🔄 Activating LIBRARY mode with path:', path);
        
        // Reset first to clear previous state
        resetState();
        
        // Format path
        const formattedPath = normalizePath(path);
        state.currentPath = formattedPath;
        state.mode = 'library';
        
        // Update UI for library mode
        elements.previewContainer.classList.add('library-mode');
        elements.sourceIndicator.innerHTML = '<span><i class="bx bx-link"></i> Media Library</span>';
        
        // Update preview
        elements.previewImage.src = formattedPath;
        elements.previewImage.style.display = 'block';
        elements.previewPlaceholder.style.display = 'none';
        
        // Set input value if it's different
        if (elements.imageInput && elements.imageInput.value !== formattedPath) {
            elements.imageInput.value = formattedPath;
        }
        
        // Clear file upload input to avoid conflicts
        if (elements.imageUpload) {
            elements.imageUpload.value = '';
        }
    }
    
    // Activate upload mode (for local file uploads)
    function activateUploadMode(dataUrl, file) {
        if (!dataUrl) {
            resetState();
            return;
        }
        
        console.log('🔄 Activating UPLOAD mode with file:', file ? file.name : 'unknown');
        
        // Reset first to clear previous state
        resetState();
        
        // Store file reference and data
        state.selectedFile = file;
        state.uploadDataUrl = dataUrl;
        state.mode = 'upload';
        
        // Update file name display
        if (elements.fileNameDisplay && file) {
            elements.fileNameDisplay.textContent = file.name;
            elements.fileNameDisplay.style.color = '#28a745';
            elements.fileNameDisplay.style.fontWeight = 'bold';
        }
        
        // Update UI for upload mode
        elements.previewContainer.classList.add('upload-mode');
        elements.sourceIndicator.innerHTML = `<span><i class="bx bx-upload"></i> Local upload: <strong>${file ? file.name : ''}</strong> (not saved until you submit)</span>`;
        
        // Update preview
        elements.previewImage.src = dataUrl;
        elements.previewImage.style.display = 'block';
        elements.previewPlaceholder.style.display = 'none';
        
        // Do NOT clear the text input - we need both for the form submission
        // This is a key difference from library mode
    }
    
    // Show error state
    function showError(message) {
        console.error('🔄 Image error:', message);
        elements.previewContainer.classList.add('error-mode');
        elements.sourceIndicator.innerHTML = '<span><i class="bx bx-error-circle"></i> ' + message + '</span>';
    }
    
    // Normalize path format
    function normalizePath(path) {
        if (!path) return '';
        
        // Replace backslashes with forward slashes
        let normalized = path.replace(/\\/g, '/');
        
        // Remove any double slashes
        normalized = normalized.replace(/\/+/g, '/');
        
        // Ensure path starts with a single slash
        normalized = '/' + normalized.replace(/^\/+/, '');
        
        return normalized;
    }
    
    // Expose global interface for external scripts
    window.UnifiedImageUploader = {
        // Public methods
        selectMediaItem: function(path) {
            activateLibraryMode(path);
        },
        reset: function() {
            resetState();
        },
        // Public getters
        getState: function() {
            return {...state}; // Return a copy
        }
    };
    
    // Create legacy compatibility layer
    window.selectMediaItem = function(path) {
        window.UnifiedImageUploader.selectMediaItem(path);
    };
    
    window.captureMediaSelection = function(path) {
        window.UnifiedImageUploader.selectMediaItem(path);
    };
})();
