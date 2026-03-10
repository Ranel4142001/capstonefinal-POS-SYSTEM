<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// api/sales_reports.php
// This API endpoint provides sales data for reports.

header('Content-Type: application/json'); // Ensure the response is JSON

// Include necessary files for database connection and input sanitization.
require_once LEGACY_BASE_PATH . '/includes/functions.php';

// Start session to access user role and potentially for security context.
if (session_status() == PHP_SESSION_NONE) {
    }

// Initialize database connection.
try {
    $conn = get_db_connection();
} catch (mysqli_sql_exception $e) {
    // Log database connection error for debugging.
    error_log("Database connection error in api/sales_reports.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

// --- Common Filtering and Pagination Parameters ---
// Sanitize and get date range from GET/POST. Default to current month.
$start_date = sanitize_input($_REQUEST['start_date'] ?? date('Y-m-01'));
$end_date = sanitize_input($_REQUEST['end_date'] ?? date('Y-m-d'));

// Sanitize and get pagination parameters.
$records_per_page = isset($_REQUEST['records_per_page']) && is_numeric($_REQUEST['records_per_page']) ? (int)$_REQUEST['records_per_page'] : 10;
$current_page = isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Determine the requested action.
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list_sales':
        $sales_data = [];
        $total_sales_count = 0;

        try {
            // First, get the total count of sales for the date range for pagination.
            // Using `? + INTERVAL 1 DAY` for `end_date` ensures that sales on the `end_date` itself are included.
            $count_sql = "
                SELECT COUNT(s.id) AS total_sales_count
                FROM sales s
                WHERE s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY;
            ";
            $count_stmt = $conn->prepare($count_sql);
            if ($count_stmt) {
                $count_stmt->bind_param("ss", $start_date, $end_date);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $count_row = $count_result->fetch_assoc();
                $total_sales_count = $count_row['total_sales_count'];
                $count_stmt->close();
            } else {
                throw new mysqli_sql_exception("Failed to prepare sales count query: " . $conn->error);
            }

            // SQL query to fetch sales with LIMIT and OFFSET for pagination.
            // GROUP_CONCAT is used to aggregate items sold per sale into a single string.
            $sql = "
                SELECT
                    s.id AS sale_id,
                    s.sale_date,
                    s.total_amount,
                    u.username AS cashier_name,
                    GROUP_CONCAT(CONCAT(pi.name, ' (Qty: ', si.quantity, ' @ ', si.price_at_sale, ')') SEPARATOR '<br>') AS items_sold
                FROM
                    sales s
                JOIN
                    users u ON s.cashier_id = u.id
                LEFT JOIN
                    sale_items si ON s.id = si.sale_id
                LEFT JOIN
                    products pi ON si.product_id = pi.id
                WHERE
                    s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY
                GROUP BY
                    s.id, s.sale_date, s.total_amount, u.username
                ORDER BY
                    s.sale_date DESC
                LIMIT ? OFFSET ?;
            ";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // 'ssii' for start_date (string), end_date (string), records_per_page (int), offset (int).
                $stmt->bind_param("ssii", $start_date, $end_date, $records_per_page, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $sales_data[] = $row;
                    }
                }
                $stmt->close();
            } else {
                throw new mysqli_sql_exception("Database query preparation failed: " . $conn->error);
            }

            // Return success status, sales data, and pagination info.
            echo json_encode([
                'status' => 'success',
                'data' => $sales_data,
                'pagination' => [
                    'total_records' => $total_sales_count,
                    'records_per_page' => $records_per_page,
                    'current_page' => $current_page,
                    'total_pages' => ceil($total_sales_count / $records_per_page) // Calculate total pages.
                ]
            ]);

        } catch (mysqli_sql_exception $e) {
            error_log("Database error fetching sales data: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error fetching sales data.']);
        }
        break;

    case 'get_summary':
        $total_sales_amount = 0;
        $total_items_sold = 0;

        try {
            // SQL query to fetch overall sales summary for the date range.
            $sql_overall_summary = "
                SELECT
                    SUM(s.total_amount) AS overall_total_amount,
                    SUM(si.quantity) AS overall_total_qty
                FROM
                    sales s
                LEFT JOIN
                    sale_items si ON s.id = si.sale_id
                WHERE
                    s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY;
            ";
            $stmt_overall_summary = $conn->prepare($sql_overall_summary);
            if ($stmt_overall_summary) {
                $stmt_overall_summary->bind_param("ss", $start_date, $end_date);
                $stmt_overall_summary->execute();
                $result_overall_summary = $stmt_overall_summary->get_result();
                if ($result_overall_summary->num_rows > 0) {
                    $summary_row = $result_overall_summary->fetch_assoc();
                    $total_sales_amount = $summary_row['overall_total_amount'] ?? 0;
                    $total_items_sold = $summary_row['overall_total_qty'] ?? 0;
                }
                $stmt_overall_summary->close();
            } else {
                throw new mysqli_sql_exception("Failed to prepare overall summary query: " . $conn->error);
            }

            // Return success status and summary data.
            echo json_encode([
                'status' => 'success',
                'summary' => [
                    'total_sales_amount' => $total_sales_amount,
                    'total_items_sold' => $total_items_sold
                ]
            ]);

        } catch (mysqli_sql_exception $e) {
            error_log("Database error fetching overall summary: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error fetching overall summary.']);
        }
        break;

    default:
        // Default response for invalid or missing action.
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing action.']);
        break;
}

// Close the database connection.
$conn->close();
?>

