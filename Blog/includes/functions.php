<?php
require_once 'config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['user_role'], $roles);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if doesn't have role
function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        header('Location: dashboard.php');
        exit();
    }
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate password
function isValidPassword($password) {
    return strlen($password) >= 6;
}

// Upload file
function uploadFile($file, $directory) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($fileSize > 5000000) { // 5MB
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $newFilename = uniqid() . '.' . $fileExt;
    $destination = $directory . $newFilename;
    
    if (move_uploaded_file($fileTmp, $destination)) {
        return ['success' => true, 'filename' => $newFilename];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

// Get user by ID
function getUserById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $user;
}

// Get all posts
function getAllPosts($limit = null, $search = null) {
    $conn = getDBConnection();
    
    $sql = "SELECT p.*, u.username, u.full_name 
            FROM posts p 
            JOIN users u ON p.author_id = u.id";
    
    if ($search) {
        $sql .= " WHERE p.title LIKE ? OR p.content LIKE ?";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($search) {
        $searchTerm = "%$search%";
        if ($limit) {
            $stmt->bind_param("ssi", $searchTerm, $searchTerm, $limit);
        } else {
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
        }
    } else if ($limit) {
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $posts;
}

// Get post by ID
function getPostById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT p.*, u.username, u.full_name 
                           FROM posts p 
                           JOIN users u ON p.author_id = u.id 
                           WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $post;
}

// Get comments for post
function getCommentsByPost($post_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT c.*, u.username, u.full_name 
                           FROM comments c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.post_id = ? 
                           ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $comments;
}

// Get counts for dashboard
function getDashboardCounts() {
    $conn = getDBConnection();
    
    $counts = [];
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $counts['users'] = $result->fetch_assoc()['count'];
    
    // Total posts
    $result = $conn->query("SELECT COUNT(*) as count FROM posts");
    $counts['posts'] = $result->fetch_assoc()['count'];
    
    // Total comments
    $result = $conn->query("SELECT COUNT(*) as count FROM comments");
    $counts['comments'] = $result->fetch_assoc()['count'];
    
    // User's posts (if logged in)
    if (isLoggedIn()) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE author_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $counts['my_posts'] = $result->fetch_assoc()['count'];
        $stmt->close();
    }
    
    $conn->close();
    return $counts;
}

// Time ago function
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return "just now";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " minutes ago";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " hours ago";
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . " days ago";
    } else {
        return date('M j, Y', $time);
    }
}
?>