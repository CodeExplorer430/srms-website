<?php
/**
 * Content Migration Tool
 * Intelligently migrates content from static pages and legacy tables to the CMS system
 *
 * This tool helps administrators transfer content from the old static pages structure
 * to the new dynamic CMS-driven content management system.
 */

// Start session and check login
session_start();

// Check login status - require admin privileges
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || 
    !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

// Get the site root directory (works anywhere in the site)
$site_root = $_SERVER['DOCUMENT_ROOT'] . '/srms-website';

// Include necessary files using absolute paths
include_once $site_root . '/includes/config.php';
include_once $site_root . '/includes/db.php';
include_once $site_root . '/includes/functions.php';

// Initialize database connection
$db = new Database();

// Initialize variables
$status_messages = [];
$completed_steps = [];
$migration_status = [
    'navigation' => 'pending',
    'legacy_pages' => 'pending',
    'about' => 'pending',
    'alumni' => 'pending',
    'faculty' => 'pending'
];

// Check existing data and migration status
$nav_count = $db->fetch_row("SELECT COUNT(*) as count FROM navigation")['count'];
$legacy_pages_count = $db->fetch_row("SELECT COUNT(*) as count FROM pages")['count'];
$cms_pages_count = $db->fetch_row("SELECT COUNT(*) as count FROM page_content")['count'];
$faculty_count = $db->fetch_row("SELECT COUNT(*) as count FROM faculty")['count'];

// Update status based on existing data
if ($nav_count > 0) {
    $migration_status['navigation'] = 'complete';
    $completed_steps[] = 'navigation';
}

if ($cms_pages_count >= $legacy_pages_count && $legacy_pages_count > 0) {
    $migration_status['legacy_pages'] = 'complete';
    $completed_steps[] = 'legacy_pages';
}

// Check for specific pages
$about_page = $db->fetch_row("SELECT id FROM page_content WHERE page_key = 'about'");
if ($about_page) {
    $migration_status['about'] = 'complete';
    $completed_steps[] = 'about';
}

$alumni_page = $db->fetch_row("SELECT id FROM page_content WHERE page_key = 'alumni'");
if ($alumni_page) {
    $migration_status['alumni'] = 'complete';
    $completed_steps[] = 'alumni';
}

