<?php
/**
 * Alumni Page
 * Displays information about the school's alumni association using content from the database
 */

// Set page metadata
$page_title = 'Alumni';
$page_description = 'Connect with the St. Raphaela Mary School alumni community. Discover alumni events, achievements, and ways to stay connected.';

// Include header
include 'includes/header.php';

// Get page content from the database
$page_key = 'alumni';
$page_content = get_page_content($page_key);

// Get alumni events from database (if you have a specific table for this)
$alumni_events = $db->fetch_all("SELECT * FROM news WHERE category = 'alumni' AND status = 'published' ORDER BY published_date DESC LIMIT 3");
?>

<section class="main-head">
    <h1><?php echo isset($page_content['title']) ? htmlspecialchars($page_content['title']) : 'ALUMNI ASSOCIATION'; ?></h1>
    <p><?php echo isset($page_content['meta_description']) ? htmlspecialchars($page_content['meta_description']) : 'Connect with fellow Raphaelians and stay updated with alumni activities'; ?></p>
</section>

<main>
    <div class="alumni-container">
        <div class="welcome-message">
            <h2>Welcome Raphaelian Alumni!</h2>
            <?php if (isset($page_content) && isset($page_content['sections_by_key']['welcome'])): ?>
                <?php display_page_section($page_content, 'welcome', false); ?>
            <?php else: ?>
                <!-- Fallback content -->
                <p>
                    The St. Raphaela Mary School Alumni Association connects graduates from all generations, fostering lifelong relationships and supporting our alma mater. We invite all alumni to stay connected, participate in events, and give back to the school community.
                </p>
            <?php endif; ?>
        </div>

        <div class="alumni-benefits">
            <h2><i class='bx bx-award'></i> Alumni Benefits</h2>
            <?php if (isset($page_content) && isset($page_content['sections_by_key']['benefits'])): ?>
                <?php display_page_section($page_content, 'benefits', false); ?>
            <?php else: ?>
                <!-- Fallback content -->
                <ul>
                    <li><strong>Networking Opportunities:</strong> Connect with fellow graduates for professional development and social connections</li>
                    <li><strong>School Events:</strong> Special invitations to school functions and reunions</li>
                    <li><strong>Giving Back:</strong> Opportunities to mentor current students and support scholarship programs</li>
                    <li><strong>Recognition:</strong> Celebrate alumni achievements and contributions to society</li>
                </ul>
            <?php endif; ?>
        </div>

        <div class="upcoming-events">
            <h2><i class='bx bx-calendar-event'></i> Upcoming Alumni Events</h2>
            <?php if (!empty($alumni_events)): ?>
                <?php foreach ($alumni_events as $event): ?>
                    <div class="event-card">
                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['published_date'])); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location'] ?? 'SRMS Campus'); ?></p>
                        <p><?php echo htmlspecialchars($event['summary'] ?? substr($event['content'], 0, 150) . '...'); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if (isset($page_content) && isset($page_content['sections_by_key']['events'])): ?>
                    <?php display_page_section($page_content, 'events', false); ?>
                <?php else: ?>
                    <!-- Fallback content -->
                    <div class="event-card">
                        <h3>Annual Homecoming 2025</h3>
                        <p><strong>Date:</strong> July 15, 2025</p>
                        <p><strong>Location:</strong> SRMS Gymnasium</p>
                        <p>Join us for a day of reminiscing, reconnecting, and celebrating your Raphaelian roots. Special recognition for milestone anniversary batches (Classes of 1975, 1985, 1995, 2005, 2015).</p>
                    </div>
                    <div class="event-card">
                        <h3>Career Mentorship Day</h3>
                        <p><strong>Date:</strong> September 5, 2025</p>
                        <p><strong>Location:</strong> SRMS Auditorium</p>
                        <p>Share your professional journey and insights with current senior high school students. Help shape the future of our younger Raphaelians!</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="alumni-registration">
            <h2><i class='bx bx-user-plus'></i> Join the Alumni Network</h2>
            <?php if (isset($page_content) && isset($page_content['sections_by_key']['registration'])): ?>
                <?php display_page_section($page_content, 'registration', false); ?>
            <?php else: ?>
                <!-- Fallback content -->
                <p>We're building a comprehensive database of our alumni. Please take a moment to register or update your information:</p>
                <div class="cta-button">
                    <a href="#" class="register-btn">Register/Update Info</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="alumni-contact">
            <h2>Contact the Alumni Association</h2>
            <?php if (isset($page_content) && isset($page_content['sections_by_key']['contact'])): ?>
                <?php display_page_section($page_content, 'contact', false); ?>
            <?php else: ?>
                <!-- Fallback content -->
                <p>For inquiries about alumni activities, reunions, or how to get involved:</p>
                <p><strong>Email:</strong> alumni@srms.edu.ph</p>
                <p><strong>Social Media:</strong> Follow us on <a href="https://web.facebook.com/srms.page">Facebook</a></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>