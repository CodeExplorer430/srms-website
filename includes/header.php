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
            <li>
                <a href="<?php echo SITE_URL; ?>/index.php" class="sub-menu-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">HOME</a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/admissions.php" class="sub-menu-link <?php echo ($current_page === 'admissions.php') ? 'active' : ''; ?>">ADMISSIONS</a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/about.php" class="sub-menu-link <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>">ABOUT SRMS</a>
                <ul class="drop-down">
                    <li><a href="<?php echo SITE_URL; ?>/alumni.php">ALUMNI</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/faculty.php">FACULTY</a></li>
                </ul>
            </li>
            <li>
                <a href="#" class="sub-menu-link <?php echo (strpos($current_url, 'academics/') !== false) ? 'active' : ''; ?>">ACADEMICS</a>
                <ul class="drop-down">
                    <?php 
                    // Get academic levels from database
                    $levels = $db->fetch_all("SELECT * FROM academic_levels ORDER BY display_order ASC");
                    foreach ($levels as $level):
                        $active_class = ($academic_level === $level['slug']) ? 'active' : '';
                    ?>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/academics/<?php echo $level['slug']; ?>.php">
                            <?php echo strtoupper($level['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/news.php" class="sub-menu-link <?php echo ($current_page === 'news.php' || $current_page === 'news-detail.php') ? 'active' : ''; ?>">NEWS</a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="sub-menu-link <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>">CONTACT</a>
            </li>
        </ul>
    </header>