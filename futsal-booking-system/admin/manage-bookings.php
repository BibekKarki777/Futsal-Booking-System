<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$messageType = '';

// Handle status update
if (isset($_POST['update_status'])) {
    $bookingId = (int)$_POST['booking_id'];
    $newStatus = $_POST['new_status'];
    
    $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (in_array($newStatus, $validStatuses)) {
        // Get current booking and payment details
        $stmt = $pdo->prepare("
            SELECT b.booking_status, b.booking_date, b.end_time, p.payment_status 
            FROM bookings b 
            LEFT JOIN payments p ON b.booking_id = p.booking_id 
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            $currentStatus = $booking['booking_status'];
            $paymentStatus = $booking['payment_status'] ?? 'unpaid';
            $canUpdate = true;
            
            // Rule: If booking status is "completed" - cannot change to anything
            if ($currentStatus === 'completed') {
                $message = "Cannot modify a completed booking. The status is final.";
                $messageType = "error";
                $canUpdate = false;
            }
            // Rule: If booking status is "cancelled" - cannot change to anything
            elseif ($currentStatus === 'cancelled') {
                $message = "Cannot modify a cancelled booking. The booking has already been cancelled.";
                $messageType = "error";
                $canUpdate = false;
            }
            // Rule: If payment status is "refunded" - cannot change to "pending" or "completed"
            elseif ($paymentStatus === 'refunded' && in_array($newStatus, ['pending', 'completed'])) {
                $message = "Cannot change booking to " . $newStatus . ". Payment has been refunded.";
                $messageType = "error";
                $canUpdate = false;
            }
            // Rule: If booking status is "confirmed" - cannot change to "pending"
            elseif ($currentStatus === 'confirmed' && $newStatus === 'pending') {
                $message = "Cannot change confirmed booking to pending.";
                $messageType = "error";
                $canUpdate = false;
            }
            // Rule: Cannot mark as "completed" before end time has passed
            elseif ($newStatus === 'completed') {
                $bookingEndDateTime = strtotime($booking['booking_date'] . ' ' . $booking['end_time']);
                $currentDateTime = time();
                
                if ($currentDateTime < $bookingEndDateTime) {
                    $message = "Cannot mark as completed. The reservation end time has not passed yet.";
                    $messageType = "error";
                    $canUpdate = false;
                }
            }
            
            // If validation passed, update the status
            if ($canUpdate) {
                $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
                if ($stmt->execute([$newStatus, $bookingId])) {
                    // Update payment status if cancelled
                    if ($newStatus == 'cancelled') {
                        $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?");
                        $stmt->execute([$bookingId]);
                    }
                    $message = "Booking status updated to " . ucfirst($newStatus) . " successfully!";
                    $messageType = "success";
                }
            }
        }
    }
}

