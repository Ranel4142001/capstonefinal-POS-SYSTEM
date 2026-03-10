<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// api/users.php
// This file handles API requests for user management (CRUD operations).

header('Content-Type: application/json'); // Set header for JSON response

// Include common utility functions (for get_db_connection and sanitize_input).
require_once LEGACY_BASE_PATH . '/includes/functions.php';

// Initialize database connection.
try {
    $conn = get_db_connection();
} catch (mysqli_sql_exception $e) {
    // Log database connection error for debugging.
    error_log("Database connection error in api/users.php: " . $e->getMessage());
    // Send a generic error message to the client.
    echo json_encode(['status' => 'error', 'message' => 'Database connection error.']);
    exit(); // Terminate script execution.
}

// Determine the action based on the request method and parameters.
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'list':
        $users = [];
        try {
            $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY username ASC");
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $stmt->close();

            echo json_encode($users);
        } catch (mysqli_sql_exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error fetching user list.']);
        }
        break;

    case 'add':
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email = sanitize_input($_POST['email'] ?? '');
        $role = sanitize_input($_POST['role'] ?? '');

        if (empty($username) || empty($password) || empty($role)) {
            echo json_encode(['status' => 'error', 'message' => 'Username, password, and role cannot be empty.']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
            break;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Username or email already exists.']);
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    echo json_encode(['status' => 'success', 'message' => "User '{$username}' added successfully!"]);
                } else {
                    error_log("Add user executed but no row inserted. Error: " . $stmt->error);
                    echo json_encode(['status' => 'error', 'message' => 'Error adding user (no rows affected).']);
                }
                $stmt->close();
            }
            $stmt_check->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error adding user: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during add operation.']);
        }
        break;

    case 'edit':
        $user_id = (int)($_POST['user_id'] ?? 0);
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email = sanitize_input($_POST['email'] ?? '');
        $role = sanitize_input($_POST['role'] ?? '');

        if ($user_id <= 0 || empty($username) || empty($role)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user ID, username, or role.']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
            break;
        }

        try {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt_check->bind_param("ssi", $username, $email, $user_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Username or email already exists for another user.']);
            } else {
                $sql = "UPDATE users SET username = ?, email = ?, role = ?";
                $params = "sss";
                $values = [$username, $email, $role];

                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql .= ", password_hash = ?";
                    $params .= "s";
                    $values[] = $hashed_password;
                }

                $sql .= " WHERE id = ?";
                $params .= "i";
                $values[] = $user_id;

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($params, ...$values);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'User updated successfully!']);
                } else {
                    error_log("Error updating user: " . $stmt->error);
                    echo json_encode(['status' => 'error', 'message' => 'Error updating user.']);
                }
                $stmt->close();
            }
            $stmt_check->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error updating user: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during update operation.']);
        }
        break;

    case 'delete':
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($user_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user ID for delete.']);
            break;
        }

        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
            break;
        }

        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'User deleted successfully!']);
            } else {
                error_log("Error deleting user: " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Error deleting user.']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error deleting user: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during delete operation.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        break;
}

// Close the database connection
$conn->close();
?>


