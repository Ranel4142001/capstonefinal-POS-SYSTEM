<?php
require_once LEGACY_BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include LEGACY_BASE_PATH . '/includes/sidebar.php'; ?>

    <div class="main-content" id="main-content">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top px-3 custom-navbar-top">
            <div class="container-fluid">
                <button id="sidebarToggle" class="btn btn-outline-light d-lg-none me-3" type="button">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <button id="sidebarToggleDesktop" class="btn btn-outline-light d-none d-lg-block me-3" type="button">
                    <i class="fas fa-bars"></i>
                </button>

                <a class="navbar-brand" href="#">POS System</a>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item d-flex align-items-center">
                            <span class="nav-link text-white me-2">
                                Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>
                                (<?php echo htmlspecialchars($_SESSION["role"]); ?>)
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-danger btn-sm text-white" href="<?= LEGACY_BASE_URL ?>/logout">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid dashboard-page-content mt-5 pt-3">

