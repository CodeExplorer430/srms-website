<?php
/**
 * Header Include File
 * Contains common header elements for all pages
 */

// Start session (if needed for future use)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include common files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Connect to database
$db = new Database();

// Get general school information
$school_info = $db->fetch_row("SELECT * FROM school_information LIMIT 1");

// Get active status for nav items
$current_page = basename($_SERVER['PHP_SELF']);
$current_url = $_SERVER['REQUEST_URI'];

// Extract academic level if in academics section
$academic_level = '';
if (strpos($current_url, 'academics/') !== false) {
    $parts = explode('/', trim($current_url, '/'));
    $academic_level = end($parts);
    $academic_level = str_replace('.php', '', $academic_level);
}

// Retrieve navigation menu items from database
try {
    $navigation = [];
    $main_items = $db->fetch_all("SELECT * FROM navigation WHERE parent_id IS NULL AND is_active = 1 ORDER BY display_order ASC");
    
    foreach ($main_items as &$item) {
        $item['children'] = $db->fetch_all("SELECT * FROM navigation WHERE parent_id = {$item['id']} AND is_active = 1 ORDER BY display_order ASC");
    }
    
    $navigation = $main_items;
} catch (Exception $e) {
    // If database query fails, fall back to static menu items
    error_log("Navigation error: " . $e->getMessage());
    
    $navigation = [
        ['id' => 1, 'name' => 'HOME', 'url' => '/index.php', 'children' => []],
        ['id' => 2, 'name' => 'ADMISSIONS', 'url' => '/admissions.php', 'children' => []],
        ['id' => 3, 'name' => 'ABOUT SRMS', 'url' => '/about.php', 'children' => [
            ['name' => 'ALUMNI', 'url' => '/alumni.php'],
            ['name' => 'FACULTY', 'url' => '/faculty.php']
        ]],
        ['id' => 4, 'name' => 'ACADEMICS', 'url' => '#', 'children' => [
            ['name' => 'PRESCHOOL', 'url' => '#'],
            ['name' => 'ELEMENTARY', 'url' => '#'],
            ['name' => 'JUNIOR HIGH', 'url' => '#'],
            ['name' => 'SENIOR HIGH', 'url' => '/academics/senior-high.php']
        ]],
        ['id' => 5, 'name' => 'NEWS', 'url' => '/news.php', 'children' => []],
        ['id' => 6, 'name' => 'CONTACT', 'url' => '/contact.php', 'children' => []]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'St. Raphaela Mary School - Catholic education institution offering quality education from preschool through senior high school.'; ?>">
    <title><?php echo isset($page_title) ? 'St. Raphaela Mary School | ' . $page_title : 'St. Raphaela Mary School'; ?></title>
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Add page-specific CSS if any -->
    <?php if (isset($page_specific_css) && is_array($page_specific_css)): ?>
        <?php foreach ($page_specific_css as $css): ?>
            <link rel="stylesheet" href="<?php echo SITE_URL . $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js" defer></script>
</head>
<body>
    <header>
        <a href="<?php echo SITE_URL; ?>/index.php" class="logo">
            <img src="<?php echo (!empty($school_info['logo'])) ? $school_info['logo'] : SITE_URL . '/assets/images/branding/logo-primary.png'; ?>" alt="<?php echo htmlspecialchars($school_info['name'] ?? 'St. Raphaela Mary School'); ?> Logo">
        </a>
        
        <ul class="menu-link">
            <?php foreach ($navigation as $item): ?>
                <?php 
                    // Check if current page matches this menu item
                    $is_active = ($current_page === basename($item['url'])) || 
                                 (strpos($current_url, dirname($item['url'])) !== false && basename($item['url']) === '#');
                ?>
                <li>
                    <a href="<?php echo SITE_URL . $item['url']; ?>" class="sub-menu-link <?php echo $is_active ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </a>
                    
                    <?php if (!empty($item['children'])): ?>
                        <ul class="drop-down">
                            <?php foreach ($item['children'] as $child): ?>
                                <?php 
                                    // Special handling for academic items to detect active state
                                    $child_is_active = (strpos($item['name'], 'ACADEMICS') !== false && 
                                                       basename($child['url'], '.php') === $academic_level);
                                ?>
                                <li>
                                    <?php if ($child['url'] && $child['url'] !== '#'): ?>
                                        <a href="<?php echo SITE_URL . $child['url']; ?>" <?php echo $child_is_active ? 'class="active"' : ''; ?>>
                                            <?php echo htmlspecialchars($child['name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($child['name']); ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </header>