/**
 * Media Library functionality for St. Raphaela Mary School Admin
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize media library
    function initMediaLibrary() {
        const mediaModal = document.getElementById('media-library-modal');
        if (!mediaModal) return;
        
        const openButtons = document.querySelectorAll('.open-media-library');
        const closeButton = document.querySelector('.media-library-close');
        const mediaItems = document.querySelectorAll('.media-item');
        const insertButton = document.querySelector('.insert-media');
        const categoryLinks = document.querySelectorAll('.media-sidebar li');
        const searchInput = document.getElementById('media-search');
        const filterSelect = document.getElementById('media-filter');
        const quickUploadForm = document.getElementById('quick-upload-form');
        
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
        
        // Select media item
        mediaItems.forEach(item => {
            item.addEventListener('click', function() {
                // Remove selection from other items
                mediaItems.forEach(i => i.classList.remove('selected'));
                
                // Add selection to clicked item
                this.classList.add('selected');
                
                // Update preview
                const path = this.getAttribute('data-path');
                const name = this.querySelector('.media-name').textContent;
                const dimensions = this.querySelector('.media-dimensions').textContent;
                
                const previewImage = document.querySelector('.preview-image');
                const previewDetails = document.querySelector('.preview-details');
                
                previewImage.innerHTML = `<img src="${path}" alt="${name}">`;
                previewDetails.innerHTML = `
                    <strong>${name}</strong><br>
                    <span>${dimensions}</span><br>
                    <code style="color: #6c757d; font-size: 12px; word-break: break-all;">${path}</code>
                `;
                
                // Enable insert button
                if (insertButton) {
                    insertButton.disabled = false;
                }
            });
        });
        
        // Insert selected media
        if (insertButton) {
            insertButton.addEventListener('click', function() {
                const selectedItem = document.querySelector('.media-item.selected');
                
                if (selectedItem) {
                    // Ensure path always starts with a slash and normalize
                    let path = selectedItem.getAttribute('data-path');
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
            });
        }
        
        // Filter by category
        categoryLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Remove active class from other links
                categoryLinks.forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Get selected category
                const category = this.getAttribute('data-category');
                
                // Update filter dropdown to match
                if (filterSelect) {
                    filterSelect.value = category;
                }
                
                // Filter items
                filterMediaItems(category, searchInput ? searchInput.value : '');
            });
        });
        
        // Search media items
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const activeCategory = document.querySelector('.media-sidebar li.active');
                const category = activeCategory ? activeCategory.getAttribute('data-category') : 'all';
                filterMediaItems(category, this.value);
            });
        }
        
        // Filter dropdown
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                const category = this.value;
                
                // Update sidebar active link
                categoryLinks.forEach(link => {
                    if (link.getAttribute('data-category') === category) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
                
                // Filter items
                filterMediaItems(category, searchInput ? searchInput.value : '');
            });
        }
        
        // Filter function
        function filterMediaItems(category, searchTerm) {
            const items = document.querySelectorAll('.media-item');
            const searchLower = searchTerm.toLowerCase();
            
            items.forEach(item => {
                const itemCategory = item.closest('.category-section').getAttribute('data-category');
                const itemName = item.querySelector('.media-name').textContent.toLowerCase();
                
                // Check category
                const categoryMatch = category === 'all' || itemCategory === category;
                
                // Check search term
                const searchMatch = !searchTerm || itemName.includes(searchLower);
                
                // Show/hide based on filters
                if (categoryMatch && searchMatch) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Quick upload
        if (quickUploadForm) {
            quickUploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                submitButton.textContent = 'Uploading...';
                submitButton.disabled = true;
                
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
                        alert('Upload failed: ' + data.message);
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
        }
    }
    
    // Initialize if media library exists on the page
    initMediaLibrary();
    
    // Image preview functionality
    function initImagePreview() {
        const imageInputs = document.querySelectorAll('input[type="text"][id$="image"]');
        
        imageInputs.forEach(input => {
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
            } else {
                input.parentNode.insertBefore(previewContainer, input.nextSibling);
            }
            
            // Update preview when path changes
            input.addEventListener('change', function() {
                updateImagePreview(this, previewContainer);
            });
            
            // Initial preview
            if (input.value) {
                updateImagePreview(input, previewContainer);
            }
        });
        
        // Update preview function
        function updateImagePreview(input, container) {
            const path = input.value;
            const previewImg = container.querySelector('img');
            const placeholder = container.querySelector('.preview-placeholder');
            
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
        }
    }
    
    // Initialize image previews
    initImagePreview();
});