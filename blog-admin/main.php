<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit();
}


include 'config.php';

// Get stats for dashboard - add these lines with your existing stats

// $totalxcel_posts = $conn->query("SELECT COUNT(*) as total FROM xcel_posts and xcel_xcel_posts WHERE deleted = 0")->fetch_assoc()['total'];
// $totalfabxcel_posts = $conn->query("SELECT COUNT(*) as total FROM xcel_xcel_posts WHERE deleted = 0")->fetch_assoc()['total'];
// $recentxcel_posts = $conn->query("SELECT COUNT(*) as recent FROM xcel_posts WHERE deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['recent'];
// $recentfabxcel_posts = $conn->query("SELECT COUNT(*) as recent FROM xcel_xcel_posts WHERE deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['recent'];
// $trashedxcel_posts = $conn->query("SELECT COUNT(*) as trashed FROM xcel_posts WHERE deleted = 1")->fetch_assoc()['trashed'];
// $trashedfabxcel_posts = $conn->query("SELECT COUNT(*) as trashed FROM xcel_xcel_posts WHERE deleted = 1")->fetch_assoc()['trashed'];



// Get stats for dashboard
$totalxcel_posts = $conn->query("SELECT COUNT(*) as total FROM xcel_posts WHERE deleted = 0")->fetch_assoc()['total'];
$totalXcelxcel_posts = $conn->query("SELECT COUNT(*) as total FROM xcel_xcel_posts WHERE deleted = 0")->fetch_assoc()['total'];
$totalfabxcel_posts = $conn->query("SELECT COUNT(*) as total FROM fab_xcel_posts WHERE deleted = 0")->fetch_assoc()['total'];
$totalAllxcel_posts = $totalxcel_posts + $totalXcelxcel_posts + $totalfabxcel_posts; 


$recentxcel_posts = $conn->query("SELECT COUNT(*) as recent FROM xcel_posts WHERE deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)")->fetch_assoc()['recent'];
$recentfabxcel_posts = $conn->query("SELECT COUNT(*) as recent FROM fab_xcel_posts WHERE deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)")->fetch_assoc()['recent'];
$recentXcelxcel_posts = $conn->query("SELECT COUNT(*) as recent FROM xcel_xcel_posts WHERE deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)")->fetch_assoc()['recent'];
$recentAllxcel_posts = $recentxcel_posts + $recentfabxcel_posts + $recentXcelxcel_posts; 

$trashedxcel_posts = $conn->query("SELECT COUNT(*) as trashed FROM xcel_posts WHERE deleted = 1")->fetch_assoc()['trashed'];
$trashedXcelxcel_posts = $conn->query("SELECT COUNT(*) as trashed FROM xcel_xcel_posts WHERE deleted = 1")->fetch_assoc()['trashed'];
$trashedfabxcel_posts = $conn->query("SELECT COUNT(*) as trashed FROM fab_xcel_posts WHERE deleted = 1")->fetch_assoc()['trashed'];
$trashedAllxcel_posts = $trashedxcel_posts + $trashedXcelxcel_posts  + $trashedfabxcel_posts; 


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








// Get monthly post data for all panels
// Get monthly post data for all panels (last 12 months)
$monthlyData = ['rcs' => [], 'xcel' => [], 'fab' => []];
$monthlyQuery = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month, 
        'rcs' as panel,
        COUNT(*) as count 
    FROM xcel_posts 
    WHERE deleted = 0 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month 
    
    UNION ALL
    
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month, 
        'xcel' as panel,
        COUNT(*) as count 
    FROM xcel_xcel_posts 
    WHERE deleted = 0 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month 
    
    UNION ALL
    
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month, 
        'fab' as panel,
        COUNT(*) as count 
    FROM fab_xcel_posts 
    WHERE deleted = 0 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month 
    
    ORDER BY month ASC
");

if ($monthlyQuery) {
    while ($row = $monthlyQuery->fetch_assoc()) {
        $monthlyData[$row['panel']][] = $row;
    }
}

// Generate complete month range for the last 12 months
$allMonths = [];
for ($i = 11; $i >= 0; $i--) {
    $allMonths[] = date('Y-m', strtotime("-$i months"));
}

// Prepare data for each panel with all months included
$preparedData = [
    'labels' => array_map(function($month) {
        return date('M Y', strtotime($month . '-01'));
    }, $allMonths),
    'rcs' => [],
    'xcel' => [],
    'fab' => []
];

