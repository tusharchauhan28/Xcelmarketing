<?php

// sidebar.php - Complete Sidebar Component

// This should be included in your pages after session_start()





// Get current page URL for active link highlighting

$current_page = basename($_SERVER['PHP_SELF']);

$query_string = $_SERVER['QUERY_STRING'] ?? '';

$full_url = $current_page . ($query_string ? '?' . $query_string : '');



// Determine user role (adjust this based on your session variable)

$is_superadmin = ($_SESSION['user_role'] ?? '') === 'superadmin';

$dashboard_link = $is_superadmin ? 'main.php' : 'index.php';

?>



<!-- Sidebar HTML -->

<div class="sidebar" id="sidebar">

    <!-- Dashboard Link (changes based on role) -->

    <a href="<?= $dashboard_link ?>" class="<?= ($current_page === 'main.php' || $current_page === 'index.php') ? 'active' : '' ?>">

        <i class="fas fa-tachometer-alt"></i> Dashboard

    </a>



    <!-- Common Links for All Users -->

    <a href="add-all-post.php" class="<?= $current_page === 'add-all-post.php' ? 'active' : '' ?>">

        <i class="fas fa-plus-circle"></i> Add New Post

    </a>

    

    <a href="view-posts.php" class="<?= ($current_page === 'view-posts.php' && !isset($_GET['recycle'])) ? 'active' : '' ?>">

        <i class="fas fa-list"></i> View All Posts

    </a>



    <!-- Superadmin Only Links -->

    <?php if ($is_superadmin): ?>

    <a href="superadmin.php" class="<?= $current_page === 'superadmin.php' ? 'active' : '' ?>">

        <i class="fas fa-users-cog"></i> User Management

    </a>

    <?php endif; ?>



    <!-- Recycle Bin -->

    <a href="view-posts.php?recycle=1" class="<?= ($current_page === 'view-posts.php' && isset($_GET['recycle'])) ? 'active' : '' ?>">

        <i class="fas fa-trash-restore"></i> Recycle Bin

    </a>



    <!-- Logout -->

    <a href="logout.php">

        <i class="fas fa-sign-out-alt"></i> Logout

    </a>



    <!-- Mobile Toggle Button -->

    <button class="sidebar-toggle" id="sidebarToggle">

        <i class="fas fa-bars"></i>

    </button>

</div>



<style>

    :root {

        --sidebar-bg: #2c3e50;

        --text-light: #ecf0f1;

        --transition: all 0.3s ease;

    }



    .sidebar {

        width: 250px;

        height: 100vh;

        background-color: var(--sidebar-bg);

        position: fixed;

        top: 0;

        left: 0;

        padding-top: 20px;

        transition: var(--transition);

        z-index: 1000;

        overflow-y: auto;

    }



    .sidebar a {

        display: flex;

        align-items: center;

        color: var(--text-light);

        padding: 12px 20px;

        text-decoration: none;

        font-size: 0.95rem;

        transition: var(--transition);

        border-left: 3px solid transparent;

        margin: 5px 10px;

        border-radius: 4px;

    }



    .sidebar a:hover {

        background-color: rgba(255, 255, 255, 0.1);

        border-left: 3px solid #3498db;

        color: white;

    }



    .sidebar a i {

        margin-right: 10px;

        width: 20px;

        text-align: center;

    }



    .sidebar a.active {

        background-color: rgba(255, 255, 255, 0.2);

        border-left: 3px solid #3498db;

        font-weight: 500;

    }



    .sidebar-toggle {

        display: none;

        position: fixed;

        left: 10px;

        top: 10px;

        background: rgba(0, 0, 0, 0.5);

        border: none;

        color: white;

        font-size: 1.5rem;

        cursor: pointer;

        z-index: 1001;

        border-radius: 50%;

        width: 40px;

        height: 40px;

    }



    @media (max-width: 768px) {

        .sidebar {

            transform: translateX(-100%);

        }

        

        .sidebar.active {

            transform: translateX(0);

        }

        

        .sidebar-toggle {

            display: block;

        }

    }

</style>



<script>

    // Toggle sidebar on mobile

    document.addEventListener('DOMContentLoaded', function() {

        const sidebar = document.getElementById('sidebar');

        const sidebarToggle = document.getElementById('sidebarToggle');

        

        sidebarToggle.addEventListener('click', function() {

            sidebar.classList.toggle('active');

        });

        

        // Close sidebar when clicking outside on mobile

        document.addEventListener('click', function(e) {

            if (window.innerWidth <= 768 && !sidebar.contains(e.target) {

                sidebar.classList.remove('active');

            }

        });

    });

</script>