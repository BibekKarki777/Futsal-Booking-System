<?php
/**
 * Book Court Page
 * Futsal Booking System
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    redirect('../admin/dashboard.php');
}

$pageTitle = 'Book Court';
$errors = [];
$success = false;

// Get all futsals
$futsals = getAllFutsals($pdo);

// Get courts (optionally filtered by futsal)
$futsal_id = isset($_GET['futsal']) ? (int)$_GET['futsal'] : null;
$selected_court = isset($_GET['court']) ? (int)$_GET['court'] : null;

// Process booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $court_id = (int)($_POST['court_id'] ?? 0);
    $booking_date = sanitize($_POST['booking_date'] ?? '');
    $start_time = sanitize($_POST['start_time'] ?? '');
    $end_time = sanitize($_POST['end_time'] ?? '');
    $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
    $transaction_ref = sanitize($_POST['transaction_ref'] ?? '');
    $payment_completed = ($_POST['payment_completed'] ?? '0') === '1';
    
    // Valid payment methods
    $valid_payment_methods = ['cash', 'esewa', 'khalti', 'bank'];
    if (!in_array($payment_method, $valid_payment_methods)) {
        $payment_method = 'cash';
    }
    
    // Check if digital payment requires transaction reference
    $is_digital_payment = in_array($payment_method, ['esewa', 'khalti', 'bank']);
    
    // Validation
    if ($court_id <= 0) {
        $errors[] = 'Please select a court';
    }
    if (empty($booking_date)) {
        $errors[] = 'Please select a booking date';
    } elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Booking date cannot be in the past';
    }
    if (empty($start_time)) {
        $errors[] = 'Please select start time';
    }
    if (empty($end_time)) {
        $errors[] = 'Please select end time';
    }
    if (!empty($start_time) && !empty($end_time) && strtotime($end_time) <= strtotime($start_time)) {
        $errors[] = 'End time must be after start time';
    }
    
    // Validate transaction reference for digital payments
    if ($is_digital_payment && empty($transaction_ref)) {
        $errors[] = 'Please complete the payment and enter the transaction reference number.';
    }
    
    // Check if booking time is in the past for today's date
    if (empty($errors) && $booking_date === date('Y-m-d')) {
        $currentTime = date('H:i:s');
        if ($start_time < $currentTime) {
            $errors[] = 'Cannot book a time slot that has already passed. Please select a future time.';
        }
    }
    
    // Check availability
    if (empty($errors)) {
        if (!isTimeSlotAvailable($pdo, $court_id, $booking_date, $start_time, $end_time)) {
            $errors[] = 'This time slot is already booked. Please select another time.';
        }
    }
    
    // Check if user already has a booking at this date/time (across all venues)
    if (empty($errors)) {
        $conflictingBooking = userHasConflictingBooking($pdo, $_SESSION['user_id'], $booking_date, $start_time, $end_time);
        if ($conflictingBooking) {
            $errors[] = 'You already have a booking at ' . htmlspecialchars($conflictingBooking['futsal_name']) . 
                        ' (' . htmlspecialchars($conflictingBooking['court_name']) . ') during this time. ' .
                        'Please select a different time slot.';
        }
    }
    
    // Get court price and calculate total
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT price_per_hour FROM courts WHERE court_id = ?");
        $stmt->execute([$court_id]);
        $court = $stmt->fetch();
        
        if (!$court) {
            $errors[] = 'Invalid court selected';
        } else {
            $hours = calculateHours($start_time, $end_time);
            $total_price = $hours * $court['price_per_hour'];
        }
    }
    
    // Create booking
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Determine booking status based on payment method
            // Digital payments: confirmed (since they paid)
            // Cash: pending (will pay at venue)
            $booking_status = $is_digital_payment ? 'confirmed' : 'pending';
            $payment_status = $is_digital_payment ? 'paid' : 'unpaid';
            
            // Insert booking
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, court_id, booking_date, start_time, end_time, total_price, booking_status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $court_id, $booking_date, $start_time, $end_time, $total_price, $booking_status]);
            $booking_id = $pdo->lastInsertId();
            
            // Create payment record
            if ($is_digital_payment) {
                // Digital payment - mark as paid with transaction reference
                $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, method, payment_status, transaction_ref, paid_at) 
                                       VALUES (?, ?, ?, 'paid', ?, NOW())");
                $stmt->execute([$booking_id, $total_price, $payment_method, $transaction_ref]);
            } else {
                // Cash payment - mark as unpaid
                $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, method, payment_status) 
                                       VALUES (?, ?, ?, 'unpaid')");
                $stmt->execute([$booking_id, $total_price, $payment_method]);
            }
            
            $pdo->commit();
            
            // Set appropriate success message based on payment method
            if ($payment_method === 'cash') {
                $_SESSION['success'] = 'Booking created successfully! Please pay at the venue.';
            } else {
                $_SESSION['success'] = 'Booking confirmed and payment successful! Your transaction reference: ' . htmlspecialchars($transaction_ref);
            }
            redirect('my-bookings.php');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to create booking. Please try again.';
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container py-4">
    <?php echo displayAlert(); ?>
    
    <div class="page-header">
        <h1 class="page-title">Book a <span class="text-gradient">Court</span></h1>
        <p class="text-secondary">Select your preferred court, date, and time</p>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo implode('<br>', $errors); ?>
        </div>
    <?php endif; ?>
    
    <div class="row g-4">
        <!-- Booking Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus me-2 text-primary"></i>
                        Booking Details
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="bookingForm" data-selected-court="<?php echo $selected_court ?? ''; ?>" onsubmit="return validateBookingForm()">
                        <!-- Step 1: Select Futsal & Court -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-building me-2"></i>
                                Select Venue & Court
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="futsal_id" class="form-label">Futsal Venue</label>
                                    <select class="form-select" id="futsal_id" name="futsal_id" onchange="loadCourts(this.value)">
                                        <option value="">Select Venue</option>
                                        <?php foreach ($futsals as $futsal): ?>
                                            <option value="<?php echo $futsal['futsal_id']; ?>" 
                                                    <?php echo $futsal_id == $futsal['futsal_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($futsal['futsal_name']); ?> - 
                                                <?php echo htmlspecialchars($futsal['city']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="court_id" class="form-label">Court</label>
                                    <select class="form-select" id="court_id" name="court_id" required onchange="updatePrice()">
                                        <option value="">Select Court</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 2: Select Date -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Select Date
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="booking_date" class="form-label">Booking Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark border-secondary">
                                            <i class="fas fa-calendar-alt text-primary"></i>
                                        </span>
                                        <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                               min="<?php echo date('Y-m-d'); ?>" required
                                               value="<?php echo isset($_POST['booking_date']) ? $_POST['booking_date'] : date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 3: Select Time -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-clock me-2"></i>
                                Select Time
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <select class="form-select" id="start_time" name="start_time" required onchange="handleTimeChange()">
                                        <option value="">Select Venue First</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <select class="form-select" id="end_time" name="end_time" required onchange="updateBookingSummary()">
                                        <option value="">Select Venue First</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 4: Select Payment Method -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-credit-card me-2"></i>
                                Payment Method
                            </h6>
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <input type="radio" name="payment_method" id="payment_cash" value="cash" checked class="payment-radio" onchange="updatePaymentMethod()">
                                    <label for="payment_cash" class="payment-box cash-box">
                                        <div class="payment-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <span class="payment-name">Cash</span>
                                        <small class="payment-desc">Pay at venue</small>
                                    </label>
                                </div>
                                <div class="col-6 col-md-3">
                                    <input type="radio" name="payment_method" id="payment_esewa" value="esewa" class="payment-radio" onchange="updatePaymentMethod()">
                                    <label for="payment_esewa" class="payment-box esewa-box">
                                        <div class="payment-icon">
                                            <span class="esewa-logo">e-</span>
                                        </div>
                                        <span class="payment-name">eSewa</span>
                                        <small class="payment-desc">Digital wallet</small>
                                    </label>
                                </div>
                                <div class="col-6 col-md-3">
                                    <input type="radio" name="payment_method" id="payment_khalti" value="khalti" class="payment-radio" onchange="updatePaymentMethod()">
                                    <label for="payment_khalti" class="payment-box khalti-box">
                                        <div class="payment-icon">
                                            <span class="khalti-logo">K</span>
                                        </div>
                                        <span class="payment-name">Khalti</span>
                                        <small class="payment-desc">Digital wallet</small>
                                    </label>
                                </div>
                                <div class="col-6 col-md-3">
                                    <input type="radio" name="payment_method" id="payment_bank" value="bank" class="payment-radio" onchange="updatePaymentMethod()">
                                    <label for="payment_bank" class="payment-box bank-box">
                                        <div class="payment-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <span class="payment-name">Bank</span>
                                        <small class="payment-desc">Bank transfer</small>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Digital Payment Section (Hidden by default) -->
                            <div id="digitalPaymentSection" class="mt-4" style="display: none;">
                                <div class="card bg-dark border-secondary">
                                    <div class="card-body">
                                        <div id="payNowSection">
                                            <p class="text-light mb-3">
                                                <i class="fas fa-info-circle text-info me-2"></i>
                                                Please complete your payment using <span id="selectedPaymentName" class="text-primary fw-bold">eSewa</span> and enter the transaction reference below.
                                            </p>
                                            <div class="d-grid mb-3">
                                                <button type="button" class="btn btn-success btn-lg" id="payNowBtn" onclick="showTransactionInput()">
                                                    <i class="fas fa-credit-card me-2"></i> Pay Now - Rs. <span id="payAmount">0.00</span>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div id="transactionSection" style="display: none;">
                                            <div class="alert alert-success mb-3">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Payment initiated! Please enter your transaction reference.
                                            </div>
                                            <div class="mb-3">
                                                <label for="transaction_ref" class="form-label">Transaction Reference <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-dark border-secondary">
                                                        <i class="fas fa-receipt text-primary"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="transaction_ref" name="transaction_ref" 
                                                           placeholder="Enter transaction ID/reference number">
                                                </div>
                                                <small class="text-secondary">Enter the transaction ID you received after payment</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="payment_completed" id="payment_completed" value="0">
                        </div>
                        
                        <input type="hidden" id="price_per_hour" value="0">
                        <input type="hidden" id="venue_open_time" value="">
                        <input type="hidden" id="venue_close_time" value="">
                        
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="confirmBookingBtn">
                                <i class="fas fa-check me-2"></i> Confirm Booking
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Booking Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2 text-warning"></i>
                        Booking Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div id="booking-summary">
                        <div class="mb-3 pb-3 border-bottom border-secondary">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Court:</span>
                                <span id="summary_court" class="text-light">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Date:</span>
                                <span id="summary_date" class="text-light">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Time:</span>
                                <span id="summary_time" class="text-light">-</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">Duration:</span>
                                <span id="summary_duration" class="text-light">-</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Price/Hour:</span>
                            <span id="summary_price" class="text-light">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Payment:</span>
                            <span id="summary_payment" class="text-light">Cash Payment</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary">
                            <span class="h5 mb-0 text-white fw-bold">Total:</span>
                            <span class="h4 mb-0 fw-bold" style="color: #10b981;" id="total_display">Rs. 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tips Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2 text-warning"></i>
                        Booking Tips
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="small text-secondary mb-0 ps-3">
                        <li class="mb-2">Book at least 1 day in advance for better availability</li>
                        <li class="mb-2">Peak hours are usually 5PM - 9PM</li>
                        <li class="mb-2">Minimum booking duration is 1 hour</li>
                        <li>Cancellation is free up to 24 hours before</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load courts based on selected futsal
function loadCourts(futsalId) {
    var courtSelect = document.getElementById('court_id');
    var startTimeSelect = document.getElementById('start_time');
    var endTimeSelect = document.getElementById('end_time');
    
    courtSelect.innerHTML = '<option value="">Loading...</option>';
    startTimeSelect.innerHTML = '<option value="">Select Venue First</option>';
    endTimeSelect.innerHTML = '<option value="">Select Venue First</option>';
    
    if (!futsalId) {
        courtSelect.innerHTML = '<option value="">Select Venue First</option>';
        return;
    }
    
    fetch('get_courts.php?futsal_id=' + futsalId)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            // Populate courts dropdown
            var html = '<option value="">Select Court</option>';
            data.courts.forEach(function(court) {
                html += '<option value="' + court.court_id + '" data-price="' + court.price_per_hour + '">' +
                    court.court_name + ' - Rs. ' + parseFloat(court.price_per_hour).toFixed(2) + '/hr (' + court.surface_type + ')' +
                '</option>';
            });
            courtSelect.innerHTML = html;
            
            // Store and populate time options based on venue hours
            if (data.open_time && data.close_time) {
                document.getElementById('venue_open_time').value = data.open_time;
                document.getElementById('venue_close_time').value = data.close_time;
                populateTimeOptions(data.open_time, data.close_time);
            }
            
            <?php if ($selected_court): ?>
            courtSelect.value = <?php echo $selected_court; ?>;
            updatePrice();
            <?php endif; ?>
        })
        .catch(function(error) {
            courtSelect.innerHTML = '<option value="">Error loading courts</option>';
        });
}

// Populate time options based on venue open/close hours
function populateTimeOptions(openTime, closeTime) {
    var startTimeSelect = document.getElementById('start_time');
    var endTimeSelect = document.getElementById('end_time');
    
    // Parse hours from time strings (format: HH:MM:SS)
    var openHour = parseInt(openTime.split(':')[0]);
    var closeHour = parseInt(closeTime.split(':')[0]);
    
    // Generate start time options (from open to close-1)
    var startHtml = '<option value="">Select Start Time</option>';
    for (var hour = openHour; hour < closeHour; hour++) {
        var timeValue = (hour < 10 ? '0' : '') + hour + ':00:00';
        var displayTime = formatHourForDisplay(hour);
        startHtml += '<option value="' + timeValue + '" data-hour="' + hour + '">' + displayTime + '</option>';
    }
    startTimeSelect.innerHTML = startHtml;
    
    // Generate end time options (from open+1 to close)
    var endHtml = '<option value="">Select End Time</option>';
    for (var hour = openHour + 1; hour <= closeHour; hour++) {
        var timeValue = (hour < 10 ? '0' : '') + hour + ':00:00';
        var displayTime = formatHourForDisplay(hour);
        endHtml += '<option value="' + timeValue + '" data-hour="' + hour + '">' + displayTime + '</option>';
    }
    endTimeSelect.innerHTML = endHtml;
    
    // Apply time filters for today
    filterTimeOptions();
}

// Format hour for display (e.g., 6 -> "6:00 AM", 18 -> "6:00 PM")
function formatHourForDisplay(hour) {
    var ampm = hour >= 12 ? 'PM' : 'AM';
    var hour12 = hour % 12;
    if (hour12 === 0) hour12 = 12;
    return hour12 + ':00 ' + ampm;
}

// Update price when court is selected
function updatePrice() {
    var courtSelect = document.getElementById('court_id');
    var selectedOption = courtSelect.options[courtSelect.selectedIndex];
    var priceInput = document.getElementById('price_per_hour');
    var summaryPrice = document.getElementById('summary_price');
    var summaryCourt = document.getElementById('summary_court');
    
    if (selectedOption && selectedOption.dataset.price) {
        var price = parseFloat(selectedOption.dataset.price);
        priceInput.value = price;
        summaryPrice.textContent = 'Rs. ' + price.toFixed(2);
        summaryCourt.textContent = selectedOption.text.split(' - ')[0].trim();
    } else {
        priceInput.value = 0;
        summaryPrice.textContent = '-';
        summaryCourt.textContent = '-';
    }
    
    updateBookingSummary();
}

// Format time for display
function formatTimeForDisplay(timeValue) {
    if (!timeValue) return '-';
    var hour = parseInt(timeValue.split(':')[0]);
    var ampm = hour >= 12 ? 'PM' : 'AM';
    var hour12 = hour % 12;
    if (hour12 === 0) hour12 = 12;
    return hour12 + ':00 ' + ampm;
}

// Get formatted date string
function getDateString() {
    var dateInput = document.getElementById('booking_date');
    var dateValue = dateInput.value;
    
    // Try Flatpickr first
    if (dateInput._flatpickr && dateInput._flatpickr.selectedDates && dateInput._flatpickr.selectedDates.length > 0) {
        var d = dateInput._flatpickr.selectedDates[0];
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    }
    
    // Parse from input value (YYYY-MM-DD)
    if (dateValue && dateValue.length === 10) {
        var parts = dateValue.split('-');
        var d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    }
    
    return '-';
}

// Main function to update booking summary - THIS IS THE KEY FUNCTION
function updateBookingSummary() {
    var startSelect = document.getElementById('start_time');
    var endSelect = document.getElementById('end_time');
    var priceInput = document.getElementById('price_per_hour');
    
    var summaryDate = document.getElementById('summary_date');
    var summaryTime = document.getElementById('summary_time');
    var summaryDuration = document.getElementById('summary_duration');
    var totalDisplay = document.getElementById('total_display');
    
    // Get current values
    var startTime = startSelect.value;
    var endTime = endSelect.value;
    var pricePerHour = parseFloat(priceInput.value) || 0;
    
    // Always update date
    summaryDate.textContent = getDateString();
    
    // Update time, duration and total only if both times are selected
    if (startTime && startTime.length > 0 && endTime && endTime.length > 0) {
        var startHour = parseInt(startTime.split(':')[0]);
        var endHour = parseInt(endTime.split(':')[0]);
        var hours = endHour - startHour;
        
        if (hours > 0) {
            summaryTime.textContent = formatTimeForDisplay(startTime) + ' - ' + formatTimeForDisplay(endTime);
            summaryDuration.textContent = hours + ' hour' + (hours > 1 ? 's' : '');
            totalDisplay.textContent = 'Rs. ' + (hours * pricePerHour).toFixed(2);
        } else {
            summaryTime.textContent = '-';
            summaryDuration.textContent = '-';
            totalDisplay.textContent = 'Rs. ' + pricePerHour.toFixed(2);
        }
    } else {
        summaryTime.textContent = '-';
        summaryDuration.textContent = '-';
        totalDisplay.textContent = 'Rs. ' + pricePerHour.toFixed(2);
    }
    
    // Update pay amount for digital payments
    updatePayAmount();
}

// Alias for compatibility
function calculateTotal() {
    updateBookingSummary();
}

// Handle time change from dropdown
function handleTimeChange() {
    filterEndTimeOptions();
    updateBookingSummary();
}

// Update payment method in summary and show/hide digital payment section
function updatePaymentMethod() {
    var summaryPayment = document.getElementById('summary_payment');
    var selectedPayment = document.querySelector('input[name="payment_method"]:checked');
    var digitalPaymentSection = document.getElementById('digitalPaymentSection');
    var payNowSection = document.getElementById('payNowSection');
    var transactionSection = document.getElementById('transactionSection');
    var transactionRef = document.getElementById('transaction_ref');
    var paymentCompleted = document.getElementById('payment_completed');
    var selectedPaymentName = document.getElementById('selectedPaymentName');
    var confirmBtn = document.getElementById('confirmBookingBtn');
    
    if (selectedPayment && summaryPayment) {
        var paymentLabels = {
            'cash': 'Cash Payment',
            'esewa': 'eSewa',
            'khalti': 'Khalti',
            'bank': 'Bank Transfer'
        };
        summaryPayment.textContent = paymentLabels[selectedPayment.value] || 'Cash Payment';
        
        // Show/hide digital payment section
        if (selectedPayment.value === 'cash') {
            digitalPaymentSection.style.display = 'none';
            transactionRef.required = false;
            paymentCompleted.value = '0';
            confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i> Confirm Booking';
        } else {
            digitalPaymentSection.style.display = 'block';
            payNowSection.style.display = 'block';
            transactionSection.style.display = 'none';
            transactionRef.required = false;
            paymentCompleted.value = '0';
            selectedPaymentName.textContent = paymentLabels[selectedPayment.value];
            confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i> Confirm Booking';
            
            // Update pay amount
            updatePayAmount();
        }
    }
}

// Update the pay amount display
function updatePayAmount() {
    var payAmountSpan = document.getElementById('payAmount');
    var totalDisplay = document.getElementById('total_display');
    
    if (totalDisplay && payAmountSpan) {
        var totalText = totalDisplay.textContent.replace('Rs. ', '').replace(',', '');
        payAmountSpan.textContent = totalText;
    }
}

// Show transaction reference input after clicking Pay Now
function showTransactionInput() {
    var payNowSection = document.getElementById('payNowSection');
    var transactionSection = document.getElementById('transactionSection');
    var transactionRef = document.getElementById('transaction_ref');
    var paymentCompleted = document.getElementById('payment_completed');
    var confirmBtn = document.getElementById('confirmBookingBtn');
    
    payNowSection.style.display = 'none';
    transactionSection.style.display = 'block';
    transactionRef.required = true;
    transactionRef.focus();
    paymentCompleted.value = '1';
    confirmBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Complete Booking & Payment';
}

// Validate form before submission
function validateBookingForm() {
    var selectedPayment = document.querySelector('input[name="payment_method"]:checked');
    var transactionRef = document.getElementById('transaction_ref');
    var paymentCompleted = document.getElementById('payment_completed');
    
    if (selectedPayment && selectedPayment.value !== 'cash') {
        if (paymentCompleted.value !== '1') {
            alert('Please click "Pay Now" and complete the payment process.');
            return false;
        }
        if (!transactionRef.value.trim()) {
            alert('Please enter the transaction reference number.');
            transactionRef.focus();
            return false;
        }
    }
    return true;
}

// Check if a date is today
function isDateToday(date) {
    var today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
}

// Filter time options to hide past times for today
function filterTimeOptions() {
    var dateInput = document.getElementById('booking_date');
    var startSelect = document.getElementById('start_time');
    var endSelect = document.getElementById('end_time');
    
    var selectedDate = null;
    
    if (dateInput._flatpickr && dateInput._flatpickr.selectedDates && dateInput._flatpickr.selectedDates.length > 0) {
        selectedDate = dateInput._flatpickr.selectedDates[0];
    } else if (dateInput.value) {
        var parts = dateInput.value.split('-');
        if (parts.length === 3) {
            selectedDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        }
    }
    
    var currentHour = new Date().getHours();
    var isTodaySelected = selectedDate && isDateToday(selectedDate);
    
    // Filter start time options
    for (var i = 0; i < startSelect.options.length; i++) {
        var option = startSelect.options[i];
        if (!option.value) continue;
        var hour = parseInt(option.getAttribute('data-hour'));
        
        if (isTodaySelected && hour <= currentHour) {
            option.style.display = 'none';
            option.disabled = true;
        } else {
            option.style.display = '';
            option.disabled = false;
        }
    }
    
    // Filter end time options
    for (var i = 0; i < endSelect.options.length; i++) {
        var option = endSelect.options[i];
        if (!option.value) continue;
        var hour = parseInt(option.getAttribute('data-hour'));
        
        if (isTodaySelected && hour <= currentHour) {
            option.style.display = 'none';
            option.disabled = true;
        } else {
            option.style.display = '';
            option.disabled = false;
        }
    }
    
    // Reset selections if they're now disabled
    if (startSelect.selectedIndex > 0 && startSelect.options[startSelect.selectedIndex].disabled) {
        startSelect.selectedIndex = 0;
    }
    if (endSelect.selectedIndex > 0 && endSelect.options[endSelect.selectedIndex].disabled) {
        endSelect.selectedIndex = 0;
    }
    
    filterEndTimeOptions();
}

// Filter end time based on start time selection
function filterEndTimeOptions() {
    var startSelect = document.getElementById('start_time');
    var endSelect = document.getElementById('end_time');
    var dateInput = document.getElementById('booking_date');
    
    var startValue = startSelect.value;
    var startHour = startValue ? parseInt(startValue.split(':')[0]) : -1;
    
    var selectedDate = null;
    if (dateInput._flatpickr && dateInput._flatpickr.selectedDates && dateInput._flatpickr.selectedDates.length > 0) {
        selectedDate = dateInput._flatpickr.selectedDates[0];
    } else if (dateInput.value) {
        var parts = dateInput.value.split('-');
        if (parts.length === 3) {
            selectedDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        }
    }
    
    var currentHour = new Date().getHours();
    var isTodaySelected = selectedDate && isDateToday(selectedDate);
    
    for (var i = 0; i < endSelect.options.length; i++) {
        var option = endSelect.options[i];
        if (!option.value) continue;
        var hour = parseInt(option.getAttribute('data-hour'));
        
        var shouldHide = (isTodaySelected && hour <= currentHour) || (startHour >= 0 && hour <= startHour);
        
        if (shouldHide) {
            option.style.display = 'none';
            option.disabled = true;
        } else {
            option.style.display = '';
            option.disabled = false;
        }
    }
    
    if (endSelect.selectedIndex > 0 && endSelect.options[endSelect.selectedIndex].disabled) {
        endSelect.selectedIndex = 0;
    }
    
    updateBookingSummary();
}

// Alias for compatibility
function updateTimeOptions() {
    filterTimeOptions();
}

function updateEndTimeOptions() {
    filterEndTimeOptions();
}

// Initialize Flatpickr date picker
function initDatePicker() {
    var dateInput = document.getElementById('booking_date');
    if (!dateInput || dateInput._flatpickr) return;
    
    flatpickr(dateInput, {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "Y-m-d",
        minDate: "today",
        disableMobile: true,
        defaultDate: dateInput.value || new Date(),
        onChange: function() {
            filterTimeOptions();
            updateBookingSummary();
        },
        onReady: function() {
            setTimeout(function() {
                filterTimeOptions();
                updateBookingSummary();
            }, 50);
        }
    });
}

// Initialize everything when page loads
document.addEventListener('DOMContentLoaded', function() {
    var startSelect = document.getElementById('start_time');
    var endSelect = document.getElementById('end_time');
    var futsalSelect = document.getElementById('futsal_id');
    
    // Add event listeners
    if (startSelect) {
        startSelect.addEventListener('change', handleTimeChange);
    }
    if (endSelect) {
        endSelect.addEventListener('change', updateBookingSummary);
    }
    
    // Load courts if venue pre-selected
    if (futsalSelect && futsalSelect.value) {
        loadCourts(futsalSelect.value);
    }
    
    // Initialize date picker
    initDatePicker();
    
    // Initial update
    setTimeout(function() {
        filterTimeOptions();
        updateBookingSummary();
    }, 100);
});
</script>

<?php include '../includes/footer.php'; ?>
