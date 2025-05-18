<?php
/**
 * Page Content Management
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
$db = new Database();

// Get all pages
$pages = $db->fetch_all("SELECT p.*, u.username as updated_by_user 
                       FROM page_content p 
                       LEFT JOIN users u ON p.last_updated_by = u.id 
                       ORDER BY p.title");

// Start output buffer for main content
ob_start();
?>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'updated'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Page content has been updated successfully.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bxs-file'></i> Website Pages</h3>
        <div class="panel-actions">
            <a href="page-edit.php" class="btn btn-primary">
                <i class='bx bx-plus'></i> Add New Page
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="search-box mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search pages...">
        </div>
        
        <div class="table-container">
            <table class="table" id="pagesTable">
                <thead>
                    <tr>
                        <th>Page Title</th>
                        <th>Page Key</th>
                        <th>Last Updated</th>
                        <th>Updated By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($pages)): ?>
                    <tr>
                        <td colspan="5" class="text-center">
                            <div class="empty-state">
                                <i class='bx bx-file'></i>
                                <p>No pages found. Create your first page content.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($pages as $page): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($page['title']); ?></td>
                            <td><code><?php echo htmlspecialchars($page['page_key']); ?></code></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($page['last_updated'])); ?></td>
                            <td><?php echo htmlspecialchars($page['updated_by_user'] ?? 'System'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="page-edit.php?id=<?php echo $page['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class='bx bxs-edit'></i> Edit
                                    </a>
                                    <a href="../<?php echo ($page['page_key'] === 'home') ? '' : $page['page_key']; ?>.php" class="btn btn-light btn-sm" target="_blank">
                                        <i class='bx bx-link-external'></i> View
                                    </a>
                                </div>
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
    // Search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const table = document.getElementById('pagesTable');
                const rows = table.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) {
                    let display = false;
                    
                    // Skip the empty state row
                    if (rows[i].cells.length === 1 && rows[i].cells[0].classList.contains('text-center')) {
                        continue;
                    }
                    
                    const titleCell = rows[i].cells[0];
                    const keyCell = rows[i].cells[1];
                    
                    if (titleCell && keyCell) {
                        const title = titleCell.textContent.toLowerCase();
                        const key = keyCell.textContent.toLowerCase();
                        
                        if (title.includes(searchValue) || key.includes(searchValue)) {
                            display = true;
                        }
                    }
                    
                    rows[i].style.display = display ? '' : 'none';
                }
            });
        }
    });
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Manage Pages';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>