<?php
$page_title = 'News & Updates';
$page_description = 'Stay updated with the latest news, events, and announcements from St. Raphaela Mary School.';

include 'includes/header.php';

/**
 * Enhanced image display function with lazy loading and better fallbacks
 */
function prepare_image_for_display($image_path) {
    // If path is empty, return a default image
    if (empty($image_path)) {
        $category = isset($_GET['category']) ? strtolower($_GET['category']) : 'news';
        return SITE_URL . '/assets/images/' . $category . '/news-placeholder.jpg';
    }
    
    // Use the consistent normalize_image_path function from functions.php if available
    if (function_exists('normalize_image_path')) {
        $image_path = normalize_image_path($image_path);
    } else {
        // Simple normalization as fallback
        $image_path = '/' . ltrim($image_path, '/');
    }
    
    // Use get_correct_image_url if available
    if (function_exists('get_correct_image_url')) {
        return get_correct_image_url($image_path);
    }
    
    // Try to get the project folder from SITE_URL
    $project_folder = '';
    if (preg_match('#/([^/]+)$#', parse_url(SITE_URL, PHP_URL_PATH), $matches)) {
        $project_folder = $matches[1];
    }
    
    // Return the correct URL with site prefix
    return SITE_URL . $image_path;
}

// Fetch news articles with search and filtering
$db = db_connect();
$query = "SELECT * FROM news WHERE status = 'published'";

// Apply category filter if provided
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = $db->escape($_GET['category']);
    $query .= " AND category = '$category'";
}

// Apply search filter if provided
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $db->escape($_GET['search']);
    $query .= " AND (title LIKE '%$search%' OR content LIKE '%$search%' OR summary LIKE '%$search%')";
}

$query .= " ORDER BY published_date DESC";
$news_articles = $db->fetch_all($query);

// Pagination settings
$items_per_page = 5;
$total_items = count($news_articles);
$total_pages = ceil($total_items / $items_per_page);

// Get current page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, min($current_page, $total_pages));

// Get subset of articles for current page
$offset = ($current_page - 1) * $items_per_page;
$paged_articles = array_slice($news_articles, 0, $items_per_page);
if ($current_page > 1 && $offset < count($news_articles)) {
    $paged_articles = array_slice($news_articles, $offset, $items_per_page);
}

// Get the 6 most recent articles for the sidebar
$recent_posts = array_slice($news_articles, 0, 6);
?>

<!-- Internal CSS for enhanced styling -->
<style>
/* Enhanced News Page Styling */
.main-title {
    background-color: var(--primary);
    padding: 80px 0 30px 0;
    margin-bottom: 0; /* Changed from 30px to connect with filters */
    text-align: center;
}

.main-title .container {
    max-width: 1200px;
    margin: 0 auto;
    background: none;
    box-shadow: none;
    padding: 0;
}

.main-title h2 {
    font-size: 28px;
    font-weight: 600;
    color: white;
    margin-bottom: 15px;
    letter-spacing: 1px;
}

.main-title .underline {
    height: 3px;
    width: 60px;
    background-color: var(--yellow);
    margin: 0 auto;
}

/* News filters */
.news-filters {
    background-color: white;
    padding: 20px 0;
    margin-bottom: 30px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.filter-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

.search-box {
    flex: 1;
    max-width: 400px;
}

.search-box form {
    display: flex;
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 10px 15px;
    padding-right: 40px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-box input:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(60, 145, 230, 0.2);
    outline: none;
}

.search-box button {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--dark-grey);
    cursor: pointer;
    font-size: 18px;
    transition: color 0.3s ease;
}

.search-box button:hover {
    color: var(--blue);
}

.category-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.filter-label {
    font-size: 14px;
    color: var(--dark-grey);
}

.category-filter {
    display: inline-block;
    padding: 6px 12px;
    font-size: 14px;
    color: var(--dark-grey);
    background-color: #f5f5f5;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.category-filter:hover, .category-filter.active {
    background-color: var(--blue);
    color: white;
}

/* Enhanced News Cards */
.news-post {
    display: flex;
    flex-direction: column;
    background-color: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
}

