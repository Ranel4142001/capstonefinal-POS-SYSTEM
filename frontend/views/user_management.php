<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// views/user_management.php
include LEGACY_BASE_PATH . '/includes/auth_check.php';
include LEGACY_BASE_PATH . '/includes/layout_start.php';
include LEGACY_BASE_PATH . '/includes/functions.php';

// Restrict access: only admin can add/edit/delete users
$isAdmin = ($_SESSION['role'] === 'admin');

// Flash messages
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Allowed roles
$allowed_roles = ['admin', 'staff', 'cashier'];
?>

<div class="container-fluid dashboard-page-content mt-5 pt-3">
    <h2 class="mb-4">Manage Users</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add User Card -->
    <div class="card mb-4">
        <div class="card-header">Add New User</div>
        <div class="card-body">
            <form id="addUserForm">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email (Optional)</label>
                    <input type="email" name="email" id="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="">Select Role</option>
                        <?php foreach ($allowed_roles as $role_option): ?>
                            <option value="<?= htmlspecialchars($role_option) ?>"><?= ucwords($role_option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($isAdmin): ?>
                    <button type="submit" class="btn btn-primary">Add User</button>
                <?php else: ?>
                    <p class="text-danger">You do not have permission to add users.</p>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- User Table -->
    <div class="card">
        <div class="card-header">Existing Users</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="userTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="6">Loading users...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editUserForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="user_id" id="edit_user_id">
        <div class="modal-header">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php if (!$isAdmin): ?>
            <p class="text-danger">You do not have permission to edit users.</p>
          <?php else: ?>
            <div class="mb-3">
              <label for="edit_username" class="form-label">Username</label>
              <input type="text" name="username" id="edit_username" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="edit_password" class="form-label">New Password (leave blank to keep current)</label>
              <input type="password" name="password" id="edit_password" class="form-control">
            </div>
            <div class="mb-3">
              <label for="edit_email" class="form-label">Email</label>
              <input type="email" name="email" id="edit_email" class="form-control">
            </div>
            <div class="mb-3">
              <label for="edit_role" class="form-label">Role</label>
              <select name="role" id="edit_role" class="form-select" required>
                <?php foreach ($allowed_roles as $role_option): ?>
                  <option value="<?= htmlspecialchars($role_option) ?>"><?= ucwords($role_option) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <?php if ($isAdmin): ?>
            <button type="submit" class="btn btn-primary">Save changes</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="deleteUserForm">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="delete_user_id">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php if (!$isAdmin): ?>
            <p class="text-danger">You do not have permission to delete users.</p>
          <?php else: ?>
            <p>Are you sure you want to delete user "<strong id="delete_username"></strong>"?</p>
            <p class="text-danger">Note: You cannot delete your own account.</p>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <?php if ($isAdmin): ?>
            <button type="submit" class="btn btn-danger">Delete</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<?php include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
<script src="<?= LEGACY_BASE_URL ?>/public/js/user_management.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const alertBox = document.querySelector('.alert');
  if (alertBox) {
    setTimeout(() => new bootstrap.Alert(alertBox).close(), 5000);
  }
});
</script>
</body>
</html>



