<div class="sidebar" id="sidebar">
    <a href="main.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="add-all-post.php"><i class="fas fa-plus-circle"></i> Add New Post</a>
    <a href="view-xcel_posts.php"><i class="fas fa-list"></i> View All xcel_posts</a>
           <a href="superadmin.php"><i class="fas fa-users-cog"></i> User Management</a>
    <a href="view-xcel_posts.php?recycle=1"><i class="fas fa-trash-restore"></i> Recycle Bin</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<style>
    





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
    border-left: 3px solid #007bff;
    color: white;
}

.sidebar a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.sidebar a.active {
    background-color: rgba(255, 255, 255, 0.2);
    border-left: 3px solid #007bff;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get current page URL
    const currentUrl = window.location.href;
    
    // Get all sidebar links
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    
    // Loop through each link
    sidebarLinks.forEach(link => {
        // Check if the link's href matches the current URL
        if (link.href === currentUrl) {
            // Add active class to the matching link
            link.classList.add('active');
        }
        
        // Special case for recycle bin which uses a query parameter
        if (link.href.includes('recycle=1') && currentUrl.includes('recycle=1')) {
            link.classList.add('active');
        }
    });
});
</script>