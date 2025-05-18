<?php
/**
 * Common Functions
 * Utility and CMS functions for the St. Raphaela Mary School website
 */

// Define constants for environment detection
if (!defined('IS_WINDOWS')) {
    define('IS_WINDOWS', (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'));
}
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Initialize database connection
function db_connect() {
    static $db = null;
    if($db === null) {
        require_once 'config.php';
        require_once 'db.php';
        $db = new Database();
    }
    return $db;
}

/**
 * SCHOOL INFORMATION FUNCTIONS
 */

// Get school information
function get_school_info() {
    $db = db_connect();
    $school_info = $db->fetch_row("SELECT * FROM school_information LIMIT 1");
    
    // Return default values if no information found
    if (!$school_info) {
        return [
            'name' => 'ST. RAPHAELA MARY SCHOOL',
            'mission' => 'Default mission text if database record not found.',
            'vision' => 'Default vision text if database record not found.',
            'philosophy' => 'Default philosophy text if database record not found.',
            'email' => 'srmseduc@gmail.com',
            'phone' => '8253-3801/0920 832 7705',
            'address' => '#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City'
        ];
    }
    
    return $school_info;
}

/**
 * NAVIGATION FUNCTIONS
 */

// Get navigation menu items
function get_navigation_menu() {
    $db = db_connect();
    
    // If no database or table exists yet, return a default menu
    try {
        // Get parent menu items
        $parent_items = $db->fetch_all("SELECT * FROM navigation WHERE parent_id IS NULL AND is_active = TRUE ORDER BY display_order ASC");
        
        foreach($parent_items as &$item) {
            // Get child menu items
            $item['children'] = $db->fetch_all("SELECT * FROM navigation WHERE parent_id = {$item['id']} AND is_active = TRUE ORDER BY display_order ASC");
        }
        
        return $parent_items;
    } catch (Exception $e) {
        // Return default menu structure if database query fails
        return [
            ['name' => 'HOME', 'url' => 'index.php', 'children' => []],
            ['name' => 'ADMISSIONS', 'url' => 'admissions.php', 'children' => []],
            ['name' => 'ABOUT SRMS', 'url' => 'about.php', 'children' => [
                ['name' => 'ALUMNI', 'url' => 'alumni.php'],
                ['name' => 'FACULTY', 'url' => 'faculty.php']
            ]],
            ['name' => 'ACADEMICS', 'url' => '#', 'children' => [
                ['name' => 'PRESCHOOL', 'url' => '#'],
                ['name' => 'ELEMENTARY', 'url' => '#'],
                ['name' => 'JUNIOR HIGH', 'url' => '#'],
                ['name' => 'SENIOR HIGH', 'url' => 'academics/senior-high.php']
            ]],
            ['name' => 'NEWS', 'url' => 'news.php', 'children' => []],
            ['name' => 'CONTACT', 'url' => 'contact.php', 'children' => []]
        ];
    }
}

// Get active page name from URL
function get_active_page() {
    $uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri_segments = explode('/', $uri_path);
    $last_segment = end($uri_segments);
    
    if($last_segment == '' || $last_segment == 'index.php') {
        return 'home';
    }
    
    return str_replace('.php', '', $last_segment);
}

/**
 * DATE AND FORMATTING FUNCTIONS
 */

// Format date for display
function format_date($db_date, $format = 'F j, Y') {
    $timestamp = strtotime($db_date);
    return date($format, $timestamp);
}

// Helper function for formatting requirements as lists
function format_requirements_list($requirements) {
    if (empty($requirements)) {
        return '';
    }
    
    $output = '<ol>';
    $lines = explode("\n", $requirements);
    $in_sublist = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            // Check if this is a sub-list item (e.g., "a. Something")
            if (preg_match('/^[a-z]\.\s+(.+)$/', $line, $matches)) {
                // Start a sub-list if not already in one
                if (!$in_sublist) {
                    $output .= '<ol>';
                    $in_sublist = true;
                }
                $output .= '<li>' . $matches[1] . '</li>';
            } else {
                // Close any open sub-list
                if ($in_sublist) {
                    $output .= '</ol>';
                    $in_sublist = false;
                }
                $output .= '<li>' . $line . '</li>';
            }
        }
    }
    
    // Close any open sub-list
    if ($in_sublist) {
        $output .= '</ol>';
    }
    
    $output .= '</ol>';
    return $output;
}

/**
 * PAGE CONTENT MANAGEMENT FUNCTIONS
 */

/**
 * Get a page content from the database
 * 
 * @param string $page_key - The unique key for the page
 * @return array|null - The page data or null if not found
 */
