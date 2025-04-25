<?php
$page_title = 'News & Updates';
$page_description = 'Stay updated with the latest news, events, and announcements from St. Raphaela Mary School.';

include 'includes/header.php';

// Fetch news articles
$db = db_connect();
$news_articles = $db->fetch_all("SELECT * FROM news WHERE status = 'published' ORDER BY published_date DESC");

// Get the 6 most recent articles for the sidebar
$recent_posts = array_slice($news_articles, 0, 6);
?>

<section class="main-title">
    <h2>SRMS NEWS UPDATES</h2>
</section>

<main>
    <section class="sec1">
        <div class="container">
            <?php if (empty($news_articles)): ?>
                <p>No news articles available at this time.</p>
            <?php else: ?>
                <?php foreach ($news_articles as $article): ?>
                    <div class="news-post">
                        <div class="news-img">
                            <a href="news-detail.php?id=<?php echo $article['id']; ?>">
                                <img src="<?php echo SITE_URL . $article['image']; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                            </a>
                        </div>
                        <div class="news-post_info">
                            <div class="news-post_date">
                                <span><?php echo date('F j, Y \a\t g:i A', strtotime($article['published_date'])); ?></span>
                            </div>

                            <h3><a href="news-detail.php?id=<?php echo $article['id']; ?>"><?php echo htmlspecialchars($article['title']); ?></a></h3>

                            <p class="news-post_text">
                                <?php 
                                // Display summary or truncated content
                                if (!empty($article['summary'])) {
                                    echo htmlspecialchars($article['summary']);
                                } else {
                                    echo htmlspecialchars(substr($article['content'], 0, 300)) . '...';
                                }
                                ?>
                                <br><br>
                                <a href="news-detail.php?id=<?php echo $article['id']; ?>" class="read-more">Read More</a>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="sec2">
        <div class="container-rp">
            <h2>Recent Posts</h2>

            <?php foreach ($recent_posts as $index => $post): ?>
                <div class="cards-rp">
                    <a href="news-detail.php?id=<?php echo $post['id']; ?>">
                        <img src="<?php echo SITE_URL . $post['image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    </a>
                    <div class="t-area">
                        <span class="recent-post_date"><?php echo date('F j, Y', strtotime($post['published_date'])); ?></span>
                        <p class="recent-post_text">
                            <a href="news-detail.php?id=<?php echo $post['id']; ?>">
                                <?php echo htmlspecialchars($post['summary']); ?>
                            </a>
                        </p>
                    </div>
                </div>
                <?php if ($index < count($recent_posts) - 1): ?>
                    <hr>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>