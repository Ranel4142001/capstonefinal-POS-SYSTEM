<?php
require_once __DIR__ . '/../includes/bootstrap.php';

        include LEGACY_BASE_PATH . '/includes/auth_check.php';
        include LEGACY_BASE_PATH . '/includes/layout_start.php';
        include LEGACY_BASE_PATH . '/includes/functions.php';

?>

<div class="container-fluid dashboard-page-content mt-5 pt-3">
    <h1 class="mb-4">Mitzikikay General Merchandise Point Of Sale Dashboard!</h1>
    <p>This dashboard provides an overview of today's sales, low stock items, and your total products.</p>

    <div class="row">
        <!-- Today's Sales Card -->
        <div class="col-md-4">
            <div class="card text-center mb-3">
                <div class="card-body">
                    <h5 class="card-title">Today's Sales</h5>
                    <p class="card-text fs-2">₱ <span id="todaySalesAmount">0.00</span></p>
                    <a href="pos_system.php" class="btn btn-primary">Go to Sales</a>
                </div>
            </div>
        </div>

        <!-- Low Stock Items Card -->
        <div class="col-md-4">
            <div class="card text-center mb-3">
                <div class="card-body">
                    <h5 class="card-title">Low Stock Items</h5>
                    <p class="card-text fs-2"><span id="lowStockCount">0</span></p>
                    <a href="inventory.php" class="btn btn-warning">View Inventory</a>
                </div>
            </div>
        </div>

        <!-- Total Products Card -->
        <div class="col-md-4">
            <div class="card text-center mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Products</h5>
                    <p class="card-text fs-2"><span id="totalProductsCount">0</span></p>
                    <a href="inventory.php" class="btn btn-info">Manage Products</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Close layout (footer, scripts, closing tags)
include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
<script src="<?= LEGACY_BASE_URL ?>/public/js/dashboard_script.js"></script>


