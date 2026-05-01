<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Get dashboard statistics
$stats = getDashboardStats($pdo);

// Get recent bookings
$stmt = $pdo->query("
    SELECT b.*, u.first_name, u.last_name, u.email, c.court_name, f.futsal_name,
           p.payment_status, p.amount
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN courts c ON b.court_id = c.court_id
    JOIN futsals f ON c.futsal_id = f.futsal_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    ORDER BY b.created_at DESC
    LIMIT 10
");
$recentBookings = $stmt->fetchAll();

// Get revenue stats
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN payment_status = 'paid' AND DATE(paid_at) = CURDATE() THEN amount ELSE 0 END) as today_revenue,
        SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END) as pending_revenue
    FROM payments
");
$revenue = $stmt->fetch();

// Get booking trends (last 7 days)
$stmt = $pdo->query("
    SELECT DATE(booking_date) as date, COUNT(*) as count
    FROM bookings
    WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(booking_date)
    ORDER BY date
");
$bookingTrends = $stmt->fetchAll();

$pageTitle = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header.php'; ?>
    <title><?php echo $pageTitle; ?> - FutsalBook</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
            <div class="header-actions">
                <span style="color: #ffffff;" class="me-3">
                    <i class="fas fa-calendar me-1"></i>
                    <?php echo date('l, F j, Y'); ?>
                </span>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="admin-stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_bookings']; ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="admin-stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Rs. <?php echo number_format($revenue['total_revenue'] ?? 0); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="admin-stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending_bookings']; ?></h3>
                        <p>Pending Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="admin-stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Registered Users</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="quick-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p style="color: #ffffff;" class="mb-1">Today's Revenue</p>
                            <h4 class="text-success mb-0">Rs. <?php echo number_format($revenue['today_revenue'] ?? 0); ?></h4>
                        </div>
                        <div class="quick-stat-icon text-success">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="quick-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p style="color: #ffffff;" class="mb-1">Pending Payments</p>
                            <h4 class="text-warning mb-0">Rs. <?php echo number_format($revenue['pending_revenue'] ?? 0); ?></h4>
                        </div>
                        <div class="quick-stat-icon text-warning">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="quick-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p style="color: #ffffff;" class="mb-1">Active Courts</p>
                            <h4 class="text-primary mb-0"><?php echo $stats['active_courts']; ?></h4>
                        </div>
                        <div class="quick-stat-icon text-primary">
                            <i class="fas fa-futbol"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Recent Bookings -->
            <div class="col-lg-8">
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock me-2"></i>Recent Bookings</h5>
                        <a href="manage-bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Court</th>
                                    <th>Date & Time</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No bookings yet</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td>
                                        <div class="customer-info">
                                            <strong style="color: #647189;"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong>
                                            <small style="color: #647189;" class="d-block"><?php echo htmlspecialchars($booking['email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="color: #647189;" class="fw-semibold"><?php echo htmlspecialchars($booking['court_name']); ?></span>
                                        <small style="color: #647189;" class="d-block"><?php echo htmlspecialchars($booking['futsal_name']); ?></small>
                                    </td>
                                    <td style="color: #647189;">
                                        <?php echo formatDate($booking['booking_date']); ?>
                                        <small style="color: #647189;" class="d-block">
                                            <?php echo formatTime($booking['start_time']) . ' - ' . formatTime($booking['end_time']); ?>
                                        </small>
                                    </td>
                                    <td style="color: #647189;">Rs. <?php echo number_format($booking['total_price']); ?></td>
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
                                        <a href="manage-bookings.php?view=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions & Info -->
            <div class="col-lg-4">
                <div class="admin-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="add-futsal.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-2"></i>Add New Venue
                            </a>
                            <a href="add-court.php" class="btn btn-outline-success">
                                <i class="fas fa-plus me-2"></i>Add New Court
                            </a>
                            <a href="manage-bookings.php?status=pending" class="btn btn-outline-warning">
                                <i class="fas fa-clock me-2"></i>View Pending Bookings
                            </a>
                            <a href="manage-payments.php?status=unpaid" class="btn btn-outline-danger">
                                <i class="fas fa-exclamation me-2"></i>Unpaid Payments
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>System Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="overview-item">
                            <span>Total Venues</span>
                            <strong><?php echo $stats['total_futsals']; ?></strong>
                        </div>
                        <div class="overview-item">
                            <span>Total Courts</span>
                            <strong><?php echo $stats['total_courts']; ?></strong>
                        </div>
                        <div class="overview-item">
                            <span>Confirmed Bookings</span>
                            <strong><?php echo $stats['confirmed_bookings']; ?></strong>
                        </div>
                        <div class="overview-item">
                            <span>Completed Bookings</span>
                            <strong><?php echo $stats['completed_bookings']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
