<?php

include 'config.php';





// Soft Delete

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {

    $id = $_GET['delete'];

    $stmt = $conn->prepare("UPDATE xcel_posts SET deleted = 1 WHERE id = ?");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    header("Location: view-xcel_posts.php?deleted=1");

    exit;

}



// Restore

if (isset($_GET['restore']) && is_numeric($_GET['restore'])) {

    $id = $_GET['restore'];

    $stmt = $conn->prepare("UPDATE xcel_posts SET deleted = 0 WHERE id = ?");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    header("Location: view-xcel_posts.php?restored=1&recycle=1");

    exit;

}



// Permanent Delete

if (isset($_GET['force_delete']) && is_numeric($_GET['force_delete'])) {

    $id = $_GET['force_delete'];



    // Get image

    $stmt = $conn->prepare("SELECT image FROM xcel_posts WHERE id = ?");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $stmt->bind_result($image);

    $stmt->fetch();

    $stmt->close();



    // Delete post

    $stmt = $conn->prepare("DELETE FROM xcel_posts WHERE id = ?");

    $stmt->bind_param("i", $id);

    $stmt->execute();



    if ($image && file_exists("uploads/$image")) {

        unlink("uploads/$image");

    }



    header("Location: view-xcel_posts.php?permanently_deleted=1&recycle=1");

    exit;

}



$isRecycle = isset($_GET['recycle']);

?>



<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <title><?= $isRecycle ? "Recycle Bin" : "All Blog xcel_posts" ?></title>

    <style>

        body {

            font-family: Arial, sans-serif;

            padding: 40px;

            background: #f4f4f4;

        }

        .alert {

            background-color: #e2f0d9;

            border-left: 4px solid green;

            padding: 10px;

            margin-bottom: 20px;

        }

        .btn-top {

            margin-bottom: 20px;

        }

        .btn-top a {

            background-color: #007bff;

            color: white;

            padding: 8px 12px;

            text-decoration: none;

            border-radius: 4px;

            margin-right: 10px;

        }

        .btn-top a:hover {

            background-color: #0056b3;

        }

        .post {

            background: white;

            padding: 20px;

            margin-bottom: 20px;

            border-left: 5px solid #007bff;

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



<h2><?= $isRecycle ? "♻️ Recycle Bin" : "📄 All Blog xcel_posts" ?></h2>



<div class="btn-top">

    <?php if (!$isRecycle): ?>

        <a href="view-xcel_posts.php?recycle=1">🗑️ View Recycle Bin</a>

    <?php else: ?>

        <a href="view-xcel_posts.php">📄 Back to Blog xcel_posts</a>

    <?php endif; ?>

</div>



<?php if (isset($_GET['deleted'])): ?>

    <div class="alert">✅ Post moved to Recycle Bin.</div>

<?php endif; ?>



<?php if (isset($_GET['restored'])): ?>

    <div class="alert">♻️ Post restored successfully.</div>

<?php endif; ?>



<?php if (isset($_GET['permanently_deleted'])): ?>

    <div class="alert">❌ Post permanently deleted.</div>

<?php endif; ?>



<?php

$query = $isRecycle 

    ? "SELECT * FROM xcel_posts WHERE deleted = 1 ORDER BY created_at DESC"

    : "SELECT * FROM xcel_posts WHERE deleted = 0 ORDER BY created_at DESC";



$result = $conn->query($query);



if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        echo "<div class='post'>

            <h3>" . htmlspecialchars($row['title']) . "</h3>";



        if (!empty($row['image'])) {

            echo "<div class='post-image'><img src='uploads/" . htmlspecialchars($row['image']) . "' alt='Blog Image'></div>";

        }



        echo "<div class='post-content'>" . nl2br(htmlspecialchars($row['content'])) . "</div>";



        echo "<div class='post-actions'>";



        if ($isRecycle) {

            echo "<a href='?restore=" . $row['id'] . "' onclick=\"return confirm('Restore this post?')\">♻️ Restore</a>";

            echo "<a href='?force_delete=" . $row['id'] . "' onclick=\"return confirm('Permanently delete this post?')\">❌ Delete Permanently</a>";

        } else {

            echo "<a href='add-post.php?slug=" . urlencode($row['slug']) . "'>✏️ Edit</a>";

            echo "<a href='view-add-all-post.php?slug=" . urlencode($row['slug']) . "'>👁️ View</a>";

            echo "<a href='?delete=" . $row['id'] . "' onclick=\"return confirm('Move this post to Recycle Bin?')\">🗑️ Delete</a>";

        }



        echo "</div></div>";

    }

} else {

    echo "<p>No blog xcel_posts found.</p>";

}

?>





</body>

</html>

