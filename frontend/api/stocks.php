<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// api/stocks.php
// This API endpoint handles operations related to product stock management.

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once LEGACY_BASE_PATH . '/includes/functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden.']);
    exit();
}

try {
    $conn = get_db_connection();
} catch (mysqli_sql_exception $e) {
    error_log('Database connection error in api/stocks.php: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list_products':
        listProducts($conn);
        break;

    case 'add_stock':
        addStock($conn);
        break;

    case 'edit_stock':
        editStock($conn);
        break;

    case 'delete_product':
        deleteProduct($conn);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing action.']);
        break;
}

$conn->close();

function listProducts(mysqli $conn): void
{
    $products = [];

    try {
        $sql_products = "
            SELECT
                p.id,
                p.name,
                p.stock_quantity,
                p.price,
                p.barcode,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.name ASC";

        $result_products = $conn->query($sql_products);

        if ($result_products) {
            while ($row = $result_products->fetch_assoc()) {
                $products[] = $row;
            }
            $result_products->free();
            echo json_encode(['status' => 'success', 'data' => $products]);
            return;
        }

        echo json_encode(['status' => 'error', 'message' => 'Error fetching products: ' . $conn->error]);
    } catch (mysqli_sql_exception $e) {
        error_log('Database error fetching products: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error fetching products.']);
    }
}

function addStock(mysqli $conn): void
{
    $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
    $quantity_to_add = filter_var($_POST['quantity_to_add'] ?? null, FILTER_VALIDATE_INT);
    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    if ($product_id === false || $product_id < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product selected.']);
        return;
    }

    if ($quantity_to_add === false || $quantity_to_add < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Quantity to Add must be a positive whole number.']);
        return;
    }

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in. Cannot log stock change.']);
        return;
    }

    try {
        \Illuminate\Support\Facades\DB::beginTransaction();

        $product = \App\Models\Product::query()
            ->lockForUpdate()
            ->find($product_id);

        if ($product === null) {
            \Illuminate\Support\Facades\DB::rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
            return;
        }

        $currentStock = (int) $product->stock_quantity;
        $newStock = $currentStock + $quantity_to_add;

        $product->stock_quantity = $newStock;
        $product->save();

        logStockHistoryUsingDb(
            $product_id,
            $quantity_to_add,
            $newStock,
            'purchase_in',
            $user_id,
            "Stock added manually (via Add Stock form) for product ID: {$product_id}"
        );

        \Illuminate\Support\Facades\DB::commit();

        echo json_encode([
            'status' => 'success',
            'message' => "Stock added successfully. {$product->name} stock updated from {$currentStock} to {$newStock}.",
            'previous_stock' => $currentStock,
            'new_stock' => $newStock,
        ]);
    } catch (Throwable $e) {
        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            \Illuminate\Support\Facades\DB::rollBack();
        }
        error_log('Database error during add stock operation: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error during stock addition.']);
    }
}

function editStock(mysqli $conn): void
{
    $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
    $newStockQuantity = filter_var($_POST['stock_quantity'] ?? null, FILTER_VALIDATE_INT);
    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    if ($product_id === false || $product_id < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product selected.']);
        return;
    }

    if ($newStockQuantity === false || $newStockQuantity < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Stock quantity must be zero or a positive whole number.']);
        return;
    }

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in. Cannot log stock change.']);
        return;
    }

    try {
        \Illuminate\Support\Facades\DB::beginTransaction();

        $product = \App\Models\Product::query()
            ->lockForUpdate()
            ->find($product_id);

        if ($product === null) {
            \Illuminate\Support\Facades\DB::rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
            return;
        }

        $currentStock = (int) $product->stock_quantity;
        $quantityChange = $newStockQuantity - $currentStock;

        $product->stock_quantity = $newStockQuantity;
        $product->save();

        if ($quantityChange !== 0) {
            $changeType = $quantityChange > 0 ? 'adjustment_in' : 'adjustment_out';
            $description = $quantityChange > 0
                ? 'Manual stock increase via Add Stocks page edit'
                : 'Manual stock decrease via Add Stocks page edit';

            logStockHistoryUsingDb(
                $product_id,
                $quantityChange,
                $newStockQuantity,
                $changeType,
                $user_id,
                $description
            );
        }

        \Illuminate\Support\Facades\DB::commit();

        echo json_encode([
            'status' => 'success',
            'message' => "Stock updated successfully. {$product->name} stock changed from {$currentStock} to {$newStockQuantity}.",
            'previous_stock' => $currentStock,
            'new_stock' => $newStockQuantity,
        ]);
    } catch (Throwable $e) {
        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            \Illuminate\Support\Facades\DB::rollBack();
        }
        error_log('Database error during edit stock operation: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error during stock update.']);
    }
}

function deleteProduct(mysqli $conn): void
{
    $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);

    if ($product_id === false || $product_id < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product selected.']);
        return;
    }

    try {
        \Illuminate\Support\Facades\DB::beginTransaction();

        $product = \App\Models\Product::query()
            ->lockForUpdate()
            ->find($product_id);

        if ($product === null) {
            \Illuminate\Support\Facades\DB::rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
            return;
        }

        $productName = $product->name;
        $product->delete();

        \Illuminate\Support\Facades\DB::commit();

        echo json_encode([
            'status' => 'success',
            'message' => "Product \"{$productName}\" deleted successfully."
        ]);
    } catch (Throwable $e) {
        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            \Illuminate\Support\Facades\DB::rollBack();
        }
        error_log('Database error during delete product operation: ' . $e->getMessage());

        if ((int) $e->getCode() === 1451) {
            echo json_encode([
                'status' => 'error',
                'message' => 'This product cannot be deleted because it is already referenced by other records.'
            ]);
            return;
        }

        echo json_encode(['status' => 'error', 'message' => 'Database error during product deletion.']);
    }
}

function getProductForUpdate(mysqli $conn, int $productId): ?array
{
    $stmt = $conn->prepare('SELECT id, name, stock_quantity FROM products WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $product;
}

function logStockHistoryUsingDb(
    int $productId,
    int $quantityChange,
    int $currentQuantityAfterChange,
    string $changeType,
    int $userId,
    string $description
): void {
    \Illuminate\Support\Facades\DB::table('stock_history')->insert([
        'product_id' => $productId,
        'quantity_change' => $quantityChange,
        'current_quantity_after_change' => $currentQuantityAfterChange,
        'change_type' => $changeType,
        'change_date' => now(),
        'user_id' => $userId,
        'description' => $description,
    ]);
}

function logStockHistory(
    mysqli $conn,
    int $productId,
    int $quantityChange,
    int $currentQuantityAfterChange,
    string $changeType,
    int $userId,
    string $description
): void {
    $stmt = $conn->prepare(
        'INSERT INTO stock_history (product_id, quantity_change, current_quantity_after_change, change_type, change_date, user_id, description)
        VALUES (?, ?, ?, ?, NOW(), ?, ?)'
    );
    $stmt->bind_param(
        'iiisis',
        $productId,
        $quantityChange,
        $currentQuantityAfterChange,
        $changeType,
        $userId,
        $description
    );
    $stmt->execute();
    $stmt->close();
}
?>
