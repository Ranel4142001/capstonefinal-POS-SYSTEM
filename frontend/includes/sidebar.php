<?php
$base_url_path = LEGACY_BASE_URL; // Base URL for legacy routes
?>

<!-- Sidebar -->
<div class="sidebar bg-dark-theme" id="sidebar">
    <div class="sidebar-header">
        <h3 class="m-0 text-light">POS SYSTEM</h3>
        <button id="closeSidebar" class="close-sidebar-btn text-light" aria-label="Close sidebar">&times;</button>
    </div>

    <ul class="sidebar-menu">
        <!-- Dashboard -->
        <li>
            <a href="<?= $base_url_path; ?>/views/dashboard.php" class="sidebar-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>

        <!-- Point of Sale -->
        <li>
            <a href="<?= $base_url_path; ?>/views/pos_system.php" class="sidebar-link">
                <i class="fas fa-cash-register"></i> Point of Sale
            </a>
        </li>

        <li>
            <a href="<?= $base_url_path; ?>/views/transaction_history.php" class="sidebar-link">
                <i class="fas fa-receipt"></i> Transaction History
            </a>
        </li>

        <!-- Inventory Management -->
        <li class="sidebar-dropdown">
            <a href="#inventoryManagementSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="sidebar-link dropdown-toggle">
                <i class="fas fa-warehouse"></i> Inventory Management
            </a>
            <ul class="collapse list-unstyled" id="inventoryManagementSubmenu">
                <li>
                    <a href="<?= $base_url_path; ?>/views/inventory.php" class="sidebar-link submenu-link">
                        <i class="fas fa-box-open"></i> Product List
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url_path; ?>/views/categories.php" class="sidebar-link submenu-link">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li>
                    <a href="<?= $base_url_path; ?>/views/add_stocks.php" class="sidebar-link submenu-link">
                        <i class="fas fa-boxes"></i> Stock-In / Receive Stock
                    </a>
                </li>
            </ul>
        </li>

        <!-- Reports -->
        <li class="menu-heading">Reports</li>
        <li>
            <a href="<?= $base_url_path; ?>/views/detailed_sales_report.php" class="sidebar-link">
                <i class="fas fa-file-invoice-dollar"></i> Sales Summary Report
            </a>
        </li>
        <li>
            <a href="<?= $base_url_path; ?>/views/stock_report.php" class="sidebar-link">
                <i class="fas fa-boxes"></i> Inventory Report
            </a>
        </li>
        <li>
            <a href="<?= $base_url_path; ?>/views/sales_analytics.php" class="sidebar-link">
                <i class="fas fa-chart-pie"></i> Analytics
            </a>
        </li>

        <!-- Administration -->
        <li class="menu-heading">Administration</li>
        <li>
            <a href="<?= $base_url_path; ?>/views/customers.php" class="sidebar-link">
                <i class="fas fa-user-friends"></i> Customers
            </a>
        </li>
        <li>
            <a href="<?= $base_url_path; ?>/views/suppliers.php" class="sidebar-link">
                <i class="fas fa-truck"></i> Suppliers
            </a>
        </li>
        <li>
            <a href="<?= $base_url_path; ?>/views/user_management.php" class="sidebar-link">
                <i class="fas fa-users"></i> User Management
            </a>
        </li>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <li>
                <a href="<?= $base_url_path; ?>/views/audit_trail.php" class="sidebar-link">
                    <i class="fas fa-clipboard-list"></i> Audit Trail
                </a>
            </li>
        <?php endif; ?>

        <!-- Logout -->
        <li class="menu-item-bottom">
            <a href="<?= $base_url_path; ?>/logout" class="sidebar-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>

