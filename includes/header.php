<?php
/**
 * Header Include File
 * Contains common header elements for all pages
 */

// Enable debugging (you can comment this out after fixing the issue)
define('DEBUG_MODE', true);

// Start session (if needed for future use)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Improved path resolution
$script_path = $_SERVER['SCRIPT_FILENAME'];
$is_in_subdirectory = strpos($script_path, 'academics') !== false || 
                      strpos($script_path, 'admin') !== false;

// Define the root path based on whether we're in a subdirectory
$root_path = $is_in_subdirectory ? dirname(dirname($script_path)) : dirname($script_path);

// Include common files with absolute paths
require_once $root_path . '/includes/config.php';
require_once $root_path . '/includes/db.php';
require_once $root_path . '/includes/functions.php';

// Connect to database
$db = new Database();

// Get general school information
$school_info = $db->fetch_row("SELECT * FROM school_information LIMIT 1");

// Set the logo URL using the get_header_image_url function
$logo_url = get_header_image_url($school_info['logo'] ?? '');

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

// DIRECT NAVIGATION QUERY
// This ensures we get exactly the navigation items we need, in the correct order
$navigation = [];

// Get HOME
$home = $db->fetch_row("SELECT * FROM navigation WHERE id = 1 AND is_active = 1");
if ($home) {
    $home['children'] = $db->fetch_all("SELECT * FROM navigation WHERE parent_id = 1 AND is_active = 1 ORDER BY display_order ASC");
    $navigation[] = $home;
}

// Get ADMISSIONS
$admissions = $db->fetch_row("SELECT * FROM navigation WHERE id = 2 AND is_active = 1");
if ($admissions) {
    $admissions['children'] = $db->fetch_all("SELECT * FROM navigation WHERE parent_id = 2 AND is_active = 1 ORDER BY display_order ASC");
    $navigation[] = $admissions;
}

// Get ABOUT SRMS
$about = $db->fetch_row("SELECT * FROM navigation WHERE id = 3 AND is_active = 1");
if ($about) {
    $about['children'] = $db->fetch_all("SELECT * FROM navigation WHERE parent_id = 3 AND is_active = 1 ORDER BY display_order ASC");
    $navigation[] = $about;
}

// Get ACADEMICS
$academics = $db->fetch_row("SELECT * FROM navigation WHERE id = 4 AND is_active = 1");
if ($academics) {
    $academics['children'] = $db->fetch_all("SELECT * FROM navigation WHERE parent_id = 4 AND is_active = 1 ORDER BY display_order ASC");
    $navigation[] = $academics;
}

// Get NEWS
$news = $db->fetch_row("SELECT * FROM navigation WHERE id = 5 AND is_active = 1");
if ($news) {
    $news['children'] = $db->fetch_all("SELECT * FROM navigation WHERE parent_id = 5 AND is_active = 1 ORDER BY display_order ASC");
    $navigation[] = $news;
}

// Get CONTACT
$contact = $db->fetch_row("SELECT * FROM navigation WHERE id = 6 AND is_active = 1");
if ($contact) {
    $contact['children'] = $db->fetch_all("SELECT * FROM navigation WHERE parent_id = 6 AND is_active = 1 ORDER BY display_order ASC");
    $navigation[] = $contact;
} else {
    // Force a CONTACT item if it doesn't exist in the database
    $navigation[] = [
        'id' => 6,
        'name' => 'CONTACT',
        'url' => '/contact.php',
        'parent_id' => null,
        'display_order' => 6,
        'is_active' => 1,
        'children' => []
    ];
}

// If everything failed, use this fallback
if (empty($navigation)) {
    $navigation = [
        ['id' => 1, 'name' => 'HOME', 'url' => '/index.php', 'children' => [], 'display_order' => 1],
        ['id' => 2, 'name' => 'ADMISSIONS', 'url' => '/admissions.php', 'children' => [], 'display_order' => 2],
        ['id' => 3, 'name' => 'ABOUT SRMS', 'url' => '/about.php', 'children' => [
            ['name' => 'ALUMNI', 'url' => '/alumni.php'],
            ['name' => 'FACULTY', 'url' => '/faculty.php']
        ], 'display_order' => 3],
        ['id' => 4, 'name' => 'ACADEMICS', 'url' => '#', 'children' => [
            ['name' => 'PRESCHOOL', 'url' => '#'],
            ['name' => 'ELEMENTARY', 'url' => '#'],
            ['name' => 'JUNIOR HIGH', 'url' => '#'],
            ['name' => 'SENIOR HIGH', 'url' => '/academics/senior-high.php']
        ], 'display_order' => 4],
        ['id' => 5, 'name' => 'NEWS', 'url' => '/news.php', 'children' => [], 'display_order' => 5],
        ['id' => 6, 'name' => 'CONTACT', 'url' => '/contact.php', 'children' => [], 'display_order' => 6]
    ];
}

