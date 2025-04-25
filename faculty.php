<?php
$page_title = 'Faculty & Staff';
$page_description = 'Meet the dedicated faculty and staff of St. Raphaela Mary School. Our team of educators and administrators are committed to providing quality education.';

include 'includes/header.php';

// Fetch faculty categories and members
$db = db_connect();
$categories = $db->fetch_all("SELECT * FROM faculty_categories ORDER BY display_order ASC");

// For each category, fetch the members
foreach ($categories as $key => $category) {
    $categories[$key]['members'] = $db->fetch_all("SELECT * FROM faculty WHERE category_id = {$category['id']} ORDER BY display_order ASC");
}
?>

<section class="main-head">
    <h1>FACULTY AND PERSONNEL ROSTER</h1>
    <p>Our dedicated team of educators and staff work tirelessly to provide quality education and support to our students.</p>
</section>

<main class="faculty-container">
    <?php if (empty($categories)): ?>
        <p>Faculty information is currently being updated. Please check back later.</p>
    <?php else: ?>
        <?php foreach ($categories as $category): ?>
            <h2 class="card-header"><?php echo htmlspecialchars($category['name']); ?></h2>
            <section class="faculty-list">
                <?php if (empty($category['members'])): ?>
                    <p>Members in this category will be updated soon.</p>
                <?php else: ?>
                    <?php foreach ($category['members'] as $member): ?>
                        <div class="faculty-card">
                            <?php 
                            $gender = (strpos($member['name'], 'Ms.') === 0 || strpos($member['name'], 'Mrs.') === 0) ? 'female' : 'male';
                            $photo = get_image_path($member['photo'], 'person', $gender);
                            ?>
                            <img src="<?php echo $photo; ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                            <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                            <p><?php echo htmlspecialchars($member['position']); ?></p>
                            <?php if (!empty($member['qualifications'])): ?>
                                <p class="qualifications"><?php echo htmlspecialchars($member['qualifications']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>