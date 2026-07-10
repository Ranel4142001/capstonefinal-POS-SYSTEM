<?php
require_once __DIR__ . '/../includes/bootstrap.php';
        include LEGACY_BASE_PATH . '/includes/auth_check.php';
require_role(['admin']);
        include LEGACY_BASE_PATH . '/includes/layout_start.php';
        include LEGACY_BASE_PATH . '/includes/functions.php';
        

        // Set a flag to check if the user is an admin
        $is_admin = ($_SESSION['role'] === 'admin');


            // Define a low stock threshold (used for display purposes in the table).
                define('LOW_STOCK_THRESHOLD', 10);

                // Only process POST requests if the user is an admin
            if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_admin) {
            // Validate inputs
            // Name
            if (empty(trim($_POST["name"]))) {
                $name_err = "Please Add Products.";
            } else {
                $name = trim($_POST["name"]);
            } }

?>


        <div class="container-fluid dashboard-page-content mt-5 pt-3">
            <h2 class="mb-4">Add Stock to Existing Product</h2>

            <?php if (!$is_admin): ?>
                <div class="alert alert-warning" role="alert">
                    You do not have permission to add stock. Only administrators can perform this action.
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    Add Stock Form
                </div>
                <div class="card-body">
                    <form id="addStockForm">
                        <fieldset <?php echo !$is_admin ? 'disabled' : ''; ?>>
                            <div class="form-group mb-3">
                                <label for="product_id" class="form-label">Product:</label>
                                <select id="product_id" name="product_id" class="form-select" required>
                                    <option value="">Loading Products...</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="quantity_to_add" class="form-label">Quantity to Add:</label>
                                <input type="number" id="quantity_to_add" name="quantity_to_add" class="form-control" value="" required min="1">
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">Add Stock</button>
                        </fieldset>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    Current Product Inventory
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="productInventoryTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Barcode</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7">Loading product inventory...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="editStockForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editStockModalLabel">Edit Stock</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="edit_stock_product_id" name="product_id">

                                <div class="mb-3">
                                    <label for="edit_stock_product_name" class="form-label">Product</label>
                                    <input type="text" id="edit_stock_product_name" class="form-control" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="edit_stock_quantity" class="form-label">New Total Stock</label>
                                    <input type="number" id="edit_stock_quantity" name="stock_quantity" class="form-control" min="0" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteStockModal" tabindex="-1" aria-labelledby="deleteStockModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="deleteStockForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteStockModalLabel">Delete Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="delete_stock_product_id" name="product_id">
                                <p class="mb-0">Are you sure you want to delete <strong id="delete_stock_product_name"></strong>?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
        // Close layout (footer, scripts, closing tags)
        include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
<script src="<?= LEGACY_BASE_URL ?>/public/js/add_stocks_script.js?v=<?= filemtime(LEGACY_BASE_PATH . '/public/js/add_stocks_script.js') ?>"></script>
</body>
</html>



