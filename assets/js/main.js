/**
 * St. Raphaela Mary School - Main JavaScript File
 * This file contains all the common JavaScript functionality used across the website.
 */

// Execute when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize core functionality
    initStickyHeader();
    initSlideshow();
    initMobileMenu();
});

/**
 * Makes the header sticky on scroll
 * Used on all pages to enhance navigation experience
 */
function initStickyHeader() {
    const header = document.querySelector('header');
    
    if (!header) return;
    
    // Get the threshold from the home page (150) or other pages (100)
    const isHomePage = document.querySelector('.enr') !== null;
    const threshold = isHomePage ? 150 : 100;
    
    window.addEventListener('scroll', function() {
        header.classList.toggle('sticky', window.scrollY > threshold);
    });
}

/**
 * Initialize slideshow functionality for the home page
 * Provides automatic rotation and manual navigation controls
 */
function initSlideshow() {
    const slideshowContainer = document.querySelector('.slideshow-container');
    
    if (!slideshowContainer) return;
    
    const slides = document.querySelectorAll('.slide');
    let slideIndex = 0;
    
    // Function to display a specific slide
    function showSlide(index) {
        if (slides.length === 0) return;
        
        // Adjust index if out of bounds
        if (index >= slides.length) slideIndex = 0;
        if (index < 0) slideIndex = slides.length - 1;
        
        // Hide all slides
        slides.forEach(slide => slide.style.display = "none");
        
        // Show the current slide
        slides[slideIndex].style.display = "block";
    }
    
    // Initialize the first slide
    showSlide(slideIndex);
    
    // Global function to change slides (needs to be global for inline button onclick)
    window.changeSlide = function(n) {
        slideIndex += n;
        showSlide(slideIndex);
    };
    
    // Set up prev/next buttons
    const prevButton = document.querySelector('.prev');
    const nextButton = document.querySelector('.next');
    
    if (prevButton) {
        prevButton.addEventListener('click', function() {
            changeSlide(-1);
        });
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', function() {
            changeSlide(1);
        });
    }
    
    // Automatic slideshow - change slides every 3 seconds
    setInterval(function() {
        changeSlide(1);
    }, 3000);
}

/**
 * Enhanced Mobile Menu Initialization
 * Adds improved submenu handling, click-outside behavior, and resize handling
 */
function initMobileMenu() {
    // Get menu elements
    const header = document.querySelector('header');
    const menuLinks = document.querySelector('.menu-link');
    
    if (!header || !menuLinks) return;
    
    // Create mobile menu toggle if it doesn't exist
    let mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    
    if (!mobileMenuToggle) {
        // Create mobile menu toggle button (keeping your existing structure)
        mobileMenuToggle = document.createElement('div');
        mobileMenuToggle.className = 'mobile-menu-toggle';
        mobileMenuToggle.innerHTML = '<span></span><span></span><span></span>';
        
        // Add mobile menu toggle to header
        header.appendChild(mobileMenuToggle);
    }
    
    // Toggle mobile menu on click (maintain existing behavior)
    mobileMenuToggle.addEventListener('click', function(e) {
        e.stopPropagation(); // Prevent event from bubbling to document
        menuLinks.classList.toggle('active');
        this.classList.toggle('active');
    });
    
    // Enhanced submenu handling for mobile
    const menuItemsWithDropdown = document.querySelectorAll('.menu-link > li');
    
    menuItemsWithDropdown.forEach(item => {
        // Check if this menu item has a dropdown
        const hasDropdown = item.querySelector('.drop-down') !== null;
        
        if (hasDropdown) {
            // Get the main menu link of this item
            const mainLink = item.querySelector('.sub-menu-link');
            
            if (mainLink) {
                // Add click event to main link only (not the entire li)
                mainLink.addEventListener('click', function(e) {
                    // Only handle specially on mobile view
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        
                        // Close other open submenus
                        menuItemsWithDropdown.forEach(otherItem => {
                            if (otherItem !== item && otherItem.classList.contains('show-submenu')) {
                                otherItem.classList.remove('show-submenu');
                            }
                        });
                        
                        // Toggle this submenu
                        item.classList.toggle('show-submenu');
                    }
                });
                
                // Add special handling for links within the dropdown
                const dropdownLinks = item.querySelectorAll('.drop-down a');
                dropdownLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        // Allow these links to navigate when clicked
                        e.stopPropagation();
                    });
                });
            }
        }
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        // If click is outside menu and toggle, and menu is open
        if (!menuLinks.contains(e.target) && 
            !mobileMenuToggle.contains(e.target) && 
            menuLinks.classList.contains('active')) {
            
            // Close main menu
            menuLinks.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
            
            // Close all submenus
            document.querySelectorAll('.menu-link > li.show-submenu').forEach(item => {
                item.classList.remove('show-submenu');
            });
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Reset menu state on larger screens
            if (menuLinks.classList.contains('active')) {
                menuLinks.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            }
            
            // Remove any show-submenu classes
            document.querySelectorAll('.menu-link > li.show-submenu').forEach(item => {
                item.classList.remove('show-submenu');
            });
        }
    });