function get_page_content($page_key) {
    $db = db_connect();
    
    $page_key = $db->escape($page_key);
    
    // First try with the page_content table
    $page = $db->fetch_row("SELECT * FROM page_content WHERE page_key = '$page_key'");
    
    // If page exists in page_content, also get its sections
    if ($page) {
        $page_id = $page['id'];
        $sections = $db->fetch_all("SELECT * FROM page_sections WHERE page_id = $page_id ORDER BY display_order ASC");
        $page['sections'] = $sections;
        
        // Organize sections by key for easier access
        $page['sections_by_key'] = [];
        foreach ($sections as $section) {
            $page['sections_by_key'][$section['section_key']] = $section;
        }
        return $page;
    }
    
    // Fallback to legacy pages table if it exists
    try {
        $legacy_page = $db->fetch_row("SELECT * FROM pages WHERE slug = '$page_key'");
        if ($legacy_page) {
            // Convert legacy page to new format
            $legacy_page['page_key'] = $legacy_page['slug'];
            $legacy_page['sections'] = [];
            $legacy_page['sections_by_key'] = [];
            
            // Create a "content" section from the page content
            if (!empty($legacy_page['content'])) {
                $main_section = [
                    'section_key' => 'content',
                    'title' => $legacy_page['title'],
                    'content' => $legacy_page['content'],
                    'display_order' => 0
                ];
                $legacy_page['sections'][] = $main_section;
                $legacy_page['sections_by_key']['content'] = $main_section;
            }
            
            return $legacy_page;
        }
    } catch (Exception $e) {
        // Table doesn't exist or query failed - ignore and return null
    }
    
    return null;
}

/**
 * Display a section of a page
 * 
 * @param array $page - The page data
 * @param string $section_key - The section key to display
 * @param boolean $show_title - Whether to show the section title
 * @param string $default_content - Default content if section not found
 * @return void
 */
function display_page_section($page, $section_key, $show_title = true, $default_content = '') {
    if (!isset($page['sections_by_key'][$section_key])) {
        echo $default_content;
        return;
    }
    
    $section = $page['sections_by_key'][$section_key];
    
    if ($show_title && !empty($section['title'])) {
        echo '<h3>' . htmlspecialchars($section['title']) . '</h3>';
    }
    
    echo nl2br(htmlspecialchars($section['content']));
}

/**
 * ACADEMIC PROGRAMS FUNCTIONS
 */

/**
 * Get all academic levels
 * 
 * @return array - Array of academic levels
 */
function get_academic_levels() {
    $db = db_connect();
    
    return $db->fetch_all("SELECT * FROM academic_levels ORDER BY display_order ASC");
}

/**
 * Get programs for a specific academic level
 * 
 * @param int $level_id - The ID of the academic level
 * @return array - Array of programs
 */
function get_programs_by_level($level_id) {
    $db = db_connect();
    
    $level_id = (int)$level_id;
    return $db->fetch_all("SELECT * FROM academic_programs WHERE level_id = $level_id ORDER BY display_order ASC");
}

/**
 * Get tracks for a specific program
 * 
 * @param int $program_id - The ID of the academic program
 * @return array - Array of tracks
 */
function get_tracks_by_program($program_id) {
    $db = db_connect();
    
    $program_id = (int)$program_id;
    return $db->fetch_all("SELECT * FROM academic_tracks WHERE program_id = $program_id ORDER BY display_order ASC");
}

/**
 * Get a specific academic level by slug
 * 
 * @param string $slug - The slug of the academic level
 * @return array|null - The academic level data or null if not found
 */
function get_academic_level_by_slug($slug) {
    $db = db_connect();
    
    $slug = $db->escape($slug);
    $level = $db->fetch_row("SELECT * FROM academic_levels WHERE slug = '$slug'");
    
    if ($level) {
        // Get programs for this level
        $level_id = $level['id'];
        $programs = $db->fetch_all("SELECT * FROM academic_programs WHERE level_id = $level_id ORDER BY display_order ASC");
        $level['programs'] = $programs;
        
        // Get tracks for each program
        foreach ($level['programs'] as $key => $program) {
            $program_id = $program['id'];
            $tracks = $db->fetch_all("SELECT * FROM academic_tracks WHERE program_id = $program_id ORDER BY display_order ASC");
            $level['programs'][$key]['tracks'] = $tracks;
        }
    }
    
    return $level;
}

/**
 * FACULTY MANAGEMENT FUNCTIONS
 */

/**
 * Get all faculty categories
 * 
 * @return array - Array of faculty categories with members
 */
