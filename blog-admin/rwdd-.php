<?php
include 'config.php';

$slug = 'rwdd-';

// Fetch blog content based on slug
$stmt = $conn->prepare("SELECT * FROM xcel_posts WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if ($post) {
    echo "<h1>" . htmlspecialchars($post['title']) . "</h1>";
    echo "<p>" . nl2br(htmlspecialchars($post['sub_content'])) . "</p>";
    if (!empty($post['image'])) {
        echo "<img src='uploads/" . htmlspecialchars($post['image']) . "' alt='Post Image' />";
    }
} else {
    echo "Post not found.";
}
?>