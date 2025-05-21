<?php
session_start();
include 'config.php';

$error = '';
$token = $_GET['token'] ?? '';

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        $error = 'Invalid or expired reset link';
        $token = ''; // Invalidate token
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        // Get user ID from token
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
        
        // Delete used token
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        $_SESSION['password_reset_success'] = true;
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .password-reset-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .btn-reset {
            background-color: #4361ee;
            border: none;
            width: 100%;
            padding: 12px;
            font-weight: 600;
        }
        .btn-reset:hover {
            background-color: #3a56d4;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background: #eee;
            border-radius: 5px;
            overflow: hidden;
        }
        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-reset-container">
            <h2 class="form-title">Reset Your Password</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php elseif (empty($token)): ?>
                <div class="alert alert-warning">Invalid or expired password reset link. Please request a new one.</div>
                <div class="text-center mt-3">
                    <a href="forgot-password.php" class="btn btn-primary">Request New Link</a>
                </div>
                <?php exit(); ?>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                    <div class="password-strength">
                        <div class="strength-meter" id="strengthMeter"></div>
                    </div>
                    <div class="form-text">Minimum 8 characters</div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                
                <button type="submit" class="btn btn-primary btn-reset">Reset Password</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.getElementById('strengthMeter');
            let strength = 0;
            
            // Check password strength
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            // Update meter
            const width = strength * 20;
            meter.style.width = width + '%';
            
            // Change color
            if (strength <= 2) {
                meter.style.backgroundColor = '#dc3545'; // Red
            } else if (strength <= 4) {
                meter.style.backgroundColor = '#fd7e14'; // Orange
            } else {
                meter.style.backgroundColor = '#28a745'; // Green
            }
        });
    </script>
</body>
</html>