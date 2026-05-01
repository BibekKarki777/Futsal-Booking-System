<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$messageType = '';

// Handle delete
if (isset($_GET['delete'])) {
    $futsalId = (int)$_GET['delete'];
    
    // Check if futsal has courts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courts WHERE futsal_id = ?");
    $stmt->execute([$futsalId]);
    $courtCount = $stmt->fetchColumn();
    
    if ($courtCount > 0) {
        $message = "Cannot delete venue. Please delete associated courts first.";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("DELETE FROM futsals WHERE futsal_id = ?");
        if ($stmt->execute([$futsalId])) {
            $message = "Venue deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to delete venue.";
            $messageType = "danger";
        }
    }
}

// Handle status toggle
if (isset($_GET['toggle'])) {
    $futsalId = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE futsals SET status = IF(status = 'active', 'maintenance', 'active') WHERE futsal_id = ?");
    if ($stmt->execute([$futsalId])) {
        $message = "Venue status updated!";
        $messageType = "success";
    }
}

// Get all futsals with court count
$stmt = $pdo->query("
    SELECT f.*, COUNT(c.court_id) as court_count
    FROM futsals f
    LEFT JOIN courts c ON f.futsal_id = c.futsal_id
    GROUP BY f.futsal_id
    ORDER BY f.futsal_name
");
$futsals = $stmt->fetchAll();

$pageTitle = "Manage Venues";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title><?php echo $pageTitle; ?> - FutsalBook Admin</title>
    <link rel="stylesheet" href="../assets/css/manage-futsals.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-building me-2"></i>Manage Venues</h1>
            <a href="add-futsal.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Venue
            </a>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>All Venues (<?php echo count($futsals); ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Venue Name</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Operating Hours</th>
                            <th>Courts</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($futsals)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No venues found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($futsals as $futsal): ?>
                        <tr>
                            <td>
                                <strong style="color: #647189;"><?php echo htmlspecialchars($futsal['futsal_name']); ?></strong>
                            </td>
                            <td style="color: #647189;">
                                <?php echo htmlspecialchars($futsal['address']); ?>
                                <small style="color: #647189;" class="d-block"><?php echo htmlspecialchars($futsal['city']); ?></small>
                            </td>
                            <td style="color: #647189;"><?php echo htmlspecialchars($futsal['contact_number']); ?></td>
                            <td style="color: #647189;">
                                <?php echo formatTime($futsal['open_time']) . ' - ' . formatTime($futsal['close_time']); ?>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo $futsal['court_count']; ?> courts</span>
                            </td>
                            <td>
                                <?php if ($futsal['status'] == 'active'): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-warning">Maintenance</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit-futsal.php?id=<?php echo $futsal['futsal_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?toggle=<?php echo $futsal['futsal_id']; ?>" 
                                       class="btn btn-sm btn-outline-warning" title="Toggle Status">
                                        <i class="fas fa-power-off"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $futsal['futsal_id']; ?>, '<?php echo htmlspecialchars($futsal['futsal_name']); ?>')"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-light">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-light">
                    <p>Are you sure you want to delete <strong id="deleteFutsalName"></strong>?</p>
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
    <script src="../assets/js/manage-futsals.js"></script>
   
</body>
</html>
