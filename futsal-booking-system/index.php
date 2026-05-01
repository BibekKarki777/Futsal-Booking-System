<?php
/**
 * Homepage / Landing Page
 * Futsal Booking System
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'Home';

// Get active futsals and courts for display
$futsals = getAllFutsals($pdo);

// Get featured courts - show all if requested
$showAllCourts = isset($_GET['view']) && $_GET['view'] === 'all';
$courtsQuery = "SELECT c.*, f.futsal_name, f.city 
                FROM courts c 
                JOIN futsals f ON c.futsal_id = f.futsal_id 
                WHERE c.status = 'active' 
                ORDER BY c.price_per_hour DESC";
if (!$showAllCourts) {
    $courtsQuery .= " LIMIT 6";
}
$stmt = $pdo->query($courtsQuery);
$featuredCourts = $stmt->fetchAll();

// Get total courts count
$totalCourtsStmt = $pdo->query("SELECT COUNT(*) as total FROM courts WHERE status = 'active'");
$totalCourts = $totalCourtsStmt->fetch()['total'];

include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title animate-fade-in">
                    Book Your Perfect <span class="highlight">Futsal Court</span> Today
                </h1>
                <p class="hero-subtitle animate-fade-in stagger-1">
                    Find and book the best futsal courts in your city. Easy online booking, 
                    instant confirmation, and hassle-free experience.
                </p>
                <div class="d-flex gap-3 animate-fade-in stagger-2">
                    <?php if (isLoggedIn()): ?>
                        <a href="user/book-court.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-calendar-plus me-2"></i> Book Now
                        </a>
                        <a href="user/my-bookings.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-list me-2"></i> My Bookings
                        </a>
                    <?php else: ?>
                        <a href="auth/register.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i> Get Started
                        </a>
                        <a href="auth/login.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Stats -->
                <div class="row mt-5 animate-fade-in stagger-3">
                    <div class="col-4">
                        <h3 class="text-gradient mb-0"><?php echo count($futsals); ?>+</h3>
                        <p class="text-secondary mb-0">Futsal Venues</p>
                    </div>
                    <div class="col-4">
                        <h3 class="text-gradient mb-0"><?php echo $totalCourts; ?>+</h3>
                        <p class="text-secondary mb-0">Active Courts</p>
                    </div>
                    <div class="col-4">
                        <h3 class="text-gradient mb-0">24/7</h3>
                        <p class="text-secondary mb-0">Online Booking</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-image text-center animate-fade-in">
                    <svg viewBox="0 0 400 400" width="400" height="400">
                        <!-- Soccer Field Background -->
                        <rect x="50" y="80" width="300" height="240" rx="10" fill="#1e293b" stroke="#10b981" stroke-width="2"/>
                        <!-- Center Circle -->
                        <circle cx="200" cy="200" r="40" fill="none" stroke="#10b981" stroke-width="2"/>
                        <circle cx="200" cy="200" r="4" fill="#10b981"/>
                        <!-- Center Line -->
                        <line x1="200" y1="80" x2="200" y2="320" stroke="#10b981" stroke-width="2"/>
                        <!-- Goal Areas -->
                        <rect x="50" y="150" width="40" height="100" fill="none" stroke="#10b981" stroke-width="2"/>
                        <rect x="310" y="150" width="40" height="100" fill="none" stroke="#10b981" stroke-width="2"/>
                        <!-- Penalty Areas -->
                        <rect x="50" y="120" width="70" height="160" fill="none" stroke="#10b981" stroke-width="2"/>
                        <rect x="280" y="120" width="70" height="160" fill="none" stroke="#10b981" stroke-width="2"/>
                        <!-- Corner Arcs -->
                        <path d="M 50 90 Q 60 80 70 80" fill="none" stroke="#10b981" stroke-width="2"/>
                        <path d="M 330 80 Q 340 80 350 90" fill="none" stroke="#10b981" stroke-width="2"/>
                        <path d="M 50 310 Q 60 320 70 320" fill="none" stroke="#10b981" stroke-width="2"/>
                        <path d="M 330 320 Q 340 320 350 310" fill="none" stroke="#10b981" stroke-width="2"/>
                        <!-- Soccer Ball -->
                        <circle cx="200" cy="200" r="20" fill="#f59e0b" class="animate-pulse"/>
                        <polygon points="200,185 212,193 208,207 192,207 188,193" fill="#0f172a"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="mb-3">Why Choose <span class="text-gradient">FutsalBook</span>?</h2>
            <p class="text-secondary">Experience the easiest way to book futsal courts</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon-box mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);">
                            <i class="fas fa-bolt" style="font-size: 2rem; color: white;"></i>
                        </div>
                        <h5>Instant Booking</h5>
                        <p class="text-secondary mb-0">
                            Book your preferred court in seconds with our easy-to-use platform
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon-box mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);">
                            <i class="fas fa-clock" style="font-size: 2rem; color: white;"></i>
                        </div>
                        <h5>24/7 Availability</h5>
                        <p class="text-secondary mb-0">
                            Book anytime, anywhere. Our system is available round the clock
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon-box mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);">
                            <i class="fas fa-shield-alt" style="font-size: 2rem; color: white;"></i>
                        </div>
                        <h5>Secure Payments</h5>
                        <p class="text-secondary mb-0">
                            Multiple payment options with secure transaction processing
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Courts Section -->
<section class="py-5" id="courts">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><?php echo $showAllCourts ? 'All' : 'Featured'; ?> <span class="text-gradient">Courts</span></h2>
                <p class="text-secondary mb-0">
                    <?php if ($showAllCourts): ?>
                        Showing all <?php echo $totalCourts; ?> available courts
                    <?php else: ?>
                        Discover our top-rated futsal courts
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($showAllCourts): ?>
                <a href="index.php#courts" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i> Show Less
                </a>
            <?php elseif ($totalCourts > 6): ?>
                <a href="index.php?view=all#courts" class="btn btn-outline-primary">
                    View All (<?php echo $totalCourts; ?>) <i class="fas fa-arrow-right ms-2"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featuredCourts as $court): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card court-card h-100">
                        <div class="court-image">
                            <i class="fas fa-futbol"></i>
                            <span class="price-tag"><?php echo formatCurrency($court['price_per_hour']); ?>/hr</span>
                            <span class="status-badge" style="position: absolute; top: 1rem; left: 1rem; background: <?php echo $court['status'] == 'active' ? 'rgba(16, 185, 129, 0.9)' : 'rgba(245, 158, 11, 0.9)'; ?>; color: white; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                <?php echo ucfirst($court['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($court['court_name']); ?></h5>
                            <p class="text-secondary mb-2">
                                <i class="fas fa-building me-2"></i>
                                <?php echo htmlspecialchars($court['futsal_name']); ?>
                            </p>
                            <p class="text-secondary mb-3">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($court['city']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-info">
                                    <i class="fas fa-layer-group me-1"></i>
                                    <?php echo htmlspecialchars($court['surface_type']); ?>
                                </span>
                                <?php if (isLoggedIn()): ?>
                                    <a href="user/book-court.php?court=<?php echo $court['court_id']; ?>" class="btn btn-sm btn-primary">
                                        Book Now
                                    </a>
                                <?php else: ?>
                                    <a href="auth/login.php" class="btn btn-sm btn-primary">
                                        Login to Book
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!$showAllCourts && $totalCourts > 6): ?>
            <div class="text-center mt-4">
                <a href="index.php?view=all#courts" class="btn btn-primary btn-lg">
                    <i class="fas fa-th-large me-2"></i> View All <?php echo $totalCourts; ?> Courts
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (empty($featuredCourts)): ?>
            <div class="text-center py-5">
                <i class="fas fa-futbol fa-4x text-secondary mb-3"></i>
                <h4 class="text-light">No Courts Available</h4>
                <p class="text-secondary">Please check back later for available courts.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Venues Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="mb-3">Our <span class="text-gradient">Partner Venues</span></h2>
            <p class="text-secondary">Top futsal venues across Nepal</p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($futsals as $futsal): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="me-3" style="min-width: 60px; width: 60px; height: 60px; background: rgba(148, 163, 184, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(148, 163, 184, 0.3);">
                                    <i class="fas fa-building" style="font-size: 1.5rem; color: #94a3b8;"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($futsal['futsal_name']); ?></h5>
                                    <p class="text-secondary mb-0">
                                        <i class="fas fa-map-marker-alt me-1" style="color: #94a3b8;"></i>
                                        <?php echo htmlspecialchars($futsal['city']); ?>
                                    </p>
                                </div>
                            </div>
                            <p class="text-secondary small mb-2">
                                <i class="fas fa-location-dot me-2" style="color: #94a3b8;"></i>
                                <?php echo htmlspecialchars($futsal['address']); ?>
                            </p>
                            <p class="text-secondary small mb-2">
                                <i class="fas fa-phone me-2" style="color: #94a3b8;"></i>
                                <?php echo htmlspecialchars($futsal['contact_number']); ?>
                            </p>
                            <p class="text-secondary small mb-0">
                                <i class="fas fa-clock me-2" style="color: #94a3b8;"></i>
                                <?php echo formatTime($futsal['open_time']); ?> - <?php echo formatTime($futsal['close_time']); ?>
                            </p>
                            <span class="badge bg-<?php echo getStatusBadge($futsal['status']); ?> mt-3">
                                <?php echo ucfirst($futsal['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5">
    <div class="container">
        <div class="card bg-gradient-primary text-white text-center p-5">
            <div class="card-body">
                <h2 class="mb-3">Ready to Play?</h2>
                <p class="mb-4 opacity-75">
                    Join thousands of players who book their futsal courts with us
                </p>
                <?php if (!isLoggedIn()): ?>
                    <a href="auth/register.php" class="btn btn-accent btn-lg">
                        <i class="fas fa-user-plus me-2"></i> Create Free Account
                    </a>
                <?php else: ?>
                    <a href="user/book-court.php" class="btn btn-accent btn-lg">
                        <i class="fas fa-calendar-plus me-2"></i> Book Your Court Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
