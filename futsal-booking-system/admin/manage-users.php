<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$messageType = '';

// Handle delete
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    // Prevent deleting self
    if ($userId == $_SESSION['user_id']) {
        $message = "You cannot delete your own account!";
        $messageType = "danger";
    } else {
        // Check for bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $bookingCount = $stmt->fetchColumn();
        
        if ($bookingCount > 0) {
            $message = "Cannot delete user with existing bookings.";
            $messageType = "danger";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            if ($stmt->execute([$userId])) {
                $message = "User deleted successfully!";
                $messageType = "success";
            }
        }
    }
}

// Handle role toggle
if (isset($_GET['toggle_role'])) {
    $userId = (int)$_GET['toggle_role'];
    
    // Prevent changing own role
    if ($userId == $_SESSION['user_id']) {
        $message = "You cannot change your own role!";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = IF(role = 'admin', 'player', 'admin') WHERE user_id = ?");
        if ($stmt->execute([$userId])) {
            $message = "User role updated!";
            $messageType = "success";
        }
    }
}

// Filter
$roleFilter = $_GET['role'] ?? '';

// Get all users
$sql = "
    SELECT u.*, 
           (SELECT COUNT(*) FROM bookings WHERE user_id = u.user_id) as booking_count,
           (SELECT SUM(total_price) FROM bookings WHERE user_id = u.user_id AND booking_status = 'completed') as total_spent
    FROM users u
    WHERE 1=1
";
$params = [];

if ($roleFilter) {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = "Manage Users";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title><?php echo $pageTitle; ?> - FutsalBook Admin</title>
    <link rel="stylesheet" href="../assets/css/manage-users.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-users me-2"></i>Manage Users</h1>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="admin-card mb-4">
            <div class="card-body py-3">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Filter by Role</label>
                        <select class="form-select" name="role" onchange="this.form.submit()">
                            <option value="">All Users</option>
                            <option value="admin" <?php echo $roleFilter == 'admin' ? 'selected' : ''; ?>>Admins</option>
                            <option value="player" <?php echo $roleFilter == 'player' ? 'selected' : ''; ?>>Players</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="manage-users.php" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>All Users (<?php echo count($users); ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Bookings</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No users found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong style="color: #647189;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                <span class="badge bg-info ms-1">You</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #647189;"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td style="color: #647189;"><?php echo htmlspecialchars($user['contact_number'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($user['role'] == 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                <span class="badge bg-primary">Player</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $user['booking_count']; ?></span>
                            </td>
                            <td class="text-success fw-semibold">
                                Rs. <?php echo number_format($user['total_spent'] ?? 0); ?>
                            </td>
                            <td style="color: #647189;">
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="?toggle_role=<?php echo $user['user_id']; ?><?php echo $roleFilter ? '&role='.$roleFilter : ''; ?>" 
                                       class="btn btn-sm btn-outline-warning" 
                                       title="Toggle Role"
                                       onclick="return confirm('Change this user\'s role?')">
                                        <i class="fas fa-user-shield"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>', '<?php echo $roleFilter; ?>')"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- View User Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-light"><i class="fas fa-user-circle me-2 text-primary"></i>User Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetails">
                    <!-- Filled by JS -->
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-light">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-light">
                    <p>Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                    <p class="text-danger mb-0"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>
    
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/manage-users.js"></script>
    
    
    </body>
</html>
