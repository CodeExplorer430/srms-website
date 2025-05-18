<?php
/**
 * About Page
 * Displays information about the school using content from the database
 */

// Set page metadata
$page_title = 'About Us';
$page_description = 'Learn about the history, mission, vision, and philosophy of St. Raphaela Mary School.';

// Include header
include 'includes/header.php';

// Get page content from the database
$page_key = 'about';
$page_content = get_page_content($page_key);

// Get school information
if (!isset($school_info) || empty($school_info)) {
    $school_info = get_school_info();
}

// Get school goals from database
$school_goals = $db->fetch_all("SELECT * FROM school_goals ORDER BY display_order ASC");
?>

<main>
    <div class="about-container">
        <img src="<?php echo get_image_path($school_info['logo'] ?? '', 'general'); ?>" alt="<?php echo htmlspecialchars($school_info['name'] ?? 'St. Raphaela Mary School'); ?> Logo">

        <div class="about-header">
            <h2><?php echo isset($page_content['title']) ? htmlspecialchars($page_content['title']) : 'About SRMS'; ?></h2>
        </div>

        <h3>Welcome to <?php echo htmlspecialchars($school_info['name'] ?? 'St. Raphaela Mary School'); ?>!</h3>

        <?php if (isset($page_content)): ?>
            <?php 
            // Display sections if available in database
            if (isset($page_content['sections_by_key']['introduction'])) {
                display_page_section($page_content, 'introduction', false);
            }
            ?>
        <?php endif; ?>

        <div class="abouttxt-area">
            <p class="text-header">PHILOSOPHY</p>
            <p><?php echo nl2br(htmlspecialchars($school_info['philosophy'] ?? 'School philosophy information not available.')); ?></p>
        </div>

        <div class="abouttxt-area">
            <p class="text-header">GOALS</p>
            <p>St. Raphaela Mary School is dedicated to cultivating well-rounded individuals through a holistic educational experience. We strive to achieve this through the following key goals:</p>
            
            <?php if (!empty($school_goals)): ?>
                <?php foreach ($school_goals as $index => $goal): ?>
                    <p><b>Goal <?php echo $index+1; ?>: <?php echo htmlspecialchars($goal['title']); ?> - </b><?php echo htmlspecialchars($goal['description']); ?></p>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback to static content if no goals in database -->
                <p><b>Goal 1: Nurturing Faith - </b>We will foster a deep and enduring faith in God, grounded in Christian teachings, empowering students to integrate their faith into their daily lives.</p>
                <p><b>Goal 2: Achieving Academic Excellence - </b>We will create a challenging and engaging academic environment where students realize their intellectual potential and cultivate a lifelong passion for learning.</p>
                <p><b>Goal 3: Building Strong Partnerships - </b>We will actively engage parents, teachers, and the broader community in a collaborative partnership that champions student success.</p>
                <p><b>Goal 4: Developing Competent Graduates - </b>We will equip students with the essential knowledge, skills, and values to thrive in their chosen fields and become responsible, contributing members of society.</p>
                <p><b>Goal 5: Promoting Holistic Growth - </b>We will nurture the intellectual, emotional, social, and spiritual development of each student, fostering their growth into compassionate and responsible individuals.</p>
            <?php endif; ?>
        </div>

        <div class="abouttxt-area">
            <p class="text-header">SCHOOL GOALS</p>
            <p>St. Raphaela Mary School aims to nurture well-rounded individuals through a holistic approach to education.</p>

            <ol>
                <?php if (!empty($school_goals)): ?>
                    <?php foreach ($school_goals as $goal): ?>
                        <li><b><?php echo htmlspecialchars($goal['title']); ?>: </b><?php echo htmlspecialchars($goal['description']); ?></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback content -->
                    <li><b>Faith Formation: </b>To foster a deep and abiding faith in God, rooted in the teachings of the Christian Church, and to encourage students to live out their faith in their daily lives.</li>
                    <li><b>Academic Excellence: </b>To provide a challenging and stimulating academic environment that enables students to reach their full intellectual potential and develop a lifelong love of learning.</li>
                    <li><b>Stakeholder Involvement: </b>To actively involve parents, teachers, and the wider community in the educational process, creating a collaborative partnership that supports student success.</li>
                    <li><b>Training Place of Competence: </b>To equip students with the knowledge, skills, and values necessary to succeed in their chosen fields and to become responsible and contributing members of society.</li>
                    <li><b>Holistic Development: </b>To promote the intellectual, emotional, social, and spiritual development of each student, fostering their growth into well-rounded, compassionate, and responsible individuals.</li>
                <?php endif; ?>
            </ol>

            <hr class="hr1">

            <div class="abouttxt-area">
                <p class="text-header">MISSION</p>
                <?php if (isset($page_content) && isset($page_content['sections_by_key']['mission'])): ?>
                    <?php display_page_section($page_content, 'mission', false); ?>
                <?php else: ?>
                    <p><?php echo nl2br(htmlspecialchars($school_info['mission'] ?? 'Mission information not available.')); ?></p>
                <?php endif; ?>
            </div>

            <div class="abouttxt-area">
                <p class="text-header">VISION</p>
                <?php if (isset($page_content) && isset($page_content['sections_by_key']['vision'])): ?>
                    <?php display_page_section($page_content, 'vision', false); ?>
                <?php else: ?>
                    <p><?php echo nl2br(htmlspecialchars($school_info['vision'] ?? 'Vision information not available.')); ?></p>
                <?php endif; ?>
            </div>

            <hr class="hr2">
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>