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
 * Initialize mobile menu functionality
 * Enhances responsive design for mobile devices
 */
function initMobileMenu() {
  // Create mobile menu button if it doesn't exist
  if (!document.querySelector('.mobile-menu-toggle')) {
    const header = document.querySelector('header');
        
    if (!header) return;
        
    // Create mobile menu toggle button
    const mobileMenuToggle = document.createElement('div');
    mobileMenuToggle.className = 'mobile-menu-toggle';
    mobileMenuToggle.innerHTML = '<span></span><span></span><span></span>';
        
    // Add mobile menu toggle to header
    header.appendChild(mobileMenuToggle);
        
     // Toggle mobile menu on click
     mobileMenuToggle.addEventListener('click', function() {
      const menuLinks = document.querySelector('.menu-link');
      menuLinks.classList.toggle('active');
      this.classList.toggle('active');
  });
}

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