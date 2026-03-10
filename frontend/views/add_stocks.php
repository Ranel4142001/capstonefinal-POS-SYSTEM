<?php
require_once __DIR__ . '/../includes/bootstrap.php';
        include LEGACY_BASE_PATH . '/includes/auth_check.php';
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

        </div>
    </div>
</div>

<?php
        // Close layout (footer, scripts, closing tags)
        include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
<script src="//kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="<?= LEGACY_BASE_URL ?>/public/js/add_stocks_script.js"></script>
</body>
</html>


