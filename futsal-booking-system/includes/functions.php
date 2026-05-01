<?php
/**
 * Helper Functions
 * Futsal Booking System
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to continue.";
        redirect('/futsal-booking-system/auth/login.php');
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = "Access denied. Admin privileges required.";
        redirect('/futsal-booking-system/index.php');
    }
}

/**
 * Sanitize input
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Display alert message
 */
function displayAlert() {
    $html = '';
    if (isset($_SESSION['success'])) {
        $html = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>' . $_SESSION['success'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                 </div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        $html = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['error'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                 </div>';
        unset($_SESSION['error']);
    }
    return $html;
}

/**
 * Format currency (Nepali Rupees)
 */
function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format time
 */
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

/**
 * Get status badge class
 */
function getStatusBadge($status) {
    $badges = [
        'active' => 'success',
        'maintenance' => 'warning',
        'pending' => 'warning',
        'confirmed' => 'success',
        'cancelled' => 'danger',
        'completed' => 'info',
        'unpaid' => 'danger',
        'paid' => 'success',
        'refunded' => 'secondary'
    ];
    return $badges[$status] ?? 'secondary';
}

/**
 * Calculate total hours between times
 */
function calculateHours($start, $end) {
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    $diff = $end_time - $start_time;
    return $diff / 3600; // Convert seconds to hours
}

/**
 * Check if time slot is available
 */
function isTimeSlotAvailable($pdo, $court_id, $date, $start_time, $end_time, $exclude_booking_id = null) {
    $sql = "SELECT COUNT(*) FROM bookings 
            WHERE court_id = ? 
            AND booking_date = ? 
            AND booking_status NOT IN ('cancelled')
            AND (
                (start_time < ? AND end_time > ?) OR
                (start_time < ? AND end_time > ?) OR
                (start_time >= ? AND end_time <= ?)
            )";
    
    if ($exclude_booking_id) {
        $sql .= " AND booking_id != ?";
    }
    
    $stmt = $pdo->prepare($sql);
    
    if ($exclude_booking_id) {
        $stmt->execute([$court_id, $date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time, $exclude_booking_id]);
    } else {
        $stmt->execute([$court_id, $date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);
    }
    
    return $stmt->fetchColumn() == 0;
}

/**
 * Check if user has conflicting booking at the same date/time (across all venues)
 */
function userHasConflictingBooking($pdo, $user_id, $date, $start_time, $end_time, $exclude_booking_id = null) {
    $sql = "SELECT b.booking_id, c.court_name, f.futsal_name 
            FROM bookings b
            JOIN courts c ON b.court_id = c.court_id
            JOIN futsals f ON c.futsal_id = f.futsal_id
            WHERE b.user_id = ? 
            AND b.booking_date = ? 
            AND b.booking_status NOT IN ('cancelled')
            AND (
                (b.start_time < ? AND b.end_time > ?) OR
                (b.start_time < ? AND b.end_time > ?) OR
                (b.start_time >= ? AND b.end_time <= ?)
            )";
    
    if ($exclude_booking_id) {
        $sql .= " AND b.booking_id != ?";
    }
    
    $stmt = $pdo->prepare($sql);
    
    if ($exclude_booking_id) {
        $stmt->execute([$user_id, $date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time, $exclude_booking_id]);
    } else {
        $stmt->execute([$user_id, $date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);
    }
    
    return $stmt->fetch(); // Returns the conflicting booking details or false
}

/**
 * Get all futsals
 */
function getAllFutsals($pdo) {
    $stmt = $pdo->query("SELECT * FROM futsals ORDER BY futsal_name");
    return $stmt->fetchAll();
}

/**
 * Get all courts
 */
function getAllCourts($pdo, $futsal_id = null) {
    if ($futsal_id) {
        $stmt = $pdo->prepare("SELECT c.*, f.futsal_name FROM courts c 
                               JOIN futsals f ON c.futsal_id = f.futsal_id 
                               WHERE c.futsal_id = ? ORDER BY c.court_name");
        $stmt->execute([$futsal_id]);
    } else {
        $stmt = $pdo->query("SELECT c.*, f.futsal_name FROM courts c 
                            JOIN futsals f ON c.futsal_id = f.futsal_id 
                            ORDER BY f.futsal_name, c.court_name");
    }
    return $stmt->fetchAll();
}

/**
 * Get user bookings
 */
function getUserBookings($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT b.*, c.court_name, c.price_per_hour, f.futsal_name, f.address,
                           p.payment_id, p.payment_status, p.method
                           FROM bookings b
                           JOIN courts c ON b.court_id = c.court_id
                           JOIN futsals f ON c.futsal_id = f.futsal_id
                           LEFT JOIN payments p ON b.booking_id = p.booking_id
                           WHERE b.user_id = ?
                           ORDER BY b.booking_date DESC, b.start_time DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get dashboard statistics
 */
function getDashboardStats($pdo) {
    $stats = [];
    
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'player'");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Total Bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $stats['total_bookings'] = $stmt->fetchColumn();
    
    // Today's Bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()");
    $stmt->execute();
    $stats['today_bookings'] = $stmt->fetchColumn();
    
    // Total Revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'paid'");
    $stats['total_revenue'] = $stmt->fetchColumn();
    
    // Pending Bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending'");
    $stats['pending_bookings'] = $stmt->fetchColumn();
    
    // Active Courts
    $stmt = $pdo->query("SELECT COUNT(*) FROM courts WHERE status = 'active'");
    $stats['active_courts'] = $stmt->fetchColumn();
    
    // Total Courts
    $stmt = $pdo->query("SELECT COUNT(*) FROM courts");
    $stats['total_courts'] = $stmt->fetchColumn();
    
    // Total Futsals/Venues
    $stmt = $pdo->query("SELECT COUNT(*) FROM futsals");
    $stats['total_futsals'] = $stmt->fetchColumn();
    
    // Confirmed Bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed'");
    $stats['confirmed_bookings'] = $stmt->fetchColumn();
    
    // Completed Bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'completed'");
    $stats['completed_bookings'] = $stmt->fetchColumn();
    
    return $stats;
}
?>
