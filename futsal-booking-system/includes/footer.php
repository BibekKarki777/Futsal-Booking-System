<?php
/**
 * Footer Include File
 * Futsal Booking System
 */
?>
<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="footer-brand">
                    <i class="fas fa-futbol"></i>
                    <h5>FutsalBook</h5>
                </div>
                <p class="text-secondary">
                    Your one-stop solution for booking futsal courts. 
                    Easy, fast, and reliable booking system.
                </p>
                <div class="social-links mt-3">
                    <a href="#" class="me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                <h6 class="mb-3">Quick Links</h6>
                <ul class="footer-links">
                    <li><a href="/futsal-booking-system/index.php">Home</a></li>
                    <li><a href="/futsal-booking-system/user/book-court.php">Book Court</a></li>
                    <li><a href="/futsal-booking-system/user/my-bookings.php">My Bookings</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h6 class="mb-3">Support</h6>
                <ul class="footer-links">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <h6 class="mb-3">Contact Info</h6>
                <ul class="footer-links">
                    <li>
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                        Kathmandu, Nepal
                    </li>
                    <li>
                        <i class="fas fa-phone me-2 text-primary"></i>
                        +977 9800000000
                    </li>
                    <li>
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        info@futsalbook.com
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> FutsalBook. All rights reserved. 
                <span class="text-primary">Database & Web Application Project</span>
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="/futsal-booking-system/assets/js/main.js"></script>



<?php if (isset($additionalJS)) echo $additionalJS; ?>

</body>
</html>