function get_faculty_categories() {
    $db = db_connect();
    
    $categories = $db->fetch_all("SELECT * FROM faculty_categories ORDER BY display_order ASC");
    
    // For each category, get its members
    foreach ($categories as $key => $category) {
        $category_id = $category['id'];
        $members = $db->fetch_all("SELECT * FROM faculty WHERE category_id = $category_id ORDER BY display_order ASC");
        $categories[$key]['members'] = $members;
    }
    
    return $categories;
}

/**
 * Get faculty members for a specific category
 * 
 * @param int $category_id - The category ID
 * @return array - Array of faculty members
 */
function get_faculty_by_category($category_id) {
    $db = db_connect();
    
    $category_id = (int)$category_id;
    return $db->fetch_all("SELECT * FROM faculty WHERE category_id = $category_id ORDER BY display_order ASC");
}

/**
 * ADMISSIONS CONTENT FUNCTIONS
 */

/**
 * Get admission policies
 * 
 * @return array - Array of admission policies
 */
function get_admission_policies() {
    $db = db_connect();
    
    // Try first in the new table structure
    $policies = $db->fetch_row("SELECT * FROM admission_policies ORDER BY display_order ASC LIMIT 1");
    
    if ($policies) {
        // Parse the content into an array of policies
        $content = $policies['content'];
        $policies_array = [];
        if (!empty($content)) {
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $policies_array[] = $line;
                }
            }
        }
        
        return [
            'title' => $policies['title'] ?? 'Admission Policies',
            'policies' => $policies_array
        ];
    }
    
    // Fallback to default
    return [
        'title' => 'Admission Policies',
        'policies' => ['No admission policies have been defined yet.']
    ];
}

/**
 * Get student types with requirements
 * 
 * @return array - Array of student types
 */
function get_student_types() {
    $db = db_connect();
    
    return $db->fetch_all("SELECT * FROM student_types ORDER BY display_order ASC");
}

/**
 * Get age requirements
 * 
 * @return array - Array of age requirements
 */
function get_age_requirements() {
    $db = db_connect();
    
    return $db->fetch_all("SELECT * FROM age_requirements ORDER BY display_order ASC");
}

/**
 * Get enrollment procedures
 * 
 * @return array - Array of enrollment procedures
 */
function get_enrollment_procedures() {
    $db = db_connect();
    
    return $db->fetch_all("SELECT * FROM enrollment_procedures ORDER BY display_order ASC");
}

/**
 * Get non-readmission grounds
 * 
 * @return array - Array of non-readmission grounds
 */
function get_non_readmission_grounds() {
    $db = db_connect();
    
    return $db->fetch_all("SELECT * FROM non_readmission_grounds ORDER BY display_order ASC");
}

/**
 * HOMEPAGE ELEMENTS FUNCTIONS
 */

/**
 * Get slideshow images
 * 
 * @param boolean $active_only - Whether to get only active slides
 * @return array - Array of slideshow images
 */
function get_slideshow($active_only = true) {
    $db = db_connect();
    
    $where_clause = $active_only ? "WHERE is_active = 1" : "";
    return $db->fetch_all("SELECT * FROM slideshow $where_clause ORDER BY display_order ASC");
}

/**
 * Get facilities
 * 
 * @return array - Array of facilities
 */
function get_facilities() {
    $db = db_connect();
    
    return $db->fetch_all("SELECT * FROM facilities ORDER BY display_order ASC");
}

/**
 * Get offer box content
 * 
 * @return array - Array of offer box items
 */
function get_offer_box() {
    $db = db_connect();
    
    try {
        return $db->fetch_all("SELECT * FROM offer_box ORDER BY display_order ASC");
    } catch (Exception $e) {
        // Table might not exist yet
        return [];
    }
}

/**
 * UTILITIES FOR DATABASE OPERATIONS
 */

/**
 * Safe query execution with error handling
 * 
 * @param string $sql The SQL query to execute
 * @param array $fallback_data Data to return in case of failure
 * @return mixed Query result or fallback data
 */
function safe_query($sql, $fallback_data = []) {
    try {
        $db = db_connect();
        $result = $db->query($sql);
        if ($result === false) {
            error_log("Query failed: $sql");
            return $fallback_data;
        }
        return $result;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return $fallback_data;
    }
}

/**
 * IMAGE HANDLING FUNCTIONS
 */

/**
 * Get image path with fallback to placeholder
 * 
 * @param string $image_path The original image path
 * @param string $type Type of placeholder (person, facility, event, etc.)
 * @param string $gender For person placeholders, specify 'male' or 'female'
 * @return string Full image path or placeholder path
 */
