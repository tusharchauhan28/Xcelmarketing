<?php
session_start();
include 'config.php';

$errors = [];
$registrationSuccess = false; // Track if registration is successful

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $terms = isset($_POST['terms']) ? true : false; // Check if terms are agreed to

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!$terms) {
        $errors[] = "You must agree to the terms and conditions.";
    }

    // Check if email already exists in the database
    if (empty($errors)) {
        $checkStmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $errors[] = "Email is already registered. Please use a different email.";
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into the database
            $stmt = $conn->prepare("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $registrationSuccess = true; // Registration successful
            } else {
                $errors[] = "❌ Error: " . $stmt->error;
            }
        }
    }
}

?>

<!-- HTML Part -->
<?php if ($registrationSuccess): ?>
    <p style="color:green;">✅ User registered successfully!</p>
    <form action="login.php" method="get">
        <button type="submit">Go to Login</button>
    </form>
<?php else: ?>
    <!-- Registration Form -->
    <form method="POST">
        <input type="hidden" name="register" value="1">
        
        <div class="form-group">
            <label for="reg-username" class="form-label">Username</label>
            <div class="input-icon">
                <i class="fas fa-user"></i>
                <input type="text" class="form-control" id="reg-username" name="username" placeholder="Choose a username" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="reg-email" class="form-label">Email</label>
            <div class="input-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" class="form-control" id="reg-email" name="email" placeholder="Your email address" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="reg-password" class="form-label">Password</label>
            <div class="input-icon">
                <i class="fas fa-lock"></i>
                <input type="password" class="form-control" id="reg-password" name="password" placeholder="Create a password" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="reg-confirm-password" class="form-label">Confirm Password</label>
            <div class="input-icon">
                <i class="fas fa-lock"></i>
                <input type="password" class="form-control" id="reg-confirm-password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
        </div>

        <select name="role" required>
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="editor">User</option>
        </select><br><br>
        
        <!-- Terms and Conditions checkbox -->
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="reg-terms" name="terms" required>
            <label class="form-check-label" for="reg-terms">I agree to the <a href="#">terms and conditions</a></label>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" name="register" class="btn btn-primary mt-2">
            <i class="fas fa-user-plus me-2"></i> Register
        </button>
        
        <div class="divider">OR</div>
        
        <button type="button" class="btn btn-secondary" id="show-login">
            <i class="fas fa-sign-in-alt me-2"></i> Already have an account?
        </button>

        <!-- Show Errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </form>
<?php endif; ?>
