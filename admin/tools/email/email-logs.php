<?php
/**
 * Email Logs Management
 * View, filter, and manage email sending history
 */

// Start session and check login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Get the site root directory more reliably
$current_dir = dirname(__FILE__);
$tools_dir = dirname($current_dir);
$admin_dir = dirname($tools_dir);
$site_root = dirname($admin_dir);

// Include environment file to get constants
if (file_exists($site_root . DIRECTORY_SEPARATOR . 'environment.php')) {
    require_once $site_root . DIRECTORY_SEPARATOR . 'environment.php';
} else {
    die('Environment file not found. Please make sure environment.php exists in the site root directory.');
}

// Include necessary files using absolute paths
include_once $site_root . DS . 'includes' . DS . 'config.php';
include_once $site_root . DS . 'includes' . DS . 'db.php';
include_once $site_root . DS . 'includes' . DS . 'functions.php';

// Initialize database connection
$db = new Database();

// Check if email_logs table exists
$tables = $db->query("SHOW TABLES LIKE 'email_logs'");
$logs_table_exists = ($tables && $tables->num_rows > 0);

// Initialize variables
$logs = [];
$total_logs = 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_email = isset($_GET['email']) ? $_GET['email'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Process log table creation if it doesn't exist
if (isset($_POST['action']) && $_POST['action'] === 'create_logs_table') {
    if (!$logs_table_exists) {
        $create_table_sql = "CREATE TABLE email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message_preview TEXT,
            status ENUM('sent', 'failed') NOT NULL,
            error TEXT,
            sent_at DATETIME NOT NULL
        )";
        
        if ($db->query($create_table_sql)) {
            $success_message = 'Email logs table created successfully.';
            $logs_table_exists = true;
        } else {
            $error_message = 'Failed to create email logs table.';
        }
    } else {
        $info_message = 'Email logs table already exists.';
    }
}

// Process log clearing if requested
if (isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    if ($logs_table_exists) {
        if ($db->query("TRUNCATE TABLE email_logs")) {
            $success_message = 'Email logs have been cleared successfully.';
        } else {
            $error_message = 'Failed to clear email logs.';
        }
    }
}

// Process log deletion if requested
if (isset($_POST['action']) && $_POST['action'] === 'delete_log' && isset($_POST['log_id'])) {
    $log_id = intval($_POST['log_id']);
    
    if ($db->query("DELETE FROM email_logs WHERE id = $log_id")) {
        $success_message = 'Log entry deleted successfully.';
    } else {
        $error_message = 'Failed to delete log entry.';
    }
}

// Get logs with filtering and pagination if table exists
if ($logs_table_exists) {
    // Build query parts
    $where_clauses = [];
    
    if (!empty($filter_status)) {
        $status = $db->escape($filter_status);
        $where_clauses[] = "status = '$status'";
    }
    
    if (!empty($filter_email)) {
        $email = $db->escape($filter_email);
        $where_clauses[] = "recipient LIKE '%$email%'";
    }
    
    if (!empty($search_term)) {
        $search = $db->escape($search_term);
        $where_clauses[] = "(recipient LIKE '%$search%' OR subject LIKE '%$search%' OR message_preview LIKE '%$search%')";
    }
    
    $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM email_logs $where_sql";
    $count_result = $db->fetch_row($count_query);
    $total_logs = $count_result ? $count_result['total'] : 0;
    
    // Get logs with pagination
    $logs_query = "SELECT * FROM email_logs $where_sql ORDER BY sent_at DESC LIMIT $offset, $per_page";
    $logs = $db->fetch_all($logs_query);
    
    // Get stats for summary
    $stats = [
        'total' => $db->fetch_row("SELECT COUNT(*) as count FROM email_logs")['count'],
        'sent' => $db->fetch_row("SELECT COUNT(*) as count FROM email_logs WHERE status = 'sent'")['count'],
        'failed' => $db->fetch_row("SELECT COUNT(*) as count FROM email_logs WHERE status = 'failed'")['count']
    ];
}

// Calculate pagination
$total_pages = ceil($total_logs / $per_page);
$prev_page = $page > 1 ? $page - 1 : null;
$next_page = $page < $total_pages ? $page + 1 : null;

