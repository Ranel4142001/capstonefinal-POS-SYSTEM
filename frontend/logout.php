<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Unset all of the session variables
$_SESSION = [];

// Destroy the session cookie if applicable
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Destroy the session.
session_destroy();

// Start a new session to store the logout message
session_start();
$_SESSION['logout_success'] = "You have been successfully logged out.";

// Redirect to login page
header("Location: " . LEGACY_BASE_URL . "/login");
exit;
?>

