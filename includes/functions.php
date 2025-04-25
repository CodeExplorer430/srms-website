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
?>