<?php
$page_title = 'Home';
$page_description = 'St. Raphaela Mary School - Catholic education institution offering quality education from preschool through senior high school.';

include 'includes/header.php';

// Get slideshow images
$db = db_connect();
$slideshow_images = $db->fetch_all("SELECT * FROM slideshow WHERE is_active = TRUE ORDER BY display_order ASC");

// Get facilities
$facilities = $db->fetch_all("SELECT * FROM facilities ORDER BY display_order ASC");
?>

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
        <?php foreach($facilities as $facility): ?>
        <li>
            <img src="<?php echo SITE_URL . $facility['image']; ?>" alt="<?php echo $facility['name']; ?>">
            <h3><?php echo $facility['name']; ?></h3>
            <p><?php echo $facility['description']; ?></p>
        </li>
        <?php endforeach; ?>
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