foreach ($allMonths as $month) {
    // RCS data
    $found = false;
    foreach ($monthlyData['rcs'] as $data) {
        if ($data['month'] == $month) {
            $preparedData['rcs'][] = $data['count'];
            $found = true;
            break;
        }
    }
    if (!$found) $preparedData['rcs'][] = 0;
    
    // Xcel data
    $found = false;
    foreach ($monthlyData['xcel'] as $data) {
        if ($data['month'] == $month) {
            $preparedData['xcel'][] = $data['count'];
            $found = true;
            break;
        }
    }
    if (!$found) $preparedData['xcel'][] = 0;
    
    // Fab data
    $found = false;
    foreach ($monthlyData['fab'] as $data) {
        if ($data['month'] == $month) {
            $preparedData['fab'][] = $data['count'];
            $found = true;
            break;
        }
    }
    if (!$found) $preparedData['fab'][] = 0;
}

// Convert to JSON for JavaScript
$monthlyLabelsJS = json_encode($preparedData['labels']);
$rcsDataJS = json_encode($preparedData['rcs']);
$xcelDataJS = json_encode($preparedData['xcel']);
$fabDataJS = json_encode($preparedData['fab']);




// Get weekly post data for all panels
$weeklyData = ['rcs' => [], 'xcel' => [], 'fab' => []];
$weeklyQuery = $conn->query("
    SELECT 
        YEARWEEK(created_at, 1) as week, 
        'rcs' as panel,
        COUNT(*) as count 
    FROM xcel_posts 
    WHERE deleted = 0 
    GROUP BY week 
    
    UNION ALL
    
    SELECT 
        YEARWEEK(created_at, 1) as week, 
        'xcel' as panel,
        COUNT(*) as count 
    FROM xcel_xcel_posts 
    WHERE deleted = 0 
    GROUP BY week 
    
    UNION ALL
    
    SELECT 
        YEARWEEK(created_at, 1) as week, 
        'fab' as panel,
        COUNT(*) as count 
    FROM fab_xcel_posts 
    WHERE deleted = 0 
    GROUP BY week 
    
    ORDER BY week DESC 
    LIMIT 24
");

if ($weeklyQuery) {
    while ($row = $weeklyQuery->fetch_assoc()) {
        $weeklyData[$row['panel']][] = $row;
    }
}

// Get day-of-week performance for all panels
$dayOfWeekData = ['rcs' => [], 'xcel' => [], 'fab' => []];
$dowQuery = $conn->query("
    SELECT 
        DAYNAME(created_at) as day, 
        'rcs' as panel,
        COUNT(*) as count 
    FROM xcel_posts 
    WHERE deleted = 0 
    GROUP BY day 
    
    UNION ALL
    
    SELECT 
        DAYNAME(created_at) as day, 
        'xcel' as panel,
        COUNT(*) as count 
    FROM xcel_xcel_posts 
    WHERE deleted = 0 
    GROUP BY day 
    
    UNION ALL
    
    SELECT 
        DAYNAME(created_at) as day, 
        'fab' as panel,
        COUNT(*) as count 
    FROM fab_xcel_posts 
    WHERE deleted = 0 
    GROUP BY day 
    
    ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
");

if ($dowQuery) {
    while ($row = $dowQuery->fetch_assoc()) {
        $dayOfWeekData[$row['panel']][] = $row;
    }
}

// Prepare data for JavaScript - with empty array fallbacks
$monthlyLabels = json_encode(array_values(array_unique(array_merge(
    array_column($monthlyData['rcs'], 'month'),
    array_column($monthlyData['xcel'], 'month'),
    array_column($monthlyData['fab'], 'month')
))) ?: []);

$weeklyLabels = json_encode(array_values(array_unique(array_merge(
    array_column($weeklyData['rcs'], 'week'),
    array_column($weeklyData['xcel'], 'week'),
    array_column($weeklyData['fab'], 'week')
))) ?: []);

$dowLabels = json_encode(array_values(array_unique(array_merge(
    array_column($dayOfWeekData['rcs'], 'day'),
    array_column($dayOfWeekData['xcel'], 'day'),
    array_column($dayOfWeekData['fab'], 'day')
))) ?: []);

// For summary cards, add checks for empty data
function safeMax($array) {
    return !empty($array) ? max($array) : 0;
}

// In your summary cards section, replace max() calls with safeMax()
$maxxcel_posts = safeMax(array_column($monthlyData['rcs'], 'count'));
$maxXcelxcel_posts = safeMax(array_column($monthlyData['xcel'], 'count'));
$maxFabxcel_posts = safeMax(array_column($monthlyData['fab'], 'count'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css" rel="stylesheet">

    <?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
            background: linear-gradient(to right, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
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
        /* Add to your existing media query */
@media (max-width: 768px) {
    .sidebar {
        transition: transform 0.3s ease;
    }
    
    .content {
        transition: margin-left 0.3s ease;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .content.shifted {
        margin-left: 250px;
    }
}

/* Make sure the toggle button is visible */
.sidebar-toggle {
    background: transparent;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    display: none;
    transition: all 0.3s ease;
    padding: 5px 10px;
}

@media (max-width: 768px) {
    .sidebar-toggle {
        display: block;
    }
}

.sidebar-toggle {
    background: transparent;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    display: none; /* Hidden by default on desktop */
    transition: all 0.3s ease;
    padding: 5px 10px;
    margin-left: auto; /* Push to the right */
}

/* Show toggle button only on mobile */
@media (max-width: 768px) {
    .sidebar-toggle {
        display: block;
    }
    
    /* Make sure icon is visible */
    .sidebar-toggle i {
        color: white;
        font-size: 1.5rem;
    }
}

.sidebar-toggle {
    background: red !important; /* Make button clearly visible */
    z-index: 1000 !important;
}
    </style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <!-- <header>
        <span><i class="fas fa-tachometer-alt"></i> Blog Admin Dashboard</span>
        <button class="sidebar-toggle" onclick="toggleSidebar()">?</button>
    </header> -->

    <!-- <header>
    <span><i class="fas fa-tachometer-alt"></i> Blog Admin Dashboard</span>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
</header> -->

    <div class="sidebar" id="sidebar">
        <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="add-all-post.php"><i class="fas fa-plus-circle"></i> Add New Post</a>
        <a href="view-xcel_posts.php"><i class="fas fa-list"></i> View All xcel_posts</a>
  
        <a href="view-xcel_posts.php?recycle=1"><i class="fas fa-trash-restore"></i> Recycle Bin</a>
        <a href="superadmin.php"><i class="fas fa-users-cog"></i> User Management</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content" id="mainContent">
        <div class="container">
            <div class="dashboard-header">
                <h2><i class="fas fa-chart-line"></i> Welcome back,
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</h2>
                <p>Here's your blog performance analytics and recent activity</p>
            </div>

            <div class="stats-cards">
                <!-- Combined Total -->
                <div class="stat-card primary">
                    <div class="title">Total xcel_posts (All Panels)</div>
                    <div class="value"><?php echo $totalAllxcel_posts; ?></div>
                    <i class="fas fa-file-alt icon"></i>
                </div>

                <!-- Blog xcel_posts -->
                <div class="stat-card primary">
                    <div class="title">RCS xcel_posts</div>
                    <div class="value"><?php echo $totalxcel_posts; ?></div>
                    <i class="fas fa-blog icon"></i>
                </div>

                <!-- Xcel xcel_posts -->
                <div class="stat-card primary">
                    <div class="title">Xcel xcel_posts</div>
                    <div class="value"><?php echo $totalXcelxcel_posts; ?></div>
                    <i class="fas fa-file-excel icon"></i>
                </div>
                <!-- Fab xcel_posts -->
                <div class="stat-card primary">
                    <div class="title">Fabmediatech xcel_posts</div>
                    <div class="value"><?php echo $totalfabxcel_posts; ?></div>
                    <i class="fas fa-file-excel icon"></i>
                </div>

                <!-- Recent Combined -->
                <div class="stat-card success">
                    <div class="title">Recent (All, 2 days)</div>
                    <div class="value"><?php echo $recentAllxcel_posts; ?></div>
                    <i class="fas fa-clock icon"></i>
                </div>

                <!-- Recent Blog -->
                <div class="stat-card success">
                    <div class="title">Recent RCS </div>
                    <div class="value"><?php echo $recentxcel_posts; ?></div>
                    <i class="fas fa-newspaper icon"></i>
                </div>

                <!-- Recent Xcel -->
                <div class="stat-card success">
                    <div class="title">Recent Xcel</div>
                    <div class="value"><?php echo $recentXcelxcel_posts; ?></div>
                    <i class="fas fa-chart-line icon"></i>
                </div>

                <!-- Recent Fab -->
                <div class="stat-card success">
                    <div class="title">Recent Fabmediatech</div>
                    <div class="value"><?php echo $recentfabxcel_posts; ?></div>
                    <i class="fas fa-chart-line icon"></i>
                </div>

                <!-- Trashed Combined -->
                <div class="stat-card danger">
                    <div class="title">Trashed (All)</div>
                    <div class="value"><?php echo $trashedAllxcel_posts; ?></div>
                    <i class="fas fa-trash icon"></i>
                </div>

                <!-- Trashed Blog -->
                <div class="stat-card danger">
                    <div class="title">Trashed RCS</div>
                    <div class="value"><?php echo $trashedxcel_posts; ?></div>
                    <i class="fas fa-trash-alt icon"></i>
                </div>

                <!-- Trashed Blog -->
                <div class="stat-card danger">
                    <div class="title">Trashed Fabmediatech</div>
                    <div class="value"><?php echo $trashedfabxcel_posts; ?></div>
                    <i class="fas fa-trash-alt icon"></i>
                </div>
                <!-- Trashed Xcel -->
                <div class="stat-card danger">
                    <div class="title">Trashed Xcel</div>
                    <div class="value"><?php echo $trashedXcelxcel_posts; ?></div>
                    <i class="fas fa-dumpster icon"></i>
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
                <a href="view-xcel_posts.php?recycle=1" class="action-btn">
                    <i class="fas fa-trash-restore"></i> Recycle Bin
                </a>
                <a href="view-xcel_posts.php?recycle=1" class="action-btn">
                    <i class="fas fa-trash-restore"></i> User Management 
                </a>
            </div>
            <div class="analytics-summary">
    <!-- Monthly Performance Card (existing) -->
    <div class="summary-card">
        <h4><i class="fas fa-calendar-alt"></i> Monthly Performance</h4>
        <?php
        if (!empty($monthlyData['rcs']) || !empty($monthlyData['xcel']) || !empty($monthlyData['fab'])) {
            // Find the maximum count across all panels to calculate percentages
            $maxCount = max(
                array_merge(
                    array_column($monthlyData['rcs'], 'count'),
                    array_column($monthlyData['xcel'], 'count'),
                    array_column($monthlyData['fab'], 'count')
                ) ?: [0]
            );
            
            // Display RCS data
            if (!empty($monthlyData['rcs'])) {
                foreach ($monthlyData['rcs'] as $month) {
                    $percentage = $maxCount > 0 ? round(($month['count'] / $maxCount) * 100) : 0;
                    $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');
                    echo '<div class="summary-item">
                            <span class="summary-label">RCS - ' . date('M Y', strtotime($month['month'] . '-01')) . '</span>
                            <span class="summary-value">
                                <span class="weakness-indicator ' . $weaknessClass . '"></span>
                                ' . $month['count'] . ' xcel_posts
                            </span>
                          </div>';
                }
            }
            
            // Display Xcel data
            if (!empty($monthlyData['xcel'])) {
                foreach ($monthlyData['xcel'] as $month) {
                    $percentage = $maxCount > 0 ? round(($month['count'] / $maxCount) * 100) : 0;
                    $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');
                    echo '<div class="summary-item">
                            <span class="summary-label">Xcel - ' . date('M Y', strtotime($month['month'] . '-01')) . '</span>
                            <span class="summary-value">
                                <span class="weakness-indicator ' . $weaknessClass . '"></span>
                                ' . $month['count'] . ' xcel_posts
                            </span>
                          </div>';
                }
            }
            
            // Display Fab data
            if (!empty($monthlyData['fab'])) {
                foreach ($monthlyData['fab'] as $month) {
                    $percentage = $maxCount > 0 ? round(($month['count'] / $maxCount) * 100) : 0;
                    $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');
                    echo '<div class="summary-item">
                            <span class="summary-label">Fab - ' . date('M Y', strtotime($month['month'] . '-01')) . '</span>
                            <span class="summary-value">
                                <span class="weakness-indicator ' . $weaknessClass . '"></span>
                                ' . $month['count'] . ' xcel_posts
                            </span>
                          </div>';
                }
            }
        } else {
            echo '<p>No monthly data available for any panel</p>';
        }
        ?>
    </div>

    <!-- Weekly Performance Card -->
    <div class="summary-card">
        <h4><i class="fas fa-calendar-week"></i> Weekly Performance</h4>
        <?php
        if (!empty($weeklyData['rcs']) || !empty($weeklyData['xcel']) || !empty($weeklyData['fab'])) {
            // Find the maximum count across all panels to calculate percentages
            $maxCount = max(
                array_merge(
                    array_column($weeklyData['rcs'], 'count'),
                    array_column($weeklyData['xcel'], 'count'),
                    array_column($weeklyData['fab'], 'count')
                ) ?: [0]
            );
            
            // Display RCS data
            if (!empty($weeklyData['rcs'])) {
                foreach ($weeklyData['rcs'] as $week) {
                    $percentage = $maxCount > 0 ? round(($week['count'] / $maxCount) * 100) : 0;
                    $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');
                    $weekNumber = substr($week['week'], 4);
                    $year = substr($week['week'], 0, 4);
                    echo '<div class="summary-item">
                            <span class="summary-label">RCS - Week ' . $weekNumber . ' ('.$year.')</span>
                            <span class="summary-value">
                                <span class="weakness-indicator ' . $weaknessClass . '"></span>
                                ' . $week['count'] . ' xcel_posts
                            </span>
                          </div>';
                }
            }
            
            // Display Xcel data
            if (!empty($weeklyData['xcel'])) {
                foreach ($weeklyData['xcel'] as $week) {
                    $percentage = $maxCount > 0 ? round(($week['count'] / $maxCount) * 100) : 0;
                    $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');
                    $weekNumber = substr($week['week'], 4);
                    $year = substr($week['week'], 0, 4);
                    echo '<div class="summary-item">
                            <span class="summary-label">Xcel - Week ' . $weekNumber . ' ('.$year.')</span>
                            <span class="summary-value">
                                <span class="weakness-indicator ' . $weaknessClass . '"></span>
                                ' . $week['count'] . ' xcel_posts
                            </span>
                          </div>';
                }
            }
            
            // Display Fab data
            if (!empty($weeklyData['fab'])) {
                foreach ($weeklyData['fab'] as $week) {
                    $percentage = $maxCount > 0 ? round(($week['count'] / $maxCount) * 100) : 0;
                    $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');
                    $weekNumber = substr($week['week'], 4);
                    $year = substr($week['week'], 0, 4);
                    echo '<div class="summary-item">
                            <span class="summary-label">Fab - Week ' . $weekNumber . ' ('.$year.')</span>
                            <span class="summary-value">
                                <span class="weakness-indicator ' . $weaknessClass . '"></span>
                                ' . $week['count'] . ' xcel_posts
                            </span>
                          </div>';
                }
            }
        } else {
            echo '<p>No weekly data available for any panel</p>';
        }
        ?>
    </div>

    <!-- Day of Week Performance Card -->
    <div class="summary-card">
        <h4><i class="fas fa-calendar-day"></i> Day of Week Performance</h4>
        <?php
        if (!empty($dayOfWeekData['rcs']) || !empty($dayOfWeekData['xcel']) || !empty($dayOfWeekData['fab'])) {
            // Find the maximum count across all panels to calculate percentages
            $maxCount = max(
                array_merge(
                    array_column($dayOfWeekData['rcs'], 'count'),
                    array_column($dayOfWeekData['xcel'], 'count'),
                    array_column($dayOfWeekData['fab'], 'count')
                ) ?: [0]
            );
            
            // Get all days in order
            $orderedDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            // Display RCS data
            if (!empty($dayOfWeekData['rcs'])) {
                // Sort RCS data by day order
                $rcsDays = array_column($dayOfWeekData['rcs'], 'day');
                $rcsCounts = array_column($dayOfWeekData['rcs'], 'count');
                $rcsData = array_combine($rcsDays, $rcsCounts);
                
                foreach ($orderedDays as $day) {
                    if (isset($rcsData[$day])) {
                        $percentage = $maxCount > 0 ? round(($rcsData[$day] / $maxCount) * 100) : 0;
                        $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');
                        echo '<div class="summary-item">
                                <span class="summary-label">RCS - ' . $day . '</span>
                                <span class="summary-value">
                                    <span class="weakness-indicator ' . $weaknessClass . '"></span>
                                    ' . $rcsData[$day] . ' xcel_posts
                                </span>
                              </div>';
                    }
                }
            }
            
            // Display Xcel data
            if (!empty($dayOfWeekData['xcel'])) {
                // Sort Xcel data by day order
                $xcelDays = array_column($dayOfWeekData['xcel'], 'day');
                $xcelCounts = array_column($dayOfWeekData['xcel'], 'count');
                $xcelData = array_combine($xcelDays, $xcelCounts);
                
                foreach ($orderedDays as $day) {
                    if (isset($xcelData[$day])) {
                        $percentage = $maxCount > 0 ? round(($xcelData[$day] / $maxCount) * 100) : 0;
                        $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');
                        echo '<div class="summary-item">
                                <span class="summary-label">Xcel - ' . $day . '</span>
                                <span class="summary-value">
                                    <span class="weakness-indicator ' . $weaknessClass . '"></span>
                                    ' . $xcelData[$day] . ' xcel_posts
                                </span>
                              </div>';
                    }
                }
            }
            
            // Display Fab data
            if (!empty($dayOfWeekData['fab'])) {
                // Sort Fab data by day order
                $fabDays = array_column($dayOfWeekData['fab'], 'day');
                $fabCounts = array_column($dayOfWeekData['fab'], 'count');
                $fabData = array_combine($fabDays, $fabCounts);
                
                foreach ($orderedDays as $day) {
                    if (isset($fabData[$day])) {
                        $percentage = $maxCount > 0 ? round(($fabData[$day] / $maxCount) * 100) : 0;
                        $weaknessClass = $percentage < 30 ? 'weakness-high' : ($percentage < 60 ? 'weakness-medium' : 'weakness-low');
                        echo '<div class="summary-item">
                                <span class="summary-label">Fab - ' . $day . '</span>
                                <span class="summary-value">
                                    <span class="weakness-indicator ' . $weaknessClass . '"></span>
                                    ' . $fabData[$day] . ' xcel_posts
                                </span>
                              </div>';
                    }
                }
            }
        } else {
            echo '<p>No day-of-week data available for any panel</p>';
        }
        ?>
    </div>
</div>




            <div class="analytics-section">
    <div class="chart-card">
        <h3><i class="fas fa-chart-bar"></i> Monthly Post  Performance</h3>
        <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <h3><i class="fas fa-chart-line"></i> Weekly Post Performance</h3>
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
</div>

            <div class="recent-xcel_posts">
                <h3><i class="fas fa-clock"></i>RCS All Post</h3>

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
                        <a href="?page=<?= $current_page - 1 ?>" class="page-link prev"><i
                                class="fas fa-angle-left"></i></a>
                        <?php endif; ?>

                        <?php 
        // Show page numbers
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        
        for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?= $i ?>"
                            class="page-link <?= ($i == $current_page) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?= $current_page + 1 ?>" class="page-link next"><i
                                class="fas fa-angle-right"></i></a>
                        <a href="?page=<?= $total_pages ?>" class="page-link last"><i
                                class="fas fa-angle-double-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="recent-xcel_posts">
                <h3><i class="fas fa-clock"></i>  Fabmediatech All Post</h3>

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
            $total_query = "SELECT COUNT(*) as total FROM fab_xcel_posts WHERE deleted = 0";
            $total_result = $conn->query($total_query);
            $total_row = $total_result->fetch_assoc();
            $total_xcel_posts = $total_row['total'];
            $total_pages = ceil($total_xcel_posts / $per_page);
            
            // Get xcel_posts for current page
            $sql = "SELECT id, title, slug, created_at FROM fab_xcel_posts WHERE deleted = 0 
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
                            <a href='https://www.fabmediatech.com/blog-admin/edit-post.php?id=" . $row['id'] . "' class='action-link edit-link'>
                                <i class='fas fa-edit'></i> Edit
                            </a>
                            <a href='https://www.fabmediatech.com/blog-admin/view-add-all-post.php?slug=" . urlencode($row['slug']) . "' class='action-link view-link'>
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
                        <a href="?page=<?= $current_page - 1 ?>" class="page-link prev"><i
                                class="fas fa-angle-left"></i></a>
                        <?php endif; ?>

                        <?php 
        // Show page numbers
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        
        for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?= $i ?>"
                            class="page-link <?= ($i == $current_page) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?= $current_page + 1 ?>" class="page-link next"><i
                                class="fas fa-angle-right"></i></a>
                        <a href="?page=<?= $total_pages ?>" class="page-link last"><i
                                class="fas fa-angle-double-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>


           

                <div class="recent-xcel_posts">
                <h3><i class="fas fa-clock"></i>  Xcelmarketing All Post</h3>

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
            $total_query = "SELECT COUNT(*) as total FROM xcel_xcel_posts WHERE deleted = 0";
            $total_result = $conn->query($total_query);
            $total_row = $total_result->fetch_assoc();
            $total_xcel_posts = $total_row['total'];
            $total_pages = ceil($total_xcel_posts / $per_page);
            
            // Get xcel_posts for current page
            $sql = "SELECT id, title, slug, created_at FROM xcel_xcel_posts WHERE deleted = 0 
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
                        <a href="?page=<?= $current_page - 1 ?>" class="page-link prev"><i
                                class="fas fa-angle-left"></i></a>
                        <?php endif; ?>

                        <?php 
        // Show page numbers
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        
        for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?= $i ?>"
                            class="page-link <?= ($i == $current_page) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?= $current_page + 1 ?>" class="page-link next"><i
                                class="fas fa-angle-right"></i></a>
                        <a href="?page=<?= $total_pages ?>" class="page-link last"><i
                                class="fas fa-angle-double-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

      


        
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
     <script>
    // Monthly Chart
// Monthly Chart with 12 months data
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo $monthlyLabelsJS; ?>,
        datasets: [
            {
                label: 'RCS xcel_posts',
                data: <?php echo $rcsDataJS; ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.7)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1,
                borderRadius: 4, // Rounded corners
                barPercentage: 0.8, // Adjust bar width
                categoryPercentage: 0.7 // Adjust space between categories
            },
            {
                label: 'Xcel xcel_posts',
                data: <?php echo $xcelDataJS; ?>,
                backgroundColor: 'rgba(28, 200, 138, 0.7)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.8,
                categoryPercentage: 0.7
            },
            {
                label: 'Fab xcel_posts',
                data: <?php echo $fabDataJS; ?>,
                backgroundColor: 'rgba(246, 194, 62, 0.7)',
                borderColor: 'rgba(246, 194, 62, 1)',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.8,
                categoryPercentage: 0.7
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of xcel_posts',
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    precision: 0,
                    callback: function(value) {
                        if (Number.isInteger(value)) {
                            return value;
                        }
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Month',
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                },
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    boxWidth: 12,
                    padding: 20,
                    font: {
                        size: 12
                    },
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 12
                },
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + ' xcel_posts';
                    },
                    title: function(context) {
                        return context[0].label;
                    }
                }
            },
            datalabels: {
                display: false
            }
        },
        animation: {
            duration: 1000,
            easing: 'easeOutQuart'
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});
     

// Weekly Chart with three colored lines
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
const weeklyChart = new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: <?php echo $weeklyLabels; ?>,
        datasets: [
            {
                label: 'RCS xcel_posts',
                data: <?php echo json_encode(array_column($weeklyData['rcs'], 'count')); ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointRadius: 5,
                pointHoverRadius: 7,
                pointHitRadius: 10,
                tension: 0.3,
                fill: true
            },
            {
                label: 'Xcel xcel_posts',
                data: <?php echo json_encode(array_column($weeklyData['xcel'], 'count')); ?>,
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                pointBorderColor: '#fff',
                pointRadius: 5,
                pointHoverRadius: 7,
                pointHitRadius: 10,
                tension: 0.3,
                fill: true
            },
            {
                label: 'Fab xcel_posts',
                data: <?php echo json_encode(array_column($weeklyData['fab'], 'count')); ?>,
                backgroundColor: 'rgba(246, 194, 62, 0.1)',
                borderColor: 'rgba(246, 194, 62, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(246, 194, 62, 1)',
                pointBorderColor: '#fff',
                pointRadius: 5,
                pointHoverRadius: 7,
                pointHitRadius: 10,
                tension: 0.3,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    callback: function(value) {
                        if (Number.isInteger(value)) {
                            return value;
                        }
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `${context.dataset.label}: ${context.parsed.y} xcel_posts`;
                    }
                },
                displayColors: true,
                usePointStyle: true,
                padding: 10,
                backgroundColor: 'rgba(0, 0, 0, 0.8)'
            },
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});


    // Day of Week Chart - Last 7 days
