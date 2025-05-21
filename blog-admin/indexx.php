<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}


include 'config.php';

// Get stats for dashboard
$totalxcel_posts = $conn->query("SELECT COUNT(*) as total FROM xcel_posts WHERE deleted = 0")->fetch_assoc()['total'];
$recentxcel_posts = $conn->query("SELECT COUNT(*) as recent FROM xcel_posts WHERE deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['recent'];
$trashedxcel_posts = $conn->query("SELECT COUNT(*) as trashed FROM xcel_posts WHERE deleted = 1")->fetch_assoc()['trashed'];
// $draftxcel_posts = $conn->query("SELECT COUNT(*) as drafts FROM xcel_posts WHERE status = 'draft' AND deleted = 0")->fetch_assoc()['drafts'];


// Soft Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("UPDATE xcel_posts SET deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: view-xcel_posts.php?deleted=1");
    exit;
}

// Restore (optional if you have restore button)
if (isset($_GET['restore']) && is_numeric($_GET['restore'])) {
    $id = $_GET['restore'];
    $stmt = $conn->prepare("UPDATE xcel_posts SET deleted = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: view-xcel_posts.php?restored=1");
    exit;
}

// Permanent Delete (optional if you have permanent delete button)
if (isset($_GET['force_delete']) && is_numeric($_GET['force_delete'])) {
    $id = $_GET['force_delete'];

    $stmt = $conn->prepare("SELECT image FROM xcel_posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($image);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM xcel_posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($image && file_exists("uploads/$image")) {
        unlink("uploads/$image");
    }

    header("Location: view-xcel_posts.php?permanently_deleted=1");
    exit;
}



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
    <style>
        :root {
            --primary-color: #4e73df;
            --accent-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --bg-light: #f8f9fc;
            --sidebar-bg: #2c3e50;
            --text-light: #ffffff;
            --text-dark: #5a5c69;
            --hover-color: #16a085;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Nunito', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
            transition: var(--transition);
        }

        header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            padding: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .sidebar-toggle {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
            transition: var(--transition);
        }

        .sidebar-toggle:hover {
            transform: rotate(90deg);
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: var(--sidebar-bg);
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 80px;
            transform: translateX(0);
            transition: var(--transition);
            z-index: 99;
            overflow-y: auto;
        }

        .sidebar.active {
            transform: translateX(-250px);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: var(--text-light);
            padding: 15px 20px;
            text-decoration: none;
            font-size: 1rem;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid var(--accent-color);
            color: white;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 3px solid var(--accent-color);
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: var(--transition);
        }

        .content.shifted {
            margin-left: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
        }

        .dashboard-header h2 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-header p {
            color: var(--text-dark);
            font-size: 1rem;
            opacity: 0.8;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(58, 59, 69, 0.2);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0.1), rgba(255,255,255,0));
            z-index: 1;
        }

        .stat-card.primary {
            border-left-color: var(--primary-color);
        }

        .stat-card.success {
            border-left-color: var(--accent-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.danger {
            border-left-color: var(--danger-color);
        }

        .stat-card.info {
            border-left-color: var(--info-color);
        }

        .stat-card .title {
            font-size: 0.9rem;
            color: var(--text-dark);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
            z-index: 2;
            position: relative;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            z-index: 2;
            position: relative;
        }

        .stat-card .icon {
            font-size: 2.5rem;
            position: absolute;
            right: 20px;
            top: 20px;
            opacity: 0.2;
            z-index: 0;
            transition: var(--transition);
        }

        .stat-card:hover .icon {
            opacity: 0.3;
            transform: scale(1.1);
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .action-btn {
            background: white;
            border: none;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            flex: 1 1 200px;
        }

        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(78, 115, 223, 0.3);
        }

        .action-btn i {
            font-size: 1.2rem;
        }

        .analytics-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1.5rem rgba(58, 59, 69, 0.15);
        }

        .chart-card h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.2rem;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .recent-xcel_posts {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .recent-xcel_posts h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.3rem;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px 15px;
            background-color: #f8f9fc;
            color: var(--text-dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .action-link {
            padding: 6px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            margin-right: 5px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .edit-link {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }

        .edit-link:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .delete-link {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }

        .delete-link:hover {
            background-color: var(--danger-color);
            color: white;
        }

        .view-link {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--accent-color);
        }

        .view-link:hover {
            background-color: var(--accent-color);
            color: white;
        }

        .no-xcel_posts {
            text-align: center;
            padding: 30px;
            color: var(--text-dark);
            opacity: 0.7;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-published {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--accent-color);
        }

        .badge-draft {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
        }

        .badge-scheduled {
            background-color: rgba(54, 185, 204, 0.1);
            color: var(--info-color);
        }

        .weakness-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .weakness-high {
            background-color: var(--danger-color);
        }

        .weakness-medium {
            background-color: var(--warning-color);
        }

        .weakness-low {
            background-color: var(--accent-color);
        }

        .analytics-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .summary-card h4 {
            margin-bottom: 15px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
        }

        @media (max-width: 1200px) {
            .analytics-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: block;
            }
            
            .action-btn {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>

<header>
    <span><i class="fas fa-tachometer-alt"></i> Blog Admin Dashboard</span>
    <button class="sidebar-toggle" onclick="toggleSidebar()">?</button>
</header>

<div class="sidebar" id="sidebar">
    <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="add-all-post.php"><i class="fas fa-plus-circle"></i> Add New Post</a>
    <a href="view-xcel_posts.php"><i class="fas fa-list"></i> View All xcel_posts</a>
    <a href="edit-profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
    <a href="recycle-bin.php"><i class="fas fa-trash-restore"></i> Recycle Bin</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content" id="mainContent">
    <div class="container">
        <div class="dashboard-header">
            <h2><i class="fas fa-chart-line"></i> Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</h2>
            <p>Here's your blog performance analytics and recent activity</p>
        </div>

        <div class="stats-cards">
            <div class="stat-card primary">
                <div class="title">Total xcel_posts</div>
                <div class="value"><?php echo $totalxcel_posts; ?></div>
                <i class="fas fa-file-alt icon"></i>
            </div>
            <div class="stat-card success">
                <div class="title">Recent xcel_posts (7 days)</div>
                <div class="value"><?php echo $recentxcel_posts; ?></div>
                <i class="fas fa-clock icon"></i>
            </div>
            <!-- <div class="stat-card warning">
                <div class="title">Draft xcel_posts</div>
                <div class="value"><?php echo $draftxcel_posts; ?></div>
                <i class="fas fa-edit icon"></i>
            </div> -->
            <div class="stat-card danger">
                <div class="title">Trashed xcel_posts</div>
                <div class="value"><?php echo $trashedxcel_posts; ?></div>
                <i class="fas fa-trash icon"></i>
            </div>
        </div>

        <div class="quick-actions">
            <a href="add-all-post.php" class="action-btn">
                <i class="fas fa-plus"></i> Add New Post
            </a>
            <a href="view-xcel_posts.php" class="action-btn">
                <i class="fas fa-list"></i> View All xcel_posts
            </a>
            <a href="edit-profile.php" class="action-btn">
                <i class="fas fa-user-cog"></i> Edit Profile
            </a>
            <a href="recycle-bin.php" class="action-btn">
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
                                    ' . $month['count'] . ' xcel_posts
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
                                    ' . $week['count'] . ' xcel_posts
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
                                    ' . $day['count'] . ' xcel_posts
                                </span>
                              </div>';
                    }
                } else {
                    echo '<p>No day-of-week data available</p>';
                }
                ?>
            </div>
        </div>
<!-- 
        <div class="analytics-section">
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Monthly Post Activity</h3>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Weekly Post Trend</h3>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3><i class="fas fa-calendar-week"></i> Day of Week Performance</h3>
                <div class="chart-container">
                    <canvas id="dowChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3><i class="fas fa-clock"></i> Hourly Post Distribution</h3>
                <div class="chart-container">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div> -->

        <div class="recent-xcel_posts">
    <h3><i class="fas fa-clock"></i> Recent Blog xcel_posts</h3>

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
            $total_xcel_posts = $total_row['total'];
            $total_pages = ceil($total_xcel_posts / $per_page);
            
            // Get xcel_posts for current page
            $sql = "SELECT id, title, slug, created_at FROM xcel_posts WHERE deleted = 0 
                    ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $serial = $offset + 1;
            
                // Inside your loop
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

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="?page=1" class="page-link first"><i class="fas fa-angle-double-left"></i></a>
            <a href="?page=<?= $current_page - 1 ?>" class="page-link prev"><i class="fas fa-angle-left"></i></a>
        <?php endif; ?>
        
        <?php 
        // Show page numbers
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