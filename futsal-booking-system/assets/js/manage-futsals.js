
    function confirmDelete(id, name) {
        document.getElementById('deleteFutsalName').textContent = name;
        document.getElementById('confirmDeleteBtn').href = '?delete=' + id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
