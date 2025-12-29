<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$post_id = (int)$_GET['id'];
$post = getPostById($post_id);

if (!$post) {
    header('Location: index.php');
    exit();
}

$comments = getCommentsByPost($post_id);
$error = '';
$success = '';

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isLoggedIn()) {
    if (isset($_POST['submit_comment'])) {
        $comment_text = sanitize($_POST['comment']);
        
        if (empty($comment_text)) {
            $error = 'Comment cannot be empty';
        } else {
            $conn = getDBConnection();
            
            // Check for duplicate comments (prevent spam)
            $stmt = $conn->prepare("SELECT id FROM comments WHERE user_id = ? AND post_id = ? AND comment = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $stmt->bind_param("iis", $_SESSION['user_id'], $post_id, $comment_text);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = 'Duplicate comment detected. Please wait before commenting again.';
            } else {
                $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $post_id, $_SESSION['user_id'], $comment_text);
                
                if ($stmt->execute()) {
                    $success = 'Comment posted successfully!';
                    $comments = getCommentsByPost($post_id);
                } else {
                    $error = 'Failed to post comment';
                }
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}

$page_title = $post['title'];
include 'includes/header.php';
?>

<div class="layout-grid">
    <div>
        <div class="card">
            <?php if ($post['image']): ?>
                <img src="uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" 
                     alt="Post Image" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; margin-bottom: 1.5rem;"
                     onerror="this.style.display='none'">
            <?php endif; ?>
            
            <h1><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta">
                By <?php echo htmlspecialchars($post['full_name']); ?> • 
                <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
            </div>
            <hr>
            <div style="line-height: 1.8; font-size: 1.1rem;">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
            
            <?php if (isLoggedIn() && hasRole(['Admin', 'Writer']) && $post['author_id'] == $_SESSION['user_id']): ?>
                <hr>
                <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-small">Edit Post</a>
                <a href="delete-post.php?id=<?php echo $post['id']; ?>" class="btn btn-danger btn-small" 
                   onclick="return confirm('Are you sure you want to delete this post?')">Delete Post</a>
            <?php endif; ?>
        </div>
        
        <div class="comments-section">
            <h3>Comments (<?php echo count($comments); ?>)</h3>
            
            <?php if (isLoggedIn()): ?>
                <div class="card">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="comment">Leave a comment</label>
                            <textarea name="comment" id="comment" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" name="submit_comment" class="btn">Post Comment</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Please <a href="login.php">login</a> to leave a comment.
                </div>
            <?php endif; ?>
            
            <?php if (empty($comments)): ?>
                <p>No comments yet. Be the first to comment!</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-author">
                            <?php echo htmlspecialchars($comment['full_name']); ?>
                            <span class="comment-time"> • <?php echo timeAgo($comment['created_at']); ?></span>
                        </div>
                        <div class="comment-text">
                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <aside>
        <div class="sidebar">
            <h3>About Author</h3>
            <p><strong><?php echo htmlspecialchars($post['full_name']); ?></strong></p>
            <p>@<?php echo htmlspecialchars($post['username']); ?></p>
        </div>
        
        <div class="sidebar" style="margin-top: 1.5rem;">
            <h3>Post Info</h3>
            <p><strong>Published:</strong><br><?php echo date('F j, Y', strtotime($post['created_at'])); ?></p>
            <?php if ($post['updated_at'] != $post['created_at']): ?>
                <p><strong>Updated:</strong><br><?php echo date('F j, Y', strtotime($post['updated_at'])); ?></p>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php include 'includes/footer.php'; ?>