<?php
/**
 * Page Content Diagnostic Tool
 * Helps identify issues with CMS integration
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../admin/login.php');
    exit;
}

// Include database connection
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
$db = new Database();

// Get all pages
$pages = $db->fetch_all("SELECT * FROM page_content ORDER BY page_key");

// Get all PHP files in root directory (potential pages)
$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$project_folder = '';
if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
    $project_folder = $matches[1]; // Should be "srms-website"
}

$website_root = $doc_root;
if (!empty($project_folder)) {
    $website_root .= DIRECTORY_SEPARATOR . $project_folder;
}

// Scan for PHP files in root directory
$php_files = glob($website_root . DIRECTORY_SEPARATOR . '*.php');
$php_pages = [];

foreach ($php_files as $file) {
    $basename = basename($file, '.php');
    // Skip includes, admin, and other non-page files
    if (!in_array($basename, ['index', 'header', 'footer', 'config', 'db', 'functions'])) {
        $php_pages[] = $basename;
    }
}

// Start output buffer for main content
ob_start();
?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bx-file-find'></i> Page Content Diagnostic Tool</h3>
        <div class="panel-actions">
            <a href="../pages-manage.php" class="btn btn-primary">
                <i class='bx bx-file'></i> Manage Pages
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="alert alert-info">
            <p>This tool helps you diagnose issues with your CMS page integration.</p>
            <p>It compares the PHP files in your website directory with the pages defined in your CMS database.</p>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>PHP Pages in Website</h5>
                    </div>
                    <div class="card-body">
                        <p>Files found: <?php echo count($php_pages); ?></p>
                        <ul class="list-group">
                            <?php foreach($php_pages as $page): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo $page; ?>.php
                                    <?php
                                    $has_cms_content = false;
                                    foreach($pages as $cms_page) {
                                        if ($cms_page['page_key'] == $page) {
                                            $has_cms_content = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if($has_cms_content): ?>
                                        <span class="badge badge-success">In CMS</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Not in CMS</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Pages in CMS Database</h5>
                    </div>
                    <div class="card-body">
                        <p>Records found: <?php echo count($pages); ?></p>
                        <ul class="list-group">
                            <?php foreach($pages as $page): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($page['page_key']); ?>
                                    <?php
                                    $has_php_file = in_array($page['page_key'], $php_pages);
                                    ?>
                                    <?php if($has_php_file): ?>
                                        <span class="badge badge-success">Has PHP file</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">No PHP file</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <h4>Missing Integrations</h4>
        <p>These are PHP files that don't have corresponding CMS content:</p>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>PHP File</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $missing_integrations = [];
                    foreach($php_pages as $page) {
                        $has_cms_content = false;
                        foreach($pages as $cms_page) {
                            if ($cms_page['page_key'] == $page) {
                                $has_cms_content = true;
                                break;
                            }
                        }
                        
                        if (!$has_cms_content) {
                            $missing_integrations[] = $page;
                        }
                    }
                    ?>
                    
                    <?php if(empty($missing_integrations)): ?>
                        <tr>
                            <td colspan="2" class="text-center">
                                <div class="empty-state">
                                    <i class='bx bx-check-circle'></i>
                                    <p>All PHP files have corresponding CMS content. Good job!</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($missing_integrations as $page): ?>
                            <tr>
                                <td><?php echo $page; ?>.php</td>
                                <td>
                                    <a href="../page-edit.php?new=1&key=<?php echo $page; ?>" class="btn btn-primary btn-sm">
                                        <i class='bx bx-plus'></i> Create CMS Content
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <h4>Orphaned CMS Content</h4>
        <p>These are CMS entries that don't have corresponding PHP files:</p>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>CMS Page Key</th>
                        <th>Page Title</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $orphaned_cms = [];
                    foreach($pages as $page) {
                        if (!in_array($page['page_key'], $php_pages)) {
                            $orphaned_cms[] = $page;
                        }
                    }
                    ?>
                    
                    <?php if(empty($orphaned_cms)): ?>
                        <tr>
                            <td colspan="3" class="text-center">
                                <div class="empty-state">
                                    <i class='bx bx-check-circle'></i>
                                    <p>All CMS entries have corresponding PHP files. Good job!</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($orphaned_cms as $page): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($page['page_key']); ?></td>
                                <td><?php echo htmlspecialchars($page['title']); ?></td>
                                <td>
                                    <a href="../page-edit.php?id=<?php echo $page['id']; ?>" class="btn btn-info btn-sm">
                                        <i class='bx bx-edit'></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $page['id']; ?>)">
                                        <i class='bx bx-trash'></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        if (confirm('Are you sure you want to delete this CMS entry? This cannot be undone.')) {
            window.location.href = '../page-process.php?action=delete&id=' + id;
        }
    }
</script>

<style>
    .badge {
        display: inline-block;
        padding: 0.25em 0.6em;
        font-size: 75%;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }
    
    .badge-success {
        background-color: #28a745;
        color: white;
    }
    
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    .list-group {
        display: flex;
        flex-direction: column;
        padding-left: 0;
        margin-bottom: 0;
    }
    
    .list-group-item {
        position: relative;
        display: block;
        padding: 0.75rem 1.25rem;
        margin-bottom: -1px;
        background-color: #fff;
        border: 1px solid rgba(0,0,0,.125);
    }
    
    .list-group-item:first-child {
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
    }
    
    .list-group-item:last-child {
        margin-bottom: 0;
        border-bottom-right-radius: 0.25rem;
        border-bottom-left-radius: 0.25rem;
    }
    
    .justify-content-between {
        justify-content: space-between!important;
    }
    
    .align-items-center {
        align-items: center!important;
    }
    
    .d-flex {
        display: flex!important;
    }
</style>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Page Content Diagnostic Tool';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include '../layout.php';
?>