<?php
/**
 * My Bookings Page
 * Futsal Booking System
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    redirect('../admin/dashboard.php');
}

$pageTitle = 'My Bookings';
$user_id = $_SESSION['user_id'];

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    // Verify booking belongs to user and can be cancelled
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ? AND booking_status IN ('pending', 'confirmed')");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        // Check if booking is at least 24 hours away
        $booking_datetime = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
        if ($booking_datetime > strtotime('+24 hours')) {
            $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = ?");
            $stmt->execute([$booking_id]);
            
            // Update payment status if exists
            $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ? AND payment_status = 'paid'");
            $stmt->execute([$booking_id]);
            
            $_SESSION['success'] = 'Booking cancelled successfully.';
        } else {
            $_SESSION['error'] = 'Cannot cancel bookings less than 24 hours before the scheduled time.';
        }
    } else {
        $_SESSION['error'] = 'Invalid booking or already cancelled.';
    }
    
    redirect('my-bookings.php');
}

// Filter options
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';

// Build query
$sql = "SELECT b.*, c.court_name, c.price_per_hour, c.surface_type, 
               f.futsal_name, f.address, f.city, f.contact_number,
               p.payment_id, p.payment_status, p.method, p.transaction_ref, p.paid_at
        FROM bookings b
        JOIN courts c ON b.court_id = c.court_id
        JOIN futsals f ON c.futsal_id = f.futsal_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.user_id = ?";

$params = [$user_id];

if ($status_filter) {
    $sql .= " AND b.booking_status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $sql .= " AND b.booking_date = ?";
    $params[] = $date_filter;
}

$sql .= " ORDER BY b.booking_date DESC, b.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>
<html>
<head>
    <link rel="stylesheet" href="../assets/css/my-bookings.css">
</head>
<body>
    <div class="container py-4">
        <?php echo displayAlert(); ?>
        
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title">My <span class="text-gradient">Bookings</span></h1>
                    <p class="text-secondary">View and manage all your court bookings</p>
                </div>
                <div class="col-auto">
                    <a href="book-court.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> New Booking
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="date" class="form-label">Date</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary">
                                <i class="fas fa-calendar-alt text-primary"></i>
                            </span>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="my-bookings.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Bookings List -->
        <?php if (empty($bookings)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-calendar-times fa-4x mb-3" style="color: #94a3b8;"></i>
                    <h4 class="text-white">No Bookings Found</h4>
                    <p class="text-secondary mb-4">You haven't made any bookings yet.</p>
                    <a href="book-court.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Book Your First Court
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($bookings as $booking): ?>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-<?php echo getStatusBadge($booking['booking_status']); ?> me-2">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                    <?php if ($booking['payment_status']): ?>
                                        <span class="badge bg-<?php echo getStatusBadge($booking['payment_status']); ?>">
                                            <?php echo ucfirst($booking['payment_status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($booking['method']): ?>
                                    <small class="text-secondary">
                                        <i class="fas fa-<?php echo $booking['method'] === 'cash' ? 'money-bill-wave' : ($booking['method'] === 'bank' ? 'university' : 'wallet'); ?> me-1"></i>
                                        <?php echo ucfirst($booking['method']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-futbol me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($booking['court_name']); ?>
                                </h5>
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-building text-secondary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Venue</small>
                                                <span style="color: #647189;"><?php echo htmlspecialchars($booking['futsal_name']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-map-marker-alt text-secondary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Location</small>
                                                <span style="color: #647189;"><?php echo htmlspecialchars($booking['city']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar text-secondary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Date</small>
                                                <span style="color: #647189;"><?php echo formatDate($booking['booking_date']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-clock text-secondary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Time</small>
                                                <span style="color: #647189;">
                                                    <?php echo formatTime($booking['start_time']); ?> - 
                                                    <?php echo formatTime($booking['end_time']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary">
                                    <div>
                                        <span style="font-size: 1.25rem; color: #647189;">Total Amount</span>
                                        <h5 class="text-primary mb-0"><?php echo formatCurrency($booking['total_price']); ?></h5>
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $booking['booking_id']; ?>">
                                            <i class="fas fa-eye me-1"></i> Details
                                        </button>
                                        <?php if (in_array($booking['booking_status'], ['pending', 'confirmed'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $booking['booking_id']; ?>">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Details Modal -->
                    <div class="modal fade" id="detailsModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content" style="background: #1E293B;">
                                <div class="modal-header" style="background: #1E293B; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <h5 class="modal-title" style="color: #f1f5f9;"><i class="fas fa-calendar-check me-2 text-primary"></i>Booking Details</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" style="background: #1E293B;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <h6 class="info-box-title">
                                                    <i class="fas fa-futbol me-2"></i>Court Information
                                                </h6>
                                                <div class="detail-row">
                                                    <span>Court</span>
                                                    <strong><?php echo htmlspecialchars($booking['court_name']); ?></strong>
                                                </div>
                                                <div class="detail-row">
                                                    <span>Surface</span>
                                                    <strong><?php echo htmlspecialchars($booking['surface_type']); ?></strong>
                                                </div>
                                                <div class="detail-row">
                                                    <span>Price/Hour</span>
                                                    <strong><?php echo formatCurrency($booking['price_per_hour']); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <h6 class="info-box-title">
                                                    <i class="fas fa-building me-2"></i>Venue Information
                                                </h6>
                                                <div class="detail-row">
                                                    <span>Venue</span>
                                                    <strong><?php echo htmlspecialchars($booking['futsal_name']); ?></strong>
                                                </div>
                                                <div class="detail-row">
                                                    <span>Address</span>
                                                    <strong><?php echo htmlspecialchars($booking['address']); ?>, <?php echo htmlspecialchars($booking['city']); ?></strong>
                                                </div>
                                                <div class="detail-row">
                                                    <span>Contact</span>
                                                    <strong><?php echo htmlspecialchars($booking['contact_number']); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <h6 class="info-box-title">
                                                    <i class="fas fa-clock me-2"></i>Booking Schedule
                                                </h6>
                                                <div class="detail-row">
                                                    <span>Date</span>
                                                    <strong><?php echo formatDate($booking['booking_date']); ?></strong>
                                                </div>
                                                <div class="detail-row">
                                                    <span>Time</span>
                                                    <strong><?php echo formatTime($booking['start_time']); ?> - <?php echo formatTime($booking['end_time']); ?></strong>
                                                </div>
                                                <div class="detail-row">
                                                    <span>Duration</span>
                                                    <strong><?php echo calculateHours($booking['start_time'], $booking['end_time']); ?> hour(s)</strong>
                                                </div>
                                                <div class="detail-row">
                                                    <span>Total</span>
                                                    <strong class="text-primary"><?php echo formatCurrency($booking['total_price']); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <h6 class="info-box-title">
                                                    <i class="fas fa-credit-card me-2"></i>Payment & Status
                                                </h6>
                                                <div class="detail-row">
                                                    <span>Booking Status</span>
                                                    <strong>
                                                        <span class="badge bg-<?php echo getStatusBadge($booking['booking_status']); ?>">
                                                            <?php echo ucfirst($booking['booking_status']); ?>
                                                        </span>
                                                    </strong>
                                                </div>
                                                <?php if ($booking['payment_status']): ?>
                                                    <div class="detail-row">
                                                        <span>Payment</span>
                                                        <strong>
                                                            <span class="badge bg-<?php echo getStatusBadge($booking['payment_status']); ?>">
                                                                <?php echo ucfirst($booking['payment_status']); ?>
                                                            </span>
                                                            <?php if ($booking['method']): ?>
                                                                <span class="text-secondary small">via <?php echo ucfirst($booking['method']); ?></span>
                                                            <?php endif; ?>
                                                        </strong>
                                                    </div>
                                                    <?php if ($booking['transaction_ref']): ?>
                                                        <div class="detail-row">
                                                            <span>Transaction</span>
                                                            <strong><code style="color: #10b981;"><?php echo htmlspecialchars($booking['transaction_ref']); ?></code></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($booking['paid_at']): ?>
                                                        <div class="detail-row">
                                                            <span>Paid At</span>
                                                            <strong><?php echo date('M d, Y h:i A', strtotime($booking['paid_at'])); ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer" style="background: #1E293B; border-top: 1px solid rgba(255,255,255,0.1);">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cancel Modal -->
                    <?php if (in_array($booking['booking_status'], ['pending', 'confirmed'])): ?>
                        <div class="modal fade" id="cancelModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content" style="background: #1E293B;">
                                    <div class="modal-header" style="background: #1E293B; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <h5 class="modal-title" style="color: #f1f5f9;"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Cancel Booking</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body" style="background: #1E293B;">
                                        <p class="text-light mb-3">Are you sure you want to cancel this booking?</p>
                                        
                                        <div class="info-box mb-3" style="border-color: rgba(245, 158, 11, 0.3);">
                                            <h6 class="info-box-title" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.3);">
                                                <i class="fas fa-info-circle me-2"></i>Cancellation Policy
                                            </h6>
                                            <p class="text-secondary small mb-0">Bookings can only be cancelled at least 24 hours before the scheduled time. Payments will be refunded if applicable.</p>
                                        </div>
                                        
                                        <div class="info-box">
                                            <h6 class="info-box-title">
                                                <i class="fas fa-calendar-times me-2"></i>Booking to Cancel
                                            </h6>
                                            <div class="detail-row">
                                                <span>Court</span>
                                                <strong><?php echo htmlspecialchars($booking['court_name']); ?></strong>
                                            </div>
                                            <div class="detail-row">
                                                <span>Date</span>
                                                <strong><?php echo formatDate($booking['booking_date']); ?></strong>
                                            </div>
                                            <div class="detail-row">
                                                <span>Time</span>
                                                <strong><?php echo formatTime($booking['start_time']); ?> - <?php echo formatTime($booking['end_time']); ?></strong>
                                            </div>
                                            <div class="detail-row">
                                                <span>Amount</span>
                                                <strong class="text-primary"><?php echo formatCurrency($booking['total_price']); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer" style="background: #1E293B; border-top: 1px solid rgba(255,255,255,0.1);">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <button type="submit" name="cancel_booking" class="btn btn-danger">
                                                <i class="fas fa-times me-1"></i> Cancel Booking
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>


<?php include '../includes/footer.php'; ?>