/**
 * Adjust table columns based on screen size
 * Hides less important columns on mobile devices for better readability
 */
function adjustTableColumns() {
  const table = document.getElementById('usersTable');
  
  // Guard clause - only proceed if table exists
  if (!table) return;
  
  const windowWidth = window.innerWidth;
  
  if (windowWidth < 768) {
      // Hide less important columns on mobile
      const hideCols = [2, 4]; // Role and Last Login columns
      for (let i = 0; i < table.rows.length; i++) {
          const cells = table.rows[i].cells;
          for (let j = 0; j < hideCols.length; j++) {
              if (cells[hideCols[j]]) {
                  cells[hideCols[j]].style.display = 'none';
              }
          }
      }
  } else {
      // Show all columns on larger screens
      for (let i = 0; i < table.rows.length; i++) {
          const cells = table.rows[i].cells;
          for (let j = 0; j < cells.length; j++) {
              cells[j].style.display = '';
          }
      }
  }
}
  
  // Call on page load and resize
  window.addEventListener('load', adjustTableColumns);
  window.addEventListener('resize', adjustTableColumns);
}

/**
 * Enhanced Image Path Handler for Media Library Integration
 * Version: 1.0.0
 */
(function() {
    console.log('Initializing Enhanced Image Path Handler');
    
    // Function to fix image paths when they fail to load
    function setupImageErrorHandling() {
        document.addEventListener('DOMContentLoaded', function() {
            // Get all images on the page
            const images = document.querySelectorAll('img');
            
            // Add error handlers to each image
            images.forEach(function(img) {
                img.addEventListener('error', function() {
                    // Only process images that aren't using the fallback already
                    if (this.src.indexOf('logo-primary.png') === -1 && !this.hasAttribute('data-tried-fallback')) {
                        console.log('Image failed to load:', this.src);
                        
                        // Extract project folder from current URL
                        const urlParts = window.location.pathname.split('/');
                        const projectFolder = urlParts[1] ? urlParts[1] : '';
                        
                        // Extract filename
                        const pathParts = this.src.split('/');
                        const filename = pathParts[pathParts.length - 1];
                        
                        console.log('Attempting fallback for:', filename);
                        
                        // Try different path combinations
                        const origin = window.location.origin;
                        const assetPath = '/assets/images/';
                        const categories = ['branding', 'news', 'events', 'promotional', 'facilities', 'campus'];
                        
                        // First try with current folder
                        if (pathParts.length > 2) {
                            const currentFolder = pathParts[pathParts.length - 2];
                            this.src = `${origin}/${projectFolder}${assetPath}${currentFolder}/${filename}`;
                            this.setAttribute('data-tried-fallback', 'current-folder');
                            return;
                        }
                        
                        // Mark this as tried to prevent infinite recursion
                        this.setAttribute('data-tried-fallback', 'true');
                        
                        // If no project folder specified, try with default branding folder
                        if (this.alt && this.alt.toLowerCase().includes('logo')) {
                            this.src = `${origin}/${projectFolder}/assets/images/branding/logo-primary.png`;
                        } else {
                            this.src = `${origin}/${projectFolder}/assets/images/placeholder.jpg`;
                        }
                    }
                });
            });
        });
    }
    
    // Set up the error handling
    setupImageErrorHandling();
    
    // Expose public API
    window.ImagePathFixer = {
        fixImagePaths: setupImageErrorHandling
    };
})();