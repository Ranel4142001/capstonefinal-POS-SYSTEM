<?php
require_once __DIR__ . '/../includes/bootstrap.php';
include LEGACY_BASE_PATH . '/includes/auth_check.php';
require_role(['admin']);
include LEGACY_BASE_PATH . '/includes/layout_start.php';
?>

<div class="container-fluid dashboard-page-content mt-5 pt-3">
    <input type="hidden" id="auditTrailApiUrl" value="<?= htmlspecialchars(LEGACY_BASE_URL . '/api/audit_logs.php', ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-4">
        <div>
            <h2 class="mb-1">Audit Trail</h2>
            <p class="text-muted mb-0">Administrator-only activity log for product, stock, sale, and refund events.</p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Filter Audit Logs</div>
        <div class="card-body">
            <form id="auditTrailFilterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="audit_start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="audit_start_date" name="start_date">
                </div>
                <div class="col-md-3">
                    <label for="audit_end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="audit_end_date" name="end_date">
                </div>
                <div class="col-md-2">
                    <label for="audit_event_type" class="form-label">Event</label>
                    <select class="form-select" id="audit_event_type" name="event_type">
                        <option value="">All Events</option>
                        <option value="Create">Create</option>
                        <option value="Update">Update</option>
                        <option value="Delete">Delete</option>
                        <option value="Refund">Refund</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="audit_auditable_type" class="form-label">Entity</label>
                    <select class="form-select" id="audit_auditable_type" name="auditable_type">
                        <option value="">All Entities</option>
                        <option value="Product">Product</option>
                        <option value="Transaction">Transaction</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="audit_user_id" class="form-label">User ID</label>
                    <input type="number" min="1" class="form-control" id="audit_user_id" name="user_id" placeholder="e.g. 1">
                </div>
                <div class="col-md-2">
                    <label for="audit_per_page" class="form-label">Rows</label>
                    <select class="form-select" id="audit_per_page" name="per_page">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Apply Filters</button>
                    <button type="button" class="btn btn-outline-secondary" id="auditTrailResetBtn">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>Audit Activity</span>
            <small class="text-muted" id="auditTrailSummaryText">Loading audit logs...</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="auditTrailTable">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>Event</th>
                            <th>Entity</th>
                            <th>Record ID</th>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="auditTrailTableBody">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Loading audit logs...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav id="auditTrailPaginationNav" aria-label="Audit Trail Pagination" class="mt-4"></nav>
        </div>
    </div>
</div>

<div class="modal fade" id="auditTrailDetailsModal" tabindex="-1" aria-labelledby="auditTrailDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="auditTrailDetailsModalLabel">Audit Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div><strong>Audit Log ID:</strong> <span id="auditDetailId">-</span></div>
                        <div><strong>User:</strong> <span id="auditDetailUser">-</span></div>
                        <div><strong>Event:</strong> <span id="auditDetailEvent">-</span></div>
                        <div><strong>Entity:</strong> <span id="auditDetailEntity">-</span></div>
                    </div>
                    <div class="col-md-6">
                        <div><strong>Record ID:</strong> <span id="auditDetailRecordId">-</span></div>
                        <div><strong>Date &amp; Time:</strong> <span id="auditDetailCreatedAt">-</span></div>
                    </div>
                </div>

                <div class="alert alert-secondary" id="auditDetailMessage">-</div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">Old Values</div>
                            <div class="card-body">
                                <pre class="mb-0 small bg-light border rounded p-3 overflow-auto" id="auditDetailOldValues" style="max-height: 420px;">No old values recorded.</pre>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">New Values</div>
                            <div class="card-body">
                                <pre class="mb-0 small bg-light border rounded p-3 overflow-auto" id="auditDetailNewValues" style="max-height: 420px;">No new values recorded.</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
<script src="<?= LEGACY_BASE_URL ?>/public/js/audit_trail.js?v=<?= filemtime(LEGACY_BASE_PATH . '/public/js/audit_trail.js') ?>"></script>
</body>
</html>
