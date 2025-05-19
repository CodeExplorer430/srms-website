<?php
/**
 * Admin Tools Dashboard
 * A centralized interface for all administrative and diagnostic tools
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Include necessary files
include_once '../../includes/config.php';
include_once '../../includes/db.php';
include_once '../../includes/functions.php';

// Define the tools categories and items
$tool_categories = [
    'system' => [
        'title' => 'System Tools',
        'description' => 'Diagnose and inspect system configuration and environment settings',
        'icon' => 'bx-server',
        'tools' => [
            [
                'id' => 'environment-check',
                'title' => 'Environment Check',
                'description' => 'Verify PHP configuration, database connections, and server settings',
                'icon' => 'bx-check-shield',
                'url' => 'system/environment-check.php'
            ],
            [
                'id' => 'path-diagnostics',
                'title' => 'Path Diagnostics',
                'description' => 'Debug file paths, directory structures, and URL configurations',
                'icon' => 'bx-link',
                'url' => 'system/path-diagnostics.php'
            ]
        ]
    ],
    'media' => [
        'title' => 'Media Tools',
        'description' => 'Test media uploads and diagnose image display issues',
        'icon' => 'bx-images',
        'tools' => [
            [
                'id' => 'image-diagnostics',
                'title' => 'Image Diagnostics',
                'description' => 'Debug image paths, file locations, and placeholder creation',
                'icon' => 'bx-image',
                'url' => 'media/image-diagnostics.php'
            ],
            [
                'id' => 'upload-tester',
                'title' => 'Upload Tester',
                'description' => 'Test file uploads for permissions, folder creation, and path handling',
                'icon' => 'bx-upload',
                'url' => 'media/upload-tester.php'
            ]
        ]
    ],
    'content' => [
        'title' => 'Content Tools',
        'description' => 'Manage content migration, database operations, and data integrity',
        'icon' => 'bx-data',
        'tools' => [
            [
                'id' => 'content-migration',
                'title' => 'Content Migration',
                'description' => 'Migrate content between tables and update data structure',
                'icon' => 'bx-transfer-alt',
                'url' => 'content/content-migration.php'
            ],
            [
                'id' => 'database-tools',
                'title' => 'Database Tools',
                'description' => 'Perform database operations, backups, and maintenance',
                'icon' => 'bx-table',
                'url' => 'content/database-tools.php'
            ]
        ]
    ],
    'maintenance' => [
        'title' => 'Maintenance Tools',
        'description' => 'Setup directories, fix common issues, and perform system maintenance',
        'icon' => 'bx-wrench',
        'tools' => [
            [
                'id' => 'setup-directories',
                'title' => 'Setup Directories',
                'description' => 'Create necessary directories and placeholders for the website',
                'icon' => 'bx-folder-plus',
                'url' => 'maintenance/setup-directories.php'
            ],
            [
                'id' => 'fix-paths',
                'title' => 'Fix Path Issues',
                'description' => 'Automatically detect and repair path-related issues',
                'icon' => 'bx-git-pull-request',
                'url' => 'maintenance/fix-paths.php'
            ]
        ]
    ]
];

// Get the content for the layout
ob_start();
?>

<div class="tools-dashboard">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-toolbox'></i> Admin Tools Dashboard</h2>
            <p>Comprehensive diagnostic and maintenance tools for website administration</p>
        </div>
    </div>

    <div class="tools-container">
        <?php foreach ($tool_categories as $category_id => $category): ?>
            <div class="tool-category">
                <div class="category-header">
                    <i class='bx <?php echo $category['icon']; ?>'></i>
                    <h3><?php echo $category['title']; ?></h3>
                </div>
                <p class="category-description"><?php echo $category['description']; ?></p>
                
                <div class="tool-grid">
                    <?php foreach ($category['tools'] as $tool): ?>
                        <a href="<?php echo $tool['url']; ?>" class="tool-card">
                            <div class="tool-icon">
                                <i class='bx <?php echo $tool['icon']; ?>'></i>
                            </div>
                            <div class="tool-info">
                                <h4><?php echo $tool['title']; ?></h4>
                                <p><?php echo $tool['description']; ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.tools-dashboard {
    max-width: 1200px;
    margin: 0 auto;
}

.header-banner {
    background: linear-gradient(120deg, #3C91E6, #0a3060);
    border-radius: 10px;
    color: white;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header-banner h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
    display: flex;
    align-items: center;
}

.header-banner h2 i {
    margin-right: 10px;
    font-size: 32px;
}

.header-banner p {
    margin: 0;
    opacity: 0.8;
    font-size: 16px;
}

.tool-category {
    background-color: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.category-header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.category-header i {
    font-size: 24px;
    margin-right: 10px;
    color: #3C91E6;
}

.category-header h3 {
    margin: 0;
    font-size: 20px;
    color: #0a3060;
}

.category-description {
    color: #6c757d;
    margin-bottom: 20px;
}

.tool-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.tool-card {
    display: flex;
    align-items: flex-start;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
}

.tool-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-color: #3C91E6;
}

.tool-icon {
    margin-right: 15px;
    width: 40px;
    height: 40px;
    background-color: rgba(60, 145, 230, 0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.tool-icon i {
    font-size: 20px;
    color: #3C91E6;
}

.tool-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: #0a3060;
}

.tool-info p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
    line-height: 1.4;
}

@media (max-width: 768px) {
    .tool-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$content = ob_get_clean();

// Define the page title and specific CSS/JS
$page_title = 'Admin Tools Dashboard';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout - need to use a different path for the layout
include '../layout.php';
?>