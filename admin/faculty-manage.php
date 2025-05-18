<?php
/**
 * Faculty Management
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
    
    // Get photo path before deleting
    $faculty = $db->fetch_row("SELECT photo FROM faculty WHERE id = $id");
    $photo_path = $faculty ? $faculty['photo'] : '';
    
    // Delete the record
    $db->query("DELETE FROM faculty WHERE id = $id");
    
    header('Location: faculty-manage.php?msg=deleted');
    exit;
}

// Get all faculty with category names
$faculty = $db->fetch_all("SELECT f.*, fc.name as category_name 
                          FROM faculty f 
                          LEFT JOIN faculty_categories fc ON f.category_id = fc.id 
                          ORDER BY fc.display_order, f.display_order");

// Get faculty categories for filtering
$categories = $db->fetch_all("SELECT * FROM faculty_categories ORDER BY display_order");

// Start output buffer for main content
ob_start();
?>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'deleted'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Faculty member has been deleted successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'updated'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Faculty member has been updated successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'added'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Faculty member has been added successfully.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bxs-user-detail'></i> Faculty Management</h3>
        <div class="panel-actions">
            <a href="faculty-categories.php" class="btn btn-secondary">
                <i class='bx bx-category'></i> Manage Categories
            </a>
            <a href="faculty-edit.php" class="btn btn-primary">
                <i class='bx bx-plus'></i> Add Faculty Member
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="filters-row">
            <div class="search-box">
                <input type="text" id="searchInput" class="form-control" placeholder="Search faculty members...">
            </div>
            
            <div class="category-filter">
                <select id="categoryFilter" class="form-control">
                    <option value="all">All Categories</option>
                    <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="faculty-grid" id="facultyGrid">
            <?php if(empty($faculty)): ?>
                <div class="empty-state">
                    <i class='bx bx-user-x'></i>
                    <p>No faculty members found.</p>
                </div>
            <?php else: ?>
                <?php 
                // Group faculty by category
                $faculty_by_category = [];
                foreach($faculty as $member) {
                    $category_id = $member['category_id'] ?? 0;
                    $category_name = $member['category_name'] ?? 'Uncategorized';
                    
                    if(!isset($faculty_by_category[$category_id])) {
                        $faculty_by_category[$category_id] = [
                            'name' => $category_name,
                            'members' => []
                        ];
                    }
                    
                    $faculty_by_category[$category_id]['members'][] = $member;
                }
                
                // Display faculty by category
                foreach($faculty_by_category as $category_id => $category):
                ?>
                    <div class="faculty-category" data-category-id="<?php echo $category_id; ?>">
                        <h3 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <div class="faculty-members">
                            <?php foreach($category['members'] as $member): ?>
                                <div class="faculty-card" data-name="<?php echo htmlspecialchars($member['name']); ?>">
                                    <div class="faculty-photo">
                                        <?php 
                                        $photo = !empty($member['photo']) ? $member['photo'] : '/assets/images/people/placeholder-'.($member['name'][0] == 'M' ? 'male' : 'female').'.jpg';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                                    </div>
                                    <div class="faculty-info">
                                        <h4 class="faculty-name"><?php echo htmlspecialchars($member['name']); ?></h4>
                                        <p class="faculty-position"><?php echo htmlspecialchars($member['position']); ?></p>
                                        <div class="faculty-actions">
                                            <a href="faculty-edit.php?id=<?php echo $member['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class='bx bxs-edit'></i> Edit
                                            </a>
                                            <a href="faculty-manage.php?action=delete&id=<?php echo $member['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this faculty member?')">
                                                <i class='bx bxs-trash'></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .filters-row {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .search-box {
        flex: 1;
        position: relative;
    }
    
    .search-box::before {
        content: '\ea11';
        font-family: 'boxicons';
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }
    
    .search-box input {
        padding-left: 35px;
        width: 100%;
    }
    
    .category-filter {
        width: 200px;
    }
    
    .faculty-grid {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .faculty-category {
        margin-bottom: 10px;
    }
    
    .category-title {
        color: var(--primary-color);
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .faculty-members {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .faculty-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: var(--box-shadow);
        overflow: hidden;
        display: flex;
        transition: all 0.3s;
    }
    
    .faculty-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .faculty-photo {
        width: 120px;
        padding: 15px 0 15px 15px;
    }
    
    .faculty-photo img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .faculty-info {
        flex: 1;
        padding: 15px;
    }
    
    .faculty-name {
        margin: 0 0 5px;
        font-size: 16px;
        font-weight: 600;
    }
    
    .faculty-position {
        color: #6c757d;
        font-size: 14px;
        margin: 0 0 10px;
    }
    
    .faculty-actions {
        display: flex;
        gap: 10px;
    }
    
    @media (max-width: 768px) {
        .filters-row {
            flex-direction: column;
        }
        
        .category-filter {
            width: 100%;
        }
        
        .faculty-photo {
            width: 80px;
        }
        
        .faculty-photo img {
            height: 80px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const facultyGrid = document.getElementById('facultyGrid');
        
        function filterFaculty() {
            const searchValue = searchInput.value.toLowerCase();
            const categoryValue = categoryFilter.value;
            
            // Get all faculty categories
            const categories = facultyGrid.querySelectorAll('.faculty-category');
            
            categories.forEach(category => {
                // Check category filter
                if (categoryValue !== 'all' && category.dataset.categoryId !== categoryValue) {
                    category.style.display = 'none';
                    return;
                } else {
                    category.style.display = '';
                }
                
                // Get faculty members in this category
                const members = category.querySelectorAll('.faculty-card');
                let visibleMembers = 0;
                
                members.forEach(member => {
                    const name = member.dataset.name.toLowerCase();
                    
                    if (name.includes(searchValue)) {
                        member.style.display = '';
                        visibleMembers++;
                    } else {
                        member.style.display = 'none';
                    }
                });
                
                // Hide category if no members visible
                if (visibleMembers === 0) {
                    category.style.display = 'none';
                }
            });
            
            // Show empty state if no results
            const visibleCategories = facultyGrid.querySelectorAll('.faculty-category[style=""]');
            if (visibleCategories.length === 0) {
                // Check if empty state already exists
                let emptyState = facultyGrid.querySelector('.empty-state');
                
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <i class='bx bx-search-alt'></i>
                        <p>No faculty members found matching your search.</p>
                    `;
                    facultyGrid.appendChild(emptyState);
                } else {
                    emptyState.style.display = '';
                }
            } else {
                // Hide empty state if results found
                const emptyState = facultyGrid.querySelector('.empty-state');
                if (emptyState) {
                    emptyState.style.display = 'none';
                }
            }
        }
        
        // Add event listeners
        if (searchInput) {
            searchInput.addEventListener('keyup', filterFaculty);
        }
        
        if (categoryFilter) {
            categoryFilter.addEventListener('change', filterFaculty);
        }
    });
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Faculty Management';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>