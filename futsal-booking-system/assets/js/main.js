/**
 * Futsal Booking System - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initNavbar();
    initAnimations();
    initForms();
    initBookingSystem();
    initSidebar();
    initTooltips();
});

/**
 * Navbar scroll effect
 */
function initNavbar() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
}

/**
 * Initialize scroll animations
 */
function initAnimations() {
    const animatedElements = document.querySelectorAll('.animate-fade-in, .animate-slide-in');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'none';
            }
        });
    }, { threshold: 0.1 });
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });
}

/**
 * Form validation and enhancement
 */
function initForms() {
    // Password visibility toggle
    const togglePassword = document.querySelectorAll('.toggle-password');
    togglePassword.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.closest('.input-group').querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Form validation feedback
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Booking system functionality
 */
function initBookingSystem() {
    const timeSlots = document.querySelectorAll('.time-slot:not(.booked)');
    let selectedStart = null;
    let selectedEnd = null;

    timeSlots.forEach(slot => {
        slot.addEventListener('click', function() {
            const time = this.dataset.time;
            
            if (!selectedStart) {
                selectedStart = time;
                this.classList.add('selected');
                updateBookingSummary();
            } else if (!selectedEnd && time > selectedStart) {
                selectedEnd = time;
                this.classList.add('selected');
                // Select all slots in between
                selectRange(selectedStart, selectedEnd);
                updateBookingSummary();
            } else {
                // Reset selection
                resetSelection();
                selectedStart = time;
                this.classList.add('selected');
                updateBookingSummary();
            }
        });
    });

    function selectRange(start, end) {
        timeSlots.forEach(slot => {
            const time = slot.dataset.time;
            if (time >= start && time <= end) {
                slot.classList.add('selected');
            }
        });
    }

    function resetSelection() {
        selectedStart = null;
        selectedEnd = null;
        timeSlots.forEach(slot => slot.classList.remove('selected'));
    }

    function updateBookingSummary() {
        const startInput = document.getElementById('start_time');
        const endInput = document.getElementById('end_time');
        const summaryDiv = document.getElementById('booking-summary');

        if (startInput && selectedStart) {
            startInput.value = selectedStart;
        }
        if (endInput && selectedEnd) {
            endInput.value = selectedEnd;
        }
        if (summaryDiv) {
            calculateTotal();
        }
    }
}

/**
 * Calculate booking total
 */
function calculateTotal() {
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');
    const pricePerHour = document.getElementById('price_per_hour');
    const totalPrice = document.getElementById('total_price');
    const totalDisplay = document.getElementById('total_display');

    if (startTime && endTime && pricePerHour && startTime.value && endTime.value) {
        const start = new Date('2000-01-01 ' + startTime.value);
        const end = new Date('2000-01-01 ' + endTime.value);
        const hours = (end - start) / (1000 * 60 * 60);
        
        if (hours > 0) {
            const total = hours * parseFloat(pricePerHour.value);
            if (totalPrice) totalPrice.value = total.toFixed(2);
            if (totalDisplay) totalDisplay.textContent = 'Rs. ' + total.toFixed(2);
        }
    }
}

/**
 * Initialize sidebar toggle for mobile
 */
function initSidebar() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            document.body.classList.toggle('sidebar-open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }
        });
    }
}

/**
 * Initialize Bootstrap tooltips
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Confirm delete action
 */
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return 'Rs. ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Get available time slots for a court on a specific date
 */
function loadTimeSlots(courtId, date) {
    const container = document.getElementById('time-slots-container');
    if (!container) return;

    container.innerHTML = '<div class="text-center"><div class="spinner"></div><p class="mt-2">Loading available slots...</p></div>';

    fetch(`get_time_slots.php?court_id=${courtId}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            let html = '<div class="time-slots">';
            data.slots.forEach(slot => {
                const bookedClass = slot.booked ? 'booked' : '';
                html += `<div class="time-slot ${bookedClass}" data-time="${slot.time}">
                    ${slot.display}
                    ${slot.booked ? '<small class="d-block text-danger">Booked</small>' : ''}
                </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
            initBookingSystem();
        })
        .catch(error => {
            container.innerHTML = '<div class="alert alert-danger">Error loading time slots. Please try again.</div>';
        });
}

/**
 * Update courts dropdown based on selected futsal
 */
function updateCourts(futsalId) {
    const courtSelect = document.getElementById('court_id');
    if (!courtSelect) return;

    courtSelect.innerHTML = '<option value="">Loading courts...</option>';

    fetch(`get_courts.php?futsal_id=${futsalId}`)
        .then(response => response.json())
        .then(data => {
            let html = '<option value="">Select Court</option>';
            data.courts.forEach(court => {
                html += `<option value="${court.court_id}" data-price="${court.price_per_hour}">
                    ${court.court_name} - Rs. ${court.price_per_hour}/hr
                </option>`;
            });
            courtSelect.innerHTML = html;
        })
        .catch(error => {
            courtSelect.innerHTML = '<option value="">Error loading courts</option>';
        });
}

/**
 * Print booking receipt
 */
function printReceipt() {
    window.print();
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