function get_image_path($image_path, $type = 'general', $gender = 'neutral') {
    // Base directory for images
    $base_dir = SITE_URL . '/assets/images/';
    
    // If image_path is empty or file doesn't exist (and it's not a URL)
    if (empty($image_path) || (!filter_var($image_path, FILTER_VALIDATE_URL) && !file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path))) {
        // Placeholder paths based on type
        $placeholders = [
            'person' => $base_dir . 'people/placeholder-' . $gender . '.jpg',
            'facility' => $base_dir . 'facilities/placeholder-facility.jpg',
            'news' => $base_dir . 'news/placeholder-news.jpg',
            'event' => $base_dir . 'events/placeholder-event.jpg',
            'campus' => $base_dir . 'campus/placeholder-campus.jpg',
            'general' => $base_dir . 'placeholder.jpg'
        ];
        
        return isset($placeholders[$type]) ? $placeholders[$type] : $placeholders['general'];
    }
    
    // If image_path is already a URL, return it as is
    if (filter_var($image_path, FILTER_VALIDATE_URL)) {
        return $image_path;
    }
    
    // If image_path is relative, prepend SITE_URL
    if (strpos($image_path, '/') === 0) {
        return SITE_URL . $image_path;
    }
    
    // Otherwise, return the image path as is
    return $image_path;
}

/**
 * Get image path with fallback if file doesn't exist
 * 
 * @param string $image_path Primary image path
 * @param string $fallback_path Fallback image path
 * @param string $default_type Type of image (facility, news, etc.)
 * @param string $name Name to use for default image
 * @return string Valid image path
 */
function get_image_with_fallback($image_path, $fallback_path = '', $default_type = '') {
    // If image path is empty, use fallback
    if (empty($image_path)) {
        return SITE_URL . $fallback_path;
    }
    
    // Normalize the path
    $image_path = normalize_image_path($image_path);
    
    // Check if file exists - try multiple path variations
    $image_exists = verify_image_exists($image_path);
    
    if ($image_exists) {
        return SITE_URL . $image_path;
    }
    
    // Try to find a matching file by pattern
    $dir_path = dirname($_SERVER['DOCUMENT_ROOT'] . $image_path);
    $basename = basename($image_path);
    
    // If directory exists, try to find files with similar name pattern
    if (is_dir($dir_path)) {
        $files = scandir($dir_path);
        $base_name_pattern = preg_replace('/[0-9]+/', '', pathinfo($basename, PATHINFO_FILENAME));
        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        
        foreach ($files as $file) {
            if (strpos($file, $base_name_pattern) !== false && 
                pathinfo($file, PATHINFO_EXTENSION) === $extension) {
                return SITE_URL . dirname($image_path) . '/' . $file;
            }
        }
    }
    
    // Last resort: return the fallback
    return SITE_URL . $fallback_path;
}

/**
 * Get fallback image based on type
 */
function get_fallback_image($fallback_path = '', $default_type = '', $name = '') {
    // If fallback is provided and exists, use it
    if (!empty($fallback_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . $fallback_path)) {
        return SITE_URL . $fallback_path;
    }
    
    // Try to use a type-based default
    if (!empty($default_type)) {
        $type_defaults = [
            'facility' => '/assets/images/facilities/' . (empty($name) ? 'placeholder-facility.jpg' : strtolower($name) . '.jpg'),
            'news' => '/assets/images/news/announcement-01.jpg',
            'campus' => '/assets/images/campus/hero-main.jpg',
            'default' => '/assets/images/placeholder.jpg'
        ];
        
        $default_path = isset($type_defaults[$default_type]) ? $type_defaults[$default_type] : $type_defaults['default'];
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $default_path)) {
            return SITE_URL . $default_path;
        }
    }
    
    // Ultimate fallback - use a placeholder
    return SITE_URL . '/assets/images/placeholder.jpg';
}

/**
 * DEBUG FUNCTIONS
 */

/**
 * Debug image paths and existence
 * Used to troubleshoot image loading issues
 */
function debug_image_path($image_path) {
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    $full_path = $server_root . $image_path;
    
    $debug_info = [
        'image_path' => $image_path,
        'server_root' => $server_root,
        'full_path' => $full_path,
        'path_exists' => file_exists($full_path),
        'is_file' => is_file($full_path),
        'is_readable' => is_readable($full_path)
    ];
    
    // Log to error log
    error_log('Image Debug: ' . json_encode($debug_info));
    
    return $debug_info;
}

/**
 * PATH MANIPULATION FUNCTIONS
 */

/**
 * Standardize image path formatting for consistent handling
 * 
 * @param string $image_path The image path to standardize
 * @return string Standardized image path
 */
