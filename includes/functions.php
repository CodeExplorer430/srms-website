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
 * Normalize image path for cross-platform compatibility
 */
function normalize_image_path($path) {
    if (empty($path)) return '';
    
    // If it's a URL, leave it unchanged
    if (filter_var($path, FILTER_VALIDATE_URL)) return $path;
    
    // Ensure path starts with slash
    $path = '/' . ltrim($path, '/');
    
    // Convert backslashes to forward slashes (for Windows paths)
    $path = str_replace('\\', '/', $path);
    
    // Clean up double slashes
    return preg_replace('#/+#', '/', $path);
}


/**
 * Enhanced file existence check for cross-platform compatibility
 */
function verify_image_exists($image_path) {
    if (empty($image_path)) return false;
    
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    $image_path = normalize_image_path($image_path);
    
    // Try multiple path variations
    $paths_to_check = [
        $server_root . $image_path,
        $server_root . DIRECTORY_SEPARATOR . ltrim($image_path, '/'),
        rtrim($server_root, '/\\') . $image_path
    ];
    
    // Log paths for debugging
    error_log("Checking image existence in paths: " . implode(', ', $paths_to_check));
    
    foreach ($paths_to_check as $path) {
        if (file_exists($path)) {
            error_log("Image found at: {$path}");
            return true;
        }
    }
    
    // Additional check: Try to find case-insensitive match (for Linux)
    $dir = dirname($server_root . $image_path);
    $filename = basename($image_path);
    
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if (strcasecmp($file, $filename) === 0) {
                error_log("Image found with case-insensitive match: {$dir}/{$file}");
                return true;
            }
        }
    }
    
    // If we get here, the file doesn't exist in any of the tried paths
    error_log("Image not found: {$image_path}");
    return false;
}

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
        
        // On Linux, we might need to set group permissions separately
        if (!IS_WINDOWS) {
            // Get the default Apache group
            $group = posix_getgrgid(posix_getgid())['name'] ?? 'www-data';
            
            // Try to set group permissions (don't fail if this doesn't work)
            @chgrp($dir, $group);
            @chmod($dir, 0775); // rwxrwxr-x
        }
        
        return true;
    }
    
    return true; // Directory already exists
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
    
    // Normalize target directory path - ALWAYS use forward slashes for web paths
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $category . '/';
    $target_dir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $target_dir);
    
    // Create target directory if it doesn't exist
    if (!is_dir($target_dir)) {
        error_log("Creating directory: {$target_dir}");
        if (!mkdir($target_dir, 0755, true)) {
            error_log("Failed to create directory: {$target_dir}");
            return false;
        }
    }
    
    // Generate unique filename
    $filename = pathinfo($file['name'], PATHINFO_FILENAME);
    // Sanitize filename - remove spaces and special characters
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
    // If filename is empty after sanitization, use a default name
    if (empty($filename)) {
        $filename = 'image';
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_name = $filename . '-' . time() . '.' . $extension;
    
    // Set target path using system-specific directory separator
    $target_path = $target_dir . $unique_name;
    
    // ALWAYS use forward slashes for web paths, regardless of OS
    $relative_path = '/assets/images/' . $category . '/' . $unique_name;
    $relative_path = str_replace('\\', '/', $relative_path);
    
    // Log paths for debugging
    error_log("Upload target path: {$target_path}");
    error_log("Upload relative path: {$relative_path}");
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Fix permissions
        chmod($target_path, 0644);
        
        // Verify file exists after upload
        if (!file_exists($target_path)) {
            error_log("CRITICAL: File was moved but doesn't exist at: {$target_path}");
            return false;
        }
        
        error_log("File uploaded successfully to {$target_path}");
        return $relative_path;
    } else {
        $error = error_get_last();
        error_log("Failed to move uploaded file to {$target_path}: " . ($error ? $error['message'] : 'Unknown error'));
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


/**
 * Cross-platform file handling utilities
 * Ensures consistent behavior across Windows (WAMP/XAMPP) and Linux (LAMP/XAMPP) environments
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
 * Convert a filesystem path to a web-friendly URL path
 * 
 * @param string $path File system path
 * @return string Web URL path
 */
function filesystem_path_to_url($path) {
    // Replace backslashes with forward slashes
    $path = str_replace('\\', '/', $path);
    
    // Remove any double slashes
    $path = preg_replace('#/+#', '/', $path);
    
    // Remove document root from path to get web path
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    if (strpos($path, $doc_root) === 0) {
        $path = substr($path, strlen($doc_root));
    }
    
    // Ensure path starts with a single slash
    $path = '/' . ltrim($path, '/');
    
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