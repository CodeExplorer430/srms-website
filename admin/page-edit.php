<?php
/**
 * Page Content Editor
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
include_once '../includes/functions.php';
$db = new Database();

// Initialize variables
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_key = '';
$title = '';
$content = '';
$meta_description = '';
$sections = [];
$errors = [];
$success = false;

// Load page data if editing
if ($id > 0) {
    $page = $db->fetch_row("SELECT * FROM page_content WHERE id = $id");
    if ($page) {
        $page_key = $page['page_key'];
        $title = $page['title'];
        $content = $page['content'];
        $meta_description = $page['meta_description'];
        
        // Load page sections
        $sections = $db->fetch_all("SELECT * FROM page_sections WHERE page_id = $id ORDER BY display_order ASC");
    } else {
        $errors[] = 'Page not found';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_key = isset($_POST['page_key']) ? trim($_POST['page_key']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    
    // Validate inputs
    if (empty($page_key)) {
        $errors[] = 'Page key is required';
    } else {
        // Check if page key is valid (alphanumeric + dash + underscore)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $page_key)) {
            $errors[] = 'Page key can only contain letters, numbers, dashes, and underscores';
        }
        
        // Check if page key already exists (for new pages)
        if ($id === 0) {
            $existingPage = $db->fetch_row("SELECT id FROM page_content WHERE page_key = '{$db->escape($page_key)}'");
            if ($existingPage) {
                $errors[] = 'Page key already exists';
            }
        }
    }
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    // Process if no errors
    if (empty($errors)) {
        $page_key = $db->escape($page_key);
        $title = $db->escape($title);
        $content = $db->escape($content);
        $meta_description = $db->escape($meta_description);
        $user_id = $_SESSION['admin_user_id'];
        
        if ($id > 0) {
            // Update existing page
            $sql = "UPDATE page_content SET 
                    page_key = '$page_key', 
                    title = '$title', 
                    content = '$content', 
                    meta_description = '$meta_description', 
                    last_updated_by = $user_id 
                    WHERE id = $id";
                    
            if ($db->query($sql)) {
                $success = true;
                
                // Process page sections
                if (isset($_POST['section_keys']) && is_array($_POST['section_keys'])) {
                    $section_keys = $_POST['section_keys'];
                    $section_titles = $_POST['section_titles'] ?? [];
                    $section_contents = $_POST['section_contents'] ?? [];
                    $section_orders = $_POST['section_orders'] ?? [];
                    
                    // First, delete any sections that are no longer present
                    $existing_keys_str = "";
                    foreach ($section_keys as $index => $key) {
                        if (!empty($key)) {
                            $key = $db->escape($key);
                            $existing_keys_str .= "'$key',";
                        }
                    }
                    
                    if (!empty($existing_keys_str)) {
                        $existing_keys_str = rtrim($existing_keys_str, ',');
                        $db->query("DELETE FROM page_sections WHERE page_id = $id AND section_key NOT IN ($existing_keys_str)");
                    } else {
                        // If no sections, delete all
                        $db->query("DELETE FROM page_sections WHERE page_id = $id");
                    }
                    
                    // Then update or insert sections
                    foreach ($section_keys as $index => $key) {
                        if (empty($key)) continue;
                        
                        $key = $db->escape($key);
                        $section_title = isset($section_titles[$index]) ? $db->escape($section_titles[$index]) : '';
                        $section_content = isset($section_contents[$index]) ? $db->escape($section_contents[$index]) : '';
                        $section_order = isset($section_orders[$index]) ? (int)$section_orders[$index] : 0;
                        
                        // Check if section exists
                        $existing = $db->fetch_row("SELECT id FROM page_sections WHERE page_id = $id AND section_key = '$key'");
                        
                        if ($existing) {
                            // Update existing section
                            $db->query("UPDATE page_sections SET 
                                        title = '$section_title', 
                                        content = '$section_content', 
                                        display_order = $section_order 
                                        WHERE id = {$existing['id']}");
                        } else {
                            // Insert new section
                            $db->query("INSERT INTO page_sections (page_id, section_key, title, content, display_order) 
                                        VALUES ($id, '$key', '$section_title', '$section_content', $section_order)");
                        }
                    }
                }
            } else {
                $errors[] = 'An error occurred while updating the page';
            }
        } else {
            // Insert new page
            $sql = "INSERT INTO page_content (page_key, title, content, meta_description, last_updated_by) 
                    VALUES ('$page_key', '$title', '$content', '$meta_description', $user_id)";
                    
            if ($db->query($sql)) {
                $id = $db->insert_id();
                $success = true;
                
                // Process page sections
                if (isset($_POST['section_keys']) && is_array($_POST['section_keys'])) {
                    $section_keys = $_POST['section_keys'];
                    $section_titles = $_POST['section_titles'] ?? [];
                    $section_contents = $_POST['section_contents'] ?? [];
                    $section_orders = $_POST['section_orders'] ?? [];
                    
                    foreach ($section_keys as $index => $key) {
                        if (empty($key)) continue;
                        
                        $key = $db->escape($key);
                        $section_title = isset($section_titles[$index]) ? $db->escape($section_titles[$index]) : '';
                        $section_content = isset($section_contents[$index]) ? $db->escape($section_contents[$index]) : '';
                        $section_order = isset($section_orders[$index]) ? (int)$section_orders[$index] : 0;
                        
                        // Insert new section
                        $db->query("INSERT INTO page_sections (page_id, section_key, title, content, display_order) 
                                    VALUES ($id, '$key', '$section_title', '$section_content', $section_order)");
                    }
                }
            } else {
                $errors[] = 'An error occurred while creating the page';
            }
        }
    }
    
    // Reload page sections after save
    if ($id > 0 && $success) {
        $sections = $db->fetch_all("SELECT * FROM page_sections WHERE page_id = $id ORDER BY display_order ASC");
    }
}

// Start output buffer for main content
ob_start();
?>

<?php if($success): ?>
    <div class="message message-success">
        <i class='bx bx-check-circle'></i>
        <span>Page content has been <?php echo $id > 0 ? 'updated' : 'created'; ?> successfully.</span>
        <a href="pages-manage.php">Return to Pages Management</a>
    </div>
<?php endif; ?>

<?php if(!empty($errors)): ?>
    <div class="message message-error">
        <i class='bx bx-error-circle'></i>
        <div>
            <strong>Please correct the following errors:</strong>
            <ul class="mt-2 mb-0">
                <?php foreach($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">
            <i class='bx bx-edit'></i> <?php echo $id > 0 ? 'Edit' : 'Add'; ?> Page Content
        </h3>
    </div>
    
    <div class="panel-body">
        <form action="<?php echo $_SERVER['PHP_SELF'] . ($id > 0 ? '?id=' . $id : ''); ?>" method="post" id="pageForm">
            <div class="form-section">
                <h4 class="section-title">Basic Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="page_key">Page Key <small>(used in URL and as identifier)</small></label>
                            <input type="text" id="page_key" name="page_key" value="<?php echo htmlspecialchars($page_key); ?>" <?php echo $id > 0 ? 'readonly' : ''; ?> required class="form-control">
                            <small class="form-text">Only letters, numbers, dashes, and underscores. Example: "about" for about.php page.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="title">Page Title</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="meta_description">Meta Description <small>(for SEO)</small></label>
                    <textarea id="meta_description" name="meta_description" class="form-control"><?php echo htmlspecialchars($meta_description); ?></textarea>
                    <small class="form-text">Brief description of the page for search engines. Recommended length: 150-160 characters.</small>
                </div>
            </div>
            
            <div class="form-section">
                <h4 class="section-title">Main Content</h4>
                <div class="form-group">
                    <label for="content">Page Content</label>
                    <textarea id="content" name="content" class="form-control rich-editor" rows="10"><?php echo htmlspecialchars($content); ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h4 class="section-title">Page Sections</h4>
                <p class="section-description">
                    Sections allow you to divide your page into manageable parts that can be referenced individually in the template.
                </p>
                
                <div id="sections-container">
                    <?php if(!empty($sections)): ?>
                        <?php foreach($sections as $index => $section): ?>
                            <div class="section-item">
                                <div class="section-header">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label>Section Key</label>
                                                <input type="text" name="section_keys[]" value="<?php echo htmlspecialchars($section['section_key']); ?>" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label>Section Title</label>
                                                <input type="text" name="section_titles[]" value="<?php echo htmlspecialchars($section['title']); ?>" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Order</label>
                                                <input type="number" name="section_orders[]" value="<?php echo $section['display_order']; ?>" class="form-control" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Section Content</label>
                                    <textarea name="section_contents[]" class="form-control rich-editor" rows="6"><?php echo htmlspecialchars($section['content']); ?></textarea>
                                </div>
                                <div class="section-actions">
                                    <button type="button" class="btn btn-danger btn-sm remove-section">
                                        <i class='bx bx-trash'></i> Remove Section
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="section-actions">
                    <button type="button" class="btn btn-secondary" id="add-section">
                        <i class='bx bx-plus'></i> Add Section
                    </button>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="pages-manage.php" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Save Page
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Section Template (hidden) -->
<template id="section-template">
    <div class="section-item">
        <div class="section-header">
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Section Key</label>
                        <input type="text" name="section_keys[]" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Section Title</label>
                        <input type="text" name="section_titles[]" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Order</label>
                        <input type="number" name="section_orders[]" value="0" class="form-control" min="0">
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Section Content</label>
            <textarea name="section_contents[]" class="form-control rich-editor" rows="6"></textarea>
        </div>
        <div class="section-actions">
            <button type="button" class="btn btn-danger btn-sm remove-section">
                <i class='bx bx-trash'></i> Remove Section
            </button>
        </div>
    </div>
</template>

<style>
    .section-item {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .section-header {
        margin-bottom: 15px;
    }
    
    .section-actions {
        margin-top: 15px;
        display: flex;
        justify-content: flex-end;
    }
    
    .section-description {
        margin-bottom: 20px;
        color: #6c757d;
    }
    
    /* Editor Styling */
    .rich-editor {
        min-height: 200px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add Section
        const addSectionBtn = document.getElementById('add-section');
        const sectionsContainer = document.getElementById('sections-container');
        const sectionTemplate = document.getElementById('section-template');
        
        if (addSectionBtn && sectionsContainer && sectionTemplate) {
            addSectionBtn.addEventListener('click', function() {
                const newSection = document.importNode(sectionTemplate.content, true);
                sectionsContainer.appendChild(newSection);
                
                // Initialize rich editor for new section
                const newEditor = sectionsContainer.querySelector('.section-item:last-child .rich-editor');
                if (newEditor && typeof tinymce !== 'undefined') {
                    initRichEditor(newEditor);
                }
            });
        }
        
        // Remove Section
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-section') || 
                e.target.parentElement.classList.contains('remove-section')) {
                
                const btn = e.target.classList.contains('remove-section') ? 
                            e.target : e.target.parentElement;
                const section = btn.closest('.section-item');
                
                if (confirm('Are you sure you want to remove this section?')) {
                    section.remove();
                }
            }
        });
        
        // Convert page_key to lowercase and replace spaces with dashes
        const pageKeyInput = document.getElementById('page_key');
        if (pageKeyInput) {
            pageKeyInput.addEventListener('input', function() {
                this.value = this.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
            });
        }
        
        // Initialize Rich Text Editor
        function initRichEditor(element) {
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: element,
                    height: 300,
                    menubar: false,
                    plugins: [
                        'advlist autolink lists link image charmap print preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime media table paste code help wordcount'
                    ],
                    toolbar: 'undo redo | formatselect | ' +
                    'bold italic backcolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | help',
                    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
                });
            }
        }
        
        // Initialize all rich editors on page load
        const richEditors = document.querySelectorAll('.rich-editor');
        if (richEditors.length && typeof tinymce !== 'undefined') {
            richEditors.forEach(editor => {
                initRichEditor(editor);
            });
        }
    });
</script>

<!-- TinyMCE Rich Text Editor -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>

<?php
// Get content from buffer
$content = ob_get_clean();

// Set page title and specific CSS/JS files
$page_title = ($id > 0 ? 'Edit' : 'Add') . ' Page Content';
$page_specific_css = [];
$page_specific_js = [];

// Include the layout
include 'layout.php';
?>