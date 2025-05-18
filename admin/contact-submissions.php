<?php
/**
 * Contact Submissions Management Page
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

// Handle status updates
if(isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $db->escape($_GET['status']);
    
    if(in_array($status, ['new', 'read', 'replied', 'archived'])) {
        $db->query("UPDATE contact_submissions SET status = '$status' WHERE id = $id");
        header('Location: contact-submissions.php?msg=updated');
        exit;
    }
}

// Handle deletion
if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->query("DELETE FROM contact_submissions WHERE id = $id");
    header('Location: contact-submissions.php?msg=deleted');
    exit;
}

// Get submissions with optional filtering
$status_filter = isset($_GET['status']) ? $db->escape($_GET['status']) : '';
$where_clause = '';

if($status_filter && in_array($status_filter, ['new', 'read', 'replied', 'archived'])) {
    $where_clause = "WHERE status = '$status_filter'";
}

$submissions = $db->fetch_all("SELECT * FROM contact_submissions $where_clause ORDER BY submission_date DESC");

// Start output buffer for main content
ob_start();
?>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'updated'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Submission status has been updated successfully.</span>
        </div>
    <?php elseif($_GET['msg'] === 'deleted'): ?>
        <div class="message message-success">
            <i class='bx bx-check-circle'></i>
            <span>Submission has been deleted successfully.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i class='bx bxs-message-detail'></i> Contact Submissions</h3>
        <div class="panel-actions">
            <!-- Filter buttons can go here -->
        </div>
    </div>
    
    <div class="panel-body">
        <!-- Status filter tabs -->
        <div class="filter-tabs">
            <a href="contact-submissions.php" class="filter-tab <?php echo empty($status_filter) ? 'active' : ''; ?>">All</a>
            <a href="contact-submissions.php?status=new" class="filter-tab <?php echo $status_filter === 'new' ? 'active' : ''; ?>">New</a>
            <a href="contact-submissions.php?status=read" class="filter-tab <?php echo $status_filter === 'read' ? 'active' : ''; ?>">Read</a>
            <a href="contact-submissions.php?status=replied" class="filter-tab <?php echo $status_filter === 'replied' ? 'active' : ''; ?>">Replied</a>
            <a href="contact-submissions.php?status=archived" class="filter-tab <?php echo $status_filter === 'archived' ? 'active' : ''; ?>">Archived</a>
        </div>
        
        <div class="table-container">
            <table class="table" id="submissionsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($submissions)): ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="empty-state">
                                <i class='bx bx-envelope-open'></i>
                                <p>No submissions found.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['name']); ?></td>
                            <td><?php echo htmlspecialchars($submission['email']); ?></td>
                            <td><?php echo htmlspecialchars($submission['subject']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($submission['submission_date'])); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $submission['status'] === 'new' ? 'danger' : 
                                        ($submission['status'] === 'read' ? 'primary' : 
                                            ($submission['status'] === 'replied' ? 'success' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($submission['status'] ?: 'new'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-primary btn-sm view-submission" data-id="<?php echo $submission['id']; ?>">
                                        <i class='bx bx-show'></i> View
                                    </button>
                                    <a href="reply.php?id=<?php echo $submission['id']; ?>" class="btn btn-success btn-sm">
                                        <i class='bx bx-reply'></i> Reply
                                    </a>
                                    <a href="contact-submissions.php?action=delete&id=<?php echo $submission['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this submission?')">
                                        <i class='bx bx-trash'></i> Delete
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

<!-- Submission Detail Modal -->
<div id="submission-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class='bx bx-envelope-open'></i> <span id="modal-subject">Message Details</span></h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="submission-meta">
                    <p><strong>From:</strong> <span id="modal-name"></span> (<span id="modal-email"></span>)</p>
                    <p><strong>Phone:</strong> <span id="modal-phone"></span></p>
                    <p><strong>Date:</strong> <span id="modal-date"></span></p>
                </div>
                
                <div class="submission-content">
                    <h4>Message</h4>
                    <div id="modal-message" class="message-content"></div>
                </div>
            </div>
            <div class="modal-footer">
                <div id="status-buttons">
                    <!-- Status buttons will be added dynamically -->
                </div>
                <button type="button" class="btn btn-secondary" id="close-modal">Close</button>
                <a href="#" id="reply-link" class="btn btn-primary">
                    <i class='bx bx-reply'></i> Reply
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .filter-tabs {
        display: flex;
        margin-bottom: 20px;
        overflow-x: auto;
        border-bottom: 1px solid var(--border-color);
    }
    
    .filter-tab {
        padding: 10px 20px;
        color: var(--text-color);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
        white-space: nowrap;
    }
    
    .filter-tab:hover {
        border-bottom-color: #ddd;
    }
    
    .filter-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        font-weight: 500;
    }
    
    .message-content {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin-top: 10px;
        white-space: pre-line;
    }
    
    #status-buttons {
        display: flex;
        gap: 5px;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
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
        // Modal functionality
        const modal = document.getElementById('submission-modal');
        const closeBtn = document.querySelector('.modal-close');
        const closeModalBtn = document.getElementById('close-modal');
        const viewButtons = document.querySelectorAll('.view-submission');
        
        // Open modal when view button is clicked
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                
                // AJAX request to get submission details
                fetch(`ajax/get-submission.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const submission = data.submission;
                            
                            // Populate modal with submission data
                            document.getElementById('modal-subject').textContent = submission.subject;
                            document.getElementById('modal-name').textContent = submission.name;
                            document.getElementById('modal-email').textContent = submission.email;
                            document.getElementById('modal-phone').textContent = submission.phone || 'Not provided';
                            document.getElementById('modal-date').textContent = new Date(submission.submission_date).toLocaleString();
                            document.getElementById('modal-message').textContent = submission.message;
                            
                            // Set reply link
                            document.getElementById('reply-link').href = `reply.php?id=${id}`;
                            
                            // Generate status buttons
                            const statusButtons = document.getElementById('status-buttons');
                            statusButtons.innerHTML = '';
                            
                            if (submission.status !== 'read') {
                                const readBtn = document.createElement('a');
                                readBtn.href = `contact-submissions.php?action=update_status&id=${id}&status=read`;
                                readBtn.className = 'btn btn-info';
                                readBtn.innerHTML = '<i class="bx bx-book-open"></i> Mark as Read';
                                statusButtons.appendChild(readBtn);
                            }
                            
                            if (submission.status !== 'replied') {
                                const repliedBtn = document.createElement('a');
                                repliedBtn.href = `contact-submissions.php?action=update_status&id=${id}&status=replied`;
                                repliedBtn.className = 'btn btn-success';
                                repliedBtn.innerHTML = '<i class="bx bx-check"></i> Mark as Replied';
                                statusButtons.appendChild(repliedBtn);
                            }
                            
                            if (submission.status !== 'archived') {
                                const archivedBtn = document.createElement('a');
                                archivedBtn.href = `contact-submissions.php?action=update_status&id=${id}&status=archived`;
                                archivedBtn.className = 'btn btn-secondary';
                                archivedBtn.innerHTML = '<i class="bx bx-archive"></i> Archive';
                                statusButtons.appendChild(archivedBtn);
                            }
                            
                            // Show modal
                            modal.style.display = 'block';
                            document.body.style.overflow = 'hidden';
                            
                            // Update status to read if it's new
                            if (submission.status === 'new') {
                                fetch(`contact-submissions.php?action=update_status&id=${id}&status=read`, {
                                    method: 'GET'
                                });
                            }
                        } else {
                            alert('Failed to load submission details.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while loading submission details.');
                    });
            });
        });
        
        // Close modal
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        closeBtn.addEventListener('click', closeModal);
        closeModalBtn.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    });
</script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Contact Submissions';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>