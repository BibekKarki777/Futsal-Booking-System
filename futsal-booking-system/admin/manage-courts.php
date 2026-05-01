<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$messageType = '';

// Handle delete
if (isset($_GET['delete'])) {
    $courtId = (int)$_GET['delete'];
    
    // Check for active bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE court_id = ? AND booking_status IN ('pending', 'confirmed')");
    $stmt->execute([$courtId]);
    $bookingCount = $stmt->fetchColumn();
    
    if ($bookingCount > 0) {
        $message = "Cannot delete court. There are active bookings for this court.";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("DELETE FROM courts WHERE court_id = ?");
        if ($stmt->execute([$courtId])) {
            $message = "Court deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to delete court.";
            $messageType = "danger";
        }
    }
}

// Handle status toggle
if (isset($_GET['toggle'])) {
    $courtId = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE courts SET status = IF(status = 'active', 'maintenance', 'active') WHERE court_id = ?");
    if ($stmt->execute([$courtId])) {
        $message = "Court status updated!";
        $messageType = "success";
    }
}

// Filter by futsal
$futsalFilter = isset($_GET['futsal']) ? (int)$_GET['futsal'] : 0;

// Get all courts
$sql = "
    SELECT c.*, f.futsal_name, f.city,
           (SELECT COUNT(*) FROM bookings b WHERE b.court_id = c.court_id) as booking_count
    FROM courts c
    JOIN futsals f ON c.futsal_id = f.futsal_id
";
if ($futsalFilter > 0) {
    $sql .= " WHERE c.futsal_id = " . $futsalFilter;
}
$sql .= " ORDER BY f.futsal_name, c.court_name";

$courts = $pdo->query($sql)->fetchAll();

// Get all futsals for filter
$futsals = $pdo->query("SELECT * FROM futsals ORDER BY futsal_name")->fetchAll();

$pageTitle = "Manage Courts";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title><?php echo $pageTitle; ?> - FutsalBook Admin</title>
    <link rel="stylesheet" href="../assets/css/manage-courts.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-th-large me-2"></i>Manage Courts</h1>
            <a href="add-court.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Court
            </a>
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
                    <div class="col-md-4">
                        <label class="form-label">Filter by Venue</label>
                        <select class="form-select" name="futsal" onchange="this.form.submit()">
                            <option value="0">All Venues</option>
                            <?php foreach ($futsals as $futsal): ?>
                            <option value="<?php echo $futsal['futsal_id']; ?>" 
                                    <?php echo $futsalFilter == $futsal['futsal_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($futsal['futsal_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="manage-courts.php" class="btn btn-outline-secondary w-100">Clear Filter</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>All Courts (<?php echo count($courts); ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Court Name</th>
                            <th>Venue</th>
                            <th>Surface Type</th>
                            <th>Price/Hour</th>
                            <th>Bookings</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No courts found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($courts as $court): ?>
                        <tr>
                            <td>
                                <strong style="color: #647189;"><?php echo htmlspecialchars($court['court_name']); ?></strong>
                            </td>
                            <td style="color: #647189;">
                                <?php echo htmlspecialchars($court['futsal_name']); ?>
                                <small style="color: #647189;" class="d-block"><?php echo htmlspecialchars($court['city']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo ucfirst($court['surface_type']); ?></span>
                            </td>
                            <td class="fw-semibold text-success">Rs. <?php echo number_format($court['price_per_hour']); ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $court['booking_count']; ?> bookings</span>
                            </td>
                            <td>
                                <?php if ($court['status'] == 'active'): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-warning">Maintenance</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit-court.php?id=<?php echo $court['court_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?toggle=<?php echo $court['court_id']; ?><?php echo $futsalFilter ? '&futsal='.$futsalFilter : ''; ?>" 
                                       class="btn btn-sm btn-outline-warning" title="Toggle Status">
                                        <i class="fas fa-power-off"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $court['court_id']; ?>, '<?php echo htmlspecialchars($court['court_name']); ?>', '<?php echo $futsalFilter; ?>')"
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
                    <p>Are you sure you want to delete <strong id="deleteCourtName"></strong>?</p>
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
    <script src="../assets/js/manage-courts.js"></script>


</body>
</html>
