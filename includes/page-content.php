<?php
/**
 * Page Content Utility Functions
 * Functions for retrieving and displaying CMS-managed page content
 */

/**
 * Get a page content from the database
 * 
 * @param string $page_key - The unique key for the page
 * @return array|null - The page data or null if not found
 */
function get_page_content($page_key) {
    global $db;
    
    if (!$db) {
        $db = new Database();
    }
    
    $page_key = $db->escape($page_key);
    $page = $db->fetch_row("SELECT * FROM page_content WHERE page_key = '$page_key'");
    
    // If page exists, also get its sections
    if ($page) {
        $page_id = $page['id'];
        $sections = $db->fetch_all("SELECT * FROM page_sections WHERE page_id = $page_id ORDER BY display_order ASC");
        $page['sections'] = $sections;
        
        // Organize sections by key for easier access
        $page['sections_by_key'] = [];
        foreach ($sections as $section) {
            $page['sections_by_key'][$section['section_key']] = $section;
        }
    }
    
    return $page;
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
    
    echo nl2br($section['content']);
}

/**
 * Get all academic levels
 * 
 * @return array - Array of academic levels
 */
function get_academic_levels() {
    global $db;
    
    if (!$db) {
        $db = new Database();
    }
    
    return $db->fetch_all("SELECT * FROM academic_levels ORDER BY display_order ASC");
}

/**
 * Get programs for a specific academic level
 * 
 * @param int $level_id - The ID of the academic level
 * @return array - Array of programs
 */
function get_programs_by_level($level_id) {
    global $db;
    
    if (!$db) {
        $db = new Database();
    }
    
    $level_id = (int)$level_id;
    return $db->fetch_all("SELECT * FROM academic_programs WHERE level_id = $level_id");
}

/**
 * Get tracks for a specific program
 * 
 * @param int $program_id - The ID of the academic program
 * @return array - Array of tracks
 */
function get_tracks_by_program($program_id) {
    global $db;
    
    if (!$db) {
        $db = new Database();
    }
    
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
    global $db;
    
    if (!$db) {
        $db = new Database();
    }
    
    $slug = $db->escape($slug);
    $level = $db->fetch_row("SELECT * FROM academic_levels WHERE slug = '$slug'");
    
    if ($level) {
        // Get programs for this level
        $level_id = $level['id'];
        $programs = $db->fetch_all("SELECT * FROM academic_programs WHERE level_id = $level_id");
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
 * Get faculty categories
 * 
 * @return array - Array of faculty categories
 */
function get_faculty_categories() {
    global $db;
    
    if (!$db) {
        $db = new Database();
    }
    
    $categories = $db->fetch_all("SELECT * FROM faculty_categories ORDER BY display_order ASC");
    
    // For each category, get its members
    foreach ($categories as $key => $category) {
        $category_id = $category['id'];
        $members = $db->fetch_all("SELECT * FROM faculty WHERE category_id = $category_id ORDER BY display_order ASC");
        $categories[$key]['members'] = $members;
    }
    
    return $categories;
}