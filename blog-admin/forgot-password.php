<?php
session_start();
include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiration
            
            // Store token in database
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user['id'], $token, $expires);
            $stmt->execute();
            
            // Send email (in production, use PHPMailer or similar)
            $reset_link = "https://yourdomain.com/reset-password.php?token=$token";
            $subject = "Password Reset Request";
            $message = "Click this link to reset your password: $reset_link\n\nThis link will expire in 1 hour.";
            $headers = "From: no-reply@yourdomain.com";
            
            if (mail($email, $subject, $message, $headers)) {
                $success = 'Password reset link has been sent to your email';
            } else {
                $error = 'Failed to send email. Please try again.';
            }
        } else {
            $error = 'If this email exists in our system, you will receive a reset link';
            // Don't reveal if email doesn't exist for security
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-reset-container">
            <h2 class="form-title">Forgot Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="form-text">Enter your account email address</div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-reset">Send Reset Link</button>
            </form>
            
            <div class="login-link">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>