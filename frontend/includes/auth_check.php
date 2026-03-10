<?php
require_once __DIR__ . '/bootstrap.php';

// Check login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: " . LEGACY_BASE_URL . "/login");
    exit;
}
?>

