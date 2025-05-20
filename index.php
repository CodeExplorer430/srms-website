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

// Get featured news articles
$featured_news = $db->fetch_all("SELECT * FROM news WHERE status = 'published' AND featured = 1 ORDER BY published_date DESC LIMIT 3");

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

<!-- Custom inline style for featured news section -->
<style>
/* Featured News Section */
.featured-news {
    padding: 60px 0;
    background-color: #f5f7fa;
}

.title-section {
    text-align: center;
    margin-bottom: 40px;
}

.title-section h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 10px;
}

.featured-news-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

.featured-card {
    flex: 1;
    min-width: 300px;
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.featured-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.featured-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.featured-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.featured-card:hover .featured-image img {
    transform: scale(1.05);
}

.featured-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background-color: var(--primary);
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.featured-badge i {
    margin-right: 5px;
}

.featured-content {
    padding: 20px;
}

.featured-date {
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.featured-date i {
    margin-right: 5px;
}

.featured-content h3 {
    margin-bottom: 10px;
    font-size: 18px;
    font-weight: 600;
    color: var(--primary);
}

.featured-content p {
    color: #6c757d;
    margin-bottom: 15px;
    line-height: 1.5;
}

.read-more {
    display: inline-flex;
    align-items: center;
    color: var(--blue);
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s;
}

.read-more:hover {
    color: var(--primary);
}

.read-more i {
    margin-left: 5px;
    transition: transform 0.3s;
}

.read-more:hover i {
    transform: translateX(3px);
}

.view-all {
    text-align: center;
    margin-top: 40px;
}

.btn-view-all {
    display: inline-block;
    background-color: var(--primary);
    color: white;
    padding: 10px 25px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.3s;
}

.btn-view-all:hover {
    background-color: var(--dark-blue);
}

@media (max-width: 768px) {
    .featured-news-container {
        flex-direction: column;
    }
    
    .featured-card {
        width: 100%;
    }
}
</style>

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

<?php if (!empty($featured_news)): ?>
<section class="featured-news">
    <div class="title-section">
        <h1>FEATURED NEWS</h1>
        <p>Stay updated with our latest announcements and events</p>
    </div>

    <div class="featured-news-container">
        <?php foreach($featured_news as $article): ?>
            <div class="featured-card">
                <?php 
                // Use the improved image URL function
                $image_url = get_homepage_image_url($article['image']);
                ?>
                <div class="featured-image">
                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                    <div class="featured-badge">
                        <i class='bx bx-star'></i> Featured
                    </div>
                </div>
                <div class="featured-content">
                    <div class="featured-date">
                        <i class='bx bx-calendar'></i> <?php echo date('F j, Y', strtotime($article['published_date'])); ?>
                    </div>
                    <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                    <p>
                        <?php 
                        if (!empty($article['summary'])) {
                            echo htmlspecialchars(substr($article['summary'], 0, 120)) . (strlen($article['summary']) > 120 ? '...' : '');
                        } else {
                            echo htmlspecialchars(substr(strip_tags($article['content']), 0, 120)) . '...';
                        }
                        ?>
                    </p>
                    <a href="news-detail.php?id=<?php echo $article['id']; ?>" class="read-more">Read More <i class='bx bx-right-arrow-alt'></i></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="view-all">
        <a href="news.php" class="btn-view-all">View All News</a>
    </div>
</section>
<?php endif; ?>

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