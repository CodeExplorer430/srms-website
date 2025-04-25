<?php
$root_dir = dirname($_SERVER['SCRIPT_FILENAME']);
$root_path = '';

if (strpos($root_dir, 'academics') !== false) {
    $root_path = '../';
}

require_once $root_path . 'includes/config.php';
require_once $root_path . 'includes/functions.php';

$school_info = get_school_info();
$active_page = get_active_page();
$navigation = get_navigation_menu();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'St. Raphaela Mary School - Catholic education institution offering quality education from preschool through senior high school.'; ?>">
    <title><?php echo isset($page_title) ? $page_title . ' | St. Raphaela Mary School' : 'St. Raphaela Mary School'; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js" defer></script>
</head>
<body>
    <header>
        <a href="<?php echo SITE_URL; ?>/index.php" class="logo">
            <img src="<?php echo SITE_URL; ?>/assets/images/branding/logo-primary.png" alt="St. Raphaela Mary School Logo"> 
        </a>
        <ul class="menu-link">
            <?php foreach ($navigation as $item): ?>
            <li>
                <a href="<?php echo SITE_URL . '/' . $item['url']; ?>" class="sub-menu-link"><?php echo $item['name']; ?></a>
                <?php if (!empty($item['children'])): ?>
                <ul class="drop-down">
                    <?php foreach ($item['children'] as $child): ?>
                    <li>
                        <?php if ($child['url'] != '#'): ?>
                        <a href="<?php echo SITE_URL . '/' . $child['url']; ?>"><?php echo $child['name']; ?></a>
                        <?php else: ?>
                        <?php echo $child['name']; ?>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </header>