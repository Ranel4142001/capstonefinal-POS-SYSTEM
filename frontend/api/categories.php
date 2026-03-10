<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// api/categories.php
header('Content-Type: application/json'); // Set header for JSON response

// Include common utility functions (for get_db_connection and sanitize_input)
require_once LEGACY_BASE_PATH . '/includes/functions.php';

// Initialize database connection
try {
    $conn = get_db_connection();
} catch (mysqli_sql_exception $e) {
    // If DB connection fails, send an error response and exit
    error_log("Database connection error in api/categories.php: " . $e->getMessage());
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
        $categories = [];
        try {
            // Prepare and execute statement to fetch all categories, ordered by name
            $stmt = $conn->prepare("SELECT id, name, description FROM categories ORDER BY name ASC");
            $stmt->execute();
            $result = $stmt->get_result(); // Get the result set

            // Fetch all rows as associative arrays
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            $stmt->close(); // Close the statement

            echo json_encode($categories); // Return categories as JSON
        } catch (mysqli_sql_exception $e) {
            // Handle database query errors
            error_log("Error fetching categories: " . $e->getMessage()); // Log error for debugging
            echo json_encode(['status' => 'error', 'message' => 'Error fetching category list.']);
        }
        break;

    case 'add': // Renamed from add_category for consistency with JS
        // Get and sanitize input data from POST request
        $category_name = sanitize_input($_POST['category_name'] ?? '');
        $category_description = sanitize_input($_POST['category_description'] ?? '');

        // Validate required fields
        if (empty($category_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
            break;
        }

        try {
            // Prepare and execute statement to insert a new category
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $category_name, $category_description);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Category added successfully!']);
            } else {
                error_log("Error adding category: " . $stmt->error); // Log MySQLi error
                echo json_encode(['status' => 'error', 'message' => 'Error adding category.']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error adding category: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during add operation.']);
        }
        break;

    case 'edit': // Renamed from edit_category for consistency with JS
        // Get and sanitize input data, including category_id for update
        $category_id = (int)($_POST['category_id'] ?? 0);
        $category_name = sanitize_input($_POST['category_name'] ?? '');
        $category_description = sanitize_input($_POST['category_description'] ?? '');

        // Validate required fields and ID
        if ($category_id <= 0 || empty($category_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data or missing category ID/name for edit.']);
            break;
        }

        try {
            // Prepare and execute statement to update an existing category
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $category_name, $category_description, $category_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Category updated successfully!']);
            } else {
                error_log("Error updating category: " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Error updating category.']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error updating category: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during update operation.']);
        }
        break;

    case 'delete': // Renamed from delete_category for consistency with JS
        // Get category_id for deletion
        $category_id = (int)($_POST['category_id'] ?? 0);

        // Validate ID
        if ($category_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid category ID for delete.']);
            break;
        }

        try {
            // Prepare and execute statement to delete a category
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $category_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully!']);
            } else {
                error_log("Error deleting category: " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Error deleting category.']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error deleting category: " . $e->getMessage());
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


