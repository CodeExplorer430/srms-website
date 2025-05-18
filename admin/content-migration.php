<?php
/**
 * Content Migration Tool
 * Intelligently migrates content from static pages and legacy tables to the CMS system
 */

// Start session and include necessary files
session_start();

// Check login status - require admin privileges
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || 
    !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php';
$db = new Database();

// Initialize variables
$status_messages = [];
$completed_steps = [];

// Check existing data and migration status
$nav_count = $db->fetch_row("SELECT COUNT(*) as count FROM navigation")['count'];
$legacy_pages_count = $db->fetch_row("SELECT COUNT(*) as count FROM pages")['count'];
$cms_pages_count = $db->fetch_row("SELECT COUNT(*) as count FROM page_content")['count'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Verify Navigation Items
    if ($action === 'verify_navigation') {
        if ($nav_count > 0) {
            $status_messages[] = [
                'type' => 'success',
                'message' => "Navigation table already contains $nav_count items. No migration needed."
            ];
            $completed_steps[] = 'navigation';
        } else {
            // If no navigation items exist, provide option to create them
            $status_messages[] = [
                'type' => 'warning',
                'message' => "Navigation table is empty. Use the 'Create Navigation Items' action to populate it."
            ];
        }
    }
    
    // Create Navigation Items if missing
    if ($action === 'create_navigation') {
        if ($nav_count > 0) {
            $status_messages[] = [
                'type' => 'info',
                'message' => "Navigation table already contains $nav_count items. Skipping creation."
            ];
            $completed_steps[] = 'navigation';
        } else {
            // Insert primary navigation items
            $nav_items = [
                ['id' => 1, 'name' => 'HOME', 'url' => '/index.php', 'parent_id' => null, 'display_order' => 1, 'is_active' => 1],
                ['id' => 2, 'name' => 'ADMISSIONS', 'url' => '/admissions.php', 'parent_id' => null, 'display_order' => 2, 'is_active' => 1],
                ['id' => 3, 'name' => 'ABOUT SRMS', 'url' => '/about.php', 'parent_id' => null, 'display_order' => 3, 'is_active' => 1],
                ['id' => 4, 'name' => 'ACADEMICS', 'url' => '#', 'parent_id' => null, 'display_order' => 4, 'is_active' => 1],
                ['id' => 5, 'name' => 'NEWS', 'url' => '/news.php', 'parent_id' => null, 'display_order' => 5, 'is_active' => 1],
                ['id' => 6, 'name' => 'CONTACT', 'url' => '/contact.php', 'parent_id' => null, 'display_order' => 6, 'is_active' => 1],
                ['id' => 7, 'name' => 'ALUMNI', 'url' => '/alumni.php', 'parent_id' => 3, 'display_order' => 1, 'is_active' => 1],
                ['id' => 8, 'name' => 'FACULTY', 'url' => '/faculty.php', 'parent_id' => 3, 'display_order' => 2, 'is_active' => 1]
            ];
            
            // Get academic levels and add them as submenu
            $academic_levels = $db->fetch_all("SELECT * FROM academic_levels ORDER BY display_order ASC");
            foreach ($academic_levels as $index => $level) {
                $nav_items[] = [
                    'id' => 9 + $index, 
                    'name' => strtoupper($level['name']), 
                    'url' => '/academics/' . $level['slug'] . '.php', 
                    'parent_id' => 4, 
                    'display_order' => $index + 1, 
                    'is_active' => 1
                ];
            }
            
            // Insert all navigation items
            $success_count = 0;
            foreach ($nav_items as $item) {
                $parent_id = $item['parent_id'] === null ? 'NULL' : $item['parent_id'];
                $sql = "INSERT INTO navigation (id, name, url, parent_id, display_order, is_active) 
                        VALUES ({$item['id']}, '{$db->escape($item['name'])}', '{$db->escape($item['url'])}', 
                                $parent_id, {$item['display_order']}, {$item['is_active']})";
                
                if ($db->query($sql)) {
                    $success_count++;
                }
            }
            
            $status_messages[] = [
                'type' => 'success',
                'message' => "Successfully inserted $success_count navigation items."
            ];
            $completed_steps[] = 'navigation';
        }
    }
    
    // Migrate Legacy Pages to CMS system
    if ($action === 'migrate_legacy_pages') {
        if ($legacy_pages_count === 0) {
            $status_messages[] = [
                'type' => 'info',
                'message' => "No legacy pages found in the 'pages' table. Skipping migration."
            ];
        } else {
            // Get all legacy pages
            $legacy_pages = $db->fetch_all("SELECT * FROM pages");
            $migrated_count = 0;
            
            foreach ($legacy_pages as $page) {
                // Check if this page already exists in the CMS system
                $existing = $db->fetch_row("SELECT id FROM page_content WHERE page_key = '{$db->escape($page['slug'])}'");
                
                if ($existing) {
                    continue; // Skip existing pages
                }
                
                // Determine the user ID for last_updated_by
                $author_id = 1; // Default to admin user
                if (isset($page['created_by']) && !empty($page['created_by'])) {
                    $author_id = (int)$page['created_by'];
                }

                // Insert into page_content
                $sql = "INSERT INTO page_content (page_key, title, content, meta_description, last_updated_by) 
                        VALUES ('{$db->escape($page['slug'])}', '{$db->escape($page['title'])}', '{$db->escape($page['content'])}', 
                                '{$db->escape($page['meta_description'])}', $author_id)";
                                
                if ($db->query($sql)) {
                    $page_id = $db->insert_id();
                    
                    // Create a main content section
                    if (!empty($page['content'])) {
                        $content_sql = "INSERT INTO page_sections (page_id, section_key, title, content, display_order) 
                                       VALUES ($page_id, 'main_content', '{$db->escape($page['title'])}', '{$db->escape($page['content'])}', 0)";
                        $db->query($content_sql);
                    }
                    
                    $migrated_count++;  
                }
            }
            
            $status_messages[] = [
                'type' => 'success',
                'message' => "Successfully migrated $migrated_count legacy pages to the CMS system."
            ];
            $completed_steps[] = 'legacy_pages';
        }
    }
    
    // Migrate About page content
    if ($action === 'migrate_about') {
        // Check if content already exists in CMS system
        $existing = $db->fetch_row("SELECT * FROM page_content WHERE page_key = 'about'");
        
        if ($existing) {
            $status_messages[] = [
                'type' => 'info',
                'message' => 'About page content already exists in the CMS system. Skipping migration.'
            ];
            $completed_steps[] = 'about';
        } else {
            // Get school information for content
            $school_info = $db->fetch_row("SELECT * FROM school_information LIMIT 1");
            $school_goals = $db->fetch_all("SELECT * FROM school_goals ORDER BY display_order ASC");
            
            // Create main page content record
            $title = 'About SRMS';
            $meta_description = 'Learn about the history, mission, vision, and philosophy of St. Raphaela Mary School.';
            
            $sql = "INSERT INTO page_content (page_key, title, content, meta_description, last_updated_by) 
                    VALUES ('about', '$title', '', '$meta_description', 1)";
                    
            if ($db->query($sql)) {
                $page_id = $db->insert_id();
                
                // Create sections using existing data
                $sections = [
                    [
                        'section_key' => 'introduction',
                        'title' => 'Introduction',
                        'content' => 'At St. Raphaela Mary School, we believe in fostering the holistic development of each student, nurturing their intellectual, spiritual, moral, and social growth from preschool through senior high school.',
                        'display_order' => 1
                    ],
                    [
                        'section_key' => 'philosophy',
                        'title' => 'Philosophy',
                        'content' => $school_info ? $school_info['philosophy'] : 'School philosophy information not available.',
                        'display_order' => 2
                    ],
                    [
                        'section_key' => 'mission',
                        'title' => 'Mission',
                        'content' => $school_info ? $school_info['mission'] : 'Mission information not available.',
                        'display_order' => 3
                    ],
                    [
                        'section_key' => 'vision',
                        'title' => 'Vision',
                        'content' => $school_info ? $school_info['vision'] : 'Vision information not available.',
                        'display_order' => 4
                    ]
                ];
                
                $section_count = 0;
                foreach ($sections as $section) {
                    $sql = "INSERT INTO page_sections (page_id, section_key, title, content, display_order) 
                            VALUES ($page_id, '{$db->escape($section['section_key'])}', '{$db->escape($section['title'])}', 
                                    '{$db->escape($section['content'])}', {$section['display_order']})";
                            
                    if ($db->query($sql)) {
                        $section_count++;
                    }
                }
                
                // Create a school goals section
                if (!empty($school_goals)) {
                    $goals_content = "St. Raphaela Mary School is dedicated to cultivating well-rounded individuals through a holistic educational experience. We strive to achieve this through the following key goals:\n\n";
                    
                    foreach ($school_goals as $index => $goal) {
                        $goals_content .= "**Goal " . ($index + 1) . ": " . $goal['title'] . "** - " . $goal['description'] . "\n\n";
                    }
                    
                    $sql = "INSERT INTO page_sections (page_id, section_key, title, content, display_order) 
                            VALUES ($page_id, 'school_goals', 'School Goals', '{$db->escape($goals_content)}', 5)";
                    $db->query($sql);
                    $section_count++;
                }
                
                $status_messages[] = [
                    'type' => 'success',
                    'message' => "Successfully created About page in CMS with $section_count sections."
                ];
                $completed_steps[] = 'about';
            } else {
                $status_messages[] = [
                    'type' => 'error',
                    'message' => "Failed to create About page in CMS system."
                ];
            }
        }
    }
    
    // Migrate Alumni page content
    if ($action === 'migrate_alumni') {
        // Check if content already exists in CMS system
        $existing = $db->fetch_row("SELECT * FROM page_content WHERE page_key = 'alumni'");
        
        if ($existing) {
            $status_messages[] = [
                'type' => 'info',
                'message' => 'Alumni page content already exists in the CMS system. Skipping migration.'
            ];
            $completed_steps[] = 'alumni';
        } else {
            // Create main page content record
            $title = 'Alumni Association';
            $meta_description = 'Connect with the St. Raphaela Mary School alumni community. Discover alumni events, achievements, and ways to stay connected.';
            
            $sql = "INSERT INTO page_content (page_key, title, content, meta_description, last_updated_by) 
                    VALUES ('alumni', '$title', '', '$meta_description', 1)";
                    
            if ($db->query($sql)) {
                $page_id = $db->insert_id();
                
                // Create sections
                $sections = [
                    [
                        'section_key' => 'welcome',
                        'title' => 'Welcome',
                        'content' => 'The St. Raphaela Mary School Alumni Association connects graduates from all generations, fostering lifelong relationships and supporting our alma mater. We invite all alumni to stay connected, participate in events, and give back to the school community.',
                        'display_order' => 1
                    ],
                    [
                        'section_key' => 'benefits',
                        'title' => 'Alumni Benefits',
                        'content' => "- **Networking Opportunities:** Connect with fellow graduates for professional development and social connections\n- **School Events:** Special invitations to school functions and reunions\n- **Giving Back:** Opportunities to mentor current students and support scholarship programs\n- **Recognition:** Celebrate alumni achievements and contributions to society",
                        'display_order' => 2
                    ],
                    [
                        'section_key' => 'events',
                        'title' => 'Upcoming Alumni Events',
                        'content' => "**Annual Homecoming 2025**\nDate: July 15, 2025\nLocation: SRMS Gymnasium\nJoin us for a day of reminiscing, reconnecting, and celebrating your Raphaelian roots. Special recognition for milestone anniversary batches (Classes of 1975, 1985, 1995, 2005, 2015).\n\n**Career Mentorship Day**\nDate: September 5, 2025\nLocation: SRMS Auditorium\nShare your professional journey and insights with current senior high school students. Help shape the future of our younger Raphaelians!",
                        'display_order' => 3
                    ],
                    [
                        'section_key' => 'registration',
                        'title' => 'Join the Alumni Network',
                        'content' => "We're building a comprehensive database of our alumni. Please take a moment to register or update your information.",
                        'display_order' => 4
                    ],
                    [
                        'section_key' => 'contact',
                        'title' => 'Contact the Alumni Association',
                        'content' => "For inquiries about alumni activities, reunions, or how to get involved:\n\n**Email:** alumni@srms.edu.ph\n**Social Media:** Follow us on Facebook",
                        'display_order' => 5
                    ]
                ];
                
                $section_count = 0;
                foreach ($sections as $section) {
                    $sql = "INSERT INTO page_sections (page_id, section_key, title, content, display_order) 
                            VALUES ($page_id, '{$db->escape($section['section_key'])}', '{$db->escape($section['title'])}', 
                                    '{$db->escape($section['content'])}', {$section['display_order']})";
                            
                    if ($db->query($sql)) {
                        $section_count++;
                    }
                }
                
                $status_messages[] = [
                    'type' => 'success',
                    'message' => "Successfully created Alumni page in CMS with $section_count sections."
                ];
                $completed_steps[] = 'alumni';
            } else {
                $status_messages[] = [
                    'type' => 'error',
                    'message' => "Failed to create Alumni page in CMS system."
                ];
            }
        }
    }
}