function standardize_image_path($image_path) {
    if (empty($image_path)) {
        return '';
    }
    
    // If it's already a full URL, return as is
    if (filter_var($image_path, FILTER_VALIDATE_URL)) {
        return $image_path;
    }
    
    // Make sure path starts with a slash
    if (strpos($image_path, '/') !== 0) {
        $image_path = '/' . $image_path;
    }
    
    // Remove any double slashes
    return preg_replace('#/+#', '/', $image_path);
}

/**
 * Check if image exists with better error logging
 * 
 * @param string $image_path Path to check
 * @return bool True if image exists
 */
function image_exists($image_path) {
    if (empty($image_path)) {
        return false;
    }
    
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    $full_path = $server_root . $image_path;
    
    // Try alternative path formats
    $alt_path = $server_root . DIRECTORY_SEPARATOR . ltrim($image_path, '/');
    
    $exists = file_exists($full_path) || file_exists($alt_path);
    
    if (!$exists) {
        // Log issue for debugging
        error_log("Image not found: {$image_path}. Tried paths: {$full_path} and {$alt_path}");
    }
    
    return $exists;
}

/**
 * Enhanced file existence check with multiple path options
 */
function file_exists_with_alternatives($image_path) {
    // Simply use the more comprehensive verify_image_exists function
    return verify_image_exists($image_path);
}

/**
 * Get correct image URL with project folder considered
 * This ensures images are properly found regardless of path storage method
 * 
 * @param string $image_path Image path from database
 * @return string Full, correct URL
 */
function get_correct_image_url($image_path) {
    if (empty($image_path)) return '';
    
    // Normalize path first (remove duplicate slashes, etc)
    $path = normalize_image_path($image_path);
    
    // Get project folder name from SITE_URL
    $project_folder = '';
    if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $project_folder = $matches[1]; // Should be "srms-website"
    }
    
    // Force use of the project folder in final URL
    return SITE_URL . $path;
}

/**
 * Enhanced normalize_image_path function that works consistently across environments
 * 
 * @param string $path Path to normalize
 * @return string Normalized path
 */
function normalize_image_path($path) {
    if (empty($path)) return '';
    
    // If it's a URL, extract just the path part
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $path = parse_url($path, PHP_URL_PATH);
    }
    
    // Get project folder from SITE_URL
    $project_folder = '';
    if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $project_folder = $matches[1]; // "srms-website"
    }
    
    // Remove project folder prefix if present (to prevent duplication)
    if (!empty($project_folder) && stripos($path, '/' . $project_folder . '/') === 0) {
        $path = substr($path, strlen('/' . $project_folder));
    }
    
    // Ensure path starts with slash and uses forward slashes
    $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
    
    // Clean up double slashes
    $path = preg_replace('#/+#', '/', $path);
    
    return $path;
}

/**
 * Improved cross-platform image existence verification
 * Specifically designed to work with media library paths
 */
/**
 * Improved cross-platform image existence verification that works consistently with project folder
 */
function verify_image_exists($image_path) {
    if (empty($image_path)) return false;
    
    // Normalize spaces and hyphens in the path
    $image_path = str_replace(" ", "-", $image_path);
    $image_path = normalize_image_path($image_path);
    
    // Get server root and project folder information
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    
    // Always use srms-website as the project folder
    $project_folder = 'srms-website';
    
    // Most important path - with project folder (primary path to check)
    $primary_path = $server_root . DIRECTORY_SEPARATOR . $project_folder . 
                   str_replace('/', DIRECTORY_SEPARATOR, $image_path);
    
    // Log for debugging
    error_log("Primary image path check: $primary_path");
    
    // Check primary path first (with project folder - most likely correct path)
    if (file_exists($primary_path)) {
        error_log("Image found at primary path: $primary_path");
        return true;
    }
    
    // Additional paths to try if primary fails
    $alt_paths = [
        // Path without project folder (less likely)
        $server_root . str_replace('/', DIRECTORY_SEPARATOR, $image_path),
        
        // Other variations including handling spaces vs hyphens
        str_replace(['-'], [' '], $primary_path),
        str_replace('-', ' ', $server_root . str_replace('/', DIRECTORY_SEPARATOR, $image_path))
    ];
    
    // Try all alternative paths
    foreach ($alt_paths as $index => $path) {
        error_log("Trying alternative path $index: $path");
        if (file_exists($path)) {
            error_log("Image found at alternative path: $path");
            return true;
        }
    }
    
    // Special handling for Windows paths with either slashes
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Try with backslashes
        $win_path = str_replace('/', '\\', $primary_path);
        error_log("Trying Windows path: $win_path");
        if (file_exists($win_path)) {
            error_log("Image found at Windows path: $win_path");
            return true;
        }
    }
    
    error_log("Image not found in any location: $image_path");
    return false;
}