// Day of Week Chart - Last 7 days
const dowCtx = document.getElementById('dowChart').getContext('2d');

// Debug: Check what data is coming from PHP
console.log("RCS Data:", <?php echo json_encode($dayOfWeekData['rcs']); ?>);
console.log("Xcel Data:", <?php echo json_encode($dayOfWeekData['xcel']); ?>);
console.log("Fab Data:", <?php echo json_encode($dayOfWeekData['fab']); ?>);

const dowChart = new Chart(dowCtx, {
    type: 'bar',
    data: {
        labels: getLast7DaysLabels(),
        datasets: [
            {
                label: 'RCS xcel_posts (Last 7 Days)',
                data: getLast7DaysData('rcs', <?php echo json_encode($dayOfWeekData['rcs']); ?>),
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.8,
                categoryPercentage: 0.3
            },
            {
                label: 'Xcel xcel_posts (Last 7 Days)',
                data: getLast7DaysData('xcel', <?php echo json_encode($dayOfWeekData['xcel']); ?>),
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.8,
                categoryPercentage: 0.3
            },
            {
                label: 'Fab xcel_posts (Last 7 Days)',
                data: getLast7DaysData('fab', <?php echo json_encode($dayOfWeekData['fab']); ?>),
                backgroundColor: 'rgba(246, 194, 62, 0.1)',
                borderColor: 'rgba(246, 194, 62, 1)',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.8,
                categoryPercentage: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of xcel_posts',
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    precision: 0,
                    stepSize: 1
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Day of Week',
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                },
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    boxWidth: 12,
                    padding: 20,
                    font: {
                        size: 12
                    },
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 12
                },
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + ' xcel_posts';
                    },
                    title: function(context) {
                        return context[0].label;
                    }
                }
            }
        },
        animation: {
            duration: 1000,
            easing: 'easeOutQuart'
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Helper function to generate labels for last 7 days (including today)
function getLast7DaysLabels() {
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const labels = [];
    const today = new Date();
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dayName = days[date.getDay()];
        const dateStr = formatDate(date);
        labels.push(`${dayName} (${dateStr})`);
    }
    
    return labels;
}

