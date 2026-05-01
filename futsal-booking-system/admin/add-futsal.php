<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$messageType = '';
$formData = [
    'futsal_name' => '',
    'address' => '',
    'city' => '',
    'contact_number' => '',
    'open_time' => '06:00',
    'close_time' => '22:00',
    'status' => 'active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'futsal_name' => trim($_POST['futsal_name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'contact_number' => trim($_POST['contact_number'] ?? ''),
        'open_time' => $_POST['open_time'] ?? '06:00',
        'close_time' => $_POST['close_time'] ?? '22:00',
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Validation
    $errors = [];
    if (empty($formData['futsal_name'])) $errors[] = "Venue name is required";
    if (empty($formData['address'])) $errors[] = "Address is required";
    if (empty($formData['city'])) $errors[] = "City is required";
    if (empty($formData['contact_number'])) $errors[] = "Contact number is required";
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO futsals (futsal_name, address, city, contact_number, open_time, close_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([
            $formData['futsal_name'],
            $formData['address'],
            $formData['city'],
            $formData['contact_number'],
            $formData['open_time'],
            $formData['close_time'],
            $formData['status']
        ])) {
            $message = "Venue added successfully!";
            $messageType = "success";
            // Reset form
            $formData = [
                'futsal_name' => '',
                'address' => '',
                'city' => '',
                'contact_number' => '',
                'open_time' => '06:00',
                'close_time' => '22:00',
                'status' => 'active'
            ];
        } else {
            $message = "Failed to add venue. Please try again.";
            $messageType = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "danger";
    }
}

$pageTitle = "Add New Venue";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title><?php echo $pageTitle; ?> - FutsalBook Admin</title>
    <link rel="stylesheet" href="../assets/css/add-futsal.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-plus-circle me-2"></i>Add New Venue</h1>
            <a href="manage-futsals.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Venues
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
                        <h5><i class="fas fa-building me-2"></i>Venue Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Venue Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="futsal_name" 
                                           value="<?php echo htmlspecialchars($formData['futsal_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="contact_number" 
                                           value="<?php echo htmlspecialchars($formData['contact_number']); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="address" 
                                           value="<?php echo htmlspecialchars($formData['address']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="city" 
                                           value="<?php echo htmlspecialchars($formData['city']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" <?php echo $formData['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="maintenance" <?php echo $formData['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Opening Time</label>
                                    <input type="time" class="form-control" name="open_time" 
                                           value="<?php echo $formData['open_time']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Closing Time</label>
                                    <input type="time" class="form-control" name="close_time" 
                                           value="<?php echo $formData['close_time']; ?>">
                                </div>
                            </div>
                            
                            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Venue
                                </button>
                                <a href="manage-futsals.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Tips</h5>
                    </div>
                    <div class="card-body">
                        <ul class="tips-list">
                            <li>Use a clear, recognizable venue name</li>
                            <li>Include full address for easy navigation</li>
                            <li>Set accurate operating hours</li>
                            <li>Add courts after creating the venue</li>
                            <li>Use 'Maintenance' status for temporary closures</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
