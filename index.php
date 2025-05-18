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
 * Get the correct image URL ensuring it points to the srms-website directory
 * 
 * @param string $image_path Image path from database
 * @return string Corrected URL or empty string if not found
 */
function get_image_url($image_path) {
    if (empty($image_path)) return '';
    
    // Normalize path for consistency
    $path = normalize_image_path($image_path);
    
    // Extract site folder name from SITE_URL
    $site_folder = '';
    if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $site_folder = $matches[1]; // This should be "srms-website"
    }
    
    // EXPLICIT PATH: Check if the file exists in the site folder
    $site_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . $site_folder . $path;
    $exists_in_site = file_exists($site_path);
    
    // FALLBACK PATH: Check if it exists directly in document root
    $root_path = $_SERVER['DOCUMENT_ROOT'] . $path;
    $exists_in_root = file_exists($root_path);
    
    // Log results for debugging
    error_log("Image path check: $path");
    error_log("Site path ($site_path): " . ($exists_in_site ? "EXISTS" : "NOT FOUND"));
    error_log("Root path ($root_path): " . ($exists_in_root ? "EXISTS" : "NOT FOUND"));
    
    // Determine correct URL based on where file was found
    if ($exists_in_site) {
        // File is in site folder, use SITE_URL
        return SITE_URL . $path;
    } else if ($exists_in_root) {
        // File is at root, remove site folder from URL
        return str_replace('/' . $site_folder, '', SITE_URL) . $path;
    }
    
    // Image not found in either location
    return '';
}

// Debugging function - uncomment to display path information for troubleshooting
function debug_image_paths() {
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
    
    // Test actual path from database
    $db = db_connect();
    $sample = $db->fetch_row("SELECT image FROM facilities LIMIT 1");
    if ($sample) {
        $test_paths = [
            $sample['image'],
            '/assets/images/facilities/library-1746261509.jpg',
            'assets/images/facilities/gymnasium-1746261509.jpg',
            '/assets/images/campus/hero-background-1746261656.jpg'
        ];
        
        echo '<h4>Path Tests:</h4>';
        foreach ($test_paths as $path) {
            $norm_path = normalize_image_path($path);
            
            // Test multiple path variations
            $standard_path = $_SERVER['DOCUMENT_ROOT'] . $norm_path;
            $with_site_folder = $_SERVER['DOCUMENT_ROOT'] . '/' . $site_folder . $norm_path;
            
            echo "<p>Original: $path<br>";
            echo "Normalized: $norm_path<br>";
            echo "Standard Path: $standard_path - Exists: " . (file_exists($standard_path) ? 'Yes' : 'No') . "<br>";
            echo "With Site Folder: $with_site_folder - Exists: " . (file_exists($with_site_folder) ? 'Yes' : 'No') . "<br>";
            echo "verify_image_exists: " . (verify_image_exists($norm_path) ? 'Yes' : 'No') . "<br>";
            echo "Final URL: " . get_image_url($norm_path) . "</p>";
        }
    }
    
    echo '</div>';
}

// Uncomment this line when you need to debug
debug_image_paths();
?>

<!-- Custom inline style for the hero section background -->
<style>
    .enr {
        background-image: linear-gradient(rgba(6, 52, 150, 0.658), rgba(6, 52, 150, 0.658)), 
            url('<?php echo get_correct_image_url($hero_background); ?>');
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
            // Get proper image URL
            $image_url = get_correct_image_url($slide['image']);
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
                    // Get proper image URL
                    $image_url = get_correct_image_url($facility['image']);
                    $default_image = SITE_URL . '/assets/images/facilities/' . strtolower($facility['name']) . '.jpg';
                    ?>
                    <img src="<?php echo !empty($image_url) ? $image_url : $default_image; ?>" 
                         alt="<?php echo htmlspecialchars($facility['name']); ?>">
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