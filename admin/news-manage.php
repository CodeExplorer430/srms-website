<?php
/**
 * News Management Page
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

// Handle deletion
if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->query("DELETE FROM news WHERE id = $id");
    header('Location: news-manage.php?msg=deleted');
    exit;
}

// Get all news articles
$news_articles = $db->fetch_all("SELECT n.*, u.username as author FROM news n LEFT JOIN users u ON n.author_id = u.id ORDER BY n.published_date DESC");

// Start output buffer for main content
ob_start();
?>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'deleted'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>News article has been successfully deleted.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bxs-news'></i> News Articles</h3>
        <div class="panel-actions">
            <a href="news-edit.php" class="btn btn-primary">
                <i class='bx bx-plus'></i> Add New Article
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="filters mb-3">
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search articles...">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <select id="statusFilter" class="form-control">
                            <option value="all">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <table class="table" id="newsTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Published Date</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($news_articles)): ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="empty-state">
                                <i class='bx bx-news'></i>
                                <p>No news articles found.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($news_articles as $article): ?>
                        <tr>
                            <td class="title-cell" title="<?php echo htmlspecialchars($article['title']); ?>">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($article['published_date'])); ?></td>
                            <td><?php echo htmlspecialchars($article['author']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $article['status'] === 'published' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($article['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($article['featured']): ?>
                                <span class="badge badge-primary">Featured</span>
                                <?php else: ?>
                                <span>No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="news-edit.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class='bx bxs-edit'></i> Edit
                                    </a>
                                    <a href="news-manage.php?action=delete&id=<?php echo $article['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this article?')">
                                        <i class='bx bxs-trash'></i> Delete
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
        const statusFilter = document.getElementById('statusFilter');
        
        if (searchInput) {
            searchInput.addEventListener('keyup', filterTable);
        }
        
        if (statusFilter) {
            statusFilter.addEventListener('change', filterTable);
        }
        
        function filterTable() {
            const searchValue = searchInput.value.toLowerCase();
            const filterValue = statusFilter.value.toLowerCase();
            const table = document.getElementById('newsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                let display = true;
                
                // Skip if it's the empty state row
                if (rows[i].cells.length === 1 && rows[i].cells[0].classList.contains('text-center')) {
                    continue;
                }
                
                // Filter by search term
                if (searchValue !== '') {
                    const titleCell = rows[i].cells[0];
                    const title = titleCell.textContent || titleCell.innerText;
                    if (title.toLowerCase().indexOf(searchValue) === -1) {
                        display = false;
                    }
                }
                
                // Filter by status
                if (filterValue !== 'all') {
                    const statusCell = rows[i].cells[3];
                    const status = statusCell.textContent || statusCell.innerText;
                    if (status.toLowerCase().indexOf(filterValue) === -1) {
                        display = false;
                    }
                }
                
                rows[i].style.display = display ? '' : 'none';
            }
        }
    });
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Manage News';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>