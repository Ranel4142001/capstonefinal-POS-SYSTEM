<?php
require_once __DIR__ . '/../includes/bootstrap.php';
        include LEGACY_BASE_PATH . '/includes/auth_check.php';
        include LEGACY_BASE_PATH . '/includes/layout_start.php';
        include LEGACY_BASE_PATH . '/includes/functions.php';

// Define the base URL path for consistency in includes
$base_url_path = LEGACY_BASE_URL;


?>


        <div class="container-fluid dashboard-page-content mt-5 pt-3 text-center">
            <div class="py-5">
                <h1 class="display-1 fw-bold text-dark">404</h1>
                <h2 class="display-4 mb-4">Page Not Found</h2>
                <p class="lead mb-4">The page you're looking for doesn't exist or has been moved.</p>
                <a href="<?php echo $base_url_path; ?>/views/dashboard.php" class="btn btn-primary btn-lg mt-3">Go to Dashboard</a>
                <a href="<?php echo $base_url_path; ?>/login" class="btn btn-secondary btn-lg mt-3 ms-2">Go to Login</a>
            </div>
        </div>
    </div>

<?php
// Close layout (footer, scripts, closing tags)
include LEGACY_BASE_PATH . '/includes/layout_end.php';
?>

    



