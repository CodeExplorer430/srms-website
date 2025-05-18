<?php
/**
 * Faculty Categories Management
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

// Initialize variables
$success_message = '';
$error_message = '';

// Handle AJAX requests
if(isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    // Add new category
    if($_POST['action'] === 'add' && isset($_POST['name'])) {
        $name = $db->escape(trim($_POST['name']));
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        if(empty($name)) {
            $response['message'] = 'Category name is required';
        } else {
            $sql = "INSERT INTO faculty_categories (name, display_order) VALUES ('$name', $display_order)";
            if($db->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'Category added successfully';
                $response['id'] = $db->insert_id();
            } else {
                $response['message'] = 'Failed to add category';
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Edit category
    if($_POST['action'] === 'edit' && isset($_POST['id'], $_POST['name'])) {
        $id = (int)$_POST['id'];
        $name = $db->escape(trim($_POST['name']));
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        if(empty($name)) {
            $response['message'] = 'Category name is required';
        } else {
            $sql = "UPDATE faculty_categories SET name = '$name', display_order = $display_order WHERE id = $id";
            if($db->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'Category updated successfully';
            } else {
                $response['message'] = 'Failed to update category';
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Delete category
    if($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // Check if category has faculty members
        $faculty_count = $db->fetch_row("SELECT COUNT(*) as count FROM faculty WHERE category_id = $id")['count'];
        
        if($faculty_count > 0) {
            $response['message'] = "Cannot delete category: $faculty_count faculty members are assigned to this category";
        } else {
            $sql = "DELETE FROM faculty_categories WHERE id = $id";
            if($db->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'Category deleted successfully';
            } else {
                $response['message'] = 'Failed to delete category';
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Get all categories
$categories = $db->fetch_all("SELECT fc.*, (SELECT COUNT(*) FROM faculty f WHERE f.category_id = fc.id) as faculty_count 
                            FROM faculty_categories fc 
                            ORDER BY fc.display_order ASC");

// Start output buffer for main content
ob_start();
?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bx-category'></i> Faculty Categories</h3>
        <div class="panel-actions">
            <button type="button" class="btn btn-primary" id="add-category-btn">
                <i class='bx bx-plus'></i> Add New Category
            </button>
            <a href="faculty-manage.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Faculty
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        <div id="alert-container"></div>
        
        <div class="table-container">
            <table class="table" id="categoriesTable">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Display Order</th>
                        <th>Faculty Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($categories)): ?>
                    <tr id="empty-row">
                        <td colspan="4" class="text-center">
                            <div class="empty-state">
                                <i class='bx bx-category'></i>
                                <p>No categories found. Add your first category.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($categories as $category): ?>
                        <tr data-id="<?php echo $category['id']; ?>">
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo $category['display_order']; ?></td>
                            <td><?php echo $category['faculty_count']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-primary btn-sm edit-category-btn">
                                        <i class='bx bxs-edit'></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm delete-category-btn" <?php echo $category['faculty_count'] > 0 ? 'disabled' : ''; ?>>
                                        <i class='bx bxs-trash'></i> Delete
                                    </button>
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

<!-- Category Modal -->
<div class="modal" id="categoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add New Category</h3>
                <button type="button" class="modal-close" id="modalClose">&times;</button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="categoryId" name="id" value="0">
                    <input type="hidden" id="action" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" class="form-control" value="0" min="0">
                        <small class="form-text">Lower numbers will appear first.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveBtn">Save Category</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM elements
        const modal = document.getElementById('categoryModal');
        const modalTitle = document.getElementById('modalTitle');
        const categoryForm = document.getElementById('categoryForm');
        const categoryId = document.getElementById('categoryId');
        const action = document.getElementById('action');
        const nameInput = document.getElementById('name');
        const displayOrderInput = document.getElementById('display_order');
        const addCategoryBtn = document.getElementById('add-category-btn');
        const modalClose = document.getElementById('modalClose');
        const cancelBtn = document.getElementById('cancelBtn');
        const saveBtn = document.getElementById('saveBtn');
        const alertContainer = document.getElementById('alert-container');
        
        // Show alert
        function showAlert(message, type) {
            alertContainer.innerHTML = `
                <div class="message message-${type}">
                    <i class='bx bx-${type === 'success' ? 'check-circle' : 'error-circle'}'></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }
        
        // Open modal for adding new category
        addCategoryBtn.addEventListener('click', function() {
            modalTitle.textContent = 'Add New Category';
            action.value = 'add';
            categoryId.value = '0';
            categoryForm.reset();
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
        
        // Close modal
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        modalClose.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Edit category button click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-category-btn') || 
                e.target.parentElement.classList.contains('edit-category-btn')) {
                
                const btn = e.target.classList.contains('edit-category-btn') ? 
                            e.target : e.target.parentElement;
                const row = btn.closest('tr');
                const id = row.dataset.id;
                const name = row.cells[0].textContent;
                const displayOrder = row.cells[1].textContent;
                
                modalTitle.textContent = 'Edit Category';
                action.value = 'edit';
                categoryId.value = id;
                nameInput.value = name;
                displayOrderInput.value = displayOrder;
                
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
        
        // Delete category button click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-category-btn') || 
                e.target.parentElement.classList.contains('delete-category-btn')) {
                
                const btn = e.target.classList.contains('delete-category-btn') ? 
                            e.target : e.target.parentElement;
                
                if (btn.disabled) {
                    showAlert('Cannot delete category: faculty members are assigned to this category', 'error');
                    return;
                }
                
                const row = btn.closest('tr');
                const id = row.dataset.id;
                const name = row.cells[0].textContent;
                
                if (confirm(`Are you sure you want to delete the category "${name}"?`)) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    fetch('faculty-categories.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert(data.message, 'success');
                            row.remove();
                            
                            // Check if table is now empty
                            const tableBody = document.querySelector('#categoriesTable tbody');
                            if (tableBody.children.length === 0) {
                                tableBody.innerHTML = `
                                    <tr id="empty-row">
                                        <td colspan="4" class="text-center">
                                            <div class="empty-state">
                                                <i class='bx bx-category'></i>
                                                <p>No categories found. Add your first category.</p>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            }
                        } else {
                            showAlert(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('An error occurred while deleting the category', 'error');
                    });
                }
            }
        });
        
        // Save category
        saveBtn.addEventListener('click', function() {
            // Validate form
            if (!nameInput.value.trim()) {
                showAlert('Category name is required', 'error');
                return;
            }
            
            const formData = new FormData(categoryForm);
            
            fetch('faculty-categories.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    
                    if (action.value === 'add') {
                        // Add new row to table
                        const tableBody = document.querySelector('#categoriesTable tbody');
                        const emptyRow = document.getElementById('empty-row');
                        
                        if (emptyRow) {
                            emptyRow.remove();
                        }
                        
                        const newRow = document.createElement('tr');
                        newRow.dataset.id = data.id;
                        newRow.innerHTML = `
                            <td>${nameInput.value}</td>
                            <td>${displayOrderInput.value}</td>
                            <td>0</td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-primary btn-sm edit-category-btn">
                                        <i class='bx bxs-edit'></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm delete-category-btn">
                                        <i class='bx bxs-trash'></i> Delete
                                    </button>
                                </div>
                            </td>
                        `;
                        
                        tableBody.appendChild(newRow);
                    } else if (action.value === 'edit') {
                        // Update existing row
                        const row = document.querySelector(`tr[data-id="${categoryId.value}"]`);
                        if (row) {
                            row.cells[0].textContent = nameInput.value;
                            row.cells[1].textContent = displayOrderInput.value;
                        }
                    }
                    
                    // Close modal
                    closeModal();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while saving the category', 'error');
            });
        });
    });
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Faculty Categories';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>