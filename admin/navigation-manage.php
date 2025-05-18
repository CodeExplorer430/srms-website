<?php
/**
 * Navigation Management
 * Allows admins to manage the website navigation menu
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

// Display success/error messages from session
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if this item has children
    $children_count = $db->fetch_row("SELECT COUNT(*) as count FROM navigation WHERE parent_id = $id")['count'];
    
    if ($children_count > 0) {
        $_SESSION['message'] = "Cannot delete this navigation item because it has children. Please delete or reassign the children first.";
        $_SESSION['message_type'] = "error";
        header('Location: navigation-manage.php');
        exit;
    } else {
        // Delete the navigation item
        if ($db->query("DELETE FROM navigation WHERE id = $id")) {
            $_SESSION['message'] = "Navigation item deleted successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to delete navigation item.";
            $_SESSION['message_type'] = "error";
        }
        header('Location: navigation-manage.php');
        exit;
    }
}

// Get all navigation items
$navigation_items = $db->fetch_all("SELECT n.*, p.name as parent_name 
                                    FROM navigation n 
                                    LEFT JOIN navigation p ON n.parent_id = p.id 
                                    ORDER BY n.parent_id IS NULL DESC, n.display_order ASC");

// Get parent navigation items for dropdown (only top-level items can be parents)
$parent_options = $db->fetch_all("SELECT id, name FROM navigation WHERE parent_id IS NULL ORDER BY display_order");

// Start output buffer for main content
ob_start();
?>

<?php if (!empty($message)): ?>
    <div class="message message-<?php echo $message_type; ?>">
        <i class='bx bx-<?php echo $message_type === 'success' ? 'check-circle' : 'error-circle'; ?>'></i>
        <span><?php echo $message; ?></span>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bx-menu'></i> Website Navigation</h3>
        <div class="panel-actions">
            <button type="button" class="btn btn-primary" id="addNavItemBtn">
                <i class='bx bx-plus'></i> Add Navigation Item
            </button>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="navigation-preview">
            <h4>Navigation Preview</h4>
            <div class="preview-box">
                <ul class="nav-preview">
                    <?php 
                    // Display parent items
                    $top_items = array_filter($navigation_items, function($item) {
                        return $item['parent_id'] === null;
                    });
                    
                    foreach ($top_items as $parent): 
                        // Find children of this parent
                        $children = array_filter($navigation_items, function($item) use ($parent) {
                            return $item['parent_id'] == $parent['id'];
                        });
                    ?>
                        <li class="<?php echo !empty($children) ? 'has-children' : ''; ?> <?php echo $parent['is_active'] ? '' : 'inactive'; ?>">
                            <span><?php echo htmlspecialchars($parent['name']); ?></span>
                            <?php if (!empty($children)): ?>
                                <ul>
                                    <?php foreach ($children as $child): ?>
                                        <li class="<?php echo $child['is_active'] ? '' : 'inactive'; ?>">
                                            <span><?php echo htmlspecialchars($child['name']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Parent</th>
                        <th>Display Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($navigation_items)): ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="empty-state">
                                <i class='bx bx-menu'></i>
                                <p>No navigation items found. Add your first navigation item.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($navigation_items as $item): ?>
                        <tr>
                            <td>
                                <?php if ($item['parent_id']): ?>
                                    <span class="indent">&mdash;</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['url']); ?></td>
                            <td><?php echo $item['parent_id'] ? htmlspecialchars($item['parent_name']) : '<span class="text-muted">None</span>'; ?></td>
                            <td><?php echo $item['display_order']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $item['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-primary btn-sm edit-nav-btn" data-id="<?php echo $item['id']; ?>">
                                        <i class='bx bxs-edit'></i> Edit
                                    </button>
                                    <a href="?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this navigation item?');">
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

<!-- Navigation Item Modal -->
<div class="modal" id="navItemModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Navigation Item</h3>
                <button type="button" class="modal-close" id="modalClose">&times;</button>
            </div>
            <div class="modal-body">
                <form id="navItemForm" action="navigation-process.php" method="post">
                    <input type="hidden" id="navItemId" name="id" value="0">
                    <input type="hidden" name="action" value="save">
                    
                    <div class="form-group">
                        <label for="navName">Name</label>
                        <input type="text" id="navName" name="name" class="form-control" required>
                        <small class="form-text">Display name in the navigation menu</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="navUrl">URL</label>
                        <input type="text" id="navUrl" name="url" class="form-control" required>
                        <small class="form-text">Example: /about.php or # for dropdown parent</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="navParent">Parent Item</label>
                        <select id="navParent" name="parent_id" class="form-control">
                            <option value="">None (Top Level)</option>
                            <?php foreach($parent_options as $option): ?>
                                <option value="<?php echo $option['id']; ?>"><?php echo htmlspecialchars($option['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="navOrder">Display Order</label>
                        <input type="number" id="navOrder" name="display_order" class="form-control" value="0" min="0">
                        <small class="form-text">Lower numbers appear first</small>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="navActive" name="is_active" value="1" checked>
                        <label for="navActive">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelBtn">Cancel</button>
                <button type="submit" form="navItemForm" class="btn btn-primary">Save Navigation Item</button>
            </div>
        </div>
    </div>
</div>

<style>
    .indent {
        margin-right: 10px;
        color: #6c757d;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-badge.inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .navigation-preview {
        margin-bottom: 30px;
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
    }
    
    .preview-box {
        background-color: white;
        border-radius: 5px;
        padding: 20px;
        margin-top: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .nav-preview {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 20px;
    }
    
    .nav-preview li {
        position: relative;
    }
    
    .nav-preview li span {
        display: block;
        padding: 8px 15px;
        font-weight: 500;
    }
    
    .nav-preview li.has-children > span::after {
        content: "▼";
        font-size: 10px;
        margin-left: 5px;
    }
    
    .nav-preview li.has-children ul {
        position: absolute;
        top: 100%;
        left: 0;
        width: 200px;
        background-color: white;
        list-style: none;
        padding: 5px 0;
        border-radius: 5px;
        box-shadow: 0 3px 5px rgba(0,0,0,0.1);
        display: none;
        z-index: 10;
    }
    
    .nav-preview li.has-children:hover ul {
        display: block;
    }
    
    .nav-preview li.inactive > span {
        color: #6c757d;
        text-decoration: line-through;
    }
    
    @media (max-width: 768px) {
        .nav-preview {
            flex-direction: column;
            gap: 5px;
        }
        
        .nav-preview li.has-children ul {
            position: static;
            width: auto;
            box-shadow: none;
            padding-left: 20px;
            display: block;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM elements
        const modal = document.getElementById('navItemModal');
        const modalTitle = document.getElementById('modalTitle');
        const navItemForm = document.getElementById('navItemForm');
        const navItemId = document.getElementById('navItemId');
        const navName = document.getElementById('navName');
        const navUrl = document.getElementById('navUrl');
        const navParent = document.getElementById('navParent');
        const navOrder = document.getElementById('navOrder');
        const navActive = document.getElementById('navActive');
        const addNavItemBtn = document.getElementById('addNavItemBtn');
        const modalClose = document.getElementById('modalClose');
        const cancelBtn = document.getElementById('cancelBtn');
        
        // Add Navigation Item
        if (addNavItemBtn) {
            addNavItemBtn.addEventListener('click', function() {
                modalTitle.textContent = 'Add Navigation Item';
                navItemId.value = '0';
                navItemForm.reset();
                navActive.checked = true;
                openModal(modal);
            });
        }
        
        // Edit Navigation Item
        const editButtons = document.querySelectorAll('.edit-nav-btn');
        editButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                
                // Fetch navigation item data
                fetch(`navigation-process.php?action=get&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            modalTitle.textContent = 'Edit Navigation Item';
                            navItemId.value = data.item.id;
                            navName.value = data.item.name;
                            navUrl.value = data.item.url;
                            navParent.value = data.item.parent_id || '';
                            navOrder.value = data.item.display_order;
                            navActive.checked = data.item.is_active == 1;
                            openModal(modal);
                        } else {
                            alert('Error loading navigation item: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading the navigation item');
                    });
            });
        });
        
        // Close modal
        function openModal(modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        [modalClose, cancelBtn].forEach(element => {
            if (element) {
                element.addEventListener('click', function() {
                    closeModal(modal);
                });
            }
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Navigation Management';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>