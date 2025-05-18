<?php
$page_title = 'Home';
$page_description = 'St. Raphaela Mary School - Catholic education institution offering quality education from preschool through senior high school.';

include 'includes/header.php';

// Get slideshow images
$db = db_connect();
$slideshow_images = $db->fetch_all("SELECT * FROM slideshow WHERE is_active = TRUE ORDER BY display_order ASC");

// Get facilities
$facilities = $db->fetch_all("SELECT * FROM facilities ORDER BY display_order ASC");

// Define the hero background image path
$hero_background = '/assets/images/campus/hero-background.jpg';

/**
 * UPDATED: Get image URL function that leverages the existing robust functions.php methods
 * This is aligned with how the admin dashboard handles images
 */
function get_homepage_image_url($image_path) {
    if (empty($image_path)) return '';
    
    // Use the consistent normalize_image_path function from functions.php
    $normalized_path = normalize_image_path($image_path);
    
    // Try to verify if the image exists using the robust file verification function
    $image_exists = verify_image_exists($normalized_path);
    
    if ($image_exists) {
        // Use the consistently working get_correct_image_url function
        return get_correct_image_url($normalized_path);
    }
    
    // If image doesn't exist, try to find alternatives
    $alternative_path = find_best_matching_image($normalized_path);
    if ($alternative_path) {
        return get_correct_image_url($alternative_path);
    }
    
    // Last resort - construct URL based on SITE_URL + normalized path
    return SITE_URL . $normalized_path;
}

// Optional debug info - comment out when not needed
function debug_homepage_image_paths() {
    // Extract site folder name
    $site_folder = '';
    if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $site_folder = $matches[1];
    }
    
    echo '<div style="position:fixed; bottom:0; right:0; background:#fff; border:1px solid #ccc; padding:10px; z-index:9999; max-width:800px; max-height:500px; overflow:auto;">';
    echo '<h3>Path Debug Information</h3>';
    echo '<p>DOCUMENT_ROOT: ' . $_SERVER['DOCUMENT_ROOT'] . '</p>';
    echo '<p>SITE_URL: ' . SITE_URL . '</p>';
    echo '<p>Site Folder: ' . $site_folder . '</p>';
    echo '<p>OS: ' . (IS_WINDOWS ? 'Windows' : 'Linux') . '</p>';
    echo '<p>Server: ' . SERVER_TYPE . '</p>';
    echo '<p>DS: ' . DS . '</p>';
    
    // Get server paths
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    $project_folder = $site_folder;
    $site_path = $server_root . DIRECTORY_SEPARATOR . $project_folder;
    
    echo '<p>Full Project Path: ' . $site_path . '</p>';
    
    // Test actual paths to important files
    $test_paths = [
        '/assets/images/facilities/library.jpg',
        '/assets/images/facilities/gymnasium.jpg',
        '/assets/images/campus/hero-background.jpg'
    ];
    
    echo '<h4>Path Tests:</h4>';
    foreach ($test_paths as $path) {
        $norm_path = normalize_image_path($path);
        
        // Test multiple path variations
        $full_path_with_project = $server_root . DIRECTORY_SEPARATOR . $project_folder . str_replace('/', DIRECTORY_SEPARATOR, $norm_path);
        
        echo "<p>Path: $path<br>";
        echo "Full path: $full_path_with_project<br>";
        echo "Exists: " . (file_exists($full_path_with_project) ? 'Yes' : 'No') . "<br>";
        echo "Final URL: " . get_homepage_image_url($path) . "</p>";
    }
    
    echo '</div>';
}

// Uncomment to debug - comment back when not needed
// debug_homepage_image_paths();
?>

<!-- Custom inline style for the hero section background -->
<style>
    .enr {
        background-image: linear-gradient(rgba(6, 52, 150, 0.658), rgba(6, 52, 150, 0.658)), 
            url('<?php echo get_homepage_image_url($hero_background); ?>');
    }
</style>

<section class="enr">
    <h6>ST. RAPHAELA MARY SCHOOL</h6>
    <p>Welcome Raphaelians!</p>

    <div class="enroll-area">
        <div class="enroll-btn">
            <a href="admissions.php">Enroll Now!</a>
        </div>
    </div>
</section>

<section class="offer-box">
    <ul class="box-info">
        <li>
            <p>RECEIVE QUALITY EDUCATION & CHRISTIAN FORMATION</p>
        </li>
        <li>
            <p>ENJOY AIRCONDITIONED ROOMS</p>
        </li>
        <li>
            <p>EXPERIENCE A HAPPY & CARING COMMUNITY</p>
        </li>
    </ul>
</section>

<section class="slideshow">
    <div class="slideshow-container">
        <?php foreach($slideshow_images as $slide): ?>
        <div class="slide">
            <?php 
            // Use the improved image URL function
            $image_url = get_homepage_image_url($slide['image']);
            ?>
            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($slide['caption']); ?>">
        </div>
        <?php endforeach; ?>
        <button class="prev" onclick="changeSlide(-1)">&#10094;</button>
        <button class="next" onclick="changeSlide(1)">&#10095;</button>
    </div>
</section>

<section class="facilities">
    <div class="title-faci">
        <h1>OUR FACILITIES</h1>
        <p>Our school is equipped with a variety of modern facilities to enhance the learning experience.</p>
    </div>

    <ul class="faci-pics">
        <?php if (empty($facilities)): ?>
            <li>
                <img src="<?php echo SITE_URL; ?>/assets/images/facilities/library.jpg" alt="Library">
                <h3>LIBRARY</h3>
                <p>Our school library is a welcoming space designed to inspire lifelong learning.</p>
            </li>
            <li>
                <img src="<?php echo SITE_URL; ?>/assets/images/facilities/gymnasium.jpg" alt="Gymnasium">
                <h3>GYMNASIUM</h3>
                <p>Our gymnasium is dedicated to fostering a passion for sports and wellness in all of our students.</p>
            </li>
            <li>
                <img src="<?php echo SITE_URL; ?>/assets/images/facilities/canteen.jpg" alt="Canteen">
                <h3>CANTEEN</h3>
                <p>Our school canteen provides nutritious meals in a positive and healthy environment.</p>
            </li>
        <?php else: ?>
            <?php foreach($facilities as $facility): ?>
                <li>
                    <?php 
                    // Use the improved image URL function
                    $image_url = get_homepage_image_url($facility['image']);
                    
                    // Fallback to default image if necessary
                    if (empty($image_url)) {
                        $image_url = SITE_URL . '/assets/images/facilities/' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $facility['name'])) . '.jpg';
                    }
                    ?>
                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($facility['name']); ?>">
                    <h3><?php echo htmlspecialchars($facility['name']); ?></h3>
                    <p><?php echo htmlspecialchars($facility['description']); ?></p>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</section>

<section class="miss-vis">
    <div class="msvs-container">
        <div class="msvs-header">
            <span>MISSION</span>
        </div>
        <div class="msvs-text">
            <p><?php echo nl2br($school_info['mission']); ?></p>
        </div>

        <div class="msvs-header">
            <span>VISION</span>
        </div>
        <div class="msvs-text">
            <p><?php echo nl2br($school_info['vision']); ?></p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>