.news-post:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
    border-color: rgba(60, 145, 230, 0.2);
}

.news-img {
    position: relative;
    height: auto;
    overflow: hidden;
}

.news-img img {
    width: 100%;
    height: auto;
    aspect-ratio: 16/9;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.news-post:hover .news-img img {
    transform: scale(1.05);
}

.news-post_info {
    padding: 24px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.news-post_meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    font-size: 14px;
    color: var(--dark-grey);
}

.news-post_date, .reading-time {
    display: flex;
    align-items: center;
}

.news-post_date i, .reading-time i {
    margin-right: 6px;
    font-size: 16px;
    color: var(--blue);
}

.news-post_info h3 {
    font-size: 22px;
    margin-bottom: 16px;
    line-height: 1.3;
}

.news-post_info h3 a {
    color: var(--primary);
    text-decoration: none;
    transition: color 0.3s ease;
    position: relative;
    display: inline;
    background-image: linear-gradient(transparent 97%, var(--blue) 3%);
    background-repeat: no-repeat;
    background-size: 0% 100%;
    transition: background-size 0.3s;
}

.news-post_info h3 a:hover {
    background-size: 100% 100%;
}

.news-post_text {
    color: #444;
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 20px;
    flex-grow: 1;
}

.news-post_footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid #f0f0f0;
}

.read-more {
    color: var(--blue);
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
    text-decoration: none;
}

.read-more:hover {
    color: var(--dark-blue);
    transform: translateX(5px);
}

.read-more i {
    margin-left: 5px;
    transition: transform 0.3s ease;
}

.read-more:hover i {
    transform: translateX(3px);
}

.share-options {
    display: flex;
    align-items: center;
}

