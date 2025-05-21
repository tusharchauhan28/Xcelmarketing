<?php

session_start();








include 'config.php';


// Handle delete action
if (isset($_GET['delete'])) {
    $post_id = (int)$_GET['delete'];
    
    // Soft delete (mark as deleted rather than actually deleting)
    $stmt = $conn->prepare("UPDATE xcel_posts SET deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Post moved to Recycle Bin successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error moving post to Recycle Bin: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    $stmt->close();
    
    // Redirect to avoid resubmission on refresh
    header("Location: index.php");
    exit();
}
// Get stats for dashboard

$totalXcelPosts = $conn->query("SELECT COUNT(*) as total FROM xcel_posts WHERE deleted = 0")->fetch_assoc()['total'];

$recentXcelPosts = $conn->query("SELECT COUNT(*) as recent FROM xcel_posts WHERE deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['recent'];

$trashedXcelPosts = $conn->query("SELECT COUNT(*) as trashed FROM xcel_posts WHERE deleted = 1")->fetch_assoc()['trashed'];



// Get monthly post data for chart

$monthlyData = [];

$monthlyQuery = $conn->query("

    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 

           COUNT(*) as count 

    FROM xcel_posts 

    WHERE deleted = 0 

    GROUP BY month 

    ORDER BY month DESC 

    LIMIT 12

");

while ($row = $monthlyQuery->fetch_assoc()) {

    $monthlyData[] = $row;

}



// Get weekly post data for chart

$weeklyData = [];

$weeklyQuery = $conn->query("

    SELECT YEARWEEK(created_at, 1) as week, 

           COUNT(*) as count 

    FROM xcel_posts 

    WHERE deleted = 0 

    GROUP BY week 

    ORDER BY week DESC 

    LIMIT 8

");

while ($row = $weeklyQuery->fetch_assoc()) {

    $weeklyData[] = $row;

}



// Get day-of-week performance

$dayOfWeekData = [];

$dowQuery = $conn->query("

    SELECT DAYNAME(created_at) as day, 

           COUNT(*) as count 

    FROM xcel_posts 

    WHERE deleted = 0 

    GROUP BY day 

    ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')

");

while ($row = $dowQuery->fetch_assoc()) {

    $dayOfWeekData[] = $row;

}



// Get hourly performance

$hourlyData = [];

$hourlyQuery = $conn->query("

    SELECT HOUR(created_at) as hour, 

           COUNT(*) as count 

    FROM xcel_posts 

    WHERE deleted = 0 

    GROUP BY hour 

    ORDER BY hour

");

while ($row = $hourlyQuery->fetch_assoc()) {

    $hourlyData[] = $row;

}



// Prepare data for JavaScript

$monthlyLabels = json_encode(array_column($monthlyData, 'month'));

$monthlyValues = json_encode(array_column($monthlyData, 'count'));

$weeklyLabels = json_encode(array_column($weeklyData, 'week'));

$weeklyValues = json_encode(array_column($weeklyData, 'count'));

$dowLabels = json_encode(array_column($dayOfWeekData, 'day'));

$dowValues = json_encode(array_column($dayOfWeekData, 'count'));

$hourlyLabels = json_encode(array_column($hourlyData, 'hour'));

$hourlyValues = json_encode(array_column($hourlyData, 'count'));

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Blog Admin Panel</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

</head>

<body>



<?php include 'header.php'; ?>

<?php include 'sidebar.php'; ?>



<div class="content" id="mainContent">

    <div class="container">

        <div class="dashboard-header">

            <h2><i class="fas fa-chart-line"></i> Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</h2>

            <p>Here's your blog performance analytics and recent activity</p>

        </div>



        <div class="stats-cards">

            <div class="stat-card primary">

                <div class="title">Total posts</div>

                <div class="value"><?php echo $totalXcelPosts; ?></div>

                <i class="fas fa-file-alt icon"></i>

            </div>

            <div class="stat-card success">

                <div class="title">Recent posts (7 days)</div>

                <div class="value"><?php echo $recentXcelPosts; ?></div>

                <i class="fas fa-clock icon"></i>

            </div>

            <div class="stat-card danger">

                <div class="title">Trashed posts</div>

                <div class="value"><?php echo $trashedXcelPosts; ?></div>

                <i class="fas fa-trash icon"></i>

            </div>

        </div>



     
        <div class="quick-actions">

            <a href="add-all-post.php" class="action-btn">

                <i class="fas fa-plus"></i> Add New Post

            </a>

            <a href="view-posts.php" class="action-btn">

                <i class="fas fa-list"></i> View All Posts

            </a>

            <a href="edit-profile.php" class="action-btn">

                <i class="fas fa-user-cog"></i> Edit Profile

            </a>

            <a href="view-posts.php?recycle=1" class="action-btn">

                <i class="fas fa-trash-restore"></i> Recycle Bin

            </a>

        </div>


        <div class="analytics-summary">

            <div class="summary-card">

                <h4><i class="fas fa-calendar-alt"></i> Monthly Performance</h4>

                <?php

                if (!empty($monthlyData)) {

                    $maxxcel_posts = max(array_column($monthlyData, 'count'));

                    foreach ($monthlyData as $month) {

                        $percentage = round(($month['count'] / $maxxcel_posts) * 100);

                        $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');

                        echo '<div class="summary-item">

                                <span class="summary-label">' . date('M Y', strtotime($month['month'] . '-01')) . '</span>

                                <span class="summary-value">

                                    <span class="weakness-indicator ' . $weaknessClass . '"></span>

                                    ' . $month['count'] . ' Posts

                                </span>

                              </div>';

                    }

                } else {

                    echo '<p>No monthly data available</p>';

                }

                ?>

            </div>



            <div class="summary-card">

                <h4><i class="fas fa-calendar-week"></i> Weekly Performance</h4>

                <?php

                if (!empty($weeklyData)) {

                    $maxWeekly = max(array_column($weeklyData, 'count'));

                    foreach ($weeklyData as $week) {

                        $percentage = round(($week['count'] / $maxWeekly) * 100);

                        $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');

                        echo '<div class="summary-item">

                                <span class="summary-label">Week ' . substr($week['week'], 4) . '</span>

                                <span class="summary-value">

                                    <span class="weakness-indicator ' . $weaknessClass . '"></span>

                                    ' . $week['count'] . ' Posts

                                </span>

                              </div>';

                    }

                } else {

                    echo '<p>No weekly data available</p>';

                }

                ?>

            </div>



            <div class="summary-card">

                <h4><i class="fas fa-calendar-day"></i> Day of Week Performance</h4>

                <?php

                if (!empty($dayOfWeekData)) {

                    $maxDow = max(array_column($dayOfWeekData, 'count'));

                    foreach ($dayOfWeekData as $day) {

                        $percentage = round(($day['count'] / $maxDow) * 100);

                        $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');

                        echo '<div class="summary-item">

                                <span class="summary-label">' . $day['day'] . '</span>

                                <span class="summary-value">

                                    <span class="weakness-indicator ' . $weaknessClass . '"></span>

                                    ' . $day['count'] . ' Posts

                                </span>

                              </div>';

                    }

                } else {

                    echo '<p>No day-of-week data available</p>';

                }

                ?>

            </div>

        </div>



        <div class="recent-posts">

            <h3><i class="fas fa-clock"></i> Recent Blog Posts</h3>

            <table>

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Title</th>

                        <th>Status</th>

                        <th>Created At</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                    <?php

                    // Pagination variables

                    $per_page = 10;

                    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

                    $offset = ($current_page - 1) * $per_page;

                    

                    // Get total number of xcel_posts

                    $total_query = "SELECT COUNT(*) as total FROM xcel_posts WHERE deleted = 0";
                    $total_result = $conn->query($total_query);
                    $total_row = $total_result->fetch_assoc();
                    $total_posts = $total_row['total'];
                    $total_pages = ceil($total_posts / $per_page);

                    

                    // Get xcel_posts for current page

                    $sql = "SELECT id, title, slug, created_at FROM xcel_posts WHERE deleted = 0 

                            ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";

                    $result = $conn->query($sql);

                    

                    if ($result->num_rows > 0) {

                        $serial = $offset + 1;

                    

                        while ($row = $result->fetch_assoc()) {

                            echo "<tr>";

                            echo "<td>" . $serial++ . "</td>";

                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";

                            echo "<td><span class='badge badge-published'>Published</span></td>";

                            echo "<td>" . date('d M Y, h:i A', strtotime($row['created_at'])) . "</td>";

                            echo "<td>

                                    <a href='edit-post.php?id=" . $row['id'] . "' class='action-link edit-link'>

                                        <i class='fas fa-edit'></i> Edit

                                    </a>

                                    <a href='view-add-all-post.php?slug=" . urlencode($row['slug']) . "' class='action-link view-link'>

                                        <i class='fas fa-eye'></i> View

                                    </a>

                                    <a href='?delete=" . $row['id'] . "' onclick=\"return confirm('Move this post to Recycle Bin?')\" class='action-link delete-link'>

                                        <i class='fas fa-trash'></i> Delete

                                    </a>

                                  </td>";

                            echo "</tr>";

                        }

                    } else {

                        echo "<tr><td colspan='5' class='no-xcel_posts'><i class='far fa-folder-open'></i><br>No xcel_posts found. Create your first post!</td></tr>";

                    }

                    ?>

                </tbody>

            </table>



            <?php if ($total_pages > 1): ?>

            <div class="pagination">

                <?php if ($current_page > 1): ?>

                    <a href="?page=1" class="page-link first"><i class="fas fa-angle-double-left"></i></a>

                    <a href="?page=<?= $current_page - 1 ?>" class="page-link prev"><i class="fas fa-angle-left"></i></a>

                <?php endif; ?>

                

                <?php 

                $start = max(1, $current_page - 2);

                $end = min($total_pages, $current_page + 2);

                

                for ($i = $start; $i <= $end; $i++): ?>

                    <a href="?page=<?= $i ?>" class="page-link <?= ($i == $current_page) ? 'active' : '' ?>"><?= $i ?></a>

                <?php endfor; ?>

                

                <?php if ($current_page < $total_pages): ?>

                    <a href="?page=<?= $current_page + 1 ?>" class="page-link next"><i class="fas fa-angle-right"></i></a>

                    <a href="?page=<?= $total_pages ?>" class="page-link last"><i class="fas fa-angle-double-right"></i></a>

                <?php endif; ?>

            </div>

            <?php endif; ?>

        </div>

    </div>

</div>



<?php include('footer.php'); ?>



<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>







<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>



<script>



    function toggleSidebar() {



        const sidebar = document.getElementById('sidebar');



        const content = document.getElementById('mainContent');



        sidebar.classList.toggle('active');



        content.classList.toggle('shifted');



    }







    // Monthly Chart



    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');



    const monthlyChart = new Chart(monthlyCtx, {



        type: 'bar',



        data: {



            labels: <?php echo $monthlyLabels; ?>,



            datasets: [{



                label: 'xcel_posts Published',



                data: <?php echo $monthlyValues; ?>,



                backgroundColor: 'rgba(78, 115, 223, 0.5)',



                borderColor: 'rgba(78, 115, 223, 1)',



                borderWidth: 1



            }]



        },



        options: {



            responsive: true,



            maintainAspectRatio: false,



            scales: {



                y: {



                    beginAtZero: true,



                    ticks: {



                        stepSize: 1



                    }



                }



            },



            plugins: {



                tooltip: {



                    callbacks: {



                        label: function(context) {



                            return context.parsed.y + ' xcel_posts';



                        }



                    }



                }



            }



        }



    });







    // Weekly Chart



    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');



    const weeklyChart = new Chart(weeklyCtx, {



        type: 'line',



        data: {



            labels: <?php echo $weeklyLabels; ?>,



            datasets: [{



                label: 'xcel_posts Published',



                data: <?php echo $weeklyValues; ?>,



                backgroundColor: 'rgba(28, 200, 138, 0.1)',



                borderColor: 'rgba(28, 200, 138, 1)',



                borderWidth: 2,



                tension: 0.3,



                fill: true



            }]



        },



        options: {



            responsive: true,



            maintainAspectRatio: false,



            scales: {



                y: {



                    beginAtZero: true,



                    ticks: {



                        stepSize: 1



                    }



                }



            },



            plugins: {



                tooltip: {



                    callbacks: {



                        label: function(context) {



                            return context.parsed.y + ' xcel_posts';



                        }



                    }



                }



            }



        }



    });







    // Day of Week Chart



    const dowCtx = document.getElementById('dowChart').getContext('2d');



    const dowChart = new Chart(dowCtx, {



        type: 'doughnut',



        data: {



            labels: <?php echo $dowLabels; ?>,



            datasets: [{



                data: <?php echo $dowValues; ?>,



                backgroundColor: [



                    'rgba(78, 115, 223, 0.7)',



                    'rgba(54, 185, 204, 0.7)',



                    'rgba(28, 200, 138, 0.7)',



                    'rgba(246, 194, 62, 0.7)',



                    'rgba(231, 74, 59, 0.7)',



                    'rgba(153, 102, 255, 0.7)',



                    'rgba(255, 159, 64, 0.7)'



                ],



                borderWidth: 1



            }]



        },



        options: {



            responsive: true,



            maintainAspectRatio: false,



            plugins: {



                legend: {



                    position: 'right',



                },



                tooltip: {



                    callbacks: {



                        label: function(context) {



                            return context.label + ': ' + context.raw + ' xcel_posts';



                        }



                    }



                }



            }



        }



    });







    // Hourly Chart



    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');



    const hourlyChart = new Chart(hourlyCtx, {



        type: 'polarArea',



        data: {



            labels: <?php echo $hourlyLabels; ?>.map(h => h + ':00'),



            datasets: [{



                data: <?php echo $hourlyValues; ?>,



                backgroundColor: Array(24).fill().map((_, i) => 



                    `hsl(${i * 15}, 70%, 60%)`



                ),



                borderWidth: 1



            }]



        },



        options: {



            responsive: true,



            maintainAspectRatio: false,



            plugins: {



                legend: {



                    display: false



                },



                tooltip: {



                    callbacks: {



                        label: function(context) {



                            return context.label + ': ' + context.raw + ' xcel_posts';



                        }



                    }



                }



            },



            scales: {



                r: {



                    pointLabels: {



                        display: false



                    }



                }



            }



        }



    });







    // Add active class to current page in sidebar



    document.addEventListener('DOMContentLoaded', function() {



        const currentPage = window.location.pathname.split('/').pop();



        const links = document.querySelectorAll('.sidebar a');



        



        links.forEach(link => {



            if (link.getAttribute('href') === currentPage) {



                link.classList.add('active');



            }



        });



    });



</script>



</body>

</html>