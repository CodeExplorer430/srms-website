<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Include database connection
include_once '../includes/config.php';
include_once '../includes/db.php';
include_once '../includes/functions.php';

$db = new Database();

// Initialize variables
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = '';
$slug = '';
$content = '';
$summary = '';
$image = '';
$published_date = date('Y-m-d H:i:s');
$status = 'draft';
$featured = 0;
$errors = [];
$warnings = [];
$success = false;
$upload_result = false;

// Load article data if editing
if($id > 0) {
    $article = $db->fetch_row("SELECT * FROM news WHERE id = $id");
    if($article) {
        $title = $article['title'];
        $slug = $article['slug'];
        $content = $article['content'];
        $summary = $article['summary'];
        $image = $article['image'];
        $published_date = $article['published_date'];
        $status = $article['status'];
        $featured = $article['featured'];
    } else {
        $errors[] = 'Article not found';
    }
}


// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $summary = isset($_POST['summary']) ? trim($_POST['summary']) : '';
    $image = isset($_POST['image']) ? trim($_POST['image']) : '';
    $published_date = isset($_POST['published_date']) ? trim($_POST['published_date']) : date('Y-m-d H:i:s');
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'draft';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate inputs
    if(empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if(empty($slug)) {
        // Generate slug from title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }
    
    if(empty($content)) {
        $errors[] = 'Content is required';
    }

   // Determine proper destination folder based on context
   $destination_folder = 'news';
   if (strpos(strtolower($title), 'event') !== false || 
       strpos(strtolower($summary), 'event') !== false) {
       $destination_folder = 'events';
   }
   
   // Process image upload first
   if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] != UPLOAD_ERR_NO_FILE) {
       $upload_result = upload_image($_FILES['image_upload'], $destination_folder);
       if ($upload_result) {
           // Use the new image path immediately
           $image = $upload_result;
           
           // Ensure path is properly formatted for web display
           $image = str_replace('\\', '/', $image);
           
           // Make sure path starts with a slash
           if (strpos($image, '/') !== 0) {
               $image = '/' . $image;
           }
           
           // Add a small delay to ensure file is written to disk
           usleep(500000); // 0.5 second delay
           
           // Flush file cache to ensure file existence checks work
           clearstatcache(true, $_SERVER['DOCUMENT_ROOT'] . $image);
           
           // Log the successful upload
           error_log("File uploaded successfully to: " . $image);
       } else {
           $errors[] = 'Image upload failed. Please check file type and size.';
       }
   }

   // Normalize the manually entered image path if no upload
   if (empty($upload_result) && isset($_POST['image'])) {
       $image = normalize_image_path($_POST['image']);
   }
   
   // Verify image path exists
   if (!empty($image) && !verify_image_exists($image)) {
       // Add warning but don't prevent saving
       $warnings[] = 'The specified image path could not be verified. Please check that the file exists.';
   }

    // Only use the text input if no file was uploaded
    if (!$upload_result) {
        $image = isset($_POST['image']) ? trim($_POST['image']) : '';
    }

    // Now standardize the path - this preserves the upload result if it exists
    $image = standardize_image_path($image);

    // Add after successful file upload in news-edit.php
    if ($upload_result) {
        // Verify file exists immediately after upload
        $full_upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_result;
        if (!file_exists($full_upload_path)) {
            error_log("File upload indicated success but file not found at: " . $full_upload_path);
            // Try flushing file cache
            clearstatcache(true, $full_upload_path);
        }
    }
    
    // Convert legacy paths to new asset structure
    if (!empty($image) && strpos($image, '/images/') === 0) {
        // Determine appropriate directory based on filename pattern
        if (strpos($image, 'School_Events') !== false) {
            $suggested_path = '/assets/images/events/' . basename($image);
            $image = $suggested_path;
        } else if (strpos($image, 'School_Announcement') !== false) {
            $suggested_path = '/assets/images/news/' . basename($image);
            $image = $suggested_path;
        }
    }

     // Make sure image path starts with a slash if it's not empty
     if (!empty($image) && strpos($image, '/') !== 0) {
        $image = '/' . $image;
    }

    // Normalize path: remove double slashes and ensure proper directory structure
    $image = preg_replace('#/+#', '/', $image);

     // Verify the image path is within the allowed directories
     $valid_image_path = false;
     $allowed_paths = [
        '/assets/images/news/', 
        '/assets/images/events/', 
        '/assets/images/promotional/',
        '/assets/images/campus/',
        '/assets/images/facilities/',
        '/images/'  // For backward compatibility with old paths
    ];
     
     foreach($allowed_paths as $allowed_path) {
         if (strpos($image, $allowed_path) === 0) {
             $valid_image_path = true;
             break;
         }
     }
     
     // If path is invalid but not empty, provide more guidance
     if (!empty($image) && !$valid_image_path) {
         // Suggest correction to proper path
         $suggested_path = '/assets/images/news/' . basename($image);
         $errors[] = 'Invalid image path. Images should be located in one of the allowed directories. Did you mean: "' . $suggested_path . '"?';
     }
     
     // Verify file exists (if path is valid and not empty)
    if (!empty($image) && $valid_image_path) {
        if (!file_exists_with_alternatives($image)) {
            $errors[] = 'Image file not found at "' . $image . '". Please check the path or upload the image first.';
        }
    }

     $image = $db->escape($image);
    
    // Process if no errors
    if(empty($errors)) {
        $title = $db->escape($title);
        $slug = $db->escape($slug);
        $content = $db->escape($content);
        $summary = $db->escape($summary);
        $image = $db->escape($image);
        $published_date = $db->escape($published_date);
        $author_id = $_SESSION['admin_user_id'];
        
        if($id > 0) {
            // Update existing article
            $sql = "UPDATE news SET 
                    title = '$title', 
                    slug = '$slug', 
                    content = '$content', 
                    summary = '$summary', 
                    image = '$image', 
                    published_date = '$published_date', 
                    status = '$status', 
                    featured = $featured 
                    WHERE id = $id";
                    
            if($db->query($sql)) {
                $success = true;
            } else {
                $errors[] = 'An error occurred while updating the article';
            }
        } else {
            // Insert new article
            $sql = "INSERT INTO news (title, slug, content, summary, image, published_date, author_id, status, featured) 
                    VALUES ('$title', '$slug', '$content', '$summary', '$image', '$published_date', $author_id, '$status', $featured)";
                    
            if($db->query($sql)) {
                $id = $db->insert_id();
                $success = true;
            } else {
                $errors[] = 'An error occurred while creating the article';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Edit' : 'Add'; ?> News Article | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <!-- Load image-related CSS first -->
    <link rel="stylesheet" href="../assets/css/image-selector.css">
    <link rel="stylesheet" href="../assets/css/media-library.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f3f3;
            margin: 0;
            padding: 0;
        }
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #003366;
            color: #fff;
            padding: 20px 0;
        }
        .sidebar .logo {
            text-align: center;
            padding: 10px 0 20px;
        }
        .sidebar .logo img {
            width: 80px;
            border-radius: 50%;
        }
        .sidebar .menu {
            margin-top: 20px;
        }
        .sidebar .menu-item {
            padding: 10px 20px;
            border-left: 4px solid transparent;
        }
        .sidebar .menu-item:hover, .sidebar .menu-item.active {
            background-color: rgba(255,255,255,0.1);
            border-left-color: #3C91E6;
        }
        .sidebar .menu-item a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .sidebar .menu-item i {
            margin-right: 10px;
            font-size: 20px;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-info .name {
            margin-right: 15px;
        }
        .logout-btn {
            background-color: transparent;
            color: #e74c3c;
            border: 1px solid #e74c3c;
            padding: 5px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }
        .logout-btn:hover {
            background-color: #e74c3c;
            color: white;
        }
        
        /* Form styles */
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="datetime-local"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }
        .form-group textarea {
            height: 200px;
            resize: vertical;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            display: inline-block;
            margin-bottom: 0;
            font-weight: 500;
            cursor: pointer;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .save-btn {
            background-color: #3C91E6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .cancel-btn {
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .warning-message {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .image-input-group {
            display: flex;
            gap: 10px;
        }

        .image-input-group input {
            flex: 1;
        }

        .open-media-library {
            background-color: #3C91E6;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .image-preview-container {
            margin-top: 15px;
            margin-bottom: 20px;
            position: relative;
        }

        .image-preview {
            width: 100%;
            height: 200px;
            border: 1px dashed #ced4da;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }

        .preview-placeholder i {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            display: block;
        }

        .image-source-indicator {
            text-align: right;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">
                <img src="../assets/images/branding/logo-primary.png" alt="St. Raphaela Mary School Logo">
                <h3>SRMS Admin</h3>
            </div>
            
            <div class="menu">
                <div class="menu-item">
                    <a href="index.php">
                        <i class='bx bxs-dashboard'></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="menu-item active">
                    <a href="news-manage.php">
                        <i class='bx bxs-news'></i>
                        <span>News</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="contact-submissions.php">
                        <i class='bx bxs-message-detail'></i>
                        <span>Contact Submissions</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="users.php">
                        <i class='bx bxs-user'></i>
                        <span>Users</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a href="settings.php">
                        <i class='bx bxs-cog'></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h2><?php echo $id > 0 ? 'Edit' : 'Add'; ?> News Article</h2>
                <div class="user-info">
                    <div class="name">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <?php if($success): ?>
            <div class="success-message">
                News article has been successfully <?php echo $id > 0 ? 'updated' : 'created'; ?>.
                <a href="news-manage.php">Return to News Management</a>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if(!empty($warnings)): ?>
            <div class="warning-message">
                <ul>
                    <?php foreach($warnings as $warning): ?>
                    <li><?php echo $warning; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="<?php echo $_SERVER['PHP_SELF'] . ($id > 0 ? '?id=' . $id : ''); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug (URL-friendly version of title)</label>
                        <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="summary">Summary</label>
                        <textarea id="summary" name="summary"><?php echo htmlspecialchars($summary); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" required><?php echo htmlspecialchars($content); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Image Path</label>
                        <div class="image-input-group">
                            <input type="text" id="image" name="image" value="<?php echo htmlspecialchars($image); ?>">
                            <button type="button" class="open-media-library" data-target="image">Browse Media Library</button>
                        </div>
                        <small style="color:#666;">Enter image path or use the media library to select an image</small>
                        
                        <div id="unified-image-preview" class="image-preview-container">
                            <div class="image-preview">
                                <div id="preview-placeholder" class="preview-placeholder">
                                    <i class='bx bx-image'></i>
                                    <span>No image selected</span>
                                    <small>Select from media library or upload a new image</small>
                                </div>
                                <img src="<?php echo !empty($image) ? htmlspecialchars($image) : ''; ?>" 
                                    alt="Preview" 
                                    id="preview-image" 
                                    style="<?php echo empty($image) ? 'display: none;' : ''; ?>">
                            </div>
                            <div id="source-indicator" class="image-source-indicator"></div>
                        </div>

                        <div class="form-group">
                            <label for="image_upload">Upload New Image</label>
                            <input type="file" id="image_upload" name="image_upload" accept="image/jpeg, image/png, image/gif">
                            <small style="color:#666;">Max file size: 2MB. Accepted formats: JPEG, PNG, GIF</small>
                        </div>
                    </div>
                    
                    <?php if (!empty($image)): 
                        // More comprehensive image verification
                        $server_root = $_SERVER['DOCUMENT_ROOT'];
                        $image_full_path = $server_root . $image;
                        $image_exists = file_exists($image_full_path);
                        $alt_path = $server_root . DIRECTORY_SEPARATOR . ltrim($image, '/');
                        $alt_exists = file_exists($alt_path);
                        $best_match = find_best_matching_image($image);
                    ?>
                        <div style="margin-top:10px; padding:10px; border-radius:4px; background-color:<?php echo ($image_exists || $alt_exists) ? '#d4edda' : '#f8d7da'; ?>; color:<?php echo ($image_exists || $alt_exists) ? '#155724' : '#721c24'; ?>;">
                            <?php if ($image_exists || $alt_exists): ?>
                                <i class='bx bx-check-circle'></i> Image file exists at this path
                            <?php elseif ($best_match): ?>
                                <i class='bx bx-check-circle'></i> Similar image found at: <?php echo htmlspecialchars($best_match); ?>
                            <?php else: ?>
                                <i class='bx bx-x-circle'></i> Image file not found at this path
                                <div style="margin-top:5px; font-size:12px;">
                                    Tried: <?php echo htmlspecialchars($image_full_path); ?><br>
                                    And: <?php echo htmlspecialchars($alt_path); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="published_date">Published Date</label>
                        <input type="datetime-local" id="published_date" name="published_date" value="<?php echo date('Y-m-d\TH:i', strtotime($published_date)); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="featured" name="featured" value="1" <?php echo $featured ? 'checked' : ''; ?>>
                            <label for="featured">Featured Article</label>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <a href="news-manage.php" class="cancel-btn">Cancel</a>
                        <button type="submit" class="save-btn">Save Article</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
// Disable any preview conflicts
$disable_media_library_preview = true;

// Include the media library
include_once '../admin/includes/media-library.php';
render_media_library('image');
?>

<!-- Load scripts in the correct order -->
<script src="../assets/js/media-library.js"></script>
<script src="../assets/js/unified-image-uploader.js"></script>

<script>
// Connect the unified image uploader with the media library
document.addEventListener('DOMContentLoaded', function() {
    console.log('Connecting unified uploader with media library...');
    
    // Initialize the image from existing path if any
    const imageInput = document.getElementById('image');
    if (imageInput && imageInput.value.trim()) {
        setTimeout(function() {
            if (window.UnifiedImageUploader) {
                window.UnifiedImageUploader.selectMediaItem(imageInput.value);
            }
        }, 200);
    }
    
    // Connect insert button from media library to unified uploader
    const mediaModal = document.getElementById('media-library-modal');
    if (mediaModal) {
        const insertButton = mediaModal.querySelector('.insert-media');
        if (insertButton) {
            insertButton.addEventListener('click', function() {
                try {
                    const selectedItem = mediaModal.querySelector('.media-item.selected');
                    if (selectedItem) {
                        const path = selectedItem.getAttribute('data-path');
                        if (window.UnifiedImageUploader && path) {
                            window.UnifiedImageUploader.selectMediaItem(path);
                        }
                    }
                } catch (error) {
                    console.error('Error connecting media library to unified uploader:', error);
                }
            });
        }
    }
});
</script>
</body>
</html>