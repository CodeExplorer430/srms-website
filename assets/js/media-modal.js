/**
 * Unified Media Library Modal Handler
 * This script consolidates all media library functionality to work across all admin pages
 */
document.addEventListener('DOMContentLoaded', function() {
    // Core selectors
    const mediaModal = document.getElementById('media-library-modal');
    if (!mediaModal) return; // Exit if modal doesn't exist on page
    
    // Button selectors
    const closeButtons = mediaModal.querySelectorAll('.media-library-close, .close-media-library');
    const openButtons = document.querySelectorAll('.open-media-library, #open-media-library-btn');
    const insertButton = mediaModal.querySelector('.insert-media');
    
    // Content selectors
    const mediaItems = mediaModal.querySelectorAll('.media-item');
    const categoryLinks = mediaModal.querySelectorAll('.media-sidebar li');
    const searchInput = mediaModal.querySelector('#media-search');
    const filterSelect = mediaModal.querySelector('#media-filter');
    const quickUploadForm = mediaModal.querySelector('#quick-upload-form');
    
    // State tracking
    let selectedItem = null;
    let targetFieldId = null;
    
    // ===== MODAL OPEN/CLOSE =====
    
    // Open modal
    openButtons.forEach(button => {
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
    
    // Select media item
    mediaItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove selection from all items
            mediaItems.forEach(i => i.classList.remove('selected'));
            
            // Add selection to clicked item
            this.classList.add('selected');
            selectedItem = this;
            
            // Update preview
            const previewImage = mediaModal.querySelector('.preview-image');
            const previewDetails = mediaModal.querySelector('.preview-details');
            
            if (previewImage && previewDetails) {
                const path = this.dataset.path || this.querySelector('img').src;
                const name = this.querySelector('.media-name').textContent;
                const dimensions = this.querySelector('.media-dimensions')?.textContent || '';
                
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
        });
    });
    
    // ===== CATEGORY FILTERING =====
    
    // Filter by category
    categoryLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Update active state
            categoryLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Get category
            const category = this.dataset.category;
            
            // Update filter dropdown if it exists
            if (filterSelect) {
                filterSelect.value = category;
            }
            
            // Apply filters
            filterMediaItems(category, searchInput ? searchInput.value : '');
        });
    });
    
    // Filter dropdown change
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const category = this.value;
            
            // Update sidebar active item
            categoryLinks.forEach(link => {
                if (link.dataset.category === category) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
            
            // Apply filters
            filterMediaItems(category, searchInput ? searchInput.value : '');
        });
    }
    
    // Search input
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Get current category
            const activeCategory = mediaModal.querySelector('.media-sidebar li.active');
            const category = activeCategory ? activeCategory.dataset.category : 'all';
            
            // Apply filters
            filterMediaItems(category, this.value);
        });
    }
    
    // Filter function
    function filterMediaItems(category, searchTerm) {
        const categorySections = mediaModal.querySelectorAll('.category-section');
        const items = mediaModal.querySelectorAll('.media-item');
        const searchLower = searchTerm.toLowerCase();
        
        // First handle category sections visibility
        if (categorySections.length > 0) {
            categorySections.forEach(section => {
                if (category === 'all' || section.dataset.category === category) {
                    section.style.display = '';
                } else {
                    section.style.display = 'none';
                }
            });
        }
        
        // Then filter individual items by search term
        items.forEach(item => {
            // Skip items in hidden sections
            const parentSection = item.closest('.category-section');
            if (parentSection && parentSection.style.display === 'none') {
                return;
            }
            
            const itemName = item.querySelector('.media-name').textContent.toLowerCase();
            const nameMatch = !searchTerm || itemName.includes(searchLower);
            
            if (nameMatch) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    // ===== INSERT FUNCTIONALITY =====
    
    // Insert selected media
    if (insertButton) {
        insertButton.addEventListener('click', function() {
            if (!selectedItem || this.classList.contains('disabled')) {
                return;
            }
            
            // Get selected image path and target field
            const path = selectedItem.dataset.path || selectedItem.querySelector('img').src;
            const field = document.getElementById(targetFieldId);
            
            if (field) {
                // Ensure path is formatted correctly
                let formattedPath = path;
                if (formattedPath.includes('/assets/') || formattedPath.includes('/images/')) {
                    // Extract the part of the path starting with /assets/ or /images/
                    const match = formattedPath.match(/\/(?:assets|images)\/.*/);
                    if (match) {
                        formattedPath = match[0];
                    }
                }
                
                // Set field value
                field.value = formattedPath;
                
                // Trigger change event to update preview if it exists
                field.dispatchEvent(new Event('change'));
            }
            
            // Close modal
            closeModal();
        });
    }
});