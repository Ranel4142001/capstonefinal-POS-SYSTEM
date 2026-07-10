<?php
require_once __DIR__ . '/../includes/bootstrap.php';

        include LEGACY_BASE_PATH . '/includes/auth_check.php';
require_role(['admin']);
        // Include common layout start (head, header, sidebar, navbar open tags)
        include LEGACY_BASE_PATH . '/includes/layout_start.php';
?>

    
            <div class="container-fluid dashboard-page-content mt-5 pt-3">
                <h2 class="mb-4">Supplier Management</h2>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="supplierSearch" class="form-control" placeholder="Search by name, contact, phone, email...">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary" id="resetFiltersBtn">Reset Filters</button>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                            <i class="fas fa-plus"></i> Add New Supplier
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="supplier-table-header"> <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="supplierTableBody">
                            <tr><td colspan="7" class="text-center">Loading suppliers...</td></tr>
                        </tbody>
                    </table>
                </div>

                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center" id="supplierPagination">
                        </ul>
                </nav>

            </div>
        </div>
    </div>

    <div class="overlay" id="overlay"></div>

    <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSupplierModalLabel">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addSupplierForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="supplierName" class="form-label">Supplier Name</label>
                            <input type="text" class="form-control" id="supplierName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactPerson" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contactPerson" name="contact_person">
                        </div>
                        <div class="mb-3">
                            <label for="supplierPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="supplierPhone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="supplierEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="supplierEmail" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="supplierAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="supplierAddress" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSupplierModalLabel">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editSupplierForm">
                    <div class="modal-body">
                        <input type="hidden" id="editSupplierId" name="id">
                        <div class="mb-3">
                            <label for="editSupplierName" class="form-label">Supplier Name</label>
                            <input type="text" class="form-control" id="editSupplierName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editContactPerson" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="editContactPerson" name="contact_person">
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="editSupplierPhone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editSupplierEmail" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="editSupplierAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="editSupplierAddress" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteSupplierModal" tabindex="-1" aria-labelledby="deleteSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSupplierModalLabel">Delete Supplier</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete supplier <strong id="deleteSupplierName">this supplier</strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteSupplierBtn">Delete Supplier</button>
                </div>
            </div>
        </div>
    </div>

        <?php
        // Close layout (footer, scripts, closing tags)
        include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
        <script src="<?= LEGACY_BASE_URL ?>/public/js/suppliers_script.js?v=<?= filemtime(LEGACY_BASE_PATH . '/public/js/suppliers_script.js') ?>"></script>