/**
 * Convert a database image path to a full URL for display
 * Fixed to always use the correct project folder
 */
function get_display_url($path) {
    // If empty, return empty
    if (empty($path)) return '';
    
    // Normalize path
    $path = normalize_image_path($path);
    
    // If already a full URL, return as is
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }
    
    // Get base URL parts (protocol + domain)
    $base_url = substr(SITE_URL, 0, strpos(SITE_URL, '/', 8));
    
    // Always use srms-website as the project folder for display URLs
    return $base_url . '/srms-website' . $path;
}

/**
 * Find best matching image in a directory
 * Helps resolve issues when filenames contain unique IDs
 * 
 * @param string $image_path Original image path
 * @return string|bool Actual file path if found, false otherwise
 */
function find_best_matching_image($image_path) {
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    $dir_path = dirname($server_root . $image_path);
    $base_filename = pathinfo(basename($image_path), PATHINFO_FILENAME);
    $extension = pathinfo(basename($image_path), PATHINFO_EXTENSION);
    
    // If directory doesn't exist
    if (!is_dir($dir_path)) {
        return false;
    }
    
    // Get all files in the directory
    $files = scandir($dir_path);
    if (!$files) {
        return false;
    }
    
    // First check for exact match
    if (in_array(basename($image_path), $files)) {
        return $image_path;
    }
    
    // Look for pattern match with exact base name (ignoring unique suffix)
    foreach ($files as $file) {
        $file_base = pathinfo($file, PATHINFO_FILENAME);
        if (strpos($file_base, $base_filename) === 0 && 
            pathinfo($file, PATHINFO_EXTENSION) === $extension) {
            return dirname($image_path) . '/' . $file;
        }
    }
    
    // Look for any file with similar pattern (case insensitive)
    foreach ($files as $file) {
        if (stripos($file, $base_filename) !== false && 
            strtolower(pathinfo($file, PATHINFO_EXTENSION)) === strtolower($extension)) {
            return dirname($image_path) . '/' . $file;
        }
    }
    
    return false;
}

/**
 * DIRECTORY & FILE OPERATIONS
 */

/**
 * Cross-platform directory creation function
 */
function create_directory($dir, $recursive = true) {
    // Normalize directory path
    $dir = str_replace(['\\', '/'], DS, $dir);
    
    if (!file_exists($dir)) {
        // Different permissions for Windows vs Linux
        $permissions = IS_WINDOWS ? 0777 : 0755;
        
        if (!mkdir($dir, $permissions, $recursive)) {
            error_log("Failed to create directory: {$dir}");
            return false;
        }
        
        return true;
    }
    
    return true; // Directory already exists
}

/**
 * Upload image to specified category directory with consistent path handling
 * 
 * @param array $file The $_FILES array element for the uploaded file
 * @param string $category The category directory to store the file in
 * @return string|false Path to the uploaded file on success, false on failure
 */
function upload_image($file, $category = 'news') {
    // Log upload attempt for debugging
    error_log("Upload attempt for category: {$category}, file: {$file['name']}");

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        error_log("Invalid file type: {$file['type']}");
        return false;
    }
    
    // Validate file size (2MB max)
    if ($file['size'] > 2 * 1024 * 1024) {
        error_log("File too large: {$file['size']}");
        return false;
    }
    
    // Get document root
    $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    
    // ALWAYS use srms-website as the project folder
    $project_folder = 'srms-website';
    
    // Build target directory path with project folder ALWAYS included
    $target_dir = $doc_root . DIRECTORY_SEPARATOR . $project_folder . 
                 DIRECTORY_SEPARATOR . 'assets' . 
                 DIRECTORY_SEPARATOR . 'images' . 
                 DIRECTORY_SEPARATOR . $category;
    
    // Debug log
    error_log("Upload target directory: {$target_dir}");
    
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        error_log("Creating directory: {$target_dir}");
        if (!mkdir($target_dir, 0755, true)) {
            error_log("Failed to create directory: {$target_dir}");
            return false;
        }
    }
    
    // Generate unique filename
    $filename = pathinfo($file['name'], PATHINFO_FILENAME);
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
    if (empty($filename)) {
        $filename = 'image';
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_name = $filename . '-' . time() . '.' . $extension;
    
    // Target path for the file system
    $target_path = $target_dir . DIRECTORY_SEPARATOR . $unique_name;
    
    // Path for web/database storage (always use forward slashes)
    $relative_path = '/assets/images/' . $category . '/' . $unique_name;
    
    // Log the actual paths being used
    error_log("File upload - Target path: {$target_path}");
    error_log("File upload - Web path: {$relative_path}");
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        chmod($target_path, 0644);
        
        // Double-check file existence after upload
        if (!file_exists($target_path)) {
            error_log("WARNING: File uploaded but not found at expected location: {$target_path}");
        } else {
            error_log("File upload successful and verified at: {$target_path}");
        }
        
        return $relative_path;
    } else {
        $error = error_get_last();
        error_log("Upload failed: " . ($error ? $error['message'] : 'Unknown error'));
        return false;
    }
}

