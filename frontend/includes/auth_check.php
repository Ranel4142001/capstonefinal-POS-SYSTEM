<?php
require_once __DIR__ . '/bootstrap.php';

// Check login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: " . LEGACY_BASE_URL . "/login");
    exit;
}

if (!function_exists('require_role')) {
    function require_role(array $roles): void {
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true)) {
            header("Location: " . LEGACY_BASE_URL . "/views/dashboard.php");
            exit;
        }
    }
}
?>

