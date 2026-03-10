<?php
// includes/sales_analytics_function.php
// Functions to fetch sales analytics data (PDO compatible)

/**
 * Fetches the top selling products by quantity within a given date range.
 * @param PDO $conn The PDO database connection object.
 * @param string $start_date The start date (Y-m-d).
 * @param string $end_date The end date (Y-m-d).
 * @return array Associative array of top products.
 */
function getTopSellingProducts($conn, $start_date, $end_date) {
    $sql = "
        SELECT
            p.name AS product_name,
            SUM(si.quantity) AS total_quantity_sold,
            SUM(si.quantity * si.price_at_sale) AS total_product_sales
        FROM
            sale_items si
        JOIN
            products p ON si.product_id = p.id
        JOIN
            sales s ON si.sale_id = s.id
        WHERE
            s.sale_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        GROUP BY
            p.name
        ORDER BY
            total_quantity_sold DESC
        LIMIT 10;
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches total sales and transaction count grouped by cashier.
 * @param PDO $conn The PDO database connection object.
 * @param string $start_date The start date (Y-m-d).
 * @param string $end_date The end date (Y-m-d).
 * @return array Associative array of sales by cashier.
 */
function getSalesByCashier($conn, $start_date, $end_date) {
    $sql = "
        SELECT
            u.username AS cashier_name,
            SUM(s.total_amount) AS total_sales_by_cashier,
            COUNT(s.id) AS total_transactions
        FROM
            sales s
        JOIN
            users u ON s.cashier_id = u.id
        WHERE
            s.sale_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        GROUP BY
            u.username
        ORDER BY
            total_sales_by_cashier DESC;
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches the daily total sales trend for plotting or reporting.
 * @param PDO $conn The PDO database connection object.
 * @param string $start_date The start date (Y-m-d).
 * @param string $end_date The end date (Y-m-d).
 * @return array Associative array of daily sales trend.
 */
function getDailySalesTrend($conn, $start_date, $end_date) {
    $sql = "
        SELECT
            DATE(sale_date) AS sale_day,
            SUM(total_amount) AS daily_total_sales
        FROM
            sales
        WHERE
            sale_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        GROUP BY
            DATE(sale_date)
        ORDER BY
            sale_day ASC;
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