/**
 * ENVIRONMENT DETECTION
 */

/**
 * Detect the server environment (Windows/Linux and server software)
 * 
 * @return array Array with 'os' and 'server' keys
 */
function detect_environment() {
    // Detect operating system
    $is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    
    // Detect server software
    $server_type = 'XAMPP'; // Default
    
    if ($is_windows) {
        // Check for WAMP-specific indicators
        if (file_exists('C:/wamp/www') || file_exists('C:/wamp64/www') || 
            strpos($_SERVER['DOCUMENT_ROOT'], 'wamp') !== false) {
            $server_type = 'WAMP';
        }
    } else {
        // On Linux, check for LAMP-specific indicators
        if (file_exists('/etc/apache2/sites-available') && !file_exists('/opt/lampp')) {
            $server_type = 'LAMP';
        }
    }
    
    return [
        'os' => $is_windows ? 'Windows' : 'Linux',
        'server' => $server_type
    ];
}

/**
 * Get the appropriate directory separator for the current environment
 * 
 * @return string Directory separator ('/' or '\')
 */
function get_directory_separator() {
    return DIRECTORY_SEPARATOR;
}

/**
 * FILE PATH UTILITIES
 */

/**
 * Create a directory with proper permissions based on environment
 * 
 * @param string $path Directory path to create
 * @return boolean Whether the operation was successful
 */
function create_directory_with_proper_permissions($path) {
    // Normalize path for the current OS
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    
    // Check if directory already exists
    if (is_dir($path)) {
        return true;
    }
    
    // Create directory with recursive option
    $success = mkdir($path, 0777, true);
    
    if ($success) {
        // Set permissions based on OS
        $env = detect_environment();
        if ($env['os'] === 'Linux') {
            // Linux needs explicit chmod
            chmod($path, 0777);
        }
    }
    
    return $success;
}

/**
 * Ensures a file path is consistent for the current operating system
 * 
 * @param string $path File path to normalize
 * @return string Normalized path
 */
function normalize_file_path_for_os($path) {
    // For web URLs, always use forward slashes
    if (strpos($path, 'http') === 0) {
        return str_replace('\\', '/', $path);
    }
    
    // For filesystem paths, use the appropriate directory separator
    $env = detect_environment();
    if ($env['os'] === 'Windows') {
        // Convert forward slashes to backslashes for Windows
        return str_replace('/', '\\', $path);
    } else {
        // Convert backslashes to forward slashes for Linux
        return str_replace('\\', '/', $path);
    }
}

/**
 * Enhanced version of file_exists that works across environments
 * 
 * @param string $path File path to check
 * @return boolean Whether the file exists
 */
function enhanced_file_exists($path) {
    // Try with original path
    if (file_exists($path)) {
        return true;
    }
    
    // Try with normalized path for current OS
    $normalized = normalize_file_path_for_os($path);
    if (file_exists($normalized)) {
        return true;
    }
    
    // Try with alternative directory separator
    $alternative = ($path === $normalized) ? 
        str_replace(['/', '\\'], ['\\', '/'], $path) : 
        str_replace(['/', '\\'], ['\\', '/'], $normalized);
    
    if (file_exists($alternative)) {
        return true;
    }
    
    return false;
}

/**
 * Convert a filesystem path to a web-friendly URL path with project folder support
 * 
 * @param string $path File system path
 * @param bool $include_project_folder Whether to include the project folder
 * @return string Web URL path
 */
