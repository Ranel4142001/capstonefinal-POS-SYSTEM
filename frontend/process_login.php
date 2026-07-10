<?php
// capstonefinal/process_login.php
require_once __DIR__ . '/includes/bootstrap.php';

// It's good practice to include init.php if it handles session_start()
// If init.php already calls session_start(), you don't need it here.
// Assuming init.php handles session_start() and is in config/
// require_once 'config/init.php'; // Uncomment if init.php handles session_start()
// Session handled by bootstrap

require_once LEGACY_BASE_PATH . '/config/db.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Input validation
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both username and password.";
        header("Location: " . LEGACY_BASE_URL . "/login");
        exit;
    }

    $sql = "SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1";

    try {
        global $pdo; // Ensure $pdo is available from db.php

        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $param_username = $username;

            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { // Fetch as associative array
                        $id = $row["id"];
                        $username = $row["username"];
                        $hashed_password = $row["password_hash"];
                        $role = $row["role"];

                        // Verify password
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session (already done by session_start())
                            session_regenerate_id(true); // Regenerate session ID for security

                            $_SESSION["loggedin"] = true;
                            // *** CRUCIAL FIX: Change $_SESSION["id"] to $_SESSION["user_id"] ***
                            $_SESSION["user_id"] = $id; // This matches what complete_sale.php expects
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;

                            // Update last login time
                            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
                            $update_stmt = $pdo->prepare($update_sql);
                            $update_stmt->bindParam(":id", $id, PDO::PARAM_INT);
                            $update_stmt->execute();

                            // Redirect to the POS dashboard or system
                            header("Location: " . LEGACY_BASE_URL . "/views/dashboard.php"); // Or /views/pos_system.php as needed
                            exit;
                        } else {
                            $_SESSION['login_error'] = "Invalid username or password.";
                        }
                    }
                } else {
                    $_SESSION['login_error'] = "Invalid username or password.";
                }
            } else {
                $_SESSION['login_error'] = "Oops! Something went wrong. Please try again later.";
            }

            $stmt = null; // Close statement
        }
    } catch (PDOException $e) {
        // Log the actual database error for debugging purposes
        error_log("Login PDO Error: " . $e->getMessage());
    $_SESSION['login_error'] = "A database error occurred. Please try again later.";
    }
}

// If login failed or accessed directly (not via POST)
header("Location: " . LEGACY_BASE_URL . "/login");
exit;
?>

