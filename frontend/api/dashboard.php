<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

global $pdo;

try {
    $lowStockThreshold = 10;
    $todayStart = date('Y-m-d 00:00:00');
    $tomorrowStart = date('Y-m-d 00:00:00', strtotime('+1 day'));

    $statement = $pdo->prepare("
        SELECT
            COALESCE((
                SELECT SUM(s.total_amount)
                FROM sales s
                WHERE s.sale_date >= :today_start
                  AND s.sale_date < :tomorrow_start
            ), 0) AS today_sales,
            COALESCE((
                SELECT COUNT(*)
                FROM products p
                WHERE p.stock_quantity <= :threshold
            ), 0) AS low_stock_items,
            COALESCE((
                SELECT COUNT(*)
                FROM products p
            ), 0) AS total_products
    ");
    $statement->bindValue(':today_start', $todayStart, PDO::PARAM_STR);
    $statement->bindValue(':tomorrow_start', $tomorrowStart, PDO::PARAM_STR);
    $statement->bindValue(':threshold', $lowStockThreshold, PDO::PARAM_INT);
    $statement->execute();

    $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    $todaySales = (float) ($row['today_sales'] ?? 0);
    $lowStockItems = (int) ($row['low_stock_items'] ?? 0);
    $totalProducts = (int) ($row['total_products'] ?? 0);

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