if ($faculty_count > 0) {
    $migration_status['faculty'] = 'complete';
    $completed_steps[] = 'faculty';
}

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
            $migration_status['navigation'] = 'complete';
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
            $migration_status['navigation'] = 'complete';
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
            $migration_status['navigation'] = 'complete';
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
            $migration_status['legacy_pages'] = 'complete';
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
            $migration_status['about'] = 'complete';
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
                $migration_status['about'] = 'complete';
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
            $migration_status['alumni'] = 'complete';
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
                $migration_status['alumni'] = 'complete';
            } else {
                $status_messages[] = [
                    'type' => 'error',
                    'message' => "Failed to create Alumni page in CMS system."
                ];
            }
        }
    }
    
    // Migrate Faculty Data
    if ($action === 'migrate_faculty') {
        // Check if content already exists in faculty table
        if ($faculty_count > 0) {
            $status_messages[] = [
                'type' => 'info',
                'message' => "Faculty table already contains $faculty_count records. Skipping migration."
            ];
            $completed_steps[] = 'faculty';
            $migration_status['faculty'] = 'complete';
        } else {
            // Create categories first
            $categories = [
                ['name' => 'Administration', 'display_order' => 1],
                ['name' => 'Faculty', 'display_order' => 2],
                ['name' => 'Support Staff', 'display_order' => 3]
            ];
            
            $category_ids = [];
            foreach ($categories as $category) {
                $sql = "INSERT INTO faculty_categories (name, display_order) 
                        VALUES ('{$db->escape($category['name'])}', {$category['display_order']})";
                if ($db->query($sql)) {
                    $category_ids[$category['name']] = $db->insert_id();
                }
            }
            
            // Sample faculty data
            $sample_faculty = [
                [
                    'name' => 'Dr. Maria Santos',
                    'position' => 'Principal',
                    'bio' => 'Dr. Santos has over 20 years of experience in education leadership. She holds a Ph.D. in Educational Administration and is passionate about cultivating a culture of excellence.',
                    'category_id' => $category_ids['Administration'],
                    'display_order' => 1
                ],
                [
                    'name' => 'Prof. Juan Dela Cruz',
                    'position' => 'Science Department Head',
                    'bio' => 'Prof. Dela Cruz specializes in Chemistry and has been with SRMS for 15 years. He is dedicated to making science accessible and engaging for all students.',
                    'category_id' => $category_ids['Faculty'],
                    'display_order' => 1
                ],
                [
                    'name' => 'Ms. Ana Reyes',
                    'position' => 'Mathematics Teacher',
                    'bio' => 'Ms. Reyes has been teaching mathematics for 8 years. She is known for her innovative teaching methods that make math concepts clear and enjoyable.',
                    'category_id' => $category_ids['Faculty'],
                    'display_order' => 2
                ],
                [
                    'name' => 'Mr. Roberto Lim',
                    'position' => 'School Counselor',
                    'bio' => 'Mr. Lim is a licensed guidance counselor with expertise in student development and well-being. He provides comprehensive support to students navigating academic and personal challenges.',
                    'category_id' => $category_ids['Support Staff'],
                    'display_order' => 1
                ]
            ];
            
            $faculty_count = 0;
            foreach ($sample_faculty as $faculty) {
                $sql = "INSERT INTO faculty (name, position, bio, category_id, display_order) 
                        VALUES (
                            '{$db->escape($faculty['name'])}', 
                            '{$db->escape($faculty['position'])}', 
                            '{$db->escape($faculty['bio'])}', 
                            {$faculty['category_id']}, 
                            {$faculty['display_order']}
                        )";
                        
                if ($db->query($sql)) {
                    $faculty_count++;
                }
            }
            
            if ($faculty_count > 0) {
                $status_messages[] = [
                    'type' => 'success',
                    'message' => "Successfully created $faculty_count faculty records and " . count($category_ids) . " categories."
                ];
                $completed_steps[] = 'faculty';
                $migration_status['faculty'] = 'complete';
            } else {
                $status_messages[] = [
                    'type' => 'error',
                    'message' => "Failed to create faculty records."
                ];
            }
        }
    }
}

// Calculate overall progress
$progress_steps = count($migration_status);
$completed_count = count($completed_steps);
$progress_percentage = $progress_steps > 0 ? round(($completed_count / $progress_steps) * 100) : 0;

// Start output buffer for main content
ob_start();
?>

