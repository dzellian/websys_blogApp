<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();
$counts = getDashboardCounts();
$recent_posts = getAllPosts(5);

$page_title = 'Dashboard';
include 'includes/header.php';
?>

<div class="fade-in">
    <div style="background: white; border-radius: var(--border-radius); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-lg);">
        <h1 style="color: var(--gray-900); margin: 0;">👋 Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem; font-size: 1.1rem;">
            Role: <span class="profile-role"><?php echo $_SESSION['user_role']; ?></span>
        </p>
    </div>
    
    <div class="dashboard-grid">
        <?php if (hasRole('Admin')): ?>
            <div class="stat-card purple">
                <div class="stat-number"><?php echo $counts['users']; ?></div>
                <div class="stat-label">👥 Total Users</div>
            </div>
        <?php endif; ?>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo $counts['posts']; ?></div>
            <div class="stat-label">📝 Total Posts</div>
        </div>
        
        <div class="stat-card blue">
            <div class="stat-number"><?php echo $counts['comments']; ?></div>
            <div class="stat-label">💬 Total Comments</div>
        </div>
        
        <?php if (hasRole(['Admin', 'Writer'])): ?>
            <div class="stat-card orange">
                <div class="stat-number"><?php echo $counts['my_posts']; ?></div>
                <div class="stat-label">✍️ My Posts</div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card card-glow">
        <div class="card-header">
            <h2 class="card-title">📰 Recent Blog Posts</h2>
        </div>
        
        <?php if (empty($recent_posts)): ?>
            <div class="text-center" style="padding: 2rem;">
                <p style="font-size: 1.2rem; color: var(--gray-500);">No posts yet.</p>
                <?php if (hasRole(['Admin', 'Writer'])): ?>
                    <a href="add-post.php" class="btn" style="margin-top: 1rem;">Create Your First Post</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_posts as $post): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($post['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($post['full_name']); ?></td>
                                <td><?php echo timeAgo($post['created_at']); ?></td>
                                <td>
                                    <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>