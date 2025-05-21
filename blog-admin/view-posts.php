<?php
include 'config.php';

// Pagination variables
$limit = 10; // Number of xcel_posts per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Soft Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("UPDATE xcel_posts SET deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: view-xcel_posts.php?deleted=1&page=".$page);
    exit;
}

// Restore
if (isset($_GET['restore']) && is_numeric($_GET['restore'])) {
    $id = $_GET['restore'];
    $stmt = $conn->prepare("UPDATE xcel_posts SET deleted = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: view-xcel_posts.php?restored=1&recycle=1&page=".$page);
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

    header("Location: view-xcel_posts.php?permanently_deleted=1&recycle=1&page=".$page);
    exit;
}

$isRecycle = isset($_GET['recycle']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <?php include './header.php'; ?>
    <link rel="stylesheet" href="./css/style.css">
    <title><?= $isRecycle ? "Recycle Bin" : "All Blog Posts" ?></title>
    <style>
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            margin-bottom: 80px;
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
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a {
            color: #007bff;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        
        .pagination a.active {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        .btn-top {
    margin-bottom: 20px;
    width: 100%;
}

.btn-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.left-btn {
    /* background-color: #007bff; */
    /* color: white; */
    color: black;
    padding: 8px 12px;
    text-decoration: none;
    border-radius: 4px;
}

.right-btns {
    display: flex;
    gap: 10px; /* Space between right-aligned buttons */
}

.right-btn {
    background-color: #28a745; /* Different color for distinction */
    color: white;
    padding: 8px 12px;
    text-decoration: none;
    border-radius: 4px;
}

.left-btn:hover {
    /* background-color: #0056b3; */
}

.right-btn:hover {
    background-color: #218838;
}
    </style>
</head>
<body>
    <?php include './sidebar.php'; ?>
    
    <div class="main-content mb-5">
      

        <div class="btn-top">
    <div class="btn-container">
    <h2 class="left-btn"><?= $isRecycle ? " ?? Recycle Bin" : " ?? All Blog Posts" ?></h2>
    
        
        <!-- You can add another button on the right if needed -->
        <div class="right-btns">
         <?php if (!$isRecycle): ?>
            <a href="view-posts.php?recycle=1" class="right-btn">??? View Recycle Bin</a>
        <?php else: ?>
            <a href="view-posts.php" class="right-btn"> ?? Back to Blog Posts</a>
        <?php endif; ?>
  
        </div>
    </div>
</div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert">? Post moved to Recycle Bin.</div>
        <?php endif; ?>

        <?php if (isset($_GET['restored'])): ?>
            <div class="alert">?? Post restored successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['permanently_deleted'])): ?>
            <div class="alert">? Post permanently deleted.</div>
        <?php endif; ?>

        <?php
        // Get total number of xcel_posts
        $query_total = $isRecycle 
            ? "SELECT COUNT(*) FROM xcel_posts WHERE deleted = 1"
            : "SELECT COUNT(*) FROM xcel_posts WHERE deleted = 0";
        
        $result_total = $conn->query($query_total);
        $total_xcel_posts = $result_total->fetch_row()[0];
        $pages = ceil($total_xcel_posts / $limit);

        // Get xcel_posts for current page
        $query = $isRecycle 
            ? "SELECT * FROM xcel_posts WHERE deleted = 1 ORDER BY created_at DESC LIMIT $start, $limit"
            : "SELECT * FROM xcel_posts WHERE deleted = 0 ORDER BY created_at DESC LIMIT $start, $limit";

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
                    echo "<a href='?restore=" . $row['id'] . "&page=".$page."' onclick=\"return confirm('Restore this post?')\">?? Restore</a>";
                    echo "<a href='?force_delete=" . $row['id'] . "&page=".$page."' onclick=\"return confirm('Permanently delete this post?')\">? Delete Permanently</a>";
                } else {
                    echo "<a href='add-post.php?slug=" . urlencode($row['slug']) . "'>?? Content Edit</a>";
                    echo "<a href='view-add-all-post.php?slug=" . urlencode($row['slug']) . "'>?? View</a>";
                    echo "<a href='?delete=" . $row['id'] . "&page=".$page."' onclick=\"return confirm('Move this post to Recycle Bin?')\">??? Delete</a>";
                    echo "<p>Published on: " . date('F d, Y h:i A', strtotime($row['created_at'])) . "</p>";
                }

                echo "</div></div>";
            }
            
            // Pagination links
            echo '<div class="pagination">';
            if ($page > 1) {
                echo '<a href="?'.($isRecycle ? 'recycle=1&' : '').'page='.($page - 1).'">&laquo; Previous</a>';
            }
            
            for ($i = 1; $i <= $pages; $i++) {
                $active = ($i == $page) ? 'active' : '';
                echo '<a class="'.$active.'" href="?'.($isRecycle ? 'recycle=1&' : '').'page='.$i.'">'.$i.'</a>';
            }
            
            if ($page < $pages) {
                echo '<a href="?'.($isRecycle ? 'recycle=1&' : '').'page='.($page + 1).'">Next &raquo;</a>';
            }
            echo '</div>';
            
        } else {
            echo "<p>No blog xcel_posts found.</p>";
        }
        ?>

        <?php include('./footer.php'); ?>
    </div>
</body>
</html>