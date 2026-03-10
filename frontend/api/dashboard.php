<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/../config/db.php';

// Set header for JSON response
header('Content-Type: application/json');

global $pdo;

try {
    // --- Today's Sales ---
    // Assuming 'sales' table has 'total_amount' and 'sale_date' columns
    // And 'sale_date' is a DATE or DATETIME column.
    $today = date('Y-m-d');
    $stmtSales = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE DATE(sale_date) = :today");
    $stmtSales->bindParam(':today', $today);
    $stmtSales->execute();
    $todaySales = $stmtSales->fetchColumn();
    $todaySales = $todaySales ? $todaySales : 0.00; // Default to 0.00 if no sales

    // --- Low Stock Items ---
    // Assuming 'products' table has 'stock_quantity' and a 'low_stock_threshold' column (or a predefined threshold)
    $lowStockThreshold = 10; // You can define your threshold here
    $stmtLowStock = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock_quantity <= :threshold");
    $stmtLowStock->bindParam(':threshold', $lowStockThreshold, PDO::PARAM_INT);
    $stmtLowStock->execute();
    $lowStockItems = $stmtLowStock->fetchColumn();

    // --- Total Products ---
    // Assuming 'products' table has product entries
    $stmtTotalProducts = $pdo->prepare("SELECT COUNT(*) FROM products");
    $stmtTotalProducts->execute();
    $totalProducts = $stmtTotalProducts->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'today_sales' => number_format($todaySales, 2, '.', ','), // Format as currency
            'low_stock_items' => $lowStockItems,
            'total_products' => $totalProducts
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    http_response_code(500); // Internal Server Error
}
?>

