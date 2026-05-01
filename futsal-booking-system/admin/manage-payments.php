<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$messageType = '';

// Handle payment update
if (isset($_POST['mark_paid'])) {
    $paymentId = (int)$_POST['payment_id'];
    $method = $_POST['method'];
    $transactionRef = $_POST['transaction_ref'] ?? '';
    
    $stmt = $pdo->prepare("
        UPDATE payments SET 
            payment_status = 'paid', 
            method = ?,
            paid_at = NOW(),
            transaction_ref = ?
        WHERE payment_id = ?
    ");
    if ($stmt->execute([$method, $transactionRef, $paymentId])) {
        // Update booking status to confirmed
        $stmt = $pdo->prepare("
            UPDATE bookings SET booking_status = 'confirmed' 
            WHERE booking_id = (SELECT booking_id FROM payments WHERE payment_id = ?)
            AND booking_status = 'pending'
        ");
        $stmt->execute([$paymentId]);
        
        $message = "Payment marked as paid!";
        $messageType = "success";
    }
}

// Filter
$statusFilter = $_GET['status'] ?? '';

// Get all payments
$sql = "
    SELECT p.*, b.booking_date, b.start_time, b.end_time, b.booking_status,
           u.first_name, u.last_name, u.email,
           c.court_name, f.futsal_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN users u ON b.user_id = u.user_id
    JOIN courts c ON b.court_id = c.court_id
    JOIN futsals f ON c.futsal_id = f.futsal_id
    WHERE 1=1
";
$params = [];

if ($statusFilter) {
    $sql .= " AND p.payment_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY p.payment_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get payment summary
$summary = $pdo->query("
    SELECT 
        SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END) as total_unpaid,
        SUM(CASE WHEN payment_status = 'refunded' THEN amount ELSE 0 END) as total_refunded,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count,
        COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) as unpaid_count
    FROM payments
")->fetch();

$pageTitle = "Manage Payments";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title><?php echo $pageTitle; ?> - FutsalBook Admin</title>
    <link rel="stylesheet" href="../assets/css/manage-payments.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-credit-card me-2"></i>Manage Payments</h1>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="summary-card bg-success-subtle">
                    <div class="summary-icon">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div class="summary-content">
                        <h3 class="text-success">Rs. <?php echo number_format($summary['total_paid'] ?? 0); ?></h3>
                        <p>Total Collected (<?php echo $summary['paid_count'] ?? 0; ?> payments)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card bg-warning-subtle">
                    <div class="summary-icon">
                        <i class="fas fa-clock text-warning"></i>
                    </div>
                    <div class="summary-content">
                        <h3 class="text-warning">Rs. <?php echo number_format($summary['total_unpaid'] ?? 0); ?></h3>
                        <p>Pending Collection (<?php echo $summary['unpaid_count'] ?? 0; ?> payments)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card bg-secondary-subtle">
                    <div class="summary-icon">
                        <i class="fas fa-undo text-secondary"></i>
                    </div>
                    <div class="summary-content">
                        <h3 class="text-secondary">Rs. <?php echo number_format($summary['total_refunded'] ?? 0); ?></h3>
                        <p>Total Refunded</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="admin-card mb-4">
            <div class="card-body py-3">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Filter by Status</label>
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All Payments</option>
                            <option value="unpaid" <?php echo $statusFilter == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="paid" <?php echo $statusFilter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="refunded" <?php echo $statusFilter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="manage-payments.php" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>All Payments (<?php echo count($payments); ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Booking</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Reference</th>
                            <th>Paid At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No payments found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <strong style="color: #647189;"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></strong>
                                <small style="color: #647189;" class="d-block"><?php echo htmlspecialchars($payment['email']); ?></small>
                            </td>
                            <td style="color: #647189;">
                                <?php echo htmlspecialchars($payment['court_name']); ?>
                                <small style="color: #647189;" class="d-block"><?php echo formatDate($payment['booking_date']); ?></small>
                            </td>
                            <td class="fw-bold text-success">Rs. <?php echo number_format($payment['amount']); ?></td>
                            <td>
                                <?php if ($payment['method']): ?>
                                <span class="badge bg-info"><?php echo ucfirst($payment['method']); ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'unpaid' => 'danger',
                                    'paid' => 'success',
                                    'refunded' => 'secondary'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusClass[$payment['payment_status']]; ?>">
                                    <?php echo ucfirst($payment['payment_status']); ?>
                                </span>
                            </td>
                            <td style="color: #647189;">
                                <?php echo $payment['transaction_ref'] ?: '<span class="text-muted">-</span>'; ?>
                            </td>
                            <td style="color: #647189;">
                                <?php if ($payment['paid_at']): ?>
                                <?php echo date('M j, Y H:i', strtotime($payment['paid_at'])); ?>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($payment['payment_status'] == 'unpaid'): ?>
                                <button type="button" class="btn btn-sm btn-success" 
                                        onclick="markPaid(<?php echo $payment['payment_id']; ?>, <?php echo $payment['amount']; ?>)">
                                    <i class="fas fa-check me-1"></i>Mark Paid
                                </button>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Mark Paid Modal -->
    <div class="modal fade" id="markPaidModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <form method="POST">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-light">Confirm Payment</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="payment_id" id="paymentId">
                        
                        <div class="text-center mb-4">
                            <div class="amount-display">
                                Rs. <span id="paymentAmount">0</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="method" required>
                                <option value="cash">Cash</option>
                                <option value="esewa">eSewa</option>
                                <option value="khalti">Khalti</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Reference (Optional)</label>
                            <input type="text" class="form-control" name="transaction_ref" placeholder="e.g., TXN123456">
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="mark_paid" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/manage-payments.js"></script>
    
</body>
</html>
