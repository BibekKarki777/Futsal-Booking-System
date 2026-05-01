
    function viewUser(user) {
        const html = `
            <div class="row g-3">
                <div class="col-12">
                    <div class="info-box text-center">
                        <div class="user-avatar-large mb-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <h5 class="text-light mb-2">${user.first_name} ${user.last_name}</h5>
                        <span class="badge bg-${user.role === 'admin' ? 'danger' : 'primary'}">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <h6 class="info-box-title">
                            <i class="fas fa-address-card me-2"></i>Contact Information
                        </h6>
                        <div class="detail-row">
                            <span>Email</span>
                            <strong>${user.email}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Phone</span>
                            <strong>${user.contact_number || 'N/A'}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <h6 class="info-box-title">
                            <i class="fas fa-chart-bar me-2"></i>Booking Statistics
                        </h6>
                        <div class="detail-row">
                            <span>Total Bookings</span>
                            <strong>${user.booking_count}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Total Spent</span>
                            <strong class="text-success">Rs. ${Number(user.total_spent || 0).toLocaleString()}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="info-box">
                        <h6 class="info-box-title">
                            <i class="fas fa-calendar me-2"></i>Account Information
                        </h6>
                        <div class="detail-row">
                            <span>Member Since</span>
                            <strong>${new Date(user.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}</strong>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('userDetails').innerHTML = html;
        new bootstrap.Modal(document.getElementById('viewModal')).show();
    }
    
    function confirmDelete(id, name, roleFilter) {
        document.getElementById('deleteUserName').textContent = name;
        document.getElementById('confirmDeleteBtn').href = '?delete=' + id + (roleFilter ? '&role=' + roleFilter : '');
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
