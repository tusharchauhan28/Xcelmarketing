<?php
$blogs = [
  [
    'title' => 'FACEBOOK: A Social Media Must Have',
    'link' => 'facebook-a-social-media-must-have.php',
    'image' => 'images/post-10-370x307.jpg',
    'desc' => 'Social media at present is a go to way to promote your business online...'
  ],
  [
    'title' => 'Boost your business on Google My Business',
    'link' => 'boost-your-business-on-google-my-business.php',
    'image' => 'images/post-11-370x307.jpg',
    'desc' => 'Google My Business is an easy and cost effective way to interact...'
  ],
  [
    'title' => 'Things to Keep in Mind While Outsourcing a Project',
    'link' => 'things-to-keep-in-mind-while-outsourcing-a-project.php',
    'image' => 'images/post-12-370x307.jpg',
    'desc' => 'Outsourcing has become a very common activity across industries...'
  ],
  [
    'title' => 'Why should you hire a digital marketing agency?',
    'link' => 'why-should-you-hire-a-digital-marketing-agency.php',
    'image' => 'images/post-13-370x307.jpg',
    'desc' => 'Online presence for businesses has now become a necessity...'
  ],
  [
    'title' => 'What makes a good website?',
    'link' => 'what-makes-a-good-website.php',
    'image' => 'images/post-14-370x307.jpg',
    'desc' => 'In today’s digital era, having a good website is like having a good shop...'
  ],
  [
    'title' => 'Do’s and Don’ts of Graphic Designing',
    'link' => 'dos-and-donts-of-graphic-designing.php',
    'image' => 'images/post-15-370x307.jpg',
    'desc' => 'Graphic designing is a powerful tool used by businesses...'
  ],
  [
    'title' => 'Digital Trends to watch in 2022',
    'link' => 'digital-trends-to-watch-in-2022.php',
    'image' => 'images/post-16-370x307.jpg',
    'desc' => 'The digital world is changing rapidly and staying up-to-date...'
  ],
  [
    'title' => 'How to get leads online?',
    'link' => 'how-to-get-leads-online.php',
    'image' => 'images/post-17-370x307.jpg',
    'desc' => 'Getting quality leads online is one of the biggest challenges...'
  ],
  [
    'title' => 'Why do businesses need branding?',
    'link' => 'why-do-businesses-need-branding.php',
    'image' => 'images/post-18-370x307.jpg',
    'desc' => 'Branding is not just about logos and colors, it’s how your audience...'
  ]
];

// Pagination logic
$blogsPerPage = 3; // Show 3 blogs per page
$totalBlogs = count($blogs);
$totalPages = ceil($totalBlogs / $blogsPerPage);
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($totalPages, $page)); // keep page in valid range
$startIndex = ($page - 1) * $blogsPerPage;
$currentBlogs = array_slice($blogs, $startIndex, $blogsPerPage);
?>

<!-- Blog Section HTML -->
<section class="section section-lg bg-default text-md-left">
  <div class="container">
    <h3 class="text-spacing-25 font-weight-normal title-opacity-9">Blog</h3>
    <div class="row row-60 row-sm">
      <?php foreach ($currentBlogs as $blog): ?>
        <div class="col-sm-6 col-lg-4 wow fadeInLeft">
          <article class="post post-modern">
            <a class="post-modern-figure" href="<?= $blog['link'] ?>">
              <img src="<?= $blog['image'] ?>" alt="blog-img" width="370" height="307" />
            </a>
            <h5 class="post-modern-title">
              <a href="<?= $blog['link'] ?>"><?= $blog['title'] ?></a>
            </h5>
            <p class="post-modern-text"><?= $blog['desc'] ?></p>
          </article>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center mt-4">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
          </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</section>
