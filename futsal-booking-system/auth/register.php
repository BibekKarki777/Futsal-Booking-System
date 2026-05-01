<?php
/**
 * Registration Page
 * Futsal Booking System
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : '../user/dashboard.php');
}

$pageTitle = 'Register';
$errors = [];
$success = false;

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    if (empty($contact_number)) {
        $errors[] = 'Contact number is required';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered';
        }
    }
    
    // Create account
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, contact_number, role) 
                                   VALUES (?, ?, ?, ?, ?, 'player')");
            $stmt->execute([$first_name, $last_name, $email, $password_hash, $contact_number]);
            
            $_SESSION['success'] = 'Account created successfully! Please login.';
            redirect('login.php');
            
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card animate-fade-in" style="max-width: 500px;">
        <div class="auth-header">
            <a href="../index.php" class="text-decoration-none">
                <i class="fas fa-futbol"></i>
            </a>
            <h2>Create Account</h2>
            <p>Join FutsalBook and start booking</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo implode('<br>', $errors); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                               placeholder="First name" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                           placeholder="Last name" required>
                </div>
            </div>
            
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
            
            <div class="mb-3">
                <label for="contact_number" class="form-label">Contact Number</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-phone"></i>
                    </span>
                    <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                           value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>"
                           placeholder="98XXXXXXXX" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Min. 6 characters" required>
                    <button type="button" class="input-group-text toggle-password" style="cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm password" required>
                </div>
            </div>
            
            <div class="form-check mb-4">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label text-secondary" for="terms">
                    I agree to the <a href="#" class="text-primary">Terms of Service</a> and 
                    <a href="#" class="text-primary">Privacy Policy</a>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-user-plus me-2"></i> Create Account
            </button>
            
            <p class="text-center text-secondary mb-0">
                Already have an account? 
                <a href="login.php" class="text-primary">Sign in</a>
            </p>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