// Handle payment status update
if (isset($_POST['update_payment'])) {
    $bookingId = (int)$_POST['booking_id'];
    $newPaymentStatus = $_POST['payment_status'];
    $method = $_POST['payment_method'];
    
    // Get current booking and payment details
    $stmt = $pdo->prepare("
        SELECT b.booking_status, p.payment_status 
        FROM bookings b 
        LEFT JOIN payments p ON b.booking_id = p.booking_id 
        WHERE b.booking_id = ?
    ");
    $stmt->execute([$bookingId]);
    $data = $stmt->fetch();
    
    if ($data) {
        $bookingStatus = $data['booking_status'];
        $currentPaymentStatus = $data['payment_status'] ?? 'unpaid';
        $canUpdate = true;
        
        // Rule: If booking status is "pending" - cannot set payment to "refunded"
        if ($bookingStatus === 'pending' && $newPaymentStatus === 'refunded') {
            $message = "Cannot refund a pending booking. The booking must be cancelled first.";
            $messageType = "error";
            $canUpdate = false;
        }
        // Rule: If payment status is "paid" - cannot change to "unpaid"
        elseif ($currentPaymentStatus === 'paid' && $newPaymentStatus === 'unpaid') {
            $message = "Cannot change payment from Paid to Unpaid.";
            $messageType = "error";
            $canUpdate = false;
        }
        // Rule: If payment status is "unpaid" - cannot change to "refunded"
        elseif ($currentPaymentStatus === 'unpaid' && $newPaymentStatus === 'refunded') {
            $message = "Cannot refund an unpaid booking. Payment must be received first.";
            $messageType = "error";
            $canUpdate = false;
        }
        // Rule: If payment status is "refunded" - cannot change to anything
        elseif ($currentPaymentStatus === 'refunded') {
            $message = "Cannot modify a refunded payment. The payment has already been refunded.";
            $messageType = "error";
            $canUpdate = false;
        }
        
        // If validation passed, update the payment
        if ($canUpdate) {
            $stmt = $pdo->prepare("
                UPDATE payments SET 
                    payment_status = ?, 
                    method = ?,
                    paid_at = IF(? = 'paid', NOW(), paid_at),
                    transaction_ref = ?
                WHERE booking_id = ?
            ");
            if ($stmt->execute([$newPaymentStatus, $method, $newPaymentStatus, $_POST['transaction_ref'] ?? '', $bookingId])) {
                // If paid, confirm booking (only if pending)
                if ($newPaymentStatus == 'paid') {
                    $pdo->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE booking_id = ? AND booking_status = 'pending'")->execute([$bookingId]);
                }
                $message = "Payment updated successfully!";
                $messageType = "success";
            }
        }
    }
}


// Filters
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Build query
$sql = "
    SELECT b.*, u.first_name, u.last_name, u.email, u.contact_number as user_phone,
           c.court_name, c.price_per_hour, f.futsal_name, f.city,
           p.payment_id, p.amount, p.method, p.payment_status, p.transaction_ref, p.paid_at
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN courts c ON b.court_id = c.court_id
    JOIN futsals f ON c.futsal_id = f.futsal_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    WHERE 1=1
";

$params = [];
if ($statusFilter) {
    $sql .= " AND b.booking_status = ?";
    $params[] = $statusFilter;
}
if ($dateFilter) {
    $sql .= " AND b.booking_date = ?";
    $params[] = $dateFilter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = "Manage Bookings";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title><?php echo $pageTitle; ?> - FutsalBook Admin</title>
    <link rel="stylesheet" href="../assets/css/manage-bookings.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-calendar-check me-2"></i>Manage Bookings</h1>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="admin-card mb-4">
            <div class="card-body py-3">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $statusFilter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary">
                                <i class="fas fa-calendar-alt text-primary"></i>
                            </span>
                            <input type="date" class="form-control" name="date" value="<?php echo $dateFilter; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-2">
                        <a href="manage-bookings.php" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>All Bookings (<?php echo count($bookings); ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Court</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Booking Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No bookings found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>
                                <strong style="color: #647189;"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong>
                                <small style="color: #647189;" class="d-block"><?php echo htmlspecialchars($booking['email']); ?></small>
                            </td>
                            <td style="color: #647189;">
                                <?php echo htmlspecialchars($booking['court_name']); ?>
                                <small style="color: #647189;" class="d-block"><?php echo htmlspecialchars($booking['futsal_name']); ?></small>
                            </td>
                            <td style="color: #647189;">
                                <?php echo formatDate($booking['booking_date']); ?>
                                <small style="color: #647189;" class="d-block">
                                    <?php echo formatTime($booking['start_time']) . ' - ' . formatTime($booking['end_time']); ?>
                                </small>
                            </td>
                            <td style="color: #647189;" class="fw-semibold">Rs. <?php echo number_format($booking['total_price']); ?></td>
                            <td>
                                <?php
                                $statusClass = [
                                    'pending' => 'warning',
                                    'confirmed' => 'success',
                                    'cancelled' => 'danger',
                                    'completed' => 'info'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusClass[$booking['booking_status']]; ?>">
                                    <?php echo ucfirst($booking['booking_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $paymentClass = [
                                    'unpaid' => 'danger',
                                    'paid' => 'success',
                                    'refunded' => 'secondary'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $paymentClass[$booking['payment_status'] ?? 'unpaid']; ?>">
                                    <?php echo ucfirst($booking['payment_status'] ?? 'unpaid'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="viewBooking(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                            onclick="updateStatus(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['booking_status']; ?>', '<?php echo $booking['booking_date']; ?>', '<?php echo $booking['end_time']; ?>', '<?php echo $booking['payment_status'] ?? 'unpaid'; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                            onclick="updatePayment(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['payment_status'] ?? 'unpaid'; ?>', '<?php echo $booking['method'] ?? ''; ?>', '<?php echo $booking['booking_status']; ?>')">
                                        <i class="fas fa-credit-card"></i>
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
    
    <!-- View Booking Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-light">Booking Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingDetails">
                    <!-- Filled by JS -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <form method="POST" id="statusForm" onsubmit="return validateStatusUpdate()">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-light">Update Booking Status</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="statusBookingId">
                        <input type="hidden" id="bookingDate">
                        <input type="hidden" id="bookingEndTime">
                        <input type="hidden" id="currentStatus">
                        <input type="hidden" id="statusPaymentStatus">
                        
                        <!-- Alert for status restrictions -->
                        <div id="statusAlert" class="alert d-none mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="statusAlertMessage"></span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Status: <span id="displayCurrentStatus" class="badge bg-secondary"></span></label>
                            <select class="form-select" name="new_status" id="newStatus">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" id="updateStatusBtn" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Custom Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-light" id="alertModalTitle">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        <span>Notice</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="alertModalMessage" class="text-light mb-0"></p>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark">
                <form method="POST" id="paymentForm" onsubmit="return validatePaymentUpdate()">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-light">Update Payment</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="paymentBookingId">
                        <input type="hidden" id="currentPaymentStatus">
                        <input type="hidden" id="paymentBookingStatus">
                        
                        <!-- Alert for payment restrictions -->
                        <div id="paymentAlert" class="alert d-none mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="paymentAlertMessage"></span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Payment Status: <span id="displayCurrentPayment" class="badge bg-secondary"></span></label>
                            <select class="form-select" name="payment_status" id="paymentStatus">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" id="paymentMethod">
                                <option value="cash">Cash</option>
                                <option value="esewa">eSewa</option>
                                <option value="khalti">Khalti</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text" class="form-control" name="transaction_ref" id="transactionRef" placeholder="Optional">
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_payment" id="updatePaymentBtn" class="btn btn-success">Update Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
        
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/manage-bookings.js"></script>
        
</body>
</html>
