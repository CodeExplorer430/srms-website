<?php
/**
 * User Management Page
 */

// Start session and include necessary files
session_start();

// Check login status
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

// Check if user has admin role
if($_SESSION['admin_role'] !== 'admin') {
    header('Location: index.php?error=access_denied');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
$db = new Database();

// Initialize variables
$errors = [];
$success = '';

// Handle user deletion
if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Prevent deleting own account
    if($id === (int)$_SESSION['admin_user_id']) {
        header('Location: users.php?error=self_delete');
        exit;
    }
    
    $db->query("DELETE FROM users WHERE id = $id");
    header('Location: users.php?msg=deleted');
    exit;
}

// Handle user status toggle
if(isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Prevent deactivating own account
    if($id === (int)$_SESSION['admin_user_id']) {
        header('Location: users.php?error=self_deactivate');
        exit;
    }
    
    $user = $db->fetch_row("SELECT active FROM users WHERE id = $id");
    $new_status = $user['active'] ? 0 : 1;
    
    $db->query("UPDATE users SET active = $new_status WHERE id = $id");
    header('Location: users.php?msg=status_updated');
    exit;
}

// Process AJAX form submission for adding/editing user
if(isset($_POST['ajax_action']) && ($_POST['ajax_action'] === 'add' || $_POST['ajax_action'] === 'edit')) {
    $response = ['success' => false, 'message' => '', 'errors' => []];
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'editor';
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validate inputs
    if(empty($username)) {
        $response['errors'][] = 'Username is required';
    }
    
    if(empty($email)) {
        $response['errors'][] = 'Email is required';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors'][] = 'Please provide a valid email address';
    }
    
    if($_POST['ajax_action'] === 'add' && empty($password)) {
        $response['errors'][] = 'Password is required for new users';
    }
    
    // Check for existing username or email
    if(!empty($username)) {
        $check_username = $db->fetch_row("SELECT id FROM users WHERE username = '{$db->escape($username)}' AND id != $id");
        if($check_username) {
            $response['errors'][] = 'Username already exists';
        }
    }
    
    if(!empty($email)) {
        $check_email = $db->fetch_row("SELECT id FROM users WHERE email = '{$db->escape($email)}' AND id != $id");
        if($check_email) {
            $response['errors'][] = 'Email already exists';
        }
    }
    
    // Process if no errors
    if(empty($response['errors'])) {
        $username = $db->escape($username);
        $email = $db->escape($email);
        $role = $db->escape($role);
        
        if($_POST['ajax_action'] === 'edit') {
            // Update existing user
            if(!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET 
                        username = '$username', 
                        email = '$email', 
                        password = '$hashed_password', 
                        role = '$role', 
                        active = $active 
                        WHERE id = $id";
            } else {
                $sql = "UPDATE users SET 
                        username = '$username', 
                        email = '$email', 
                        role = '$role', 
                        active = $active 
                        WHERE id = $id";
            }
            
            if($db->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'User has been updated successfully';
            } else {
                $response['errors'][] = 'An error occurred while updating the user';
            }
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, role, active) 
                    VALUES ('$username', '$email', '$hashed_password', '$role', $active)";
            
            if($db->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'User has been created successfully';
                $response['user_id'] = $db->insert_id();
            } else {
                $response['errors'][] = 'An error occurred while creating the user';
            }
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get user data for AJAX edit request
if(isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_user' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $user = $db->fetch_row("SELECT id, username, email, role, active FROM users WHERE id = $id");
    
    header('Content-Type: application/json');
    echo json_encode($user ? $user : ['error' => 'User not found']);
    exit;
}

// Get all users
$users = $db->fetch_all("SELECT * FROM users ORDER BY username");

// Start output buffer for main content
ob_start();
?>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'deleted'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>User has been deleted successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'status_updated'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>User status has been updated successfully.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if(isset($_GET['error'])): ?>
    <?php if($_GET['error'] === 'self_delete'): ?>
        <div class="message message-error">
            <i class='bx bx-error-circle'></i>
            <span>You cannot delete your own account.</span>
        </div>
    <?php elseif($_GET['error'] === 'self_deactivate'): ?>
        <div class="message message-error">
            <i class='bx bx-error-circle'></i>
            <span>You cannot deactivate your own account.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bxs-user-detail'></i> User Management</h3>
        <div class="panel-actions">
            <button type="button" class="btn btn-primary" id="addUserBtn">
                <i class='bx bx-user-plus'></i> Add New User
            </button>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="search-box mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search users...">
        </div>
        
        <div class="table-container">
            <table class="table" id="usersTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="empty-state">
                                <i class='bx bx-user-x'></i>
                                <p>No users found.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $user['role'] === 'admin' ? 'primary' : 
                                        ($user['role'] === 'editor' ? 'success' : 'warning'); 
                                ?>">
                                    <?php 
                                        switch($user['role']) {
                                            case 'admin':
                                                echo 'Administrator';
                                                break;
                                            case 'content_manager':
                                                echo 'Content Manager';
                                                break;
                                            default:
                                                echo 'Editor';
                                        }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $user['active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-primary btn-sm edit-user-btn" data-id="<?php echo $user['id']; ?>">
                                        <i class='bx bxs-edit'></i> Edit
                                    </button>
                                    
                                    <?php if($user['id'] !== (int)$_SESSION['admin_user_id']): ?>
                                        <?php if($user['active']): ?>
                                            <a href="users.php?action=toggle_status&id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                <i class='bx bxs-x-circle'></i> Deactivate
                                            </a>
                                        <?php else: ?>
                                            <a href="users.php?action=toggle_status&id=<?php echo $user['id']; ?>" class="btn btn-success btn-sm">
                                                <i class='bx bxs-check-circle'></i> Activate
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class='bx bxs-trash'></i> Delete
                                        </a>
                                    <?php endif; ?>
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

<!-- User Modal -->
<div class="modal" id="userModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add New User</h3>
                <button type="button" class="modal-close" id="modalClose">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" id="modalErrors" style="display: none;">
                    <ul id="errorList"></ul>
                </div>
                <form id="userForm">
                    <input type="hidden" id="userId" name="id" value="0">
                    <input type="hidden" id="ajaxAction" name="ajax_action" value="add">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" id="passwordLabel">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="form-control">
                            <option value="admin">Administrator</option>
                            <option value="editor" selected>Editor</option>
                            <option value="content_manager">Content Manager</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="active" name="active" value="1" checked>
                        <label for="active">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="cancelBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveBtn">Save User</button>
            </div>
        </div>
    </div>
</div>

<style>
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    
    .search-box input {
        padding-left: 35px;
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
    
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    
    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #842029;
    }
    
    .alert ul {
        margin: 0;
        padding-left: 20px;
    }
    
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }
        
        .action-buttons .btn {
            margin-bottom: 5px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM elements
        const modal = document.getElementById('userModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalErrors = document.getElementById('modalErrors');
        const errorList = document.getElementById('errorList');
        const userForm = document.getElementById('userForm');
        const userId = document.getElementById('userId');
        const ajaxAction = document.getElementById('ajaxAction');
        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const passwordLabel = document.getElementById('passwordLabel');
        const role = document.getElementById('role');
        const active = document.getElementById('active');
        const addUserBtn = document.getElementById('addUserBtn');
        const modalClose = document.getElementById('modalClose');
        const cancelBtn = document.getElementById('cancelBtn');
        const saveBtn = document.getElementById('saveBtn');
        const searchInput = document.getElementById('searchInput');
        
        // Open modal for adding new user
        addUserBtn.addEventListener('click', function() {
            modalTitle.textContent = 'Add New User';
            ajaxAction.value = 'add';
            userId.value = '0';
            passwordLabel.textContent = 'Password';
            password.required = true;
            userForm.reset();
            role.value = 'editor';
            active.checked = true;
            modalErrors.style.display = 'none';
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
        
        // Edit user button click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-user-btn') || 
                e.target.parentElement.classList.contains('edit-user-btn')) {
                
                const btn = e.target.classList.contains('edit-user-btn') ? 
                            e.target : e.target.parentElement;
                const userId = btn.dataset.id;
                
                // Fetch user data
                fetch(`users.php?ajax_action=get_user&id=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        
                        modalTitle.textContent = 'Edit User';
                        ajaxAction.value = 'edit';
                        document.getElementById('userId').value = data.id;
                        username.value = data.username;
                        email.value = data.email;
                        password.value = '';
                        passwordLabel.textContent = 'Password (leave blank to keep current)';
                        password.required = false;
                        role.value = data.role;
                        active.checked = data.active == 1;
                        modalErrors.style.display = 'none';
                        modal.style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while fetching user data');
                    });
            }
        });
        
        // Save user
        saveBtn.addEventListener('click', function() {
            const formData = new FormData(userForm);
            
            // Add checkbox value if not checked
            if (!active.checked) {
                formData.append('active', '0');
            }
            
            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the page to show updated data
                    window.location.href = 'users.php?msg=' + (ajaxAction.value === 'add' ? 'added' : 'updated');
                } else {
                    // Show errors
                    errorList.innerHTML = '';
                    data.errors.forEach(error => {
                        const li = document.createElement('li');
                        li.textContent = error;
                        errorList.appendChild(li);
                    });
                    modalErrors.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the user');
            });
        });
        
        // Search functionality
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const table = document.getElementById('usersTable');
                const rows = table.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) {
                    let display = false;
                    
                    // Skip the empty state row
                    if (rows[i].cells.length === 1 && rows[i].cells[0].classList.contains('text-center')) {
                        continue;
                    }
                    
                    const usernameCell = rows[i].cells[0];
                    const emailCell = rows[i].cells[1];
                    
                    if (usernameCell && emailCell) {
                        const username = usernameCell.textContent.toLowerCase();
                        const email = emailCell.textContent.toLowerCase();
                        
                        if (username.includes(searchValue) || email.includes(searchValue)) {
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
$page_title = 'User Management';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>