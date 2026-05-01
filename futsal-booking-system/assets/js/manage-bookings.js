
// ========================================
// MANAGE BOOKINGS PAGE FUNCTIONS
// ========================================

// Initialize Flatpickr for date inputs
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(function(input) {
            if (input._flatpickr) return;
            flatpickr(input, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "Y-m-d",
                disableMobile: true
            });
        });
    });
    
    function formatTime12Hour(time24) {
        if (!time24) return 'N/A';
        const [hours, minutes] = time24.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
    }
    
    function viewBooking(booking) {
        const html = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="info-box">
                        <h6 class="info-box-title">
                            <i class="fas fa-user me-2"></i>Customer Information
                        </h6>
                        <div class="detail-row">
                            <span>Name</span>
                            <strong>${booking.first_name} ${booking.last_name}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Email</span>
                            <strong>${booking.email}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Phone</span>
                            <strong>${booking.user_phone || 'N/A'}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <h6 class="info-box-title">
                            <i class="fas fa-calendar-check me-2"></i>Booking Information
                        </h6>
                        <div class="detail-row">
                            <span>Court</span>
                            <strong>${booking.court_name}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Venue</span>
                            <strong>${booking.futsal_name}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Date</span>
                            <strong>${booking.booking_date}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Time</span>
                            <strong>${formatTime12Hour(booking.start_time)} - ${formatTime12Hour(booking.end_time)}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Total</span>
                            <strong class="text-success">Rs. ${Number(booking.total_price).toLocaleString()}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <h6 class="info-box-title">
                            <i class="fas fa-credit-card me-2"></i>Payment Information
                        </h6>
                        <div class="detail-row">
                            <span>Status</span>
                            <strong><span class="badge bg-${booking.payment_status === 'paid' ? 'success' : (booking.payment_status === 'refunded' ? 'secondary' : 'danger')}">${booking.payment_status ? booking.payment_status.charAt(0).toUpperCase() + booking.payment_status.slice(1) : 'Unpaid'}</span></strong>
                        </div>
                        <div class="detail-row">
                            <span>Method</span>
                            <strong>${booking.method ? booking.method.charAt(0).toUpperCase() + booking.method.slice(1) : 'N/A'}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Reference</span>
                            <strong>${booking.transaction_ref || 'N/A'}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Paid At</span>
                            <strong>${booking.paid_at || 'N/A'}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <h6 class="info-box-title">
                            <i class="fas fa-info-circle me-2"></i>Booking Status
                        </h6>
                        <div class="detail-row">
                            <span>Status</span>
                            <strong><span class="badge bg-${booking.booking_status === 'confirmed' ? 'success' : (booking.booking_status === 'completed' ? 'info' : (booking.booking_status === 'cancelled' ? 'danger' : 'warning'))}">${booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1)}</span></strong>
                        </div>
                        <div class="detail-row">
                            <span>Created</span>
                            <strong>${booking.created_at}</strong>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('bookingDetails').innerHTML = html;
        new bootstrap.Modal(document.getElementById('viewModal')).show();
    }
    
    function updateStatus(bookingId, currentStatus, bookingDate, endTime, paymentStatus) {
        document.getElementById('statusBookingId').value = bookingId;
        document.getElementById('bookingDate').value = bookingDate;
        document.getElementById('bookingEndTime').value = endTime;
        document.getElementById('currentStatus').value = currentStatus;
        document.getElementById('statusPaymentStatus').value = paymentStatus;
        document.getElementById('newStatus').value = currentStatus;
        document.getElementById('displayCurrentStatus').textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
        
        var statusSelect = document.getElementById('newStatus');
        var statusAlert = document.getElementById('statusAlert');
        var statusAlertMessage = document.getElementById('statusAlertMessage');
        var updateBtn = document.getElementById('updateStatusBtn');
        
        // Reset all options
        for (var i = 0; i < statusSelect.options.length; i++) {
            statusSelect.options[i].disabled = false;
            statusSelect.options[i].style.display = '';
        }
        statusAlert.classList.add('d-none');
        statusSelect.disabled = false;
        updateBtn.disabled = false;
        
        // Set badge color
        var badgeColors = {'pending': 'warning', 'confirmed': 'success', 'cancelled': 'danger', 'completed': 'info'};
        document.getElementById('displayCurrentStatus').className = 'badge bg-' + (badgeColors[currentStatus] || 'secondary');
        
        // Apply rules based on current status
        if (currentStatus === 'completed') {
            statusAlert.className = 'alert alert-info mb-3';
            statusAlertMessage.textContent = 'This booking is already completed. Status cannot be changed.';
            statusAlert.classList.remove('d-none');
            statusSelect.disabled = true;
            updateBtn.disabled = true;
        } else if (currentStatus === 'cancelled') {
            statusAlert.className = 'alert alert-danger mb-3';
            statusAlertMessage.textContent = 'This booking has been cancelled. Status cannot be changed.';
            statusAlert.classList.remove('d-none');
            statusSelect.disabled = true;
            updateBtn.disabled = true;
        } else if (paymentStatus === 'refunded') {
            // If payment is refunded, can only stay cancelled or go to cancelled
            statusAlert.className = 'alert alert-secondary mb-3';
            statusAlertMessage.textContent = 'Payment has been refunded. Cannot change to pending or completed.';
            statusAlert.classList.remove('d-none');
            // Disable pending and completed options
            for (var i = 0; i < statusSelect.options.length; i++) {
                if (statusSelect.options[i].value === 'pending' || statusSelect.options[i].value === 'completed') {
                    statusSelect.options[i].disabled = true;
                }
            }
        } else if (currentStatus === 'confirmed') {
            // Confirmed can go to completed or cancelled, but not pending
            statusAlert.className = 'alert alert-warning mb-3';
            statusAlertMessage.textContent = 'Confirmed bookings can be cancelled or marked as completed (after reservation ends).';
            statusAlert.classList.remove('d-none');
            // Disable only pending option
            for (var i = 0; i < statusSelect.options.length; i++) {
                if (statusSelect.options[i].value === 'pending') {
                    statusSelect.options[i].disabled = true;
                }
            }
        }
        // Pending can go to confirmed, cancelled, or completed
        
        new bootstrap.Modal(document.getElementById('statusModal')).show();
    }
    
    function validateStatusUpdate() {
        var newStatus = document.getElementById('newStatus').value;
        var currentStatus = document.getElementById('currentStatus').value;
        var paymentStatus = document.getElementById('statusPaymentStatus').value;
        var bookingDate = document.getElementById('bookingDate').value;
        var endTime = document.getElementById('bookingEndTime').value;
        
        // Prevent changes if already completed
        if (currentStatus === 'completed') {
            showAlertModal('Status Update Not Allowed', 'Cannot modify a completed booking. The status is final.');
            return false;
        }
        
        // Prevent changes if already cancelled
        if (currentStatus === 'cancelled') {
            showAlertModal('Status Update Not Allowed', 'Cannot modify a cancelled booking. The booking has already been cancelled.');
            return false;
        }
        
        // Prevent pending or completed if payment is refunded
        if (paymentStatus === 'refunded' && (newStatus === 'pending' || newStatus === 'completed')) {
            showAlertModal('Status Update Not Allowed', 'Cannot change to ' + newStatus + '. Payment has been refunded.');
            return false;
        }
        
        // Prevent confirmed from going to pending
        if (currentStatus === 'confirmed' && newStatus === 'pending') {
            showAlertModal('Status Update Not Allowed', 'Confirmed bookings cannot be changed to pending.');
            return false;
        }
        
        // Check if trying to mark as completed before end time
        if (newStatus === 'completed') {
            var endDateTime = new Date(bookingDate + 'T' + endTime);
            var now = new Date();
            
            if (now < endDateTime) {
                showAlertModal('Cannot Complete Booking', 'The reservation end time has not passed yet. You can only mark a booking as completed after the scheduled end time.');
                return false;
            }
        }
        
        return true;
    }
    
    function showAlertModal(title, message) {
        document.getElementById('alertModalTitle').innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-2"></i>' + title;
        document.getElementById('alertModalMessage').textContent = message;
        
        // Close any open modal first
        var statusModal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
        if (statusModal) {
            statusModal.hide();
        }
        var paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
        if (paymentModal) {
            paymentModal.hide();
        }
        
        // Show alert modal
        setTimeout(function() {
            new bootstrap.Modal(document.getElementById('alertModal')).show();
        }, 300);
    }
    
    function updatePayment(bookingId, currentPaymentStatus, currentMethod, bookingStatus) {
        document.getElementById('paymentBookingId').value = bookingId;
        document.getElementById('currentPaymentStatus').value = currentPaymentStatus;
        document.getElementById('paymentBookingStatus').value = bookingStatus;
        document.getElementById('paymentStatus').value = currentPaymentStatus;
        document.getElementById('displayCurrentPayment').textContent = currentPaymentStatus.charAt(0).toUpperCase() + currentPaymentStatus.slice(1);
        
        if (currentMethod) {
            document.getElementById('paymentMethod').value = currentMethod;
        }
        
        var paymentSelect = document.getElementById('paymentStatus');
        var paymentAlert = document.getElementById('paymentAlert');
        var paymentAlertMessage = document.getElementById('paymentAlertMessage');
        var updateBtn = document.getElementById('updatePaymentBtn');
        
        // Reset all options
        for (var i = 0; i < paymentSelect.options.length; i++) {
            paymentSelect.options[i].disabled = false;
        }
        paymentAlert.classList.add('d-none');
        paymentSelect.disabled = false;
        updateBtn.disabled = false;
        
        // Set badge color
        var badgeColors = {'unpaid': 'danger', 'paid': 'success', 'refunded': 'secondary'};
        document.getElementById('displayCurrentPayment').className = 'badge bg-' + (badgeColors[currentPaymentStatus] || 'secondary');
        
        // Apply rules based on current payment status and booking status
        if (currentPaymentStatus === 'refunded') {
            paymentAlert.className = 'alert alert-secondary mb-3';
            paymentAlertMessage.textContent = 'This payment has been refunded. Payment status cannot be changed.';
            paymentAlert.classList.remove('d-none');
            paymentSelect.disabled = true;
            updateBtn.disabled = true;
        } else if (currentPaymentStatus === 'paid') {
            // Paid can only go to refunded (if booking is cancelled)
            paymentAlert.className = 'alert alert-success mb-3';
            paymentAlertMessage.textContent = 'Payment is already marked as paid. Can only be refunded if booking is cancelled.';
            paymentAlert.classList.remove('d-none');
            // Disable unpaid option
            for (var i = 0; i < paymentSelect.options.length; i++) {
                if (paymentSelect.options[i].value === 'unpaid') {
                    paymentSelect.options[i].disabled = true;
                }
                // Also disable refunded if booking is not cancelled
                if (paymentSelect.options[i].value === 'refunded' && bookingStatus !== 'cancelled') {
                    paymentSelect.options[i].disabled = true;
                }
            }
        } else if (currentPaymentStatus === 'unpaid') {
            // Unpaid can only go to paid
            paymentAlert.className = 'alert alert-warning mb-3';
            paymentAlertMessage.textContent = 'Payment is unpaid. Can only be marked as paid. Cannot be refunded until paid first.';
            paymentAlert.classList.remove('d-none');
            // Disable refunded option
            for (var i = 0; i < paymentSelect.options.length; i++) {
                if (paymentSelect.options[i].value === 'refunded') {
                    paymentSelect.options[i].disabled = true;
                }
            }
        }
        
        // Additional rule: If booking is pending, cannot refund
        if (bookingStatus === 'pending') {
            for (var i = 0; i < paymentSelect.options.length; i++) {
                if (paymentSelect.options[i].value === 'refunded') {
                    paymentSelect.options[i].disabled = true;
                }
            }
        }
        
        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    }
    
    function validatePaymentUpdate() {
        var newPaymentStatus = document.getElementById('paymentStatus').value;
        var currentPaymentStatus = document.getElementById('currentPaymentStatus').value;
        var bookingStatus = document.getElementById('paymentBookingStatus').value;
        
        // Prevent any changes if refunded
        if (currentPaymentStatus === 'refunded') {
            showAlertModal('Payment Update Not Allowed', 'Cannot modify a refunded payment. The payment has already been refunded.');
            return false;
        }
        
        // Prevent paid to unpaid
        if (currentPaymentStatus === 'paid' && newPaymentStatus === 'unpaid') {
            showAlertModal('Payment Update Not Allowed', 'Cannot change payment from Paid to Unpaid.');
            return false;
        }
        
        // Prevent unpaid to refunded
        if (currentPaymentStatus === 'unpaid' && newPaymentStatus === 'refunded') {
            showAlertModal('Payment Update Not Allowed', 'Cannot refund an unpaid payment. Payment must be received first.');
            return false;
        }
        
        // Prevent refund if booking is pending
        if (bookingStatus === 'pending' && newPaymentStatus === 'refunded') {
            showAlertModal('Payment Update Not Allowed', 'Cannot refund a pending booking. The booking must be cancelled first.');
            return false;
        }
        
        return true;
    }