// Build the pagination URL
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// Format the filter URL
function buildFilterUrl($params_to_update) {
    $params = $_GET;
    
    // Remove page parameter when filters change
    if (isset($params['page'])) {
        unset($params['page']);
    }
    
    // Update with new parameters
    foreach ($params_to_update as $key => $value) {
        if ($value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    
    return '?' . http_build_query($params);
}

// Start output buffer for main content
ob_start();
?>

<div class="email-logs">
    <div class="header-banner">
        <div class="banner-content">
            <h2><i class='bx bx-list-ul'></i> Email Logs</h2>
            <p>View and manage email sending history</p>
        </div>
        <div class="banner-actions">
            <a href="email-tools.php" class="btn btn-light">
                <i class='bx bx-arrow-back'></i> Back to Email Tools
            </a>
        </div>
    </div>
    
    <?php if (isset($success_message)): ?>
    <div class="alerts">
        <div class="alert alert-success">
            <i class='bx bx-check-circle'></i>
            <div class="alert-content"><?php echo htmlspecialchars($success_message); ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="alerts">
        <div class="alert alert-danger">
            <i class='bx bx-error-circle'></i>
            <div class="alert-content"><?php echo htmlspecialchars($error_message); ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($info_message)): ?>
    <div class="alerts">
        <div class="alert alert-info">
            <i class='bx bx-info-circle'></i>
            <div class="alert-content"><?php echo htmlspecialchars($info_message); ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Logs Container -->
    <div class="logs-container">
        <?php if (!$logs_table_exists): ?>
        <!-- Table not found message -->
        <div class="logs-setup">
            <div class="setup-message">
                <i class='bx bx-table'></i>
                <h4>Email Logs Table Not Found</h4>
                <p>To enable email logging, you need to create the email_logs table in your database.</p>
                <form method="post" action="">
                    <input type="hidden" name="action" value="create_logs_table">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-plus'></i> Create Email Logs Table
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Logs Stats & Controls -->
        <div class="logs-header">
            <div class="logs-stats">
                <div class="stat-item">
                    <div class="stat-label">Total Logs</div>
                    <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Sent</div>
                    <div class="stat-value stat-success"><?php echo number_format($stats['sent']); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Failed</div>
                    <div class="stat-value stat-error"><?php echo number_format($stats['failed']); ?></div>
                </div>
            </div>
            
            <div class="logs-actions">
                <form method="post" action="" onsubmit="return confirm('Are you sure you want to clear all logs? This action cannot be undone.');">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-danger">
                        <i class='bx bx-trash'></i> Clear All Logs
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Logs Filters -->
        <div class="logs-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter-status">Status:</label>
                    <select id="filter-status" onchange="applyFilter('status', this.value)">
                        <option value="">All</option>
                        <option value="sent" <?php echo $filter_status === 'sent' ? 'selected' : ''; ?>>Sent</option>
                        <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter-email">Recipient:</label>
                    <input type="text" id="filter-email" value="<?php echo htmlspecialchars($filter_email); ?>" 
                           placeholder="Filter by email" onchange="applyFilter('email', this.value)">
                </div>
                
                <div class="filter-group search-group">
                    <label for="search-term">Search:</label>
                    <div class="search-input">
                        <input type="text" id="search-term" value="<?php echo htmlspecialchars($search_term); ?>" 
                               placeholder="Search logs...">
                        <button type="button" onclick="applyFilter('search', document.getElementById('search-term').value)">
                            <i class='bx bx-search'></i>
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($filter_status) || !empty($filter_email) || !empty($search_term)): ?>
                <div class="filter-group">
                    <a href="email-logs.php" class="btn btn-light btn-sm btn-clear-filters">
                        <i class='bx bx-x'></i> Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($logs)): ?>
        <!-- No logs found message -->
        <div class="no-logs">
            <i class='bx bx-envelope-open'></i>
            <p>No email logs found with the current filters.</p>
        </div>
        <?php else: ?>
        
        <!-- Logs Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="160">Date & Time</th>
                        <th width="200">Recipient</th>
                        <th>Subject</th>
                        <th width="80">Status</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="log-entry <?php echo $log['status'] === 'sent' ? 'success' : 'error'; ?>" data-id="<?php echo $log['id']; ?>">
                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['sent_at'])); ?></td>
                        <td><?php echo htmlspecialchars($log['recipient']); ?></td>
                        <td><?php echo htmlspecialchars($log['subject']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $log['status'] === 'sent' ? 'status-success' : 'status-error'; ?>">
                                <?php echo ucfirst($log['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="entry-actions">
                                <button type="button" class="btn btn-light btn-sm btn-view" onclick="viewLogDetails(<?php echo $log['id']; ?>)">
                                    <i class='bx bx-show'></i>
                                </button>
                                <form method="post" action="" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this log entry?');">
                                    <input type="hidden" name="action" value="delete_log">
                                    <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="log-details" id="details-<?php echo $log['id']; ?>">
                        <td colspan="5">
                            <div class="details-content">
                                <div class="details-section">
                                    <h5>Message Preview</h5>
                                    <div class="message-preview"><?php echo nl2br(htmlspecialchars($log['message_preview'])); ?></div>
                                </div>
                                
                                <?php if (!empty($log['error'])): ?>
                                <div class="details-section">
                                    <h5>Error Details</h5>
                                    <div class="error-details"><?php echo htmlspecialchars($log['error']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="details-actions">
                                    <button type="button" class="btn btn-light btn-sm" onclick="hideLogDetails(<?php echo $log['id']; ?>)">
                                        <i class='bx bx-chevron-up'></i> Hide Details
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <div class="pagination-info">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_logs); ?> of <?php echo $total_logs; ?> logs
            </div>
            <div class="pagination-links">
                <?php if ($prev_page): ?>
                <a href="<?php echo buildPaginationUrl($prev_page); ?>" class="pagination-btn">
                    <i class='bx bx-chevron-left'></i> Previous
                </a>
                <?php endif; ?>
                
                <div class="pagination-pages">
                    <?php
                    // Calculate range of pages to show
                    $range = 2; // Show 2 pages before and after current page
                    $start_page = max(1, $page - $range);
                    $end_page = min($total_pages, $page + $range);
                    
                    // Always show first page
                    if ($start_page > 1) {
                        echo '<a href="' . buildPaginationUrl(1) . '" class="page-num' . (1 == $page ? ' active' : '') . '">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="page-ellipsis">...</span>';
                        }
                    }
                    
                    // Show pages in range
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        echo '<a href="' . buildPaginationUrl($i) . '" class="page-num' . ($i == $page ? ' active' : '') . '">' . $i . '</a>';
                    }
                    
                    // Always show last page
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="page-ellipsis">...</span>';
                        }
                        echo '<a href="' . buildPaginationUrl($total_pages) . '" class="page-num' . ($total_pages == $page ? ' active' : '') . '">' . $total_pages . '</a>';
                    }
                    ?>
                </div>
                
                <?php if ($next_page): ?>
                <a href="<?php echo buildPaginationUrl($next_page); ?>" class="pagination-btn">
                    Next <i class='bx bx-chevron-right'></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Apply filter
