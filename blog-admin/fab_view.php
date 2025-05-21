<?php
// Connect database
include 'config.php';

// Get slug from URL
if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    // Process the slug (e.g., query the database or show the content)
} else {
    echo "No slug provided!";
}

// Fetch post by slug
$stmt = $conn->prepare("SELECT * FROM  xcel_posts WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

// If no post found
if (!$post) {
    die("Post not found");
}

// Set dynamic meta variables
$meta_title = $post['meta_title'] ?? 'Default Blog Title';
$meta_description = $post['meta_description'] ?? 'Default Blog Description';
$meta_keywords = $post['meta_keywords'] ?? 'Default Blog Keywords';

// Now include header (which uses meta variables)
include './includes/header.php';
include './includes/links.php';

// Generate Table of Contents from sub_content
preg_match_all('/<h[2-3][^>]*>(.*?)<\/h[2-3]>/', $post['sub_content'], $matches);

$generated_toc = '<ul class="list-unstyled">';
foreach ($matches[1] as $index => $heading) {
    $id = 'heading-' . $index;
    $post['sub_content'] = preg_replace(
        '/'.preg_quote($matches[0][$index], '/').'/', 
        '<h2 id="'.$id.'">'.$heading.'</h2>',
        $post['sub_content'],
        1
    );
    $generated_toc .= '<li><a class="nav-link mb-2 px-0" href="#'.$id.'">'.$heading.'</a></li>';
}
$generated_toc .= '</ul>';

// Use DB TOC if available, else use generated
$toc = !empty($post['table_of_content']) ? $post['table_of_content'] : $generated_toc;

?>

<!-- Style for TOC -->
<style>
.toc-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.toc-nav li {
    display: inline-block;
}

.toc-nav a {
    text-decoration: none;
    padding: 6px 12px;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 5px;
    color: #333;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.toc-nav a:hover {
    background-color: #e2e6ea;
}

.banner {
    background-size: cover;
    background-position: center;
    padding: 100px 0;
    position: relative;
    color: #fff;
    text-align: center;
}
.banner .overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}
.banner h1 {
    position: relative;
    z-index: 1;
    font-size: 48px;
}
.breadcrumb-container {
    position: relative;
    z-index: 1;
    margin-top: 20px;
}
</style>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Banner Section -->
<div class="banner" style="background-image: url('https://rcsmessage.co.in/assets/images/contact/contact-us1.avif');">
    <div class="overlay"></div>
    <h1>BLOG</h1>
    <div class="breadcrumb-container">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="text-white">Home</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Blog Details</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container my-5">
    <div class="row">
        
        <!-- Table of Contents -->
        <div class="col-md-3 mb-4">
            <div class="p-3 bg-light border rounded sticky-top" style="top: 100px;">
                <h5>Table of Contents</h5>
                <div class="toc-nav">
                    <?= $toc ?>
                </div>
            </div>
        </div>

        <!-- Blog Content -->
        <div class="col-md-9">
            <!-- Show Published Date -->
            <p class="text-muted">
                Published on: <?= date('F d, Y h:i A', strtotime($post['created_at'])) ?>
            </p>
            <!-- Show Blog Content -->
            <div><?= $post['sub_content'] ?></div>
        </div>

    </div>
</div>

<?php include './includes/footer.php'; ?>
