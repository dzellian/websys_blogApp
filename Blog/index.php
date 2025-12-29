<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : null;
$posts = getAllPosts(null, $search);
$recent_posts = getAllPosts(5);

$page_title = 'Home';
include 'includes/header.php';
?>

<div class="fade-in">
    <h1>✨ Latest Blog Posts</h1>
    
    <div class="search-box">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="🔍 Search posts by title or content..." 
                   value="<?php echo $search ? htmlspecialchars($search) : ''; ?>" 
                   class="form-control">
        </form>
    </div>
    
    <div class="layout-grid">
        <div>
            <?php if (empty($posts)): ?>
                <div class="card text-center">
                    <p style="font-size: 1.2rem; color: var(--gray-500);">
                        📭 No posts found. 
                        <?php if (isLoggedIn() && hasRole(['Admin', 'Writer'])): ?>
                            <a href="add-post.php" style="color: var(--primary);">Create the first post!</a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card card-glow hover-lift">
                            <div class="post-image-wrapper">
                                <?php if ($post['image']): ?>
                                    <img src="uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image"
                                         onerror="this.parentElement.style.display='none'">
                                <?php else: ?>
                                    <div style="height: 220px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                        📝
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <div class="post-meta">
                                    <?php echo htmlspecialchars($post['full_name']); ?> • 
                                    <?php echo timeAgo($post['created_at']); ?>
                                </div>
                                <p class="post-excerpt">
                                    <?php echo substr(strip_tags($post['content']), 0, 150) . '...'; ?>
                                </p>
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-small">Read More →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <aside>
            <div class="sidebar">
                <h3>📌 Recent Posts</h3>
                <?php if (empty($recent_posts)): ?>
                    <p style="color: var(--gray-500);">No recent posts</p>
                <?php else: ?>
                    <ul class="sidebar-list">
                        <?php foreach ($recent_posts as $post): ?>
                            <li>
                                <a href="post.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                                <small>
                                    <?php echo timeAgo($post['created_at']); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <?php if (isLoggedIn() && hasRole(['Admin', 'Writer'])): ?>
                <div class="sidebar" style="margin-top: 1.5rem; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);">
                    <h3>✍️ Quick Actions</h3>
                    <a href="add-post.php" class="btn" style="width: 100%; margin-bottom: 0.75rem;">Create New Post</a>
                    <a href="manage-posts.php" class="btn btn-secondary" style="width: 100%;">Manage Posts</a>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php include 'includes/footer.php'; ?>