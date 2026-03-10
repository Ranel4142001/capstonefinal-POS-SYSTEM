<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// api/stocks.php
// This API endpoint handles operations related to product stock management.

error_reporting(E_ALL);     // Report all PHP errors
ini_set('display_errors', 1); // Display errors directly in the browser
header('Content-Type: application/json'); // Keep this header to indicate JSON intent
header('Content-Type: application/json'); // Ensure the response is JSON

// Include necessary files for database connection and input sanitization.
require_once LEGACY_BASE_PATH . '/includes/functions.php'; // Contains get_db_connection() and sanitize_input()

// Start session to access user_id for logging stock changes.
if (session_status() == PHP_SESSION_NONE) {
    }

// Initialize database connection.
try {
    $conn = get_db_connection();
} catch (mysqli_sql_exception $e) {
    // Log the error and send a generic message to the client.
    error_log("Database connection error in api/stocks.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

// Determine the requested action.
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list_products':
        // Action to fetch all products for display in the form dropdown and inventory table.
        $products = [];
        try {
            // SQL query to join products with categories to get category names.
            $sql_products = "
                SELECT
                    p.id,
                    p.name,
                    p.stock_quantity,
                    p.price,
                    p.barcode,
                    c.name AS category_name
                FROM
                    products p
                LEFT JOIN
                    categories c ON p.category_id = c.id
                ORDER BY
                    p.name ASC";

            $result_products = $conn->query($sql_products);

            if ($result_products) {
                while ($row = $result_products->fetch_assoc()) {
                    $products[] = $row;
                }
                $result_products->free();
                echo json_encode(['status' => 'success', 'data' => $products]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error fetching products: ' . $conn->error]);
            }
        } catch (mysqli_sql_exception $e) {
            error_log("Database error fetching products: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error fetching products.']);
        }
        break;

    case 'add_stock':
        // Action to add stock to an existing product and log the transaction.
        $product_id = sanitize_input($_POST['product_id'] ?? '');
        $quantity_to_add = sanitize_input($_POST['quantity_to_add'] ?? '');
        $user_id = $_SESSION['user_id'] ?? null; // Get the ID of the logged-in user

        // Basic validation
        if (empty($product_id) || empty($quantity_to_add)) {
            echo json_encode(['status' => 'error', 'message' => 'Both Product and Quantity to Add are required.']);
            break;
        } elseif (!is_numeric($product_id) || $product_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Product selected.']);
            break;
        } elseif (!is_numeric($quantity_to_add) || $quantity_to_add <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Quantity to Add must be a positive number.']);
            break;
        } elseif (!$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'User not logged in. Cannot log stock change.']);
            break;
        } else {
            try {
                $conn->begin_transaction(); // Start a database transaction for atomicity.

                // Step 1: Update products table - increase stock_quantity.
                $stmt_update_product = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ?, updated_at = NOW() WHERE id = ?");
                if ($stmt_update_product) {
                    $stmt_update_product->bind_param("ii", $quantity_to_add, $product_id);

                    if ($stmt_update_product->execute()) {
                        // Step 2: Get the NEW current stock quantity AFTER the update.
                        $stmt_get_new_stock = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                        $stmt_get_new_stock->bind_param("i", $product_id);
                        $stmt_get_new_stock->execute();
                        $result_new_stock = $stmt_get_new_stock->get_result();
                        $new_stock_after_add = $result_new_stock->fetch_assoc()['stock_quantity'];
                        $stmt_get_new_stock->close();

                        // Step 3: Log the stock change to stock_history table.
                        $stmt_log_stock_history = $conn->prepare(
                            "INSERT INTO stock_history (product_id, quantity_change, current_quantity_after_change, change_type, change_date, user_id, description)
                            VALUES (?, ?, ?, ?, NOW(), ?, ?)"
                        );

                        $change_type = 'purchase_in'; // Or 'adjustment_in', 'restock', etc.
                        $description = "Stock added manually (via Add Stock form) for product ID: $product_id";

                        // Bind parameters for stock_history. 'i' for int, 's' for string.
                        $stmt_log_stock_history->bind_param(
                            "iiisss",
                            $product_id,
                            $quantity_to_add, // Positive for addition
                            $new_stock_after_add,
                            $change_type,
                            $user_id, // User ID from session
                            $description
                        );
                        $stmt_log_stock_history->execute();
                        $stmt_log_stock_history->close();

                        $conn->commit(); // Commit the transaction if all steps are successful.
                        echo json_encode(['status' => 'success', 'message' => 'Stock successfully added to product!']);

                    } else {
                        $conn->rollback(); // Rollback if product update fails.
                        echo json_encode(['status' => 'error', 'message' => 'Error adding stock: ' . $stmt_update_product->error]);
                    }
                    $stmt_update_product->close();
                } else {
                    $conn->rollback(); // Rollback if statement preparation fails.
                    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare stock update query: ' . $conn->error]);
                }
            } catch (mysqli_sql_exception $e) {
                $conn->rollback(); // Rollback for any other database exceptions.
                error_log("Database error during add stock operation: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Database error during stock addition.']);
            }
        }
        break;

    default:
        // Default case for invalid or missing action.
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing action.']);
        break;
}

// Close the database connection.
$conn->close();
?>