function applyFilter(key, value) {
    window.location.href = <?php echo json_encode(buildFilterUrl([])); ?> + '&' + key + '=' + encodeURIComponent(value);
}

// View log details
function viewLogDetails(logId) {
    document.getElementById('details-' + logId).classList.add('visible');
}

// Hide log details
function hideLogDetails(logId) {
    document.getElementById('details-' + logId).classList.remove('visible');
}
</script>

<style>
.email-logs {
    max-width: 1200px;
    margin: 0 auto;
}

.header-banner {
    background: linear-gradient(120deg, #3C91E6, #0a3060);
    border-radius: 10px;
    color: white;
    padding: 30px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.banner-content h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
    display: flex;
    align-items: center;
}

.banner-content h2 i {
    margin-right: 10px;
    font-size: 32px;
}

.banner-content p {
    margin: 0;
    opacity: 0.8;
    font-size: 16px;
}

.logs-container {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 30px;
}

.logs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.logs-stats {
    display: flex;
    gap: 30px;
}

.stat-item {
    text-align: center;
}

.stat-label {
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #0a3060;
}

.stat-success {
    color: #28a745;
}

.stat-error {
    color: #dc3545;
}

.logs-actions {
    display: flex;
    gap: 10px;
}

.logs-filters {
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 12px;
    margin-bottom: 5px;
    color: #6c757d;
}

.filter-group select,
.filter-group input {
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    min-width: 150px;
}

.search-group {
    flex-grow: 1;
}

.search-input {
    display: flex;
}

.search-input input {
    flex-grow: 1;
    border-right: none;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.search-input button {
    padding: 8px 12px;
    background-color: #6c757d;
    color: white;
    border: 1px solid #6c757d;
    border-top-right-radius: 4px;
    border-bottom-right-radius: 4px;
    cursor: pointer;
}

.btn-clear-filters {
    margin-top: 23px;
}

.logs-setup {
    display: flex;
    justify-content: center;
    padding: 60px 0;
}

.setup-message {
    text-align: center;
    max-width: 500px;
}

.setup-message i {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 20px;
    opacity: 0.5;
}

.setup-message h4 {
    margin: 0 0 10px 0;
    color: #0a3060;
}

.setup-message p {
    margin-bottom: 20px;
    color: #6c757d;
}

.no-logs {
    padding: 60px 0;
    text-align: center;
    color: #6c757d;
}

.no-logs i {
    font-size: 48px;
    opacity: 0.5;
    margin-bottom: 10px;
}

.no-logs p {
    margin: 0;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.data-table th {
    background-color: #f8f9fa;
    font-weight: 500;
    color: #0a3060;
}

.log-entry.success td {
    background-color: rgba(40, 167, 69, 0.05);
}

.log-entry.error td {
    background-color: rgba(220, 53, 69, 0.05);
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
}

.status-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-error {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.entry-actions {
    display: flex;
    gap: 5px;
}

.btn {
    padding: 8px 15px;
    border-radius: 5px;
    border: none;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-primary {
    background-color: #3C91E6;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-light {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.btn-view i {
    font-size: 14px;
}

.delete-form {
    display: inline;
}

.log-details {
    display: none;
}

.log-details.visible {
    display: table-row;
}

.details-content {
    padding: 15px;
    background-color: #f8f9fa;
}

.details-section {
    margin-bottom: 15px;
}

.details-section h5 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #0a3060;
}

.message-preview {
    background-color: white;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    font-size: 13px;
    max-height: 150px;
    overflow-y: auto;
}

.error-details {
    background-color: rgba(220, 53, 69, 0.05);
    padding: 10px;
    border-radius: 4px;
    border: 1px solid rgba(220, 53, 69, 0.2);
    color: #dc3545;
    font-size: 13px;
}

.details-actions {
    text-align: right;
}

.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-top: 1px solid #dee2e6;
}

.pagination-info {
    color: #6c757d;
    font-size: 14px;
}

.pagination-links {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pagination-btn {
    padding: 5px 10px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    color: #495057;
    text-decoration: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.pagination-pages {
    display: flex;
    align-items: center;
    gap: 5px;
}

.page-num {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    color: #495057;
    text-decoration: none;
    font-size: 14px;
}

.page-num.active {
    background-color: #3C91E6;
    border-color: #3C91E6;
    color: white;
}

.page-ellipsis {
    color: #6c757d;
}

.alert {
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
    padding: 15px;
    border-radius: 8px;
}

.alerts {
    margin-bottom: 20px;
}

.alert i {
    font-size: 24px;
    margin-right: 15px;
}

.alert-content {
    flex-grow: 1;
}

.alert-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.2);
}

.alert-info {
    background-color: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
    border: 1px solid rgba(23, 162, 184, 0.2);
}

@media (max-width: 768px) {
    .header-banner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .banner-actions {
        margin-top: 15px;
    }
    
    .logs-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .logs-stats {
        width: 100%;
        justify-content: space-between;
    }
    
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-group select,
    .filter-group input {
        width: 100%;
        min-width: 0;
    }
    
    .btn-clear-filters {
        margin-top: 0;
    }
    
    .pagination {
        flex-direction: column;
        gap: 10px;
    }
    
    .pagination-links {
        width: 100%;
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = 'Email Logs';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout with absolute path
include_once $site_root . DS . 'admin' . DS . 'layout.php';
?>