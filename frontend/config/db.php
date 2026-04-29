<?php
// capstonefinal/config/db.php

// Basic database connection configuration
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbUser = getenv('DB_USERNAME') ?: 'root';      // CHANGE THIS in production to a dedicated DB user
$dbPass = getenv('DB_PASSWORD') ?: '';          // CHANGE THIS in production to a strong password
$dbName = getenv('DB_DATABASE') ?: 'capstonefinal'; // Your actual database name
$dbTimeout = (int) (getenv('DB_TIMEOUT') ?: 5);

define('DB_SERVER', $dbHost);
define('DB_USERNAME', $dbUser);
define('DB_PASSWORD', $dbPass);
define('DB_NAME', $dbName);

if (!function_exists('respond_with_db_connection_error')) {
    function respond_with_db_connection_error(string $message): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $expectsJson = str_contains($requestUri, '/api/')
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

        http_response_code(500);

        if ($expectsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $message,
            ]);
            exit();
        }

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Database Error</title><style>body{margin:0;font-family:Arial,sans-serif;background:#f6f7fb;color:#1f2937}main{max-width:640px;margin:10vh auto;padding:32px;background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08)}h1{margin-top:0;font-size:28px}p{line-height:1.6}code{background:#eef2ff;padding:2px 6px;border-radius:6px}</style></head><body><main><h1>Database connection failed</h1><p>The application could not connect to MySQL, so the page cannot finish loading.</p><p>Check that your MariaDB/MySQL service is running and that <code>backend/.env</code> has the correct <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, and <code>DB_PASSWORD</code> values.</p><p>Technical detail: ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></main></body></html>';
        exit();
    }
}

// Attempt to connect to MySQL database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_SERVER . ";port=" . $dbPort . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USERNAME,
        DB_PASSWORD,
        [
            PDO::ATTR_TIMEOUT => $dbTimeout,
        ]
    );

    // Set PDO attributes for better error handling, data fetching, and security
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);     // Throw exceptions on errors
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Fetch rows as associative arrays by default
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);            // Disable emulated prepares for true prepared statements

    // Expose PDO to legacy code that uses global $pdo inside function-scoped includes.
    $GLOBALS['pdo'] = $pdo;

} catch (PDOException $e) {
    // Log the detailed error for debugging purposes (e.g., in Apache's error.log)
    error_log("FATAL DB CONNECTION ERROR in db.php: " . $e->getMessage());

    respond_with_db_connection_error('Server error: Database connection failed. Please contact support.');
}

// Set default timezone if it's not already configured in php.ini
// This prevents PHP warnings when using date/time functions like `date()`
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Manila'); // Set to your local timezone (e.g., 'America/New_York', 'Europe/London')
}

/**
 * Retrieves product details by barcode from the database.
 *
 * @param string $barcode The barcode of the product to search for.
 * @return array|false An associative array of product data if found, otherwise false.
 */
function get_product_by_barcode($barcode) {
    global $pdo; // Access the global PDO connection object

    try {
        // Assuming your products table has 'stock_quantity' column for consistency with frontend
        $stmt = $pdo->prepare("SELECT id, name, barcode, price, stock_quantity FROM products WHERE barcode = :barcode LIMIT 1");
        $stmt->execute(['barcode' => $barcode]);
        return $stmt->fetch(); // Will return an associative array due to ATTR_DEFAULT_FETCH_MODE
    } catch (PDOException $e) {
        error_log("Database error in get_product_by_barcode: " . $e->getMessage());
        return false; // Return false or re-throw exception based on desired error handling
    }
}