.share-btn {
    background: none;
    border: none;
    color: var(--dark-grey);
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.share-btn:hover {
    background-color: rgba(60, 145, 230, 0.1);
    color: var(--blue);
}

.share-btn i {
    margin-right: 5px;
    font-size: 16px;
}

/* Featured and Category Badges */
.featured-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background-color: var(--primary);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    z-index: 1;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.featured-badge i {
    margin-right: 5px;
}

.category-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    z-index: 1;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.badge-general {
    background-color: var(--blue);
    color: white;
}

.badge-events {
    background-color: #0aaa2dd7;
    color: white;
}

.badge-announcement {
    background-color: #FD7238;
    color: white;
}

/* Enhanced recent posts styling */
.sec2 {
    position: sticky;
    top: 90px;
}

.container-rp {
    background-color: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.cards-rp {
    display: flex;
    gap: 15px;
    padding: 15px 0;
    transition: all 0.3s ease;
    border-radius: 8px;
}

.cards-rp:hover {
    background-color: rgba(60, 145, 230, 0.05);
    transform: translateX(5px);
}

.recent-post-image {
    flex: 0 0 80px;
    transition: all 0.3s ease;
}

.recent-post-image img {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.cards-rp:hover .recent-post-image img {
    transform: scale(1.05);
}

/* Improved pagination */
.pagination-container {
    margin: 40px 0;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
}

.pagination .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 10px;
    margin: 0 5px;
    font-size: 14px;
    background-color: white;
    color: var(--primary);
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination .page-link:hover {
    background-color: var(--light-blue);
    color: var(--primary);
    border-color: var(--blue);
}

.pagination .current {
    background-color: var(--blue);
    color: white;
    border-color: var(--blue);
    font-weight: 600;
}

.pagination .prev,
.pagination .next {
    font-weight: 600;
    padding: 0 15px;
}

.pagination .prev i,
.pagination .next i {
    font-size: 16px;
}

/* Pagination ellipsis */
.page-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 5px;
    color: var(--dark-grey);
}

/* Loading state for articles */
.articles-wrapper.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* No results styling */
.no-news-message {
    background-color: white;
    padding: 40px;
    text-align: center;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.no-results-icon {
    font-size: 60px;
    color: var(--blue);
    margin-bottom: 20px;
}

.no-news-message h3 {
    color: var(--primary);
    margin-bottom: 15px;
}

.no-news-message p {
    color: var(--dark-grey);
    max-width: 500px;
    margin: 0 auto 20px;
}

.btn-reset-search {
    display: inline-block;
    background-color: var(--blue);
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.3s;
}

.btn-reset-search:hover {
    background-color: var(--dark-blue);
}

/* Mobile responsiveness */
@media screen and (max-width: 992px) {
    .news-container {
        flex-direction: column;
    }
    
    .sec1, .sec2 {
        max-width: 100%;
    }
    
    .sec2 {
        position: static;
        margin-top: 30px;
    }
    
    .filter-wrapper {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        max-width: 100%;
        margin-bottom: 15px;
    }
}

@media screen and (max-width: 768px) {
    .main-title h2 {
        font-size: 24px;
    }
    
    .pagination .page-link {
        min-width: 32px;
        height: 32px;
        margin: 0 3px;
        font-size: 13px;
    }
    
    .news-post_meta {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .news-post_date {
        margin-bottom: 5px;
    }
    
    .news-post_info {
        padding: 20px;
    }
    
    .news-post_footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .share-options {
        width: 100%;
        justify-content: flex-start;
    }
}

@media screen and (max-width: 480px) {
    .main-title h2 {
        font-size: 22px;
    }
    
    .news-post_info h3 {
        font-size: 18px;
    }
    
    .cards-rp {
        padding: 10px 0;
    }
    
    .recent-post-image {
        flex: 0 0 60px;
    }
    
    .recent-post-image img {
        width: 60px;
        height: 60px;
    }
}
</style>

<section class="main-title">
    <div class="container">
        <h2>SRMS NEWS UPDATES</h2>
        <div class="underline"></div>
    </div>
</section>

<!-- News Filters Section -->
<div class="news-filters">
    <div class="filter-wrapper">
        <div class="search-box">
            <form action="news.php" method="get" id="searchForm">
                <input type="text" name="search" placeholder="Search news..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit"><i class='bx bx-search'></i></button>
            </form>
        </div>
        
        <div class="category-filters">
            <?php
            // Get all unique categories
            $categories = $db->fetch_all("SELECT DISTINCT category FROM news WHERE status = 'published' AND category IS NOT NULL AND category != ''");
            ?>
            <span class="filter-label">Filter by:</span>
            <a href="news.php" class="category-filter <?php echo !isset($_GET['category']) ? 'active' : ''; ?>">All</a>
            
            <?php foreach($categories as $cat): ?>
                <?php if(!empty($cat['category'])): ?>
                <a href="news.php?category=<?php echo urlencode($cat['category']); ?>" 
                   class="category-filter <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['category']) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars(ucfirst($cat['category'])); ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<main class="news-container">
    <section class="sec1">
        <div class="container">
            <?php if (empty($paged_articles)): ?>
                <div class="no-news-message">
                    <div class="no-results-icon">
                        <i class='bx bx-news'></i>
                    </div>
                    <?php if (isset($_GET['search'])): ?>
                        <h3>No results found for "<?php echo htmlspecialchars($_GET['search']); ?>"</h3>
                        <p>Try different keywords or browse all news articles.</p>
                        <a href="news.php" class="btn-reset-search">View All News</a>
                    <?php elseif (isset($_GET['category'])): ?>
                        <h3>No news articles in this category</h3>
                        <p>There are currently no published articles in the "<?php echo htmlspecialchars(ucfirst($_GET['category'])); ?>" category.</p>
                        <a href="news.php" class="btn-reset-search">View All News</a>
                    <?php else: ?>
                        <p>No news articles available at this time. Please check back soon for updates.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="articles-wrapper">
                    <?php foreach ($paged_articles as $article): ?>
                        <div class="news-post" data-category="<?php echo htmlspecialchars($article['category'] ?? 'general'); ?>">
                            <div class="news-img">
                                <a href="news-detail.php?id=<?php echo $article['id']; ?>">
                                    <img src="<?php echo prepare_image_for_display($article['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                                         loading="lazy">
                                </a>
                                <?php if(isset($article['featured']) && $article['featured']): ?>
                                    <div class="featured-badge">
                                        <i class='bx bx-star'></i> Featured
                                    </div>
                                <?php endif; ?>
                                <?php if(isset($article['category']) && $article['category']): ?>
                                    <div class="category-badge badge-<?php echo htmlspecialchars($article['category']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($article['category'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="news-post_info">
                                <div class="news-post_meta">
                                    <div class="news-post_date">
                                        <i class='bx bx-calendar'></i> 
                                        <span><?php echo date('F j, Y', strtotime($article['published_date'])); ?></span>
                                    </div>
                                    
                                    <?php 
                                    // Calculate and display reading time
                                    $word_count = str_word_count(strip_tags($article['content']));
                                    $reading_time = max(1, ceil($word_count / 200)); // Assumes 200 words per minute
                                    ?>
                                    <div class="reading-time">
                                        <i class='bx bx-time'></i> <?php echo $reading_time; ?> min read
                                    </div>
                                </div>

                                <h3><a href="news-detail.php?id=<?php echo $article['id']; ?>"><?php echo htmlspecialchars($article['title']); ?></a></h3>

                                <p class="news-post_text">
                                    <?php 
                                    if (!empty($article['summary'])) {
                                        echo htmlspecialchars($article['summary']);
                                    } else {
                                        echo htmlspecialchars(substr(strip_tags($article['content']), 0, 280)) . '...';
                                    }
                                    ?>
                                </p>
                                
                                <div class="news-post_footer">
                                    <a href="news-detail.php?id=<?php echo $article['id']; ?>" class="read-more">
                                        Read More <i class='bx bx-right-arrow-alt'></i>
                                    </a>
                                    
                                    <div class="share-options">
                                        <button class="share-btn" data-title="<?php echo htmlspecialchars($article['title']); ?>" data-url="news-detail.php?id=<?php echo $article['id']; ?>">
                                            <i class='bx bx-share-alt'></i> Share
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?><?php echo isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?>" class="page-link prev">
                                <i class='bx bx-chevron-left'></i> Prev
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        // Show limited page numbers with ellipsis for better UX
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1) {
                            echo '<a href="?page=1'.
                                (isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : '').
                                (isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '').
                                '" class="page-link">1</a>';
                                
                            if ($start_page > 2) {
                                echo '<span class="page-ellipsis">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="page-link current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?>" class="page-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; 
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="page-ellipsis">...</span>';
                            }
                            
                            echo '<a href="?page='.$total_pages.
                                (isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : '').
                                (isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '').
                                '" class="page-link">'.$total_pages.'</a>';
                        }
                        ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?><?php echo isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?>" class="page-link next">
                                Next <i class='bx bx-chevron-right'></i>
                            </a>
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
                                <img src="<?php echo prepare_image_for_display($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy">
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

<script>
// Share functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize share buttons
    document.querySelectorAll('.share-btn').forEach(button => {
        button.addEventListener('click', function() {
            const title = this.getAttribute('data-title');
            const url = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/' + this.getAttribute('data-url');
            
            if (navigator.share) {
                // Web Share API is supported
                navigator.share({
                    title: title,
                    url: url
                }).catch(error => console.log('Error sharing:', error));
            } else {
                // Fallback copy to clipboard
                const tempInput = document.createElement('input');
                document.body.appendChild(tempInput);
                tempInput.value = url;
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                // Show success message
                this.innerHTML = '<i class="bx bx-check"></i> Copied!';
                setTimeout(() => {
                    this.innerHTML = '<i class="bx bx-share-alt"></i> Share';
                }, 2000);
            }
        });
    });

    // Add loading state when clicking filters
    const categoryFilters = document.querySelectorAll('.category-filter');
    const searchForm = document.getElementById('searchForm');
    
    if (categoryFilters.length) {
        categoryFilters.forEach(filter => {
            filter.addEventListener('click', function(e) {
                if (!this.classList.contains('active')) {
                    // Add loading state to articles container
                    const articlesWrapper = document.querySelector('.articles-wrapper');
                    if (articlesWrapper) {
                        articlesWrapper.classList.add('loading');
                    }
                }
            });
        });
    }
    
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            // Add loading state to articles container
            const articlesWrapper = document.querySelector('.articles-wrapper');
            if (articlesWrapper) {
                articlesWrapper.classList.add('loading');
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>