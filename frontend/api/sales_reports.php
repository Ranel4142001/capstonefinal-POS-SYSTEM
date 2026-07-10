<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// api/sales_reports.php
// This API endpoint provides sales data for reports.

header('Content-Type: application/json'); // Ensure the response is JSON

// Include necessary files for database connection and input sanitization.
require_once LEGACY_BASE_PATH . '/includes/functions.php';

// Start session to access user role and potentially for security context.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

function refunds_table_exists(mysqli $conn): bool
{
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    $result = $conn->query("SHOW TABLES LIKE 'refunds'");
    if ($result === false) {
        throw new mysqli_sql_exception("Failed to check refunds table: " . $conn->error);
    }

    $exists = $result->num_rows > 0;
    $result->free();

    return $exists;
}

function ensure_refunds_table_exists(mysqli $conn): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS refunds (
            id INT(11) NOT NULL AUTO_INCREMENT,
            sale_id INT(11) NOT NULL,
            reason VARCHAR(255) NOT NULL,
            amount_returned DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            processed_by_user_id INT(11) DEFAULT NULL,
            refund_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_refunds_sale_id (sale_id),
            KEY idx_refunds_refund_date (refund_date),
            KEY idx_refunds_processed_by_user_id (processed_by_user_id),
            CONSTRAINT fk_refunds_sale_id FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
            CONSTRAINT fk_refunds_processed_by_user FOREIGN KEY (processed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    if ($conn->query($sql) === false) {
        throw new mysqli_sql_exception("Failed to ensure refunds table exists: " . $conn->error);
    }
}

function get_latest_refund(mysqli $conn, int $sale_id): ?array
{
    if (!refunds_table_exists($conn)) {
        return null;
    }

    $refund_sql = "
        SELECT
            r.reason,
            r.amount_returned,
            r.refund_date,
            r.processed_by_user_id,
            u.username AS processed_by_username
        FROM refunds r
        LEFT JOIN users u ON r.processed_by_user_id = u.id
        WHERE r.sale_id = ?
        ORDER BY r.refund_date DESC, r.id DESC
        LIMIT 1;
    ";

    $refund_stmt = $conn->prepare($refund_sql);
    if (!$refund_stmt) {
        throw new mysqli_sql_exception("Failed to prepare refund lookup query: " . $conn->error);
    }

    $refund_stmt->bind_param("i", $sale_id);
    $refund_stmt->execute();
    $refund_result = $refund_stmt->get_result();
    $refund = $refund_result->fetch_assoc() ?: null;
    $refund_stmt->close();

    return $refund;
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

    case 'list_transactions':
        $transaction_data = [];
        $total_transaction_count = 0;

        try {
            $count_sql = "
                SELECT COUNT(s.id) AS total_transaction_count
                FROM sales s
                WHERE s.sale_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY);
            ";
            $count_stmt = $conn->prepare($count_sql);
            if ($count_stmt) {
                $count_stmt->bind_param("ss", $start_date, $end_date);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $count_row = $count_result->fetch_assoc();
                $total_transaction_count = $count_row['total_transaction_count'] ?? 0;
                $count_stmt->close();
            } else {
                throw new mysqli_sql_exception("Failed to prepare transaction count query: " . $conn->error);
            }

            $sql = "
                SELECT
                    s.id AS sale_id,
                    s.sale_date,
                    s.total_amount,
                    s.status,
                    u.username AS cashier_name
                FROM sales s
                JOIN users u ON s.cashier_id = u.id
                WHERE s.sale_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
                ORDER BY s.sale_date DESC
                LIMIT ? OFFSET ?;
            ";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssii", $start_date, $end_date, $records_per_page, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $transaction_data[] = $row;
                }

                $stmt->close();
            } else {
                throw new mysqli_sql_exception("Database query preparation failed: " . $conn->error);
            }

            echo json_encode([
                'status' => 'success',
                'data' => $transaction_data,
                'pagination' => [
                    'total_records' => $total_transaction_count,
                    'records_per_page' => $records_per_page,
                    'current_page' => $current_page,
                    'total_pages' => ceil($total_transaction_count / $records_per_page)
                ]
            ]);

        } catch (mysqli_sql_exception $e) {
            error_log("Database error fetching transaction history: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error fetching transaction history.']);
        }
        break;

    case 'get_transaction_details':
        $sale_id = isset($_REQUEST['sale_id']) && is_numeric($_REQUEST['sale_id']) ? (int) $_REQUEST['sale_id'] : 0;

        if ($sale_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid transaction selected.']);
            break;
        }

        try {
            $transaction = null;
            $items = [];
            $refund = null;

            $transaction_sql = "
                SELECT
                    s.id AS sale_id,
                    s.sale_date,
                    s.total_amount,
                    s.discount_amount,
                    s.tax_amount,
                    s.payment_method,
                    s.cash_received,
                    s.change_due,
                    s.status,
                    u.username AS cashier_name
                FROM sales s
                JOIN users u ON s.cashier_id = u.id
                WHERE s.id = ?
                LIMIT 1;
            ";

            $transaction_stmt = $conn->prepare($transaction_sql);
            if ($transaction_stmt) {
                $transaction_stmt->bind_param("i", $sale_id);
                $transaction_stmt->execute();
                $transaction_result = $transaction_stmt->get_result();
                $transaction = $transaction_result->fetch_assoc();
                $transaction_stmt->close();
            } else {
                throw new mysqli_sql_exception("Failed to prepare transaction detail query: " . $conn->error);
            }

            if (!$transaction) {
                echo json_encode(['status' => 'error', 'message' => 'Transaction not found.']);
                break;
            }

            $items_sql = "
                SELECT
                    COALESCE(p.name, CONCAT('Product #', si.product_id)) AS product_name,
                    si.quantity,
                    si.price_at_sale,
                    si.subtotal
                FROM sale_items si
                LEFT JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = ?
                ORDER BY si.id ASC;
            ";

            $items_stmt = $conn->prepare($items_sql);
            if ($items_stmt) {
                $items_stmt->bind_param("i", $sale_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();

                while ($row = $items_result->fetch_assoc()) {
                    $items[] = $row;
                }

                $items_stmt->close();
            } else {
                throw new mysqli_sql_exception("Failed to prepare transaction items query: " . $conn->error);
            }

            $refund = get_latest_refund($conn, $sale_id);

            echo json_encode([
                'status' => 'success',
                'transaction' => $transaction,
                'items' => $items,
                'refund' => $refund
            ]);

        } catch (mysqli_sql_exception $e) {
            error_log("Database error fetching transaction details: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error fetching transaction details.']);
        }
        break;

    case 'refund_transaction':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            break;
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Only administrators can process refunds.']);
            break;
        }

        $sale_id = isset($_POST['sale_id']) && is_numeric($_POST['sale_id']) ? (int) $_POST['sale_id'] : 0;
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $processed_by_user_id = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

        if ($sale_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid transaction selected for refund.']);
            break;
        }

        if ($processed_by_user_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Authentication error. Please log in again.']);
            break;
        }

        if ($reason === '') {
            echo json_encode(['status' => 'error', 'message' => 'Refund reason is required.']);
            break;
        }

        if (strlen($reason) > 255) {
            echo json_encode(['status' => 'error', 'message' => 'Refund reason must be 255 characters or fewer.']);
            break;
        }

        try {
            // Create the audit table before the transaction because DDL causes an implicit commit in MySQL.
            ensure_refunds_table_exists($conn);

            \Illuminate\Support\Facades\DB::beginTransaction();

            $sale = \App\Models\Sale::query()
                ->lockForUpdate()
                ->find($sale_id);

            if (!$sale) {
                throw new RuntimeException('Transaction not found.');
            }

            if (strtolower((string) $sale->status) !== 'completed') {
                throw new RuntimeException('Only completed transactions can be refunded.');
            }

            $existing_refund = \Illuminate\Support\Facades\DB::table('refunds')
                ->where('sale_id', $sale_id)
                ->lockForUpdate()
                ->first();

            if ($existing_refund) {
                throw new RuntimeException('This transaction has already been refunded.');
            }

            $sale_items = \Illuminate\Support\Facades\DB::table('sale_items as si')
                ->join('products as p', 'p.id', '=', 'si.product_id')
                ->selectRaw('si.product_id, SUM(si.quantity) AS quantity_to_restock, COALESCE(p.name, CONCAT(\'Product #\', si.product_id)) AS product_name')
                ->where('si.sale_id', $sale_id)
                ->groupBy('si.product_id', 'p.name')
                ->lockForUpdate()
                ->get();

            if ($sale_items->isEmpty()) {
                throw new RuntimeException('No sale items were found for this transaction.');
            }

            foreach ($sale_items as $item) {
                $product_id = (int) $item['product_id'];
                $quantity_to_restock = (int) $item['quantity_to_restock'];
                $product = \App\Models\Product::query()
                    ->lockForUpdate()
                    ->find($product_id);

                if (!$product) {
                    throw new RuntimeException("Product not found for refund restock (product ID {$product_id}).");
                }

                $new_stock = (int) $product->stock_quantity + $quantity_to_restock;
                $description = sprintf(
                    'Refund restock for Sale #%d (%s, Qty: %d)',
                    $sale_id,
                    $item['product_name'],
                    $quantity_to_restock
                );

                $product->stock_quantity = $new_stock;
                $product->save();

                \Illuminate\Support\Facades\DB::table('stock_history')->insert([
                    'product_id' => $product_id,
                    'quantity_change' => $quantity_to_restock,
                    'current_quantity_after_change' => $new_stock,
                    'change_type' => 'adjustment_in',
                    'change_date' => now(),
                    'user_id' => $processed_by_user_id,
                    'description' => $description,
                ]);
            }

            $sale->status = 'refunded';
            $sale->save();

            $amount_returned = (float) $sale->total_amount;
            \Illuminate\Support\Facades\DB::table('refunds')->insert([
                'sale_id' => $sale_id,
                'reason' => $reason,
                'amount_returned' => $amount_returned,
                'processed_by_user_id' => $processed_by_user_id,
                'refund_date' => now(),
                'created_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Refund processed successfully. Inventory has been restocked.',
                'sale_id' => $sale_id
            ]);

        } catch (Throwable $e) {
            if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
                \Illuminate\Support\Facades\DB::rollBack();
            }
            error_log("Refund processing error: " . $e->getMessage());

            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to process refund right now. Please try again.';

            echo json_encode(['status' => 'error', 'message' => $message]);
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

