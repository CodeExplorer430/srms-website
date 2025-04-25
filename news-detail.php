<?php
// Get article ID from URL parameter
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect to news listing if no valid ID provided
if ($article_id <= 0) {
    header('Location: news.php');
    exit;
}

// Include database connection
include_once 'includes/config.php';
include_once 'includes/functions.php';

// Fetch article data
$db = db_connect();
$article = $db->fetch_row("SELECT * FROM news WHERE id = $article_id AND status = 'published'");

// If article not found, redirect to news listing
if (!$article) {
    header('Location: news.php');
    exit;
}

// Set page title and description
$page_title = $article['title'];
$page_description = $article['summary'];

include 'includes/header.php';

// Get author info if available
$author_name = 'SRMS Staff';
if ($article['author_id']) {
    $author = $db->fetch_row("SELECT username FROM users WHERE id = {$article['author_id']}");
    if ($author) {
        $author_name = $author['username'];
    }
}

// Get related articles (same month or similar titles)
$related_articles = [];
$pub_month = date('Y-m', strtotime($article['published_date']));
$related_query = "SELECT id, title, image, published_date FROM news 
                 WHERE id != {$article['id']} 
                 AND status = 'published' 
                 AND (DATE_FORMAT(published_date, '%Y-%m') = '$pub_month' 
                      OR title LIKE '%{$db->escape(substr($article['title'], 0, 10))}%')
                 ORDER BY published_date DESC
                 LIMIT 3";
$related_articles = $db->fetch_all($related_query);
?>

<section class="news-detail">
    <div class="container">
        <div class="article-header">
            <h1><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="article-meta">
                <span class="date"><?php echo date('F j, Y', strtotime($article['published_date'])); ?></span>
                <span class="author">By <?php echo htmlspecialchars($author_name); ?></span>
            </div>
        </div>
        
        <?php if ($article['image']): ?>
        <div class="article-image">
            <img src="<?php echo SITE_URL . $article['image']; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
        </div>
        <?php endif; ?>
        
        <div class="article-content">
            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
        </div>
        
        <div class="article-share">
            <h3>Share This Article</h3>
            <div class="share-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/news-detail.php?id=' . $article['id']); ?>" target="_blank" class="facebook">
                    <i class='bx bxl-facebook'></i> Share on Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/news-detail.php?id=' . $article['id']); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank" class="twitter">
                    <i class='bx bxl-twitter'></i> Share on Twitter
                </a>
            </div>
        </div>
        
        <?php if (!empty($related_articles)): ?>
        <div class="related-articles">
            <h3>Related Articles</h3>
            <div class="related-grid">
                <?php foreach($related_articles as $related): ?>
                <div class="related-item">
                    <a href="news-detail.php?id=<?php echo $related['id']; ?>">
                        <img src="<?php echo SITE_URL . $related['image']; ?>" alt="<?php echo htmlspecialchars($related['title']); ?>">
                        <h4><?php echo htmlspecialchars($related['title']); ?></h4>
                        <span class="date"><?php echo date('F j, Y', strtotime($related['published_date'])); ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="news.php">&larr; Back to News</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>