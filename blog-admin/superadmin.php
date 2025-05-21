<?php
// superadmin.php
session_start();

include 'config.php';



if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit();
}

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define all available systems and their corresponding tables
$systems = [
    'fabmediatech' => [
        'table' => 'fab_user',
        'name' => 'Fabmediatech',
        'color' => '#8b5cf6'
    ],
    'xcel' => [
        'table' => 'xcel_user',
        'name' => 'XCEL',
        'color' => '#3b82f6'
    ],
    'rcs' => [
        'table' => 'rcs_user',
        'name' => 'RCS',
        'color' => '#10b981'
    ]
];

// Handle user creation
$user_errors = [];
$user_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $user_errors[] = "Invalid CSRF token";
    } else {
        $new_username = trim($_POST['new_username']);
        $new_email = trim($_POST['new_email']);
        $new_password = trim($_POST['new_password']);
        $new_role = trim($_POST['new_role']);
        $user_types = $_POST['user_type'] ?? [];

        // Enhanced validation
        if (empty($new_username) || empty($new_email) || empty($new_password) || empty($new_role) || empty($user_types)) {
            $user_errors[] = "All fields are required";
        }

        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $user_errors[] = "Invalid email format";
        }

        if (strlen($new_password) < 8) {
            $user_errors[] = "Password must be at least 8 characters";
        }

        if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $user_errors[] = "Password must contain uppercase, lowercase letters and numbers";
        }

        if (empty($user_errors)) {
            foreach ($user_types as $user_type) {
                if (!array_key_exists($user_type, $systems)) {
                    $user_errors[] = "Invalid user type selected";
                    continue;
                }
                
                $table_name = $systems[$user_type]['table'];
                
                // Check if username or email exists in the selected table
                $checkStmt = $conn->prepare("SELECT id FROM $table_name WHERE name = ? OR email = ?");
                $checkStmt->bind_param("ss", $new_username, $new_email);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    $user_errors[] = "Username or email already exists in " . $systems[$user_type]['name'];
                } else {
                    // Hash password and create user
                    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO $table_name (name, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $new_username, $new_email, $hashedPassword, $new_role);

                    if ($stmt->execute()) {
                        $user_success = "User created successfully in " . $systems[$user_type]['name'] . " system";
                        // Regenerate CSRF token after successful operation
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $user_errors[] = "Error creating user: " . $stmt->error;
                    }
                }
            }
        }
    }
}

// Function to fetch users from all tables
function fetchAllUsers($conn, $systems) {
    $all_users = [];
    
    foreach ($systems as $system_key => $system) {
        $stmt = $conn->prepare("SELECT id, name, email, role, created_at, ? as system_name, ? as system_color FROM " . $system['table'] . " ORDER BY created_at DESC");
        $system_name = $system['name'];
        $system_color = $system['color'];
        $stmt->bind_param("ss", $system_name, $system_color);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $all_users[] = $row;
        }
    }
    
    // Sort by created_at (most recent first)
    usort($all_users, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $all_users;
}

// Get all users from all systems
$all_users = fetchAllUsers($conn, $systems);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
<link rel="stylesheet" href="./css/style.css">
   