// Function to ensure proper image paths in header
function get_header_image_url($path) {
    if (empty($path)) {
        return SITE_URL . '/assets/images/branding/logo-primary.png';
    }
    
    // Normalize the path
    $normalized_path = normalize_image_path($path);
    
    // Get the correct URL with our path resolution function
    return get_correct_image_url($normalized_path);
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
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>/assets/images/branding/favicon.ico" type="image/x-icon">
</head>
<body>
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
    <!-- NAVIGATION DEBUG INFORMATION -->
    <div style="display: none;">
        <h3>Navigation Debug Info:</h3>
        <p>Total Items: <?php echo count($navigation); ?></p>
        <ul>
            <?php foreach ($navigation as $index => $item): ?>
                <li>
                    Item #<?php echo $index; ?>: 
                    ID: <?php echo $item['id']; ?>,
                    Name: <?php echo $item['name']; ?>,
                    URL: <?php echo $item['url']; ?>,
                    Order: <?php echo $item['display_order']; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <header>
        <a href="<?php echo SITE_URL; ?>/index.php" class="logo">
            <img src="<?php echo $logo_url; ?>" alt="<?php echo htmlspecialchars($school_info['name'] ?? 'St. Raphaela Mary School'); ?> Logo" 
                onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/branding/logo-primary.png'; console.log('Logo fallback used');">
        </a>
        
        <ul class="menu-link">
            <?php if (empty($navigation)): ?>
                <!-- Fallback navigation if database query fails -->
                <li><a href="<?php echo SITE_URL; ?>/index.php" class="sub-menu-link">HOME</a></li>
                <li><a href="<?php echo SITE_URL; ?>/admissions.php" class="sub-menu-link">ADMISSIONS</a></li>
                <li><a href="<?php echo SITE_URL; ?>/about.php" class="sub-menu-link">ABOUT SRMS</a></li>
                <li><a href="<?php echo SITE_URL; ?>/academics/senior-high.php" class="sub-menu-link">ACADEMICS</a></li>
                <li><a href="<?php echo SITE_URL; ?>/news.php" class="sub-menu-link">NEWS</a></li>
                <li><a href="<?php echo SITE_URL; ?>/contact.php" class="sub-menu-link">CONTACT</a></li>
            <?php else: ?>                
                <?php foreach ($navigation as $index => $item): ?>
                    <?php 
                    // Check if current page matches this menu item
                    $is_active = ($current_page === basename($item['url'])) || 
                                (strpos($current_url, dirname($item['url'])) !== false && basename($item['url']) === '#');
                    
                    // Add debug comment
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        echo "<!-- Nav Item #{$index}: {$item['name']} (ID: {$item['id']}, Order: {$item['display_order']}) -->";
                    }
                    ?>
                    <li>
                        <a href="<?php echo SITE_URL . $item['url']; ?>" class="sub-menu-link <?php echo $is_active ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                        
                        <?php if (!empty($item['children'])): ?>
                            <ul class="drop-down">
                                <?php foreach ($item['children'] as $child_index => $child): ?>
                                    <?php 
                                        // Debug comment for child item
                                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                                            echo "<!-- Child #{$child_index}: {$child['name']} (ID: {$child['id']}, Parent: {$item['id']}) -->";
                                        }
                                        
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
            <?php endif; ?>
        </ul>
        
        <!-- Mobile Menu Toggle Button -->
        <div class="mobile-menu-toggle">
            <i class='bx bx-menu'></i>
        </div>
    </header>
    
    <!-- Add mobile menu JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const menuLinks = document.querySelector('.menu-link');
        
        if (mobileMenuToggle && menuLinks) {
            mobileMenuToggle.addEventListener('click', function() {
                menuLinks.classList.toggle('active');
                this.classList.toggle('active');
            });
        }
    });
    </script>