// Get database status for display
$nav_status = $nav_count > 0 ? 'complete' : 'incomplete';
$cms_page_status = $cms_pages_count > 0 ? 'in_progress' : 'not_started';
if ($cms_pages_count >= ($legacy_pages_count + 2)) { // +2 for about and alumni pages
    $cms_page_status = 'complete';
}

// Start output buffer for main content
ob_start();
?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bx-transfer-alt'></i> Content Migration Tool</h3>
    </div>
    
    <div class="panel-body">
        <div class="alert alert-info">
            <p><strong>Note:</strong> This tool helps migrate content from static files and legacy database tables to the new CMS system.</p>
            <p>It intelligently checks for existing data to avoid duplication. Each step is safe to run multiple times.</p>
        </div>
        
        <div class="database-status">
            <h4>Current Database Status</h4>
            <div class="status-grid">
                <div class="status-item">
                    <span class="status-label">Navigation Items:</span>
                    <span class="status-value <?php echo $nav_status; ?>"><?php echo $nav_count; ?> items</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Legacy Pages:</span>
                    <span class="status-value"><?php echo $legacy_pages_count; ?> pages</span>
                </div>
                <div class="status-item">
                    <span class="status-label">CMS Pages:</span>
                    <span class="status-value <?php echo $cms_page_status; ?>"><?php echo $cms_pages_count; ?> pages</span>
                </div>
            </div>
        </div>
        
        <?php if (!empty($status_messages)): ?>
            <div class="status-messages">
                <?php foreach ($status_messages as $msg): ?>
                    <div class="message message-<?php echo $msg['type']; ?>">
                        <i class='bx bx-<?php echo $msg['type'] === 'success' ? 'check-circle' : ($msg['type'] === 'info' ? 'info-circle' : ($msg['type'] === 'warning' ? 'error-circle' : 'error-circle')); ?>'></i>
                        <span><?php echo $msg['message']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="migration-steps">
            <form method="post" action="content-migration.php">
                <div class="migration-step <?php echo $nav_status === 'complete' ? 'completed' : ''; ?>">
                    <h4>Step 1: Verify Navigation Menu Items</h4>
                    <p>Checks if navigation items already exist in the database.</p>
                    <input type="hidden" name="action" value="verify_navigation">
                    <button type="submit" class="btn btn-primary">Verify Navigation</button>
                    
                    <?php if ($nav_count === 0): ?>
                        <div class="additional-action">
                            <form method="post" action="content-migration.php">
                                <input type="hidden" name="action" value="create_navigation">
                                <button type="submit" class="btn btn-secondary">Create Navigation Items</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
            
            <form method="post" action="content-migration.php">
                <div class="migration-step <?php echo in_array('legacy_pages', $completed_steps) ? 'completed' : ''; ?>">
                    <h4>Step 2: Migrate Legacy Pages to CMS</h4>
                    <p>Transfers content from the 'pages' table to the new CMS system.</p>
                    <input type="hidden" name="action" value="migrate_legacy_pages">
                    <button type="submit" class="btn btn-primary">Migrate Legacy Pages</button>
                </div>
            </form>
            
            <form method="post" action="content-migration.php">
                <div class="migration-step <?php echo in_array('about', $completed_steps) ? 'completed' : ''; ?>">
                    <h4>Step 3: Create About Page in CMS</h4>
                    <p>Creates structured content for the About page using data from school_information and school_goals tables.</p>
                    <input type="hidden" name="action" value="migrate_about">
                    <button type="submit" class="btn btn-primary">Create About Page</button>
                </div>
            </form>
            
            <form method="post" action="content-migration.php">
                <div class="migration-step <?php echo in_array('alumni', $completed_steps) ? 'completed' : ''; ?>">
                    <h4>Step 4: Create Alumni Page in CMS</h4>
                    <p>Creates structured content for the Alumni page.</p>
                    <input type="hidden" name="action" value="migrate_alumni">
                    <button type="submit" class="btn btn-primary">Create Alumni Page</button>
                </div>
            </form>
        </div>
        
        <?php 
        $all_steps_completed = (
            $nav_status === 'complete' && 
            in_array('legacy_pages', $completed_steps) && 
            in_array('about', $completed_steps) && 
            in_array('alumni', $completed_steps)
        );
        
        if ($all_steps_completed): 
        ?>
            <div class="message message-success">
                <i class='bx bx-check-circle'></i>
                <span>All content migration steps have been completed successfully!</span>
            </div>
            
            <div class="next-steps">
                <h4>Next Steps:</h4>
                <ol>
                    <li>Go to <a href="navigation-manage.php">Navigation Management</a> to manage the menu structure.</li>
                    <li>Go to <a href="pages-manage.php">Pages Management</a> to view and edit the migrated content.</li>
                    <li>Check the front-end pages to ensure they're properly displaying the dynamic content.</li>
                </ol>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .database-status {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }
    
    .status-item {
        padding: 10px;
        border-radius: 5px;
        background-color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .status-label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    
    .status-value {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 14px;
    }
    
    .status-value.complete {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-value.incomplete {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-value.in_progress {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-value.not_started {
        background-color: #e2e3e5;
        color: #383d41;
    }
    
    .status-messages {
        margin-bottom: 20px;
    }
    
    .migration-steps {
        margin-top: 20px;
    }
    
    .migration-step {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .migration-step.completed {
        border-left: 5px solid #28a745;
    }
    
    .migration-step.completed h4::after {
        content: " ✓";
        color: #28a745;
    }
    
    .migration-step h4 {
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .additional-action {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #dee2e6;
    }
    
    .next-steps {
        margin-top: 30px;
        padding: 20px;
        background-color: #f0f8ff;
        border-radius: 8px;
    }
</style>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Content Migration Tool';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>