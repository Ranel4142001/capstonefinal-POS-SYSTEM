<?php
require_once __DIR__ . '/../includes/bootstrap.php';

include LEGACY_BASE_PATH . '/includes/auth_check.php';
include LEGACY_BASE_PATH . '/includes/layout_start.php';
include LEGACY_BASE_PATH . '/includes/functions.php';

$is_admin = ($_SESSION['role'] === 'admin');
// Optional: Include database connection if directly needed on this page
// require_once LEGACY_BASE_PATH . '/config/db.php';
?>

<div class="container-fluid dashboard-page-content mt-5 pt-3">
    <h2 class="mb-4">Transactions</h2>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Product Scan / Find</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-2">
                        <input
                            type="text"
                            id="barcodeInput"
                            class="form-control form-control-lg"
                            placeholder="Scan barcode or type product name"
                            aria-label="Barcode Input"
                            autocomplete="off"
                            autofocus
                        >
                        <button class="btn btn-primary" type="button" id="lookupProductBtn">Find</button>
                    </div>
                    <div id="productLookupResults" class="list-group d-none" aria-live="polite"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Current Sale (Cart)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="cartItems">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No items in cart yet. Scan a product!</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <h4 class="mb-2">Total: <span id="cartTotal">PHP 0.00</span></h4>
                    <button class="btn btn-success me-2" id="completeSaleBtn">Complete Sale</button>
                    <button class="btn btn-danger" id="clearCartBtn">Clear Cart</button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Sale Details / Payment</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="discountInput" class="form-label">Discount (%)</label>
                        <input type="number" class="form-control" id="discountInput" value="0" min="0" max="100">
                    </div>
                    <div class="mb-3">
                        <label for="taxRateInput" class="form-label">Tax Rate (%)</label>
                        <input type="number" class="form-control" id="taxRateInput" value="12" min="0" step="0.1">
                    </div>
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">Payment Method</label>
                        <select class="form-select" id="paymentMethod">
                            <option value="Cash">Cash</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="GCash">GCash</option>
                        </select>
                    </div>
                    <div id="paymentAmountSection" class="mb-3">
                        <label for="cashReceived" class="form-label" id="paymentAmountLabel">Cash Received</label>
                        <input
                            type="number"
                            class="form-control"
                            id="cashReceived"
                            placeholder="Enter cash received"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                        >
                        <div id="paymentAmountHelpText" class="form-text">Enter the amount received from the customer before completing the sale.</div>
                        <div id="changeDueWrapper" class="mt-2">Change Due: <span id="changeDue">PHP 0.00</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newProductModal" tabindex="-1" role="dialog" aria-labelledby="newProductModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="newProductForm" class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="newProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"> </button>
            </div>

            <div class="modal-body">
                <?php if (!$is_admin): ?>
                    <div class="alert alert-warning" role="alert">
                        You do not have permission to add products. Only administrators can perform this action.
                    </div>
                <?php endif; ?>

                <fieldset <?php echo !$is_admin ? 'disabled' : ''; ?>>
                    <div class="form-group">
                        <label for="newProductBarcode">Barcode</label>
                        <input type="text" id="newProductBarcode" name="barcode" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="newProductName">Product Name</label>
                        <input type="text" id="newProductName" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="newProductCategory">Category</label>
                        <select id="newProductCategory" name="category_id" class="form-control" required>
                            <option value="" disabled selected>Select a category</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="newProductPrice">Selling Price</label>
                        <input type="number" id="newProductPrice" name="price" class="form-control" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="newProductCostPrice">Cost Price</label>
                        <input type="number" id="newProductCostPrice" name="cost_price" class="form-control" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="newProductStock">Initial Stock</label>
                        <input type="number" id="newProductStock" name="stock_quantity" class="form-control" required>
                    </div>
                </fieldset>
            </div>

            <div class="modal-footer">
                <?php if ($is_admin): ?>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="overlay" id="overlay"></div>

<div class="modal fade" id="heldSalesModal" tabindex="-1" aria-labelledby="heldSalesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="heldSalesModalLabel">Held Sales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Timestamp</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="heldSalesList">
                            <tr><td colspan="5" class="text-center text-muted">No sales on hold.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="posConfirmationModal" tabindex="-1" aria-labelledby="posConfirmationModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="posConfirmationModalTitle">Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="posConfirmationModalBody">
                Are you sure you want to continue?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="posConfirmationModalCancelBtn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="posConfirmationModalConfirmBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div id="receipt-template" style="display: none;">
    <div>
        <h3>MITZIKIKAY GENERAL MERCHANDISE</h3>
        <p>Borbon, Cebu City, Philippines</p>
        <p>Contact: 09301071994</p>
        <p>VAT Reg. TIN: 123-456-789-00000</p>
    </div>
    <hr>
    <p>Date: <span id="receipt-date"></span></p>
    <p>Sale ID: <span id="receipt-sale-id"></span></p>
    <p>Cashier: <span id="receipt-cashier"><?php echo htmlspecialchars($_SESSION["username"]); ?></span></p>
    <hr>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody id="receipt-items">
        </tbody>
    </table>
    <hr>

    <div>
        <p>Subtotal: <span id="receipt-subtotal"></span></p>
        <p>Discount (<span id="receipt-discount-percent"></span>%): <span id="receipt-discount-amount"></span></p>
        <p>Tax (<span id="receipt-tax-percent"></span>%): <span id="receipt-tax-amount"></span></p>
        <h4>Grand Total: <span id="receipt-grand-total"></span></h4>
    </div>
    <div>
        <p>Payment Method: <span id="receipt-payment-method"></span></p>
        <p id="receipt-cash-received-row"><span id="receipt-payment-amount-label">Cash Received</span>: <span id="receipt-cash-received"></span></p>
        <p id="receipt-change-due-row">Change: <span id="receipt-change-due"></span></p>
    </div>
    <hr>
    <div>
        <p>Thank you for your purchase!</p>
        <p>Please come again.</p>
    </div>
</div>

<?php // Close layout (footer, scripts, closing tags)
include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="<?= LEGACY_BASE_URL ?>/public/js/pos_script.js?v=<?= filemtime(LEGACY_BASE_PATH . '/public/js/pos_script.js') ?>"></script>
