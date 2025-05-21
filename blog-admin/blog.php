<?php include 'admin/config.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Our Blog</title>
</head>
<body>
    <h1>Latest Blog xcel_posts</h1>

    <?php
    $stmt = $pdo->query("SELECT * FROM xcel_posts ORDER BY created_at DESC");
    while ($row = $stmt->fetch()) {
        echo "<div style='margin-bottom: 20px;'>";
        echo "<h2>" . htmlspecialchars($row['title']) . "</h2>";
        echo "<p>" . nl2br(htmlspecialchars(substr($row['content'], 0, 200))) . "...</p>";
        echo "<small>Posted on " . $row['created_at'] . "</small>";
        echo "</div>";
    }
    ?>
</body>
</html>
