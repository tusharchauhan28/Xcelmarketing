<?php
session_start();

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

include 'config.php';
include 'includes/header.php';

// Handle restore action
if (isset($_GET['restore'])) {
    $slug = $_GET['restore'];
    $stmt = $conn->prepare("UPDATE xcel_posts SET is_deleted = 0 WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    header("Location: recycle-bin.php");
    exit();
}

// Handle permanent delete action
if (isset($_GET['delete'])) {
    $slug = $_GET['delete'];
    
    // First, delete the image file
    $imgQuery = $conn->prepare("SELECT image FROM xcel_posts WHERE slug = ?");
    $imgQuery->bind_param("s", $slug);
    $imgQuery->execute();
    $imgResult = $imgQuery->get_result();
    if ($imgRow = $imgResult->fetch_assoc()) {
        $imagePath = "uploads/" . $imgRow['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Then delete the record from database
    $stmt = $conn->prepare("DELETE FROM xcel_posts WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    header("Location: recycle-bin.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recycle Bin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f4f4f4;
        }
        h2 {
            text-align: center;
            color: #dc3545;
        }
        .post {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #dc3545;
            box-shadow: 0 0 8px rgba(0,0,0,0.05);
        }
        .post h3 {
            margin: 0;
        }
        .post-content {
            margin-top: 10px;
        }
        .post-actions {
            margin-top: 10px;
        }
        .post-actions a {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
        }
        .post-actions a.restore {
            color: #28a745;
        }
        .post-actions a.delete {
            color: #dc3545;
        }
        .post-actions a:hover {
            text-decoration: underline;
        }
        .post-image {
            margin-top: 10px;
        }
        .post-image img {
            width: 100px;
            height: auto;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<h2>🗑️ Recycle Bin - Deleted xcel_posts</h2>

<?php
$result = $conn->query("SELECT * FROM xcel_posts WHERE is_deleted = 1 ORDER BY created_at DESC");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='post'>
            <h3>" . htmlspecialchars($row['title']) . "</h3>";

        if (!empty($row['image'])) {
            echo "<div class='post-image'><img src='uploads/" . htmlspecialchars($row['image']) . "' alt='Blog Image'></div>";
        }

        echo "<div class='post-content'>" . nl2br(htmlspecialchars($row['content'])) . "</div>";

        echo "<div class='post-actions'>
                <a class='restore' href='recycle-bin.php?restore=" . urlencode($row['slug']) . "'>♻️ Restore</a>
                <a class='delete' href='recycle-bin.php?delete=" . urlencode($row['slug']) . "' onclick='return confirm(\"Are you sure you want to permanently delete this post?\")'>❌ Delete Permanently</a>
            </div>
        </div>";
    }
} else {
    echo "<p>No deleted blog xcel_posts found.</p>";
}
?>

</body>
</html>
