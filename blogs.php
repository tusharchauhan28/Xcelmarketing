<?php include 'blog-admin/config.php';?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Blogs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #eef2f3;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 40px;
        }
        .blog {
            background: #fff;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 100%;
           
        }
        .blog h2 {
            margin: 0 0 10px;
            color: #0056b3;
            font-size: 20px;
            margin-top: 10px;
        }
        .blog img {
            width: 376px; 
            height: 251px; 
            object-fit: cover;
            margin-top: 15px;
            border-radius: 6px;
        }
        .blog-content {
            color: #444;
            line-height: 1.6;
        }
        .read-more {
            display: inline-block;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
        }
        .read-more:hover {
            text-decoration: underline;
        }
                /* BLOG */
.blog-post {
 background: #ffffff; 
padding: 0px ;
border-radius: 10px;
box-shadow: none !important;
transition: transform 0.3s ease-in-out;
}
    </style>

    <?php include('header.php'); ?>
</head>
<body>

<div style="background-color: rgb(226, 223, 223);">

  <center>

    <h1 style="padding: 70px; color: rgb(0, 0, 0); font-weight: bolder; font-size: 65px; font-family: JetBrains Mono,monospace; ">

      <b>BL<b style="color: rgb(192, 53, 53);">O</b>G'S </b>

    </h1>

  </center>

</div>


<div class="container pt-5">
    <div class="row" id="blog-posts">
        <?php
        $result = $conn->query("SELECT * FROM xcel_posts ORDER BY created_at DESC");

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='col-md-4 mb-4 blog-post'>
                        <div class='blog'>";

                if (!empty($row['image'])) {
                    echo "<img src='https://xcelmarketing.in/blog-admin/uploads/" . htmlspecialchars($row['image']) . "' alt='Blog Image'>";
                }

                echo "<h2>" . htmlspecialchars($row['title']) . "</h2>";
                echo "<div class='blog-content'>" . nl2br(htmlspecialchars(substr($row['content'], 0, 250))) . "...</div>";

                if (!empty($row['link_url'])) {
                    echo "<a href='" . htmlspecialchars($row['link_url']) . "' class='read-more' target='_blank'>Read More <i class='fas fa-arrow-right'></i></a>";
                } else {
                   
             echo "<a href='/" . htmlspecialchars($row['slug']) . "' class='read-more'>Read More <i class='fas fa-arrow-right'></i></a>";                   }
                
                echo " 
   </div>
                      </div>";
                    
            }
        } else {
            echo "<p>No blog posts available.</p>";

        }

        ?>
   
    </div>

    <!-- Pagination buttons -->
    <div id="pagination" class="text-center my-4"></div>
</div>

<script>
    const postsPerPage = 6;
    const postsContainer = document.getElementById("blog-posts");
    const allPosts = postsContainer.querySelectorAll(".blog-post");
    const pagination = document.getElementById("pagination");

    const totalPages = Math.ceil(allPosts.length / postsPerPage);

    function showPage(page) {
        allPosts.forEach((post, index) => {
            post.style.display = (index >= (page - 1) * postsPerPage && index < page * postsPerPage) ? 'block' : 'none';
        });

        pagination.innerHTML = '';

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.innerText = i;
            btn.className = "btn btn-outline-primary mx-1";
            if (i === page) {
                btn.classList.add("active");
            }
            btn.addEventListener("click", () => showPage(i));
            pagination.appendChild(btn);
        }
    }

    showPage(1); 
</script>
<?php include 'footer.php';?>
</body>
</html>
