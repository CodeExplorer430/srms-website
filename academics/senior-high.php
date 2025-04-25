<?php
$page_title = 'Senior High School';
$page_description = 'Explore the Senior High School program at St. Raphaela Mary School, featuring ABM, HUMSS, and GAS academic tracks.';

$root_path = dirname(__DIR__);
require_once $root_path . '/includes/config.php';
require_once $root_path . '/includes/functions.php';
include $root_path . '/includes/header.php';

// Fetch senior high program details
$db = db_connect();

// Get the senior high level ID first
$senior_high_level = $db->fetch_row("SELECT id FROM academic_levels WHERE slug = 'senior-high' LIMIT 1");
$level_id = $senior_high_level['id'];

// Get the program details
$program = $db->fetch_row("SELECT * FROM academic_programs WHERE level_id = $level_id LIMIT 1");

// Get the academic tracks
$academic_tracks = $db->fetch_all("SELECT * FROM academic_tracks WHERE program_id = {$program['id']} ORDER BY display_order ASC");
?>

<main>
    <div class="container">
        <div class="lvl-grade">
            <h1>Senior High School</h1>
        </div>

        <div class="caption">
            <p><?php echo nl2br(htmlspecialchars($program['description'])); ?></p>
        </div>

        <hr>

        <div class="headline">
            <h1>TRACKS, STRANDS AND SPECIALIZATIONS</h1>
        </div>

        <div class="text-area">
            <h1>ACADEMIC TRACK</h1>
            <ul>
                <?php foreach ($academic_tracks as $track): ?>
                    <li><?php echo htmlspecialchars($track['name']); ?> (<?php echo htmlspecialchars($track['code']); ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="promotional-banner">
            <h2>SENIOR HIGH IS FREE!</h2>
            <p>Whether you are from a PUBLIC school or a PRIVATE school, when you enroll here in SRMS for SENIOR HIGH, it's FREE!</p>
            <p>Plus, incoming Grade 11 students from public schools will receive:</p>
            <ul>
                <li>FREE school and P.E. uniform</li>
                <li>FREE ID with lace</li>
                <li>CASH incentive</li>
            </ul>
            <div class="cta-button">
                <a href="<?php echo SITE_URL; ?>/admissions.php" class="enroll-now">ENROLL NOW!</a>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>