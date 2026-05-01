<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
</head>
<body>
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-futbol"></i>
                <span>FutsalBook</span>
            </div>
            <button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="user-info">
                <h6><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h6>
                <span class="badge bg-success">Administrator</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-futsals.php' ? 'active' : ''; ?>" href="manage-futsals.php">
                        <i class="fas fa-building"></i>
                        <span>Manage Venues</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-courts.php' ? 'active' : ''; ?>" href="manage-courts.php">
                        <i class="fas fa-th-large"></i>
                        <span>Manage Courts</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-bookings.php' ? 'active' : ''; ?>" href="manage-bookings.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Manage Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-users.php' ? 'active' : ''; ?>" href="manage-users.php">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-payments.php' ? 'active' : ''; ?>" href="manage-payments.php">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../index.php" class="btn btn-outline-light btn-sm w-100 mb-2">
                <i class="fas fa-home me-2"></i>View Site
            </a>
            <a href="../auth/logout.php" class="btn btn-danger btn-sm w-100">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </aside>

    
    <button class="mobile-toggle d-lg-none" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <script src="../assets/js/sidebar.js"></script>

    
</body>
</html>