<?php include('./header.php'); ?>
<?php include('./sidebar.php'); ?>
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
        
     
    
     
        /* Card Styles */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-header i {
            color: var(--primary-color);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Form Styles */
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #475569;
        }
        
        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.625rem 2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        /* Button Styles */
        .btn {
            border-radius: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
            border-color: #dc2626;
        }
        
        .btn-outline-danger {
            color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-outline-danger:hover {
            background-color: var(--danger-color);
            color: white;
        }
        
        /* Badge Styles */
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            text-transform: capitalize;
        }
        
        .badge-superadmin {
            background-color: rgb(139 92 246);
            color:white;
        }
        
        .badge-admin {
            background-color: rgb(59 130 246);
            color:white;
        }
        
        .badge-user {
            background-color: rgba(100, 116, 139, 0.1);
            color:white;
        }
        
        .badge-system {
            background-color: rgb(16 185 129 / 63%);
            color:white;
        }
        
        /* Alert Styles */
        .alert {
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
            border: none;
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        /* Table Styles */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-top: none;
        }
        
        .table td {
            vertical-align: middle;
            padding: 1rem;
            border-top: 1px solid #f1f5f9;
        }
        
        .table tr:hover td {
            background-color: #f8fafc;
        }
        
        /* Avatar Styles */
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-weight: 600;
            margin-right: 0.75rem;
        }
        
        /* Custom checkbox group */
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .checkbox-item {
            position: relative;
            flex: 1 0 calc(33.333% - 1rem);
            min-width: 120px;
        }
        
        .checkbox-item input[type="checkbox"] {
            position: absolute;
            opacity: 0;
        }
        
        .checkbox-item label {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .checkbox-item label:hover {
            border-color: var(--primary-color);
        }
        
        .checkbox-item input[type="checkbox"]:checked + label {
            background-color: rgba(99, 102, 241, 0.05);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .checkbox-item .check-icon {
            margin-right: 0.75rem;
            color: transparent;
            transition: all 0.2s;
        }
        
        .checkbox-item input[type="checkbox"]:checked + label .check-icon {
            color: var(--primary-color);
        }
        
        /* System color indicators */
        .system-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .page-title {
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .page-title i {
            color: var(--primary-color);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }


        
        /* Adjust main content padding */
        .main-content {
            margin-left: 280px;
            padding-top: 30px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        

    </style>
   
</head>
<body>

    <!-- Main Content -->
         <!-- Full-width Header -->
    

    <!-- Sidebar -->

    <div class="main-content" id="main-content">
        <div class="container-fluid">
            <!-- Error Messages -->
            <?php if (!empty($user_errors)): ?>
                <div class="alert alert-danger fade-in">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php foreach ($user_errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if ($user_success): ?>
                <div class="alert alert-success fade-in">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($user_success); ?>
                </div>
            <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-users-cog"></i>
                User Management
            </h1>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-user-plus me-2"></i> Create User
                </button>
            </div>
        </div>
        
        <!-- Error Messages -->
        <?php if (!empty($user_errors)): ?>
            <div class="alert alert-danger fade-in">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php foreach ($user_errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Success Message -->
        <?php if ($user_success): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($user_success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Users Table Card -->
        <div class="card animate__animated animate__fadeIn">
            <div class="card-header">
                <i class="fas fa-users me-2"></i>
                All Users
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>System</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($user['name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo 'badge-' . strtolower($user['role']);
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-system">
                                        <span class="system-indicator" style="background-color: <?php echo $user['system_color']; ?>"></span>
                                        <?php echo htmlspecialchars($user['system_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-nowrap">
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        <small class="text-muted d-block"><?php echo date('H:i', strtotime($user['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary edit-user" 
                                                data-id="<?php echo $user['id']; ?>" 
                                                data-system="<?php echo array_search($user['system_name'], array_column(array_map(function($k, $v) { return ['key' => $k, 'name' => $v['name']]; }, array_keys($systems), $systems), 'name', 'key')); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-user" 
                                                data-id="<?php echo $user['id']; ?>" 
                                                data-system="<?php echo array_search($user['system_name'], array_column(array_map(function($k, $v) { return ['key' => $k, 'name' => $v['name']]; }, array_keys($systems), $systems), 'name', 'key')); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Create New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="createUserForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="new_username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="new_email" required>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="new_password" id="newPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted" id="passwordStrengthText">Password strength</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="new_role" required>
                                    <option value="">Select Role</option>
                                    <option value="superadmin">Super Admin</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">System(s)</label>
                            <div class="checkbox-group">
                                <?php foreach ($systems as $key => $system): ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="user_type[]" 
                                               id="system_<?php echo htmlspecialchars($key); ?>" 
                                               value="<?php echo htmlspecialchars($key); ?>">
                                        <label for="system_<?php echo htmlspecialchars($key); ?>">
                                            <i class="fas fa-check check-icon"></i>
                                            <?php echo htmlspecialchars($system['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="create_user" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>
                        Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="id">
                        <input type="hidden" id="editUserSystem" name="system">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="superadmin">Super Admin</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reset Password (leave blank to keep current)</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="editPassword" name="password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimum 8 characters with uppercase, lowercase and numbers</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveUserChanges">
                        <i class="fas fa-save me-2"></i> Save changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        All data associated with this user will be permanently removed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash me-2"></i> Delete User
                    </button>
                </div>
            </div>
        </div>
    </div>    </div>  
    </div>  
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/zxcvbn@4.4.2/dist/zxcvbn.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
    $(document).ready(function() {
        // Initialize DataTable with export buttons
        $('#usersTable').DataTable({
            responsive: true,
            order: [[5, 'desc']],
            dom: '<"top"<"d-flex justify-content-between align-items-center"fB>>rt<"bottom"lip><"clear">',
            buttons: [
                {
                    extend: 'copy',
                    className: 'btn btn-sm btn-outline-secondary',
                    text: '<i class="fas fa-copy me-1"></i> Copy',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'excel',
                    className: 'btn btn-sm btn-outline-success',
                    text: '<i class="fas fa-file-excel me-1"></i> Excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-outline-danger',
                    text: '<i class="fas fa-file-pdf me-1"></i> PDF',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                // {
                //     extend: 'print',
                //     className: 'btn btn-sm btn-outline-info',
                //     text: '<i class="fas fa-print me-1"></i> Print',
                //     exportOptions: {
                //         columns: [0, 1, 2, 3, 4, 5]
                //     }
                // }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search users...",
                lengthMenu: "Show _MENU_ users per page",
                zeroRecords: "No users found",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                infoEmpty: "No users available",
                infoFiltered: "(filtered from _MAX_ total users)"
            },
            pageLength: 25,
            initComplete: function() {
                $('.dt-buttons .btn').removeClass('btn-secondary');
            }
        });

        // Password strength meter
        $('#newPassword').on('input', function() {
            const password = $(this).val();
            const result = zxcvbn(password);
            const strength = result.score;
            const width = (strength + 1) * 25;
            
            let color, text;
            switch(strength) {
                case 0:
                    color = '#ef4444';
                    text = 'Very weak';
                    break;
                case 1:
                    color = '#f97316';
                    text = 'Weak';
                    break;
                case 2:
                    color = '#f59e0b';
                    text = 'Moderate';
                    break;
                case 3:
                    color = '#10b981';
                    text = 'Strong';
                    break;
                case 4:
                    color = '#3b82f6';
                    text = 'Very strong';
                    break;
                default:
                    color = '#e2e8f0';
                    text = '';
            }
            
            $('#passwordStrengthBar').css({
                'width': width + '%',
                'background-color': color
            });
            
            $('#passwordStrengthText').text(text).css('color', color);
        });

        // Toggle password visibility
        $('#togglePassword').click(function() {
            const passwordField = $('#newPassword');
            const icon = $(this).find('i');
            
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        $('#toggleEditPassword').click(function() {
            const passwordField = $('#editPassword');
            const icon = $(this).find('i');
            
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Edit user button click handler
        $(document).on('click', '.edit-user', function() {
            const userId = $(this).data('id');
            const system = $(this).data('system');
            const $btn = $(this);
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i>');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: 'get_user.php',
                method: 'POST',
                data: { 
                    id: userId,
                    system: system,
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                },
                dataType: 'json',
                success: function(response) {
                    $btn.html('<i class="fas fa-edit"></i>');
                    $btn.prop('disabled', false);
                    
                    if (response && response.success) {
                        $('#editUserId').val(response.data.id);
                        $('#editUserSystem').val(system);
                        $('#editUsername').val(response.data.name);
                        $('#editEmail').val(response.data.email);
                        $('#editRole').val(response.data.role);
                        $('#editPassword').val('');
                        $('#editUserModal').modal('show');
                    } else {
                        showAlert('danger', response?.message || 'Invalid response from server');
                    }
                },
                error: function(xhr) {
                    $btn.html('<i class="fas fa-edit"></i>');
                    $btn.prop('disabled', false);
                    let errorMessage = 'Request failed';
                    
                    if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            errorMessage += ': ' + xhr.responseText.substring(0, 100).replace(/\s+/g, ' ').trim();
                        }
                    }
                    
                    showAlert('danger', errorMessage);
                }
            });
        });

        // Delete user button click handler
        let deleteUserId, deleteUserSystem;
        $(document).on('click', '.delete-user', function() {
            deleteUserId = $(this).data('id');
            deleteUserSystem = $(this).data('system');
            $('#deleteConfirmModal').modal('show');
        });

        // Confirm delete button click handler
        $('#confirmDelete').click(function() {
            const $btn = $(this);
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: 'delete_user.php',
                method: 'POST',
                data: { 
                    id: deleteUserId,
                    system: deleteUserSystem,
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                },
                dataType: 'json',
                success: function(response) {
                    $btn.html('<i class="fas fa-trash me-2"></i> Delete User');
                    $btn.prop('disabled', false);
                    $('#deleteConfirmModal').modal('hide');
                    
                    if (response.success) {
                        showAlert('success', response.message || 'User deleted successfully');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('danger', response.message || 'Error deleting user');
                    }
                },
                error: function(xhr) {
                    $btn.html('<i class="fas fa-trash me-2"></i> Delete User');
                    $btn.prop('disabled', false);
                    let errorMessage = 'Error deleting user';
                    
                    if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            errorMessage += ': ' + xhr.responseText.substring(0, 100).replace(/\s+/g, ' ').trim();
                        }
                    }
                    
                    showAlert('danger', errorMessage);
                }
            });
        });

        // Save changes button click handler
        $('#saveUserChanges').click(function() {
            const $btn = $(this);
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            $btn.prop('disabled', true);
            
            const formData = $('#editUserForm').serialize();
            
            $.ajax({
                url: 'update_user.php',
                method: 'POST',
                data: formData + '&csrf_token=<?php echo $_SESSION['csrf_token']; ?>',
                dataType: 'json',
                success: function(response) {
                    $('#editUserModal').modal('hide');
                    
                    if (response.success) {
                        setTimeout(() => {
                            const row = $(`#usersTable tbody tr td:first-child:contains("${response.updatedData.id}")`).closest('tr');
                            
                            if (row.length) {
                                // Update the cells
                                row.find('td:nth-child(2) .fw-semibold').text(response.updatedData.name);
                                row.find('td:nth-child(3)').text(response.updatedData.email);
                                
                                // Update role badge
                                const roleBadge = row.find('td:nth-child(4) span.badge');
                                roleBadge.removeClass('badge-superadmin badge-admin badge-user')
                                         .addClass('badge-' + response.updatedData.role.toLowerCase())
                                         .text(response.updatedData.role.charAt(0).toUpperCase() + 
                                              response.updatedData.role.slice(1));
                                
                                showAlert('success', 'User updated successfully');
                            } else {
                                location.reload();
                            }
                        }, 300);
                    } else {
                        showAlert('danger', response.message || 'Error updating user');
                    }
                },
                error: function(xhr) {
                    $('#editUserModal').modal('hide');
                    let errorMsg = 'Error updating user';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        errorMsg = res.message || errorMsg;
                    } catch (e) {
                        errorMsg += ': ' + xhr.statusText;
                    }
                    setTimeout(() => showAlert('danger', errorMsg), 300);
                },
                complete: function() {
                    $btn.html('<i class="fas fa-save me-2"></i> Save changes');
                    $btn.prop('disabled', false);
                }
            });
        });

        // Form validation for create user
        $('#createUserForm').on('submit', function(e) {
            const password = $('#newPassword').val();
            const systemsSelected = $('input[name="user_type[]"]:checked').length;
            
            if (systemsSelected === 0) {
                e.preventDefault();
                showAlert('danger', 'Please select at least one system');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showAlert('danger', 'Password must be at least 8 characters');
                return false;
            }
            
            if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password)) {
                e.preventDefault();
                showAlert('danger', 'Password must contain uppercase, lowercase letters and numbers');
                return false;
            }
            
            return true;
        });

        // Helper function to show alerts
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                    <i class="fas ${type === 'danger' ? 'fa-exclamation-circle' : 'fa-check-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            $('.alert-dismissible').remove();
            $('.container-fluid').prepend(alertHtml);
            
            setTimeout(() => {
                $('.alert-dismissible').alert('close');
            }, 5000);
        }
    });
    </script>
     <script>
        // Add this function if not already present
        function toggleSidebar() {
            $('#sidebar').toggleClass('collapsed');
            localStorage.setItem('sidebarCollapsed', $('#sidebar').hasClass('collapsed'));
        }

        // Check localStorage for sidebar state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            $('#sidebar').addClass('collapsed');
        }
    </script>

<?php include('./footer.php'); ?>
</body>
</html>