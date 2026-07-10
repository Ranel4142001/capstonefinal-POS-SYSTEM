<?php
// includes/products_functions.php
// This file contains functions for managing product-related data.

/**
 * Retrieves all products from the database.
 * @return array An array of associative arrays, where each array represents a product.
 * Returns an empty array on failure.
 */
function get_all_products() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT id, barcode, name, description, price, cost_price, stock_quantity, category_id, supplier_id, brand, is_active, created_at, updated_at FROM products ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in get_all_products: " . $e->getMessage());
        return [];
    }
}

/**
 * Retrieves a single product by its barcode.
 * @param string $barcode The barcode of the product to search for.
 * @return mixed An associative array of product data if found, otherwise false.
 */
function get_product_by_barcode($barcode) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, name, barcode, price, stock_quantity FROM products WHERE barcode = :barcode");
        $stmt->bindParam(":barcode", $barcode, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in get_product_by_barcode: " . $e->getMessage());
        return false;
    }
}

/**
 * Checks if a barcode already exists in the products table.
 * @param string $barcode The barcode to check.
 * @return bool True if barcode exists, false otherwise.
 */
function is_barcode_taken($barcode) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = :barcode");
        $stmt->bindParam(":barcode", $barcode, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Database error in is_barcode_taken: " . $e->getMessage());
        return false;
    }
}

/**
 * Inserts a new product into the database.
 * @param array $data Associative array containing product fields.
 * @return bool True on success, false on failure.
 */
function add_product($data) {
    global $pdo;
    try {
        $sql = "INSERT INTO products (
                    name, barcode, description, price, cost_price,
                    stock_quantity, category_id, supplier_id, brand, is_active
                ) VALUES (
                    :name, :barcode, :description, :price, :cost_price,
                    :stock_quantity, :category_id, :supplier_id, :brand, 1
                )";
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(":name", $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(":barcode", $data['barcode'], PDO::PARAM_STR);
        $stmt->bindParam(":description", $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(":price", $data['price']);
        $stmt->bindParam(":cost_price", $data['cost_price']);
        $stmt->bindParam(":stock_quantity", $data['stock_quantity'], PDO::PARAM_INT);
        $stmt->bindParam(":category_id", $data['category_id'], PDO::PARAM_INT);
        $stmt->bindParam(":supplier_id", $data['supplier_id'], PDO::PARAM_INT);
        $stmt->bindParam(":brand", $data['brand'], PDO::PARAM_STR);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Database error in add_product: " . $e->getMessage());
        return false;
    }
}

