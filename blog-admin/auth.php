// auth.php
session_start();
include 'config.php';

// After successful authentication:
if (password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    
    // Redirect based on role
    switch ($user['role']) {
        case 'superadmin':
            header('Location: main.index.php');
            break;
        case 'admin':
            header('Location: admin.php');
            break;
        default:
            header('Location: dashboard.php');
    }
    exit();
}