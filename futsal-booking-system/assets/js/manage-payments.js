
    function markPaid(paymentId, amount) {
        document.getElementById('paymentId').value = paymentId;
        document.getElementById('paymentAmount').textContent = Number(amount).toLocaleString();
        new bootstrap.Modal(document.getElementById('markPaidModal')).show();
    }
