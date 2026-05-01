<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$messageType = '';

// Get court ID
$courtId = (int)($_GET['id'] ?? 0);
if (!$courtId) {
    header('Location: manage-courts.php');
    exit;
}

// Fetch court data
$stmt = $pdo->prepare("SELECT * FROM courts WHERE court_id = ?");
$stmt->execute([$courtId]);
$court = $stmt->fetch();

if (!$court) {
    header('Location: manage-courts.php');
    exit;
}

// Get all futsals for dropdown
$futsals = $pdo->query("SELECT * FROM futsals ORDER BY futsal_name")->fetchAll();

$formData = $court;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'futsal_id' => (int)($_POST['futsal_id'] ?? 0),
        'court_name' => trim($_POST['court_name'] ?? ''),
        'surface_type' => $_POST['surface_type'] ?? 'artificial_grass',
        'price_per_hour' => floatval($_POST['price_per_hour'] ?? 0),
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Validation
    $errors = [];
    if (empty($formData['futsal_id'])) $errors[] = "Please select a venue";
    if (empty($formData['court_name'])) $errors[] = "Court name is required";
    if ($formData['price_per_hour'] <= 0) $errors[] = "Please enter a valid price";
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE courts SET 
                futsal_id = ?, court_name = ?, surface_type = ?, price_per_hour = ?, status = ?
            WHERE court_id = ?
        ");
        
        if ($stmt->execute([
            $formData['futsal_id'],
            $formData['court_name'],
            $formData['surface_type'],
            $formData['price_per_hour'],
            $formData['status'],
            $courtId
        ])) {
            $message = "Court updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update court. Please try again.";
            $messageType = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "danger";
    }
}

// Get booking stats for this court
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(total_price) as total_revenue
    FROM bookings WHERE court_id = ?
");
$stmt->execute([$courtId]);
$stats = $stmt->fetch();

$pageTitle = "Edit Court";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title><?php echo $pageTitle; ?> - FutsalBook Admin</title>
    <link rel="stylesheet" href="../assets/css/edit-court.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-edit me-2"></i>Edit Court</h1>
            <a href="manage-courts.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Courts
            </a>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="fas fa-th-large me-2"></i>Court Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Select Venue <span class="text-danger">*</span></label>
                                    <select class="form-select" name="futsal_id" required>
                                        <option value="">-- Select Venue --</option>
                                        <?php foreach ($futsals as $futsal): ?>
                                        <option value="<?php echo $futsal['futsal_id']; ?>"
                                                <?php echo $formData['futsal_id'] == $futsal['futsal_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($futsal['futsal_name']); ?> (<?php echo $futsal['city']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Court Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="court_name" 
                                           value="<?php echo htmlspecialchars($formData['court_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Surface Type</label>
                                    <select class="form-select" name="surface_type">
                                        <option value="artificial_grass" <?php echo $formData['surface_type'] == 'artificial_grass' ? 'selected' : ''; ?>>Artificial Grass</option>
                                        <option value="natural_grass" <?php echo $formData['surface_type'] == 'natural_grass' ? 'selected' : ''; ?>>Natural Grass</option>
                                        <option value="rubber" <?php echo $formData['surface_type'] == 'rubber' ? 'selected' : ''; ?>>Rubber</option>
                                        <option value="concrete" <?php echo $formData['surface_type'] == 'concrete' ? 'selected' : ''; ?>>Concrete</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Price Per Hour (Rs.) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="price_per_hour" 
                                           value="<?php echo $formData['price_per_hour']; ?>" 
                                           min="0" step="100" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" <?php echo $formData['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="maintenance" <?php echo $formData['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Court
                                </button>
                                <a href="manage-courts.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Court Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <span>Court ID</span>
                            <strong><?php echo $courtId; ?></strong>
                        </div>
                        <div class="info-item">
                            <span>Created</span>
                            <strong><?php echo date('M j, Y', strtotime($court['created_at'] ?? 'now')); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
