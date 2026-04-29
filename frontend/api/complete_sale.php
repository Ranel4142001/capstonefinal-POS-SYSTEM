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
$total_amount = (float)($data['total_amount'] ?? 0);
$discount_amount = (float)($data['discount_amount'] ?? 0.00);
$tax_amount = (float)($data['tax_amount'] ?? 0.00);
$payment_method = trim((string)($data['payment_method'] ?? 'Cash'));
$customer_id = $data['customer_id'] ?? null;

if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit();
}

$cash_received = (float)($data['cash_received'] ?? 0.00);
$change_due = (float)($data['change_due'] ?? 0.00);
$allowed_payment_methods = ['Cash', 'Credit Card', 'GCash'];

if (!in_array($payment_method, $allowed_payment_methods, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method selected.']);
    exit();
}

if ($cash_received <= 0) {
    echo json_encode(['success' => false, 'message' => 'Payment amount must be greater than zero.']);
    exit();
}

if ($cash_received + 0.00001 < $total_amount) {
    echo json_encode(['success' => false, 'message' => 'Payment amount must cover the sale total.']);
    exit();
}

if ($payment_method !== 'Cash') {
    $change_due = 0.00;
}


try {
    \Illuminate\Support\Facades\DB::beginTransaction();

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

    $sale = \App\Models\Sale::query()->create([
        'sale_date' => now(),
        'total_amount' => $total_amount,
        'discount_amount' => $discount_amount,
        'tax_amount' => $tax_amount,
        'payment_method' => $payment_method,
        'cashier_id' => $cashier_id,
        'customer_id' => $customer_id,
        'cash_received' => $cash_received,
        'change_due' => $change_due,
        'status' => 'completed',
    ]);
    $sale_id = $sale->id;

    if (!$sale_id) {
        throw new Exception("Failed to create sale record.");
    }

    // 2. Insert into sale_items table and update product stock, and log stock history
    foreach ($cart as $item) {
        $product_id = (int) $item['id'];
        $quantity_sold = (int) $item['quantity'];
        $price_at_sale = (float) $item['price'];
        $subtotal = $price_at_sale * $quantity_sold;

        $product = \App\Models\Product::query()
            ->lockForUpdate()
            ->find($product_id);

        if (!$product || $product->stock_quantity < $quantity_sold) {
            throw new Exception("Insufficient stock or product not found for product ID: $product_id. Transaction aborted.");
        }

        \App\Models\SaleItem::query()->create([
            'sale_id' => $sale_id,
            'product_id' => $product_id,
            'quantity' => $quantity_sold,
            'price_at_sale' => $price_at_sale,
            'subtotal' => $subtotal,
        ]);

        $product->stock_quantity = (int) $product->stock_quantity - $quantity_sold;
        $product->save();

        \Illuminate\Support\Facades\DB::table('stock_history')->insert([
            'product_id' => $product_id,
            'quantity_change' => -$quantity_sold,
            'current_quantity_after_change' => $product->stock_quantity,
            'change_type' => 'sale_out',
            'change_date' => now(),
            'user_id' => $cashier_id,
            'description' => "Sale (Order ID: $sale_id)",
        ]);
    }

    \Illuminate\Support\Facades\DB::commit();
    echo json_encode(['success' => true, 'message' => 'Sale completed successfully!', 'sale_id' => $sale_id]);

} catch (Exception $e) {
    if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
        \Illuminate\Support\Facades\DB::rollBack();
    }
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

