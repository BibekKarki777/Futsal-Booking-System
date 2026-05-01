<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$message = '';
$messageType = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $contactNumber = trim($_POST['contact_number']);
    
    $errors = [];
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, contact_number = ? WHERE user_id = ?");
        if ($stmt->execute([$firstName, $lastName, $contactNumber, $_SESSION['user_id']])) {
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $message = "Profile updated successfully!";
            $messageType = "success";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        } else {
            $message = "Failed to update profile.";
            $messageType = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "danger";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $errors = [];
    
    if (!password_verify($currentPassword, $user['password_hash'])) {
        $errors[] = "Current password is incorrect";
    }
    if (strlen($newPassword) < 6) {
        $errors[] = "New password must be at least 6 characters";
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = "New passwords do not match";
    }
    
    if (empty($errors)) {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        if ($stmt->execute([$newHash, $_SESSION['user_id']])) {
            $message = "Password changed successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to change password.";
            $messageType = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "danger";
    }
}

// Get user statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN booking_status = 'completed' THEN total_price ELSE 0 END) as total_spent
    FROM bookings WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

$pageTitle = "My Profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title><?php echo $pageTitle; ?> - FutsalBook</title>
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <main class="dashboard-main">
        <div class="container py-5">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-4 mb-4">
                    <div class="profile-card text-center">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p class="mb-3" style="color: #64748B;"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge bg-primary mb-3"><?php echo ucfirst($user['role']); ?></span>
                        <hr style="border-color: rgba(255,255,255,0.1);">
                        <div class="profile-stats">
                            <div class="stat-item">
                                <h5><?php echo $stats['total_bookings']; ?></h5>
                                <small style="color: #64748B;">Total Bookings</small>
                            </div>
                            <div class="stat-item">
                                <h5><?php echo $stats['completed']; ?></h5>
                                <small style="color: #64748B;">Completed</small>
                            </div>
                            <div class="stat-item">
                                <h5>Rs. <?php echo number_format($stats['total_spent'] ?? 0); ?></h5>
                                <small style="color: #647189;">Total Spent</small>
                            </div>
                        </div>
                        <hr style="border-color: rgba(255,255,255,0.1);">
                        <p class="mb-0" style="color: #647189;">
                            <i class="fas fa-calendar me-2" style="color: #647189;"></i>
                            Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                    
                    <div class="quick-links mt-4">
                        <a href="dashboard.php" class="quick-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="my-bookings.php" class="quick-link">
                            <i class="fas fa-calendar-check"></i>
                            <span>My Bookings</span>
                        </a>
                        <a href="book-court.php" class="quick-link">
                            <i class="fas fa-plus-circle"></i>
                            <span>Book Court</span>
                        </a>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-lg-8">
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Profile Info -->
                    <div class="content-card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-user-edit me-2"></i>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                        <small style="color: #647189;">Email cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="contact_number" 
                                               value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"
                                               placeholder="e.g., 9800000000">
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="content-card">
                        <div class="card-header">
                            <h5><i class="fas fa-lock me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Current Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="current_password" 
                                                   id="currentPassword" required>
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('currentPassword')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="new_password" 
                                                   id="newPassword" minlength="6" required>
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('newPassword')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small style="color: #64748B;">Minimum 6 characters</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   id="confirmPassword" minlength="6" required>
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('confirmPassword')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
        
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/profile.js"></script>
    
</body>
</html>
