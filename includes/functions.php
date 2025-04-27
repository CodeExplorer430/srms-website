<?php
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

// Format date for display
function format_date($db_date, $format = 'F j, Y') {
    $timestamp = strtotime($db_date);
    return date($format, $timestamp);
}

// Helper function for formatting requirements as lists
function format_requirements_list($requirements) {
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
 * Check if file exists, allowing for some flexibility in path formats
 * and case-sensitive filesystems.
 *
 * Tries the following:
 * 1. Direct path
 * 2. With and without leading slash
 * 3. Different case variations (useful on case-sensitive filesystems)
 *
 * @param string $image_path Path to check
 * @return bool True if file exists
 */
function file_exists_with_alternatives($image_path) {
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    
    // Try direct path
    if (file_exists($server_root . $image_path)) {
        return true;
    }
    
    // Try with and without leading slash
    $alt_path = $server_root . DIRECTORY_SEPARATOR . ltrim($image_path, '/');
    if (file_exists($alt_path)) {
        return true;
    }
    
    // Try different case variations (useful on case-sensitive filesystems)
    $basename = basename($image_path);
    $dirname = dirname($image_path);
    
    if (is_dir($server_root . $dirname)) {
        $files = scandir($server_root . $dirname);
        foreach ($files as $file) {
            if (strtolower($file) === strtolower($basename)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Normalize image paths for consistent handling
 */
function normalize_image_path($path) {
    if (empty($path)) return '';
    
    // If it's a URL, leave it unchanged
    if (filter_var($path, FILTER_VALIDATE_URL)) return $path;
    
    // Ensure path starts with slash
    $path = '/' . ltrim($path, '/');
    
    // Convert legacy paths to new structure
    if (strpos($path, '/images/School_Events') !== false) {
        $basename = basename($path);
        $event_number = preg_replace('/[^0-9]/', '', $basename);
        $ext = pathinfo($basename, PATHINFO_EXTENSION);
        $path = '/assets/images/events/event-' . $event_number . '.' . $ext;
    } else if (strpos($path, '/images/School_Announcement') !== false) {
        $basename = basename($path);
        $announcement_number = preg_replace('/[^0-9]/', '', $basename);
        $ext = pathinfo($basename, PATHINFO_EXTENSION);
        $path = '/assets/images/news/announcement-' . $announcement_number . '.' . $ext;
    }
    
    // Clean up double slashes
    return preg_replace('#/+#', '/', $path);
}

/**
 * Enhanced file existence check with detailed logging
 */
function verify_image_exists($image_path) {
    if (empty($image_path)) return false;
    
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    $paths_to_check = [
        $server_root . $image_path,
        $server_root . DIRECTORY_SEPARATOR . ltrim($image_path, '/'),
        // Try without 'assets' folder (legacy structure)
        $server_root . str_replace('/assets/', '/', $image_path),
        // Try with 'assets' folder (new structure)
        $server_root . str_replace('/images/', '/assets/images/', $image_path)
    ];
    
    foreach ($paths_to_check as $path) {
        if (file_exists($path)) {
            return $path; // Return the actual path that exists
        }
    }
    
    // Log the issue
    error_log("Image not found: {$image_path}. Tried paths: " . implode(', ', $paths_to_check));
    return false;
}


/**
 * Upload image to specified category directory
 * 
 * @param array $file The $_FILES array element for the uploaded file
 * @param string $category The category directory to store the file in
 * @return string|false Path to the uploaded file on success, false on failure
 */
function upload_image($file, $category = 'news') {
    // Log upload attempt for debugging
    error_log("Upload attempt for category: {$category}, file type: {$file['type']}, size: {$file['size']}");

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
    
    // Create target directory if it doesn't exist
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $category . '/';
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            error_log("Failed to create directory: {$target_dir}");
            return false;
        }
    }
    
    // Generate unique filename
    $filename = pathinfo($file['name'], PATHINFO_FILENAME);
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_name = $filename . '-' . time() . '.' . $extension;
    
    // Set target path
    $target_path = $target_dir . $unique_name;
    $relative_path = '/assets/images/' . $category . '/' . $unique_name;
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        error_log("File uploaded successfully to {$target_path}");
        return $relative_path;
    } else {
        error_log("Failed to move uploaded file to {$target_path}");
        return false;
    }
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
        error_log("Directory not found: " . $dir_path);
        return false;
    }
    
    // Get all files in the directory
    $files = scandir($dir_path);
    if (!$files) {
        error_log("Failed to scan directory: " . $dir_path);
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
    
    error_log("No matching image found for: " . $image_path);
    return false;
}