<?php
/**
 * Login Page
 * Futsal Booking System
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : '../user/dashboard.php');
}

$pageTitle = 'Login';
$errors = [];

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                $_SESSION['success'] = 'Welcome back, ' . $user['first_name'] . '!';
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('../admin/dashboard.php');
                } else {
                    redirect('../user/dashboard.php');
                }
            } else {
                $errors[] = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            $errors[] = 'Login failed. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card animate-fade-in">
        <div class="auth-header">
            <a href="../index.php" class="text-decoration-none">
                <i class="fas fa-futbol"></i>
            </a>
            <h2>Welcome Back</h2>
            <p>Sign in to continue to FutsalBook</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo implode('<br>', $errors); ?>
            </div>
        <?php endif; ?>
        
        <?php echo displayAlert(); ?>
        
        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           placeholder="Enter your email" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter your password" required>
                    <button type="button" class="input-group-text toggle-password" style="cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label text-secondary" for="remember">Remember me</label>
                </div>
                <a href="#" class="text-primary small">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i> Sign In
            </button>
            
            <p class="text-center text-secondary mb-0">
                Don't have an account? 
                <a href="register.php" class="text-primary">Create one</a>
            </p>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