function filesystem_path_to_url($path, $include_project_folder = true) {
    if (empty($path)) return '';
    
    // Replace backslashes with forward slashes
    $path = str_replace('\\', '/', $path);
    
    // Remove any double slashes
    $path = preg_replace('#/+#', '/', $path);
    
    // Get document root with consistent slashes
    $doc_root = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
    
    // Determine project folder from SITE_URL
    $project_folder = '';
    if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $project_folder = $matches[1]; // Should be "srms-website"
    }
    
    // Project path with consistent slashes
    $project_path = $doc_root;
    if (!empty($project_folder)) {
        $project_path .= '/' . $project_folder;
    }
    
    // Remove document root and possibly project folder from path
    if (strpos($path, $project_path) === 0) {
        // Path includes project folder, remove project path
        $path = substr($path, strlen($project_path));
    } else if (strpos($path, $doc_root) === 0) {
        // Path doesn't include project folder, remove doc root
        $path = substr($path, strlen($doc_root));
    }
    
    // Ensure path starts with a single slash
    $path = '/' . ltrim($path, '/');
    
    // Add project folder if needed and requested
    if ($include_project_folder && !empty($project_folder)) {
        // Only add project folder if it doesn't start with it already
        // and path isn't already a full URL
        if (strpos($path, '/' . $project_folder . '/') !== 0 && 
            !preg_match('#^https?://#', $path)) {
            $path = '/' . $project_folder . $path;
        }
    }
    
    return $path;
}

/**
 * Enhanced file upload function with detailed error reporting and cross-platform support
 * 
 * @param array $file File data from $_FILES
 * @param string $destination Destination directory
 * @param boolean $rename Whether to rename the file
 * @return array|boolean Array with result data or false on failure
 */
function enhanced_file_upload($file, $destination, $rename = true) {
    // Initialize error reporting
    $errors = [];
    $debug_info = [];
    
    // Validate file exists and has no errors
    if (!isset($file) || !is_array($file)) {
        $errors[] = 'No file data provided';
        return ['success' => false, 'errors' => $errors, 'debug' => $debug_info];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        $error_code = $file['error'];
        $error_message = isset($error_messages[$error_code]) ? 
            $error_messages[$error_code] : 'Unknown upload error';
        
        $errors[] = "Upload error ($error_code): $error_message";
        return ['success' => false, 'errors' => $errors, 'debug' => $debug_info];
    }
    
    // Environment detection
    $env = detect_environment();
    $debug_info['environment'] = $env;
    $debug_info['original_file'] = $file;
    
    // Normalize destination path
    $destination = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $destination), DIRECTORY_SEPARATOR);
    $full_destination = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim($destination, '/\\');
    $full_destination = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $full_destination);
    
    $debug_info['destination_path'] = $full_destination;
    
    // Create destination directory if it doesn't exist
    if (!is_dir($full_destination)) {
        $mkdir_result = create_directory_with_proper_permissions($full_destination);
        $debug_info['mkdir_result'] = $mkdir_result;
        
        if (!$mkdir_result) {
            $errors[] = "Failed to create destination directory: $full_destination";
            $errors[] = "PHP Error: " . error_get_last()['message'];
            return ['success' => false, 'errors' => $errors, 'debug' => $debug_info];
        }
    }
    
    // Determine filename
    $original_filename = basename($file['name']);
    $debug_info['original_filename'] = $original_filename;
    
    // Sanitize filename (remove special characters)
    $sanitized_filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $original_filename);
    $debug_info['sanitized_filename'] = $sanitized_filename;
    
    // Rename with timestamp if requested
    if ($rename) {
        $pathinfo = pathinfo($sanitized_filename);
        $new_filename = $pathinfo['filename'] . '_' . time() . '.' . $pathinfo['extension'];
        $debug_info['renamed_filename'] = $new_filename;
    } else {
        $new_filename = $sanitized_filename;
    }
    
    // Full path to destination file
    $dest_file = $full_destination . DIRECTORY_SEPARATOR . $new_filename;
    $debug_info['destination_file'] = $dest_file;
    
    // Move the uploaded file
    $move_result = move_uploaded_file($file['tmp_name'], $dest_file);
    $debug_info['move_result'] = $move_result;
    
    if (!$move_result) {
        $errors[] = "Failed to move uploaded file from {$file['tmp_name']} to {$dest_file}";
        $errors[] = "PHP Error: " . error_get_last()['message'];
        return ['success' => false, 'errors' => $errors, 'debug' => $debug_info];
    }
    
    // Set file permissions
    if ($env['os'] === 'Linux') {
        chmod($dest_file, 0644);
    }
    
    // Generate web path
    $web_path = '/' . $destination . '/' . $new_filename;
    $web_path = str_replace('\\', '/', $web_path);
    $web_path = preg_replace('#/+#', '/', $web_path);
    
    $debug_info['web_path'] = $web_path;
    
    // Success result
    return [
        'success' => true,
        'path' => $web_path,
        'filename' => $new_filename,
        'original_filename' => $original_filename,
        'file_size' => $file['size'],
        'file_type' => $file['type'],
        'debug' => $debug_info
    ];
}