// Helper function to format date as MM/DD
function formatDate(date) {
    const month = date.getMonth() + 1;
    const day = date.getDate();
    return `${month.toString().padStart(2, '0')}/${day.toString().padStart(2, '0')}`;
}

// Updated helper function to get data for exact last 7 days
function getLast7DaysData(panel, data) {
    const result = new Array(7).fill(0);
    
    // If data is not an array, return empty array
    if (!Array.isArray(data)) {
        console.error(`Invalid data format for panel ${panel}:`, data);
        return result;
    }
    
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Normalize time
    
    // Create date strings for last 7 days
    const dateStrings = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        dateStrings.push(date.toISOString().split('T')[0]); // YYYY-MM-DD
    }
    
    // Check if data uses 'date' or 'day' field
    const usesDates = data.some(item => item.hasOwnProperty('date'));
    const usesDays = data.some(item => item.hasOwnProperty('day'));
    
    if (usesDates) {
        // Create mapping of dates to counts
        const dateMap = {};
        data.forEach(item => {
            if (item.date) {
                dateMap[item.date] = item.count || 0;
            }
        });
        
        // Match data to dates
        dateStrings.forEach((dateStr, index) => {
            result[index] = dateMap[dateStr] || 0;
        });
    } 
    else if (usesDays) {
        // Fallback for day-of-week data
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const dayMap = {};
        data.forEach(item => {
            if (item.day) {
                dayMap[item.day] = item.count || 0;
            }
        });
        
        // Match data to days
        dateStrings.forEach((dateStr, index) => {
            const date = new Date(dateStr);
            const dayName = days[date.getDay()];
            result[index] = dayMap[dayName] || 0;
        });
    }
    
    console.log(`Processed data for ${panel}:`, result);
    return result;
}


</script>




<script>
   function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('mainContent');
    
    sidebar.classList.toggle('active');
    content.classList.toggle('shifted');
    
    // Change icon when toggled
    const icon = document.querySelector('.sidebar-toggle i');
    if (sidebar.classList.contains('active')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
    } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
    }
}
</script>

    <?php include('./footer.php'); ?>
</body>

</html>