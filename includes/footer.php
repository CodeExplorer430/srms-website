<?php
// Get social media links
$db = db_connect();
try {
    $social_media = $db->fetch_all("SELECT * FROM social_media WHERE is_active = TRUE ORDER BY display_order ASC");
} catch (Exception $e) {
    // Default social media if database query fails
    $social_media = [
        ['platform' => 'Facebook', 'url' => 'https://web.facebook.com/srms.page', 'icon' => 'bx bxl-facebook-circle'],
        ['platform' => 'Instagram', 'url' => '#', 'icon' => 'bx bxl-instagram-alt'],
        ['platform' => 'Twitter', 'url' => '#', 'icon' => 'bx bxl-twitter'],
        ['platform' => 'LinkedIn', 'url' => '#', 'icon' => 'bx bxl-linkedin-square']
    ];
}
?>
    <footer>
        <ul class="information">
            <li class="footer-link">
                <h5>Links</h5>
                <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
                <a href="<?php echo SITE_URL; ?>/about.php">About School</a>
                <a href="<?php echo SITE_URL; ?>/admissions.php">Admissions</a>
                <a href="<?php echo SITE_URL; ?>/contact.php">Contact Us</a>
            </li>
            <li>
                <h5>Contact us</h5>
                <p><b>Contact #:</b> <?php echo $school_info['phone']; ?></p>
                <p><b>Email:</b> <?php echo $school_info['email']; ?></p>
                <p><b>Address:</b> <?php echo $school_info['address']; ?></p>
            </li>
            <li class="app">
                <h5>Platform</h5>
                <?php foreach ($social_media as $platform): ?>
                <a href="<?php echo $platform['url']; ?>">
                    <i class='<?php echo $platform['icon']; ?>'></i>
                </a>
                <?php endforeach; ?>
            </li>
        </ul>

        <div class="copyrighted">
            <div class="copyright">
                <p>© Copyright <?php echo date('Y'); ?> St. Raphaela Mary School. All Rights Reserved</p>
            </div>
        </div>
    </footer>
</body>
</html>