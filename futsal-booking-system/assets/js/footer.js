// Initialize Flatpickr for all date inputs 

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all date inputs with Flatpickr (skip if already initialized)
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(function(input) {
        // Skip if already initialized by page-specific script
        if (input._flatpickr) return;
        
        // Get min date if set
        const minDate = input.getAttribute('min') || null;
        
        flatpickr(input, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "Y-m-d",
            minDate: minDate,
            disableMobile: true,
            theme: "dark",
            onChange: function(selectedDates, dateStr, instance) {
                // Trigger change event for any listeners
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
});
