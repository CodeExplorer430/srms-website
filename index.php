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
?>

<!-- Custom inline style for the hero section background -->
<style>
    .enr {
        background-image: linear-gradient(rgba(6, 52, 150, 0.658), rgba(6, 52, 150, 0.658)), url('<?php echo SITE_URL . $hero_background; ?>');
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
            <img src="<?php echo SITE_URL . $slide['image']; ?>" alt="<?php echo $slide['caption']; ?>">
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
                    $image_path = SITE_URL . $facility['image'];
                    $default_image = SITE_URL . '/assets/images/facilities/' . strtolower($facility['name']) . '.jpg';
                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $facility['image'])): 
                    ?>
                        <img src="<?php echo $image_path; ?>" alt="<?php echo $facility['name']; ?>">
                    <?php else: ?>
                        <img src="<?php echo $default_image; ?>" alt="<?php echo $facility['name']; ?>">
                    <?php endif; ?>
                    <h3><?php echo $facility['name']; ?></h3>
                    <p><?php echo $facility['description']; ?></p>
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