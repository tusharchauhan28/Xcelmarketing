<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username == 'admin' && $password == 'password123') {
        $_SESSION['loggedin'] = true;
        header('Location: index.php');
        exit();
    } else {
        $error_message = 'Invalid credentials. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Secure Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            color: var(--dark);
            background-color: #f5f7fa;
        }
        
        .split-container {
            display: flex;
            min-height: 100vh;
        }
        
        .image-side {
            flex: 1;
            background: url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') center center;
            background-size: cover;
            position: relative;
        }
        
        .image-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(67, 97, 238, 0.9), rgba(63, 55, 201, 0.7));
        }
        
        .image-content {
            position: relative;
            z-index: 2;
            color: white;
            padding: 3rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .image-content h2 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
        }
        
        .image-content p {
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0.9;
            max-width: 500px;
        }
        
        .form-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: white;
        }
        
        .login-container {
            width: 100%;
            max-width: 650px;
            padding: 2.5rem;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-header h1 {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 1rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }
        
        .input-group-text {
            background-color: #f1f3ff;
            border: 1px solid #e0e0e0;
            border-right: none;
            color: var(--primary);
        }
        
        .input-with-icon {
            border-left: none;
        }
        
        .btn-login {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            height: 50px;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 1rem;
            transition: all 0.3s;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--secondary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .forgot-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        .alert-danger {
            background: rgba(247, 37, 133, 0.1);
            border-color: rgba(247, 37, 133, 0.2);
            color: var(--accent);
            border-radius: 8px;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .footer-text a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
        }
        
        @media (max-width: 992px) {
            .split-container {
                flex-direction: column;
            }
            
            .image-side {
                min-height: 300px;
            }
            
            .form-side {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="split-container">
        <div class="image-side">
            <div class="image-content">
                <h2>Welcome to Admin Portal</h2>
                <p>Manage your content, users, and settings with our powerful admin dashboard. Secure, reliable, and built for performance.</p>
                <p><i class="fas fa-shield-alt me-2"></i> Enterprise-grade security</p>
                <p><i class="fas fa-chart-line me-2"></i> Real-time analytics</p>
                <p><i class="fas fa-cog me-2"></i> Full control panel</p>
            </div>
        </div>
        
        <div class="form-side">
            <div class="login-container">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                
                <div class="login-header">
                    <h1>Sign In</h1>
                    <p>Enter your credentials to access the dashboard</p>
                </div>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control input-with-icon" id="username" name="username" placeholder="Enter admin username" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <div class="text-end mt-2">
                            <a href="#" class="forgot-link">Forgot password?</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>
                    
                    <div class="footer-text">
                        <p>© <?php echo date('Y'); ?> Admin Dashboard v2.0 | <a href="#">Need help?</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add focus effects to form inputs
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.boxShadow = '0 0 0 3px rgba(67, 97, 238, 0.1)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>