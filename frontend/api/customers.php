<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// api/customers.php
header('Content-Type: application/json'); // Set header for JSON response

// Include common utility functions (for get_db_connection and sanitize_input)
require_once LEGACY_BASE_PATH . '/includes/functions.php';

// Initialize database connection
try {
    $conn = get_db_connection();
} catch (mysqli_sql_exception $e) {
    // If DB connection fails, send an error response and exit
    echo json_encode(['status' => 'error', 'message' => 'Database connection error.']);
    exit();
}

// Determine the action based on request method and parameters
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'list':
        $customers = [];
        try {
            // Prepare and execute statement to fetch all customers, ordered by last name
            $stmt = $conn->prepare("SELECT customer_id, first_name, last_name, contact_number, email, address FROM customers ORDER BY last_name ASC");
            $stmt->execute();
            $result = $stmt->get_result(); // Get the result set

            // Fetch all rows as associative arrays
            while ($row = $result->fetch_assoc()) {
                $customers[] = $row;
            }
            $stmt->close(); // Close the statement

            echo json_encode($customers); // Return customers as JSON
        } catch (mysqli_sql_exception $e) {
            // Handle database query errors
            error_log("Error fetching customers: " . $e->getMessage()); // Log error for debugging
            echo json_encode(['status' => 'error', 'message' => 'Error fetching customer list.']);
        }
        break;

    case 'add':
        // Get and sanitize input data from POST request
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $contact_number = sanitize_input($_POST['contact_number'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');

        // Validate required fields
        if (empty($first_name) || empty($last_name)) {
            echo json_encode(['status' => 'error', 'message' => 'First name and Last name are required.']);
            break;
        }

        try {
            // Prepare and execute statement to insert a new customer
            $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, contact_number, email, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $first_name, $last_name, $contact_number, $email, $address);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Customer added successfully!']);
            } else {
                error_log("Error adding customer: " . $stmt->error); // Log MySQLi error
                echo json_encode(['status' => 'error', 'message' => 'Error adding customer.']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error adding customer: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during add operation.']);
        }
        break;

    case 'edit':
        // Get and sanitize input data, including customer_id for update
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $contact_number = sanitize_input($_POST['contact_number'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');

        // Validate required fields and ID
        if ($customer_id <= 0 || empty($first_name) || empty($last_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data or missing customer ID/name for edit.']);
            break;
        }

        try {
            // Prepare and execute statement to update an existing customer
            $stmt = $conn->prepare("UPDATE customers SET first_name = ?, last_name = ?, contact_number = ?, email = ?, address = ? WHERE customer_id = ?");
            $stmt->bind_param("sssssi", $first_name, $last_name, $contact_number, $email, $address, $customer_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Customer updated successfully!']);
            } else {
                error_log("Error updating customer: " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Error updating customer.']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error updating customer: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during update operation.']);
        }
        break;

    case 'delete':
        // Get customer_id for deletion
        $customer_id = (int)($_POST['customer_id'] ?? 0);

        // Validate ID
        if ($customer_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID for delete.']);
            break;
        }

        try {
            // Prepare and execute statement to delete a customer
            $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
            $stmt->bind_param("i", $customer_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Customer deleted successfully!']);
            } else {
                error_log("Error deleting customer: " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Error deleting customer.']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error deleting customer: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during delete operation.']);
        }
        break;

    default:
        // This is the fallback if no valid action is provided
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        break;
}

// Close the database connection at the end of the script execution
$conn->close();
?>