<div class="content-migration-tool">
    <div class="tool-header">
        <div class="header-icon">
            <i class='bx bx-transfer-alt'></i>
        </div>
        <div class="header-title">
            <h2>Content Migration Tool</h2>
            <p>Migrate content from legacy systems to the new CMS platform</p>
        </div>
    </div>

    <div class="tool-dashboard">
        <div class="progress-panel">
            <div class="progress-header">
                <h3>Migration Progress</h3>
                <div class="progress-percentage"><?php echo $progress_percentage; ?>%</div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
            </div>
            <div class="progress-steps">
                <?php foreach ($migration_status as $step => $status): ?>
                <div class="progress-step <?php echo $status; ?>">
                    <div class="step-icon">
                        <?php if ($status === 'complete'): ?>
                        <i class='bx bx-check-circle'></i>
                        <?php elseif ($status === 'in_progress'): ?>
                        <i class='bx bx-loader-alt'></i>
                        <?php else: ?>
                        <i class='bx bx-circle'></i>
                        <?php endif; ?>
                    </div>
                    <div class="step-label">
                        <?php 
                        switch($step) {
                            case 'navigation':
                                echo 'Navigation Menu';
                                break;
                            case 'legacy_pages':
                                echo 'Legacy Pages';
                                break;
                            case 'about':
                                echo 'About Page';
                                break;
                            case 'alumni':
                                echo 'Alumni Page';
                                break;
                            case 'faculty':
                                echo 'Faculty Data';
                                break;
                            default:
                                echo ucfirst($step);
                        }
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stats-panel">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class='bx bx-menu'></i>
                </div>
                <div class="stats-content">
                    <div class="stats-value"><?php echo $nav_count; ?></div>
                    <div class="stats-label">Navigation Items</div>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon">
                    <i class='bx bx-file'></i>
                </div>
                <div class="stats-content">
                    <div class="stats-value"><?php echo $legacy_pages_count; ?></div>
                    <div class="stats-label">Legacy Pages</div>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon">
                    <i class='bx bx-layer'></i>
                </div>
                <div class="stats-content">
                    <div class="stats-value"><?php echo $cms_pages_count; ?></div>
                    <div class="stats-label">CMS Pages</div>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon">
                    <i class='bx bx-user'></i>
                </div>
                <div class="stats-content">
                    <div class="stats-value"><?php echo $faculty_count; ?></div>
                    <div class="stats-label">Faculty Members</div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($status_messages)): ?>
    <div class="status-container">
        <?php foreach ($status_messages as $msg): ?>
        <div class="status-message <?php echo $msg['type']; ?>">
            <i class='bx bx-<?php 
                switch($msg['type']) {
                    case 'success':
                        echo 'check-circle';
                        break;
                    case 'error':
                        echo 'error-circle';
                        break;
                    case 'warning':
                        echo 'error';
                        break;
                    default:
                        echo 'info-circle';
                }
            ?>'></i>
            <span><?php echo $msg['message']; ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="migration-steps">
        <h3>Migration Steps</h3>
        <div class="steps-container">
            <!-- Step 1: Navigation -->
            <div class="migration-step <?php echo $migration_status['navigation']; ?>">
                <div class="step-header">
                    <h4>Step 1: Navigation Menu Migration</h4>
                    <div class="step-status">
                        <?php if ($migration_status['navigation'] === 'complete'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="step-description">
                    <p>Create the primary navigation menu structure for the website. This step creates the main menu items and their relationships.</p>
                </div>
                <div class="step-actions">
                    <?php if ($migration_status['navigation'] !== 'complete'): ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="verify_navigation">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-check-shield'></i> Verify Navigation
                        </button>
                    </form>
                    
                    <form method="post" action="">
                        <input type="hidden" name="action" value="create_navigation">
                        <button type="submit" class="btn btn-secondary">
                            <i class='bx bx-list-plus'></i> Create Navigation Menu
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="step-complete-message">
                        <i class='bx bx-check-circle'></i>
                        <span>Navigation menu has been successfully created with <?php echo $nav_count; ?> items.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Step 2: Legacy Pages -->
            <div class="migration-step <?php echo $migration_status['legacy_pages']; ?>">
                <div class="step-header">
                    <h4>Step 2: Legacy Pages Migration</h4>
                    <div class="step-status">
                        <?php if ($migration_status['legacy_pages'] === 'complete'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="step-description">
                    <p>Migrate content from the old static pages to the new CMS system. This transfers titles, content, and metadata to the structured page system.</p>
                </div>
                <div class="step-actions">
                    <?php if ($migration_status['legacy_pages'] !== 'complete'): ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="migrate_legacy_pages">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-transfer-alt'></i> Migrate Legacy Pages
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="step-complete-message">
                        <i class='bx bx-check-circle'></i>
                        <span>Legacy pages have been successfully migrated to the CMS system.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Step 3: About Page -->
            <div class="migration-step <?php echo $migration_status['about']; ?>">
                <div class="step-header">
                    <h4>Step 3: About Page Creation</h4>
                    <div class="step-status">
                        <?php if ($migration_status['about'] === 'complete'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="step-description">
                    <p>Create the About page with structured sections for Introduction, Philosophy, Mission and Vision using data from the school information table.</p>
                </div>
                <div class="step-actions">
                    <?php if ($migration_status['about'] !== 'complete'): ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="migrate_about">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-building-house'></i> Create About Page
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="step-complete-message">
                        <i class='bx bx-check-circle'></i>
                        <span>About page has been successfully created in the CMS.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Step 4: Alumni Page -->
            <div class="migration-step <?php echo $migration_status['alumni']; ?>">
                <div class="step-header">
                    <h4>Step 4: Alumni Page Creation</h4>
                    <div class="step-status">
                        <?php if ($migration_status['alumni'] === 'complete'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="step-description">
                    <p>Create the Alumni page with structured sections for the alumni association, benefits, events, registration, and contact information.</p>
                </div>
                <div class="step-actions">
                    <?php if ($migration_status['alumni'] !== 'complete'): ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="migrate_alumni">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-group'></i> Create Alumni Page
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="step-complete-message">
                        <i class='bx bx-check-circle'></i>
                        <span>Alumni page has been successfully created in the CMS.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Step 5: Faculty Data -->
            <div class="migration-step <?php echo $migration_status['faculty']; ?>">
                <div class="step-header">
                    <h4>Step 5: Faculty Data Migration</h4>
                    <div class="step-status">
                        <?php if ($migration_status['faculty'] === 'complete'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="step-description">
                    <p>Create faculty categories and sample faculty data to populate the faculty directory.</p>
                </div>
                <div class="step-actions">
                    <?php if ($migration_status['faculty'] !== 'complete'): ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="migrate_faculty">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-user-plus'></i> Create Faculty Data
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="step-complete-message">
                        <i class='bx bx-check-circle'></i>
                        <span>Faculty data has been successfully created with <?php echo $faculty_count; ?> members.</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($progress_percentage === 100): ?>
    <div class="completion-message">
        <div class="completion-icon">
            <i class='bx bx-check-shield'></i>
        </div>
        <div class="completion-content">
            <h3>Content Migration Complete!</h3>
            <p>All migration steps have been successfully completed. Your website now has a fully functional CMS.</p>
            <div class="completion-actions">
                <a href="../../admin/index.php" class="btn btn-primary">
                    <i class='bx bx-home'></i> Go to Dashboard
                </a>
                <a href="../../admin/pages-manage.php" class="btn btn-secondary">
                    <i class='bx bx-edit'></i> Manage Pages
                </a>
                <a href="../../admin/navigation-manage.php" class="btn btn-secondary">
                    <i class='bx bx-menu'></i> Manage Navigation
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.content-migration-tool {
    font-family: 'Poppins', sans-serif;
    max-width: 1200px;
    margin: 0 auto;
}

.tool-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.header-icon {
    background-color: rgba(60, 145, 230, 0.1);
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
}

.header-icon i {
    font-size: 30px;
    color: #3C91E6;
}

.header-title h2 {
    margin: 0 0 5px 0;
    font-size: 24px;
    color: #0a3060;
}

.header-title p {
    margin: 0;
    color: #6c757d;
}

.tool-dashboard {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
    margin-bottom: 30px;
}

.progress-panel {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 20px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.progress-header h3 {
    margin: 0;
    font-size: 18px;
    color: #0a3060;
}

.progress-percentage {
    font-size: 18px;
    font-weight: bold;
    color: #3C91E6;
}

.progress-bar {
    width: 100%;
    height: 10px;
    background-color: #e9ecef;
    border-radius: 5px;
    margin-bottom: 20px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background-color: #3C91E6;
    border-radius: 5px;
    transition: width 0.5s ease;
}

.progress-steps {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.progress-step {
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 5px;
    background-color: #f8f9fa;
}

.progress-step.complete {
    background-color: rgba(40, 167, 69, 0.1);
}

.progress-step.in_progress {
    background-color: rgba(255, 193, 7, 0.1);
}

.step-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    margin-right: 10px;
}

.progress-step.complete .step-icon i {
    color: #28a745;
}

.progress-step.in_progress .step-icon i {
    color: #ffc107;
    animation: spin 2s linear infinite;
}

.progress-step.pending .step-icon i {
    color: #6c757d;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.step-label {
    font-size: 14px;
    font-weight: 500;
    color: #495057;
}

.progress-step.complete .step-label {
    color: #155724;
}

.stats-panel {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.stats-card {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 20px;
    display: flex;
    align-items: center;
}

.stats-icon {
    background-color: rgba(60, 145, 230, 0.1);
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.stats-icon i {
    font-size: 20px;
    color: #3C91E6;
}

.stats-content {
    flex-grow: 1;
}

.stats-value {
    font-size: 24px;
    font-weight: bold;
    color: #0a3060;
    line-height: 1;
}

.stats-label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.status-container {
    margin-bottom: 30px;
}

.status-message {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 10px;
}

.status-message i {
    font-size: 20px;
    margin-right: 10px;
}

.status-message.success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #155724;
    border-left: 4px solid #28a745;
}

.status-message.error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.status-message.warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #856404;
    border-left: 4px solid #ffc107;
}

.status-message.info {
    background-color: rgba(23, 162, 184, 0.1);
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}

.migration-steps {
    margin-bottom: 40px;
}

.migration-steps h3 {
    font-size: 20px;
    color: #0a3060;
    margin-bottom: 20px;
}

.steps-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.migration-step {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.migration-step.complete {
    border-left: 5px solid #28a745;
}

.migration-step.in_progress {
    border-left: 5px solid #ffc107;
}

.migration-step.pending {
    border-left: 5px solid #6c757d;
}

.step-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.step-header h4 {
    margin: 0;
    font-size: 16px;
    color: #0a3060;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
}

.badge-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.badge-pending {
    background-color: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.badge-in-progress {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.step-description {
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
}

.step-description p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
}

.step-actions {
    padding: 15px 20px;
    display: flex;
    gap: 10px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    border-radius: 5px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn i {
    font-size: 16px;
}

.btn-primary {
    background-color: #3C91E6;
    color: white;
}

.btn-primary:hover {
    background-color: #2e73b8;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.step-complete-message {
    display: flex;
    align-items: center;
    color: #28a745;
    font-weight: 500;
}

.step-complete-message i {
    margin-right: 5px;
}

.completion-message {
    background-color: #d4edda;
    border-radius: 10px;
    padding: 30px;
    display: flex;
    align-items: center;
    margin-bottom: 40px;
}

.completion-icon {
    width: 80px;
    height: 80px;
    background-color: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 30px;
}

.completion-icon i {
    font-size: 40px;
    color: white;
}

.completion-content h3 {
    margin: 0 0 10px 0;
    font-size: 24px;
    color: #155724;
}

.completion-content p {
    margin: 0 0 20px 0;
    color: #155724;
}

.completion-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 992px) {
    .tool-dashboard {
        grid-template-columns: 1fr;
    }
    
    .stats-panel {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .stats-panel {
        grid-template-columns: 1fr;
    }
    
    .step-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .step-status {
        margin-top: 10px;
    }
    
    .step-actions {
        flex-direction: column;
    }
    
    .completion-message {
        flex-direction: column;
        text-align: center;
    }
    
    .completion-icon {
        margin-right: 0;
        margin-bottom: 20px;
    }
    
    .completion-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any JavaScript functionality here if needed
    
    // Example: Add animation to progress fill
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        setTimeout(() => {
            progressFill.style.width = progressFill.getAttribute('style').split(':')[1];
        }, 200);
    }
});
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Content Migration Tool';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout with absolute path
include_once $site_root . '/admin/layout.php';
?>