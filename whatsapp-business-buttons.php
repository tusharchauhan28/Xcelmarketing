<?php
include 'config.php';

$slug = 'whatsapp-business-buttons';

// Fetch blog content based on slug
$stmt = $conn->prepare("SELECT * FROM xcel_posts WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if ($post) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= htmlspecialchars($post['meta_title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($post['meta_description']) ?>">
<meta name="keywords" content="<?= htmlspecialchars($post['meta_keywords']) ?>">
<link rel="canonical" href="<?php echo htmlspecialchars($post['canonical_url'] ?? ('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])); ?>" />


</head>
<body>

    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <p><?= nl2br(htmlspecialchars($post['sub_content'])) ?></p>

    <?php if (!empty($post['image'])): ?>
        <img src="uploads/<?= htmlspecialchars($post['image']) ?>" alt="Post Image" />
    <?php endif; ?>

    <?php if (!empty($post['table_of_content'])): ?>
        <div class="table-of-content"><?= nl2br(htmlspecialchars($post['table_of_content'])) ?></div>
    <?php endif; ?>

</body>
</html>
<?php
} else {
    echo "Post not found.";
}
?>