<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// api/complete_sale.php

header('Content-Type: application/json');
// Ensure session is started at the very beginning
require_once LEGACY_BASE_PATH . '/config/db.php';
require_once LEGACY_BASE_PATH . '/config/init.php'; // Assuming this handles other necessary initializations

error_log("complete_sale.php: Session user_id is " . ($_SESSION['user_id'] ?? 'NULL'));

// Get raw POST data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data received.']);
    exit();
}

$cart = $data['cart'] ?? [];
$total_amount = $data['total_amount'] ?? 0;
$discount_amount = $data['discount_amount'] ?? 0.00;
$tax_amount = $data['tax_amount'] ?? 0.00;
$payment_method = $data['payment_method'] ?? 'Cash';
$customer_id = $data['customer_id'] ?? null;

if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit();
}

// Ensure $pdo is available from db.php
global $pdo;

$cash_received = $data['cash_received'] ?? 0.00;
$change_due = $data['change_due'] ?? 0.00; // This should now be coming from JS


try {
    $pdo->beginTransaction(); // Start a transaction

    // 1. Insert into sales table
    $cashier_id = $_SESSION['user_id'] ?? null;
    if (!$cashier_id) {
        throw new Exception("Authentication error: Cashier ID not found in session. Please log in.");
    }

    // --- ADD THESE DEBUGGING LINES ---
    error_log("DEBUG: total_amount = " . $total_amount);
    error_log("DEBUG: discount_amount = " . $discount_amount);
    error_log("DEBUG: tax_amount = " . $tax_amount);
    error_log("DEBUG: payment_method = " . $payment_method);
    error_log("DEBUG: cashier_id = " . $cashier_id);
    error_log("DEBUG: customer_id = " . ($customer_id ?? 'NULL'));
    error_log("DEBUG: cash_received = " . $cash_received);
    error_log("DEBUG: change_due = " . $change_due);
    // --- END DEBUGGING LINES ---

    $stmt_sale = $pdo->prepare(
        "INSERT INTO sales (sale_date, total_amount, discount_amount, tax_amount, payment_method, cashier_id, customer_id, cash_received, change_due, status)
        VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt_sale->execute([
        $total_amount,
        $discount_amount,
        $tax_amount,
        $payment_method,
        $cashier_id,
        $customer_id,
        $cash_received,
        $change_due,
        'completed'
    ]);
    $sale_id = $pdo->lastInsertId();

    if (!$sale_id) {
        throw new Exception("Failed to create sale record.");
    }

    // 2. Insert into sale_items table and update product stock, and log stock history
    $stmt_sale_item = $pdo->prepare(
        "INSERT INTO sale_items (sale_id, product_id, quantity, price_at_sale, subtotal)
        VALUES (?, ?, ?, ?, ?)"
    );
    $stmt_update_stock = $pdo->prepare(
        "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?"
    );
    // Prepare statement to get the current stock quantity after update
    $stmt_get_current_stock = $pdo->prepare(
        "SELECT stock_quantity FROM products WHERE id = ?"
    );
    // Prepare statement to log stock history
    $stmt_log_stock_history = $pdo->prepare(
        "INSERT INTO stock_history (product_id, quantity_change, current_quantity_after_change, change_type, change_date, user_id, description)
        VALUES (?, ?, ?, ?, NOW(), ?, ?)"
    );

    foreach ($cart as $item) {
        $product_id = $item['id'];
        $quantity_sold = $item['quantity']; // Renamed for clarity: quantity sold
        $price_at_sale = $item['price'];
        $subtotal = $price_at_sale * $quantity_sold;

        // Insert into sale_items
        $stmt_sale_item->execute([$sale_id, $product_id, $quantity_sold, $price_at_sale, $subtotal]);

        // Update product stock
        $stmt_update_stock->execute([$quantity_sold, $product_id, $quantity_sold]);

        if ($stmt_update_stock->rowCount() === 0) {
            throw new Exception("Insufficient stock or product not found for product ID: $product_id. Transaction aborted.");
        }

        // Get the current stock quantity AFTER the update
        $stmt_get_current_stock->execute([$product_id]);
        $current_stock_after_sale = $stmt_get_current_stock->fetchColumn();

        // Log the stock change in stock_history
        $quantity_change_for_log = -$quantity_sold; // Negative for stock reduction
        $change_type = 'sale_out';
        $description = "Sale (Order ID: $sale_id)";
        $stmt_log_stock_history->execute([
            $product_id,
            $quantity_change_for_log,
            $current_stock_after_sale,
            $change_type,
            $cashier_id,
            $description
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Sale completed successfully!', 'sale_id' => $sale_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Sale completion error: " . $e->getMessage());
    $user_message = "An error occurred while completing the sale.";
    if (strpos($e->getMessage(), "Insufficient stock") !== false) {
        $user_message = $e->getMessage();
    } elseif (strpos($e->getMessage(), "SQLSTATE") !== false) {
        $user_message = "A database error occurred. Please check logs for details.";
        error_log("SQL Error during sale: " . $e->getMessage());
    }
    echo json_encode(['success' => false, 'message' => $user_message]);
}

?>

