<?php
$page_title = 'News & Updates';
$page_description = 'Stay updated with the latest news, events, and announcements from St. Raphaela Mary School.';

include 'includes/header.php';

// Function to ensure image paths are properly formatted for display
function prepare_image_for_display($image_path) {
    // If path is empty, return a default image
    if (empty($image_path)) {
        return '/assets/images/news/news-placeholder.jpg';
    }
    
    // Normalize path format
    $image_path = '/' . ltrim($image_path, '/');
    
    // Verify the image exists (use the functions already in your code)
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    $full_path = $server_root . $image_path;
    
    if (file_exists($full_path)) {
        return $image_path;
    }
    
    // Try alternative paths if the direct path doesn't work
    $alt_paths = [
        str_replace('/assets/images/', '/images/', $image_path),
        str_replace('/images/', '/assets/images/', $image_path)
    ];
    
    foreach ($alt_paths as $alt_path) {
        if (file_exists($server_root . $alt_path)) {
            return $alt_path;
        }
    }
    
    // Return default if no valid path is found
    return '/assets/images/news/news-placeholder.jpg';
}

// Fetch news articles
$db = db_connect();
$news_articles = $db->fetch_all("SELECT * FROM news WHERE status = 'published' ORDER BY published_date DESC");

// Pagination settings
$items_per_page = 5;
$total_items = count($news_articles);
$total_pages = ceil($total_items / $items_per_page);

// Get current page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, min($current_page, $total_pages));

// Get subset of articles for current page
$offset = ($current_page - 1) * $items_per_page;
$paged_articles = array_slice($news_articles, $offset, $items_per_page);

// Get the 6 most recent articles for the sidebar
$recent_posts = array_slice($news_articles, 0, 6);
?>

<section class="main-title">
    <div class="container">
        <h2>SRMS NEWS UPDATES</h2>
        <div class="underline"></div>
    </div>
</section>

<main class="news-container">
    <section class="sec1">
        <div class="container">
            <?php if (empty($news_articles)): ?>
                <div class="no-news-message">
                    <p>No news articles available at this time.</p>
                </div>
            <?php else: ?>
                <div class="articles-wrapper">
                    <?php foreach ($paged_articles as $article): ?>
                        <div class="news-post">
                            <div class="news-img">
                                <a href="news-detail.php?id=<?php echo $article['id']; ?>">
                                    <img src="<?php echo prepare_image_for_display($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                </a>
                            </div>
                            <div class="news-post_info">
                                <div class="news-post_date">
                                    <i class='bx bx-calendar'></i> <span><?php echo date('F j, Y \a\t g:i A', strtotime($article['published_date'])); ?></span>
                                </div>

                                <h3><a href="news-detail.php?id=<?php echo $article['id']; ?>"><?php echo htmlspecialchars($article['title']); ?></a></h3>

                                <p class="news-post_text">
                                    <?php 
                                    if (!empty($article['summary'])) {
                                        echo htmlspecialchars($article['summary']);
                                    } else {
                                        echo htmlspecialchars(substr($article['content'], 0, 300)) . '...';
                                    }
                                    ?>
                                </p>
                                <div class="read-more-link">
                                    <a href="news-detail.php?id=<?php echo $article['id']; ?>" class="read-more">Read More <i class='bx bx-right-arrow-alt'></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?>" class="page-link prev">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="page-link current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?>" class="page-link next">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="sec2">
        <div class="container-rp">
            <h2><i class='bx bx-news'></i> Recent Posts</h2>
            <div class="recent-posts-content">
                <?php if (empty($recent_posts)): ?>
                    <p>No recent posts available.</p>
                <?php else: ?>
                    <?php foreach ($recent_posts as $index => $post): ?>
                        <div class="cards-rp">
                            <a href="news-detail.php?id=<?php echo $post['id']; ?>" class="recent-post-image">
                                <img src="<?php echo prepare_image_for_display($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                            </a>
                            <div class="t-area">
                                <span class="recent-post_date"><?php echo date('F j, Y', strtotime($post['published_date'])); ?></span>
                                <p class="recent-post_text">
                                    <a href="news-detail.php?id=<?php echo $post['id']; ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        <?php if ($index < count($recent_posts) - 1): ?>
                            <hr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>