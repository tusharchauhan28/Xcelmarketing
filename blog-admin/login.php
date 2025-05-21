<?php
session_start();
include 'config.php'; // Your database connection

// Login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $hasErrors = false;

    // Validation
    if (empty($username)) {
        $errors['username'] = 'Username is required';
        $hasErrors = true;
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
        $hasErrors = true;
    }

    if (!$hasErrors) {
        // Check all user tables (modify according to your DB structure)
        $tables = [
            'fab_user',
            'xcel_user', 
            'rcs_user'
        ];
        
        $userFound = false;
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE name = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $userFound = true;
                session_regenerate_id();  // Prevent session fixation
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $user['name'];
                $_SESSION['role'] = $user['role']; // store role (superadmin/admin/editor)
                $_SESSION['user_table'] = $table; // store which table user came from

                // Role-based redirection
                switch ($user['role']) {
                    case 'superadmin':
                        header('Location: main.php');
                        break;
                    case 'admin':
                        header('Location: index.php');
                        break;
                    default:
                        header('Location: index.php'); // Default dashboard for other roles
                }
                exit();
            }
        }
        
        if (!$userFound) {
            $errors['general'] = 'Invalid username or password';
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Secure Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #e0e7ff;
            --secondary: #6366f1;
            --accent: #ec4899;
            --accent-light: #fce7f3;
            --light: #f9fafb;
            --dark: #111827;
            --gray: #6b7280;
            --success: #10b981;
            --success-light: #d1fae5;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            background-color: #f3f4f6;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .auth-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .auth-hero {
            flex: 1;
            background: linear-gradient(rgba(79, 70, 229, 0.85), rgba(67, 56, 202, 0.9)), 
                        url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5rem;
            color: white;
            position: relative;
        }
        
        .auth-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .hero-content {
            max-width: 600px;
            position: relative;
            z-index: 2;
        }
        
        .hero-logo {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .hero-logo i {
            font-size: 2rem;
            color: white;
        }
        
        .hero-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
        }
        
        .feature-list {
            list-style: none;
        }
        
        .feature-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            position: relative;
            padding-left: 2.5rem;
        }
        
        .feature-list li::before {
            content: '';
            position: absolute;
            left: 0;
            width: 24px;
            height: 24px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .feature-list i {
            position: absolute;
            left: 0;
            font-size: 0.9rem;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .auth-form {
            flex: 1;
            max-width: 800px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem 5rem;
            position: relative;
        }
        
        .form-header {
            margin-bottom: 3rem;
            text-align: center;
        }
        
        .form-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
            color: white;
            font-size: 2rem;
        }
        
        .form-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .form-header p {
            color: var(--gray);
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            height: 52px;
            padding: 0.75rem 1.25rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }
        
        .form-control.is-invalid {
            border-color: var(--accent);
            background-color: var(--accent-light);
            padding-right: 2.5rem;
        }
        
        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background-color: white;
            outline: none;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .input-icon input {
            padding-left: 3.25rem;
        }
        
        .invalid-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
        }
        
        .invalid-feedback {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--accent);
            animation: fadeIn 0.3s ease-out;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 52px;
            padding: 0 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            width: 100%;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(79, 70, 229, 0.3);
        }
        
        .forgot-link {
            display: inline-block;
            margin-top: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            animation: fadeIn 0.3s ease-out;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .alert-danger {
            background-color: var(--accent-light);
            border: 1px solid rgba(236, 72, 153, 0.2);
            color: var(--accent);
        }
        
        .alert-success {
            background-color: var(--success-light);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
        
        .footer-text {
            margin-top: 3rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .footer-text a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .footer-text a:hover {
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        @media (max-width: 1200px) {
            .auth-container {
                flex-direction: column;
            }
            
            .auth-hero, .auth-form {
                padding: 3rem;
            }
            
            .auth-hero {
                min-height: 400px;
            }
            
            .hero-content {
                max-width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .auth-hero, .auth-form {
                padding: 2rem;
            }
            
            .form-logo {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
            
            .form-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-hero">
            <div class="hero-content">
                <div class="hero-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2>Enterprise Admin Console</h2>
                <p>Secure access to your organization's management dashboard with advanced monitoring and control features.</p>
                
                <ul class="feature-list">
                    <li><i class="fas fa-lock"></i> Military-grade encryption</li>
                    <li><i class="fas fa-chart-pie"></i> Real-time analytics</li>
                    <li><i class="fas fa-users-cog"></i> User management</li>
                    <li><i class="fas fa-bell"></i> Instant alerts</li>
                    <li><i class="fas fa-cloud"></i> Cloud integration</li>
                </ul>
            </div>
        </div>
        
        <div class="auth-form">
            <div class="form-header">
                <div class="form-logo">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Please sign in to your admin account</p>
            </div>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="login" value="1">
                
                <div class="form-group">
                    <label for="login-username" class="form-label">Admin Username</label>
                    <div class="input-icon">
                        <i class="fas fa-user-tie"></i>
                        <input type="text" class="form-control <?php echo !empty($errors['username']) ? 'is-invalid' : ''; ?>" 
                               id="login-username" name="username" placeholder="Enter your username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <?php if (!empty($errors['username'])): ?>
                            <i class="fas fa-exclamation-circle invalid-icon"></i>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($errors['username'])): ?>
                        <div class="invalid-feedback">
                            <?php echo $errors['username']; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="login-password" class="form-label">Password</label>
                    <div class="input-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" 
                               id="login-password" name="password" placeholder="Enter your password">
                        <?php if (!empty($errors['password'])): ?>
                            <i class="fas fa-exclamation-circle invalid-icon"></i>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="invalid-feedback">
                            <?php echo $errors['password']; ?>
                        </div>
                    <?php endif; ?>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary mt-2">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </button>
            </form>
            
            <div class="footer-text">
                <p>© <?php echo date('Y'); ?> Blog Admin  | <a href="#">Terms</a> | <a href="#">Privacy</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Input focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>