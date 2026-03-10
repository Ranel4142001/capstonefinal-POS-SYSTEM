<?php
// includes/stock_report_function.php
// UPDATED FOR PDO COMPATIBILITY

/**
 * Function to get total number of products (for pagination).
 * @param PDO $conn The PDO database connection object.
 * @return int Total count of active/inactive products.
 */
function getTotalProductCount($conn) {
    try {
        // PDO syntax for simple queries
        $sql = "SELECT COUNT(id) AS total_count FROM products";
        $stmt = $conn->query($sql);
        
        // Check if result is valid and fetch the count using PDO method
        if ($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['total_count'];
        }
    } catch (PDOException $e) {
        // Log error instead of throwing a massive error on a simple count
        error_log("Database error in getTotalProductCount (PDO): " . $e->getMessage());
    }
    return 0;
}

/**
 * Function to get stock data from the database.
 * The quantity displayed will be AS OF the $asOfDateTime.
 * @param PDO $conn The PDO database connection object.
 * @param int $limit The maximum number of records to return.
 * @param int $offset The starting record offset for pagination.
 * @param string $sort_order The column and direction to sort by.
 * @param string $asOfDateTime The date and time (Y-m-d H:i:s) to check stock against.
 * @return array The fetched stock data.
 * @throws Exception If the prepared statement fails or executes incorrectly.
 */
function getStockDataAsOfDate($conn, $limit, $offset, $sort_order, $asOfDateTime) {
    $orderByClause = "";
    switch ($sort_order) {
        case 'quantity_asc':
            $orderByClause = "calculated_quantity ASC, p.name ASC";
            break;
        case 'quantity_desc':
            $orderByClause = "calculated_quantity DESC, p.name ASC";
            break;
        case 'name_asc':
        default:
            $orderByClause = "p.name ASC";
            break;
    }

    $sql = "
        SELECT
            p.id AS product_id,
            p.name AS product_name,
            c.name AS category_name,
            s.name AS supplier_name,
            COALESCE(
                (
                    SELECT sh.current_quantity_after_change
                    FROM stock_history sh
                    WHERE sh.product_id = p.id
                      AND sh.change_date <= ?
                    ORDER BY sh.change_date DESC, sh.id DESC
                    LIMIT 1
                ),
                0
            ) AS calculated_quantity,
            p.cost_price,
            p.price AS selling_price,
            p.is_active AS status
        FROM
            products p
        LEFT JOIN
            categories c ON p.category_id = c.id
        LEFT JOIN
            suppliers s ON p.supplier_id = s.id
        ORDER BY
            " . $orderByClause . "
        LIMIT ? OFFSET ?";

    try {
        // 1. Prepare the PDO statement
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare PDO statement.");
        }

        // 2. Execute the statement with parameters
        // The parameters are passed in the order of the placeholders (?)
        // PDO correctly handles LIMIT/OFFSET as integers when passed in execute
        $stmt->execute([
            $asOfDateTime, // Bound to the change_date <= ? (string/datetime)
            $limit,        // Bound to the LIMIT ? (integer)
            $offset        // Bound to the OFFSET ? (integer)
        ]);

        // 3. Fetch all results
        // Use fetchAll() to get all rows as an associative array
        $stockData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stockData;

    } catch (PDOException $e) {
        // Log the detailed error
        error_log("Database error in getStockDataAsOfDate (PDO): " . $e->getMessage()); 
        // Throw a general exception for the calling script (stock_report.php) to catch
        throw new Exception("Error fetching stock data: " . $e->getMessage());
    }
}
