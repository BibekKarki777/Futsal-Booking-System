
    function confirmDelete(id, name, futsalFilter) {
        document.getElementById('deleteCourtName').textContent = name;
        document.getElementById('confirmDeleteBtn').href = '?delete=' + id + (futsalFilter ? '&futsal=' + futsalFilter : '');
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
