<?php

include 'config.php';



$urlFromQuery = $_GET['slug'] ?? '';



// Handle form submission

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $heading = $_POST['heading'];

    $content = $_POST['content'];

    $link_url = $_POST['link_url'];

    $sub_content = $_POST['sub_content'] ?? '';

    $table_of_content = $_POST['table_of_content'] ?? '';

    $meta_title = $_POST['meta_title'] ?? '';

    $meta_description = $_POST['meta_description'] ?? '';

    $meta_keywords = $_POST['meta_keywords'] ?? '';

    $canonical_url = $_POST['canonical_url'] ?? '';



    // Generate slug from link_url or heading

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', basename($link_url))));



    // Handle image upload

    $imageName = $_FILES['image']['name'];

    $imageTmp = $_FILES['image']['tmp_name'];

    $uploadDir = 'uploads/';

    $uploadPath = $uploadDir . basename($imageName);



    // Ensure uploads directory exists

    if (!file_exists($uploadDir)) {

        mkdir($uploadDir, 0777, true);

    }



    if (move_uploaded_file($imageTmp, $uploadPath)) {

        // Insert into DB

        $stmt = $conn->prepare("INSERT INTO xcel_posts (title, slug, content, sub_content, image, table_of_content, meta_title, meta_description, meta_keywords, canonical_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssssss", $heading, $slug, $content, $sub_content, $imageName, $table_of_content, $meta_title, $meta_description, $meta_keywords, $canonical_url);



        if ($stmt->execute()) {

            // Generate PHP file

            $generatedFileName = $slug . '.php';

            $generatedFilePath = __DIR__ . '/../' . $generatedFileName;



            // Content of the generated PHP file

            $fileContent = <<<PHP

<?php

include 'config.php';



\$slug = '$slug';



// Fetch blog content based on slug

\$stmt = \$conn->prepare("SELECT * FROM xcel_posts WHERE slug = ?");

\$stmt->bind_param("s", \$slug);

\$stmt->execute();

\$result = \$stmt->get_result();

\$post = \$result->fetch_assoc();



if (\$post) {

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars(\$post['meta_title']) ?></title>

    <meta name="description" content="<?= htmlspecialchars(\$post['meta_description']) ?>">

    <meta name="keywords" content="<?= htmlspecialchars(\$post['meta_keywords']) ?>">

    <link rel="canonical" href="<?php echo htmlspecialchars(\$post['canonical_url'] ?? ('https://' . \$_SERVER['HTTP_HOST'] . \$_SERVER['REQUEST_URI'])); ?>" />

</head>

<body>

    <h1><?= htmlspecialchars(\$post['title']) ?></h1>

    <p><?= nl2br(htmlspecialchars(\$post['sub_content'])) ?></p>



    <?php if (!empty(\$post['image'])): ?>

        <img src="uploads/<?= htmlspecialchars(\$post['image']) ?>" alt="Post Image" />

    <?php endif; ?>



    <?php if (!empty(\$post['table_of_content'])): ?>

        <div class="table-of-content"><?= nl2br(htmlspecialchars(\$post['table_of_content'])) ?></div>

    <?php endif; ?>

</body>

</html>

<?php

} else {

    echo "Post not found.";

}

?>

PHP;



            if (file_put_contents($generatedFilePath, $fileContent)) {

                header("Location: add-post.php?slug=$slug");

                exit();

            }

        }

    }

}

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <?php include 'header.php'; ?>

    <link rel="stylesheet" href="./css/style.css">

    <title>Add Blog Post</title>

    <style>

 

        .sidebar {

            /* width: 250px; */

            /* background: #f8f9fa; */

            /* padding: 20px; */

        }

        

        .main-content {

            flex: 1;

            padding: 20px;

            margin-bottom: 50px;

            margin-left: 250px;

        }

        

        .form-container {

            width: 100%;

            max-width: 100%;

            background: white;

            padding: 20px;

            border-radius: 10px;

            box-shadow: 0 0 10px rgba(0,0,0,0.1);

        }

        

        h2 {

            text-align: center;

            margin-bottom: 20px;

        }

        

        label {

            margin-top: 15px;

            display: block;

            font-weight: bold;

        }

        

        input[type="text"],

        textarea,

        input[type="file"],

        select {

            width: 100%;

            padding: 10px;

            margin-top: 5px;

            box-sizing: border-box;

            border: 1px solid #ddd;

            border-radius: 4px;

        }

        

        textarea {

            min-height: 50px;

            resize: vertical;

        }

        

        button {

            margin-top: 20px;

            padding: 10px 20px;

            background: #007bff;

            color: white;

            border: none;

            border-radius: 5px;

            cursor: pointer;

            font-size: 16px;

            width: 100%;

        }

        

        button:hover {

            background: #0056b3;

        }

        

        .form-row {

            display: flex;

            gap: 20px;

        }

        

        .form-group {

            flex: 1;

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

    </style>

</head>

<body>

    <?php include './sidebar.php'; ?>

    

    <div class="main-content ">



        <div class="btn-top">

    <div class="btn-container">

    <h2 class="left-btn"> ➕ Add New Blog Post</h2>

    

        

        <!-- You can add another button on the right if needed -->

        <div class="right-btns">

  

            <a href="view-posts.php" class="right-btn">📝 Blog Posts</a>

    

       

    

  

        </div>

    </div>

</div>

        

        <div class="form-container">

            <form method="POST" enctype="multipart/form-data">

                <div class="form-row">

                    <div class="form-group">

                        <label>Heading</label>

                        <input type="text" name="heading" id="heading" required>

                    </div>

                    <div class="form-group">

                        <label>Link URL</label>

                        <input type="text" name="link_url" id="link_url" placeholder="https://example.com/page" required value="<?= htmlspecialchars($urlFromQuery) ?>">

                    </div>

                </div>

                

                <label> Sub Content</label>

                <textarea name="content" id="content" rows="1" required></textarea>

                

                <label>Image</label>

                <input type="file" name="image" id="image" accept="image/*" required>

                

                <div class="form-row">

                    <div class="form-group">

                        <label>Meta Title</label>

                        <input type="text" name="meta_title" id="meta_title" required>

                    </div>

                    <div class="form-group">

                        <label>Meta Keywords</label>

                        <input type="text" name="meta_keywords" id="meta_keywords" required>

                    </div>

                </div>

                

                <label>Meta Description</label>

                <textarea name="meta_description" id="meta_description" rows="2" required></textarea>

                

                <label>Canonical URL</label>

                <input type="text" name="canonical_url" id="canonical_url" placeholder="https://example.com/blog/your-post" required>

                

                <button type="submit">Submit</button>

            </form>

        </div>

    </div>

    

    <?php include('./footer.php'); ?>

</body>

</html>