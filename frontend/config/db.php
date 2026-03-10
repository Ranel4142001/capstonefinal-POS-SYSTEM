<?php
// capstonefinal/config/db.php

// Basic database connection configuration
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbUser = getenv('DB_USERNAME') ?: 'root';      // CHANGE THIS in production to a dedicated DB user
$dbPass = getenv('DB_PASSWORD') ?: '';          // CHANGE THIS in production to a strong password
$dbName = getenv('DB_DATABASE') ?: 'capstonefinal'; // Your actual database name

define('DB_SERVER', $dbHost);
define('DB_USERNAME', $dbUser);
define('DB_PASSWORD', $dbPass);
define('DB_NAME', $dbName);

// Attempt to connect to MySQL database
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . $dbPort . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);

    // Set PDO attributes for better error handling, data fetching, and security
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);     // Throw exceptions on errors
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Fetch rows as associative arrays by default
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);            // Disable emulated prepares for true prepared statements

} catch (PDOException $e) {
    // Log the detailed error for debugging purposes (e.g., in Apache's error.log)
    error_log("FATAL DB CONNECTION ERROR in db.php: " . $e->getMessage());

    // Send a JSON error response to the client
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "success" => false,
        "message" => "Server error: Database connection failed. Please contact support."
    ]);
    exit(); // Crucial: Stop script execution immediately on connection failure
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

