<?php

include 'config.php';



// Helper function to clean MS Word junk

function cleanContent($content) {

    // Allow only specific tags

    $content = strip_tags($content, '<p><b><strong><i><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6>');



    // Remove class and style attributes

    $content = preg_replace('/class="[^"]*"/i', '', $content);

    $content = preg_replace('/style="[^"]*"/i', '', $content);



    // Remove span tags

    $content = preg_replace('/<span[^>]*>/i', '', $content);

    $content = str_replace('</span>', '', $content);



    // Replace &nbsp; with normal space

    $content = str_replace('&nbsp;', ' ', $content);



    return $content;

}



// Get ID from URL

$id = $_GET['id'] ?? null;

$editing = false;

$post = [];



// If ID is provided, fetch the post details

if ($id) {

    $stmt = $conn->prepare("SELECT * FROM xcel_posts WHERE id = ?");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $post = $stmt->get_result()->fetch_assoc();

    $editing = true;

}



// Handle form submission (Update the post)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $id = $_POST['id'];

    $heading = cleanContent($_POST['heading']);

    $content = cleanContent($_POST['content']);

    // $sub_content = cleanContent($_POST['sub_content']);

    // $table_of_content = cleanContent($_POST['table_of_content']);

    $meta_title = cleanContent($_POST['meta_title']);

    $meta_description = cleanContent($_POST['meta_description']);

    $meta_keywords = cleanContent($_POST['meta_keywords']);

    $canonical_url = cleanContent($_POST['canonical_url']);



    // Handle image upload if a new image is selected

    if (!empty($_FILES['image']['name'])) {

        $imageName = $_FILES['image']['name'];

        $imageTmp = $_FILES['image']['tmp_name'];

        $uploadDir = 'uploads/';

        $uploadPath = $uploadDir . basename($imageName);

        

        if (!file_exists($uploadDir)) {

            mkdir($uploadDir, 0777, true);

        }



        move_uploaded_file($imageTmp, $uploadPath);

    } else {

        // If no new image uploaded, keep old image

        $stmt = $conn->prepare("SELECT image FROM xcel_posts WHERE id = ?");

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $oldImage = $stmt->get_result()->fetch_assoc();

        $imageName = $oldImage['image'];

    }



    // Update post

    $stmt = $conn->prepare("UPDATE xcel_posts SET title = ?, content = ?, sub_content = ?, table_of_content = ?, image = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, canonical_url = ? WHERE id = ?");

    $stmt->bind_param("sssssssssi", $heading, $content, $sub_content, $table_of_content, $imageName, $meta_title, $meta_description, $meta_keywords, $canonical_url, $id);



    if ($stmt->execute()) {

        // Fetch the slug of the updated post

        $slug = $post['slug']; // Assuming 'slug' is a field in your 'posts' table

    

        // Redirect to add-post.php with the slug

        // header("Location: add-post.php?slug=" . urlencode($slug));

        //   header("Location: add-post.php?slug=" . urlencode($slug));

      header("Location: view-posts.php" );

        exit();

    } else {

        echo "<p style='color: red;'>Failed to update post.</p>";

    }

}



// Fetch all posts for list view

$stmt = $conn->prepare("SELECT * FROM xcel_posts ORDER BY id DESC");

$stmt->execute();

$allPosts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>
    <link rel="stylesheet" href="./css/style.css">
    <title>Edit Blog Posts</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        
        .main-content {
            margin-left: 250px; /* Adjust based on your sidebar width */
            padding: 20px;
            width: calc(100% - 250px);
        }
        
        h2, h3 {
            color: #333;
            margin-top: 0;
            padding-top: 20px;
        }

        .post-list {
            width: 100%;
            margin: 30px auto;
        }

        .post-item {
            background: #fff;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .post-item:hover {
            transform: translateY(-3px);
        }

        .post-item strong {
            font-size: 18px;
            color: #444;
        }

        a.button {
            margin-top: 10px;
            display: inline-block;
            padding: 8px 16px;
            background: #0069d9;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }

        a.button:hover {
            background: #004c9d;
        }

        form {
            width: 100%;
            background: #fff;
            padding: 30px;
            margin: 30px auto;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        label {
            display: block;
            margin-top: 20px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus {
            border-color: #007bff;
            outline: none;
        }

        img {
            border-radius: 5px;
            margin-top: 10px;
            max-width: 100%;
            height: auto;
        }

        button {
            margin-top: 30px;
            padding: 12px 20px;
            font-size: 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #218838;
        }

        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            form, .post-list {
                padding: 15px;
            }

            input[type="text"],
            textarea {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    
    <div class="main-content">
        <h2>Edit Blog Posts</h2>

        <?php if (!$editing): ?>
            <div class="post-list">
                <?php foreach ($allPosts as $postItem): ?>
                    <div class="post-item">
                        <strong><?= htmlspecialchars($postItem['title']) ?></strong><br>
                        <a class="button" href="?id=<?= $postItem['id'] ?>">Edit</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>

            <h3>Edit Post: <?= htmlspecialchars($post['title']) ?></h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= htmlspecialchars($post['id']) ?>">

                <label>Heading</label>
                <input type="text" name="heading" value="<?= htmlspecialchars($post['title']) ?>" required>

                <label>Sub Content</label>
                <textarea name="content" rows="6" required><?= htmlspecialchars($post['content']) ?></textarea>
<!-- 
                <label>Sub Content</label>
                <textarea name="sub_content" rows="6" required><?= htmlspecialchars($post['sub_content']) ?></textarea> -->

     

                <label>Current Image:</label><br>
                <?php if (!empty($post['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($post['image']) ?>" alt="Current Image" style="max-width: 300px;"><br><br>
                <?php endif; ?>
                
                <label>Upload New Image (Optional)</label>
                <input type="file" name="image" accept="image/*">

                <label>Meta Title</label>
                <input type="text" name="meta_title" value="<?= htmlspecialchars($post['meta_title']) ?>" required>

                <label>Meta Description</label>
                <textarea name="meta_description" rows="3" required><?= htmlspecialchars($post['meta_description']) ?></textarea>
                

                <label>Meta Keywords</label>
                <input type="text" name="meta_keywords" value="<?= htmlspecialchars($post['meta_keywords']) ?>" required>

                <label>Canonical URL</label>
                <input type="text" name="canonical_url" value="<?= htmlspecialchars($post['canonical_url']) ?>" required>

                <button type="submit" name="update">Update Post</button>
            </form>

        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>