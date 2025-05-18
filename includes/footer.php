<?php
/**
 * Footer Include File
 * Contains common footer elements for all pages
 */

// Get contact info from database if not already loaded
if (!isset($school_info) || empty($school_info)) {
    $school_info = $db->fetch_row("SELECT * FROM school_information LIMIT 1");
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
                <p><b>Contact #:</b> <?php echo htmlspecialchars($school_info['phone'] ?? '8253-3801/0920 832 7705'); ?></p>
                <p><b>Email:</b> <?php echo htmlspecialchars($school_info['email'] ?? 'srmseduc@gmail.com'); ?></p>
                <p><b>Address:</b> <?php echo htmlspecialchars($school_info['address'] ?? '#63 Road 7 GSIS Hills Subdivision, Talipapa, Caloocan City'); ?></p>
            </li>
            <li class="app">
                <h5>Platform</h5>
                <a href="https://web.facebook.com/srms.page">
                    <i class='bx bxl-facebook-circle'></i>
                </a>
                <a href="#">
                    <i class='bx bxl-instagram-alt'></i>
                </a>
                <a href="#">
                    <i class='bx bxl-twitter'></i>
                </a>
                <a href="#">
                    <i class='bx bxl-linkedin-square'></i>
                </a>
            </li>
        </ul>
        
        <div class="copyrighted">
            <div class="copyright">
                <p>© Copyright <?php echo date('Y'); ?> <?php echo htmlspecialchars($school_info['name'] ?? 'St. Raphaela Mary School'); ?>. All Rights Reserved</p>
            </div>
        </div>
    </footer>

    <!-- Add page-specific JS if any -->
    <?php if (isset($page_specific_js) && is_array($page_specific_js)): ?>
        <?php foreach ($page_specific_js as $js): ?>
            <script src="<?php echo SITE_URL . $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>