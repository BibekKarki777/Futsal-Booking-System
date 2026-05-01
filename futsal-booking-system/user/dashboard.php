<?php
/**
 * User Dashboard
 * Futsal Booking System
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

// Redirect admin to admin dashboard
if (isAdmin()) {
    redirect('../admin/dashboard.php');
}

$pageTitle = 'Dashboard';
$user_id = $_SESSION['user_id'];

// Get user's booking statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get upcoming bookings
$stmt = $pdo->prepare("SELECT b.*, c.court_name, c.price_per_hour, f.futsal_name, f.address
                       FROM bookings b
                       JOIN courts c ON b.court_id = c.court_id
                       JOIN futsals f ON c.futsal_id = f.futsal_id
                       WHERE b.user_id = ? AND b.booking_date >= CURDATE() AND b.booking_status IN ('pending', 'confirmed')
                       ORDER BY b.booking_date ASC, b.start_time ASC
                       LIMIT 5");
$stmt->execute([$user_id]);
$upcomingBookings = $stmt->fetchAll();

// Get recent bookings
$stmt = $pdo->prepare("SELECT b.*, c.court_name, f.futsal_name, p.payment_status
                       FROM bookings b
                       JOIN courts c ON b.court_id = c.court_id
                       JOIN futsals f ON c.futsal_id = f.futsal_id
                       LEFT JOIN payments p ON b.booking_id = p.booking_id
                       WHERE b.user_id = ?
                       ORDER BY b.created_at DESC
                       LIMIT 5");
$stmt->execute([$user_id]);
$recentBookings = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container py-4">
    <?php echo displayAlert(); ?>
    
    <!-- Welcome Section -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h1>
                <p class="text-secondary mb-0">Here's your booking summary</p>
            </div>
            <div class="col-auto">
                <a href="book-court.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> New Booking
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['confirmed']; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Upcoming Bookings -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        Upcoming Bookings
                    </h5>
                    <a href="my-bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingBookings)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x mb-3" style="color: #94a3b8;"></i>
                            <p class="text-secondary mb-3">No upcoming bookings</p>
                            <a href="book-court.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> Book a Court
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcomingBookings as $booking): ?>
                            <div class="d-flex align-items-center mb-3 p-3 rounded" style="background: var(--bg-card-hover);">
                                <div class="stat-icon green me-3" style="min-width: 50px; height: 50px;">
                                    <i class="fas fa-futbol"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($booking['court_name']); ?></h6>
                                    <p class="text-secondary small mb-0">
                                        <?php echo htmlspecialchars($booking['futsal_name']); ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <p class="mb-1" style="color: #647189;">
                                        <i class="fas fa-calendar me-1" style="color: #647189;"></i>
                                        <?php echo formatDate($booking['booking_date']); ?>
                                    </p>
                                    <p class="small mb-0" style="color: #647189;">
                                        <?php echo formatTime($booking['start_time']); ?> - 
                                        <?php echo formatTime($booking['end_time']); ?>
                                    </p>
                                </div>
                                <span class="badge bg-<?php echo getStatusBadge($booking['booking_status']); ?> ms-3">
                                    <?php echo ucfirst($booking['booking_status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <a href="book-court.php" class="btn btn-outline-primary w-100 mb-3 text-start">
                        <i class="fas fa-calendar-plus me-2"></i> Book a Court
                    </a>
                    <a href="my-bookings.php" class="btn btn-outline-primary w-100 mb-3 text-start">
                        <i class="fas fa-list me-2"></i> View All Bookings
                    </a>
                    <a href="profile.php" class="btn btn-outline-primary w-100 text-start">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-history me-2 text-info"></i>
                Recent Activity
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentBookings)): ?>
                <div class="text-center py-4">
                    <p class="text-secondary mb-0">No recent activity</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0" style="background: #222D3F;">
                        <thead>
                            <tr style="background: #222D3F;">
                                <th style="background: #222D3F; color: #94a3b8;">Court</th>
                                <th style="background: #222D3F; color: #94a3b8;">Date</th>
                                <th style="background: #222D3F; color: #94a3b8;">Time</th>
                                <th style="background: #222D3F; color: #94a3b8;">Amount</th>
                                <th style="background: #222D3F; color: #94a3b8;">Status</th>
                                <th style="background: #222D3F; color: #94a3b8;">Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $booking): ?>
                                <tr style="background: #222D3F;">
                                    <td style="background: #222D3F;">
                                        <strong style="color: #647189;"><?php echo htmlspecialchars($booking['court_name']); ?></strong>
                                        <br>
                                        <small style="color: #647189;"><?php echo htmlspecialchars($booking['futsal_name']); ?></small>
                                    </td>
                                    <td style="background: #222D3F; color: #647189;"><?php echo formatDate($booking['booking_date']); ?></td>
                                    <td style="background: #222D3F; color: #647189;">
                                        <?php echo formatTime($booking['start_time']); ?> - 
                                        <?php echo formatTime($booking['end_time']); ?>
                                    </td>
                                    <td style="background: #222D3F; color: #647189;"><?php echo formatCurrency($booking['total_price']); ?></td>
                                    <td style="background: #222D3F;">
                                        <span class="badge bg-<?php echo getStatusBadge($booking['booking_status']); ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td style="background: #222D3F;">
                                        <?php if ($booking['payment_status']): ?>
                                            <span class="badge bg-<?php echo getStatusBadge($booking['payment_status']); ?>">
                                                <?php echo ucfirst($booking['payment_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
