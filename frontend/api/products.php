<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// products.php - API endpoint for products

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once LEGACY_BASE_PATH . '/config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequest($pdo);
        break;

    case 'POST':
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "You do not have permission to add products."]);
            exit;
        }
        handlePostRequest($pdo);
        break;

    case 'PUT':
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "You do not have permission to edit products."]);
            exit;
        }
        handlePutRequest($pdo);
        break;

    case 'DELETE':
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "You do not have permission to delete products."]);
            exit;
        }
        handleDeleteRequest($pdo);
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed."]);
        break;
}

// === GET ===
function handleGetRequest($pdo) {
    $searchTerm = trim($_GET['search'] ?? '');
    $categoryId = $_GET['category_id'] ?? '';
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = (int)($_GET['offset'] ?? 0);

    $sql = "SELECT p.id, p.name, p.barcode, p.price, p.cost_price, p.stock_quantity, p.category_id, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id";
    $conditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        if (preg_match('/^[0-9]+$/', $searchTerm)) {
            $conditions[] = "(p.barcode = :searchBarcode OR p.name LIKE :searchName)";
            $params[':searchBarcode'] = $searchTerm;
            $params[':searchName'] = "%" . $searchTerm . "%";
        } else {
            $conditions[] = "(p.name LIKE :searchName OR p.barcode LIKE :searchPartialBarcode)";
            $params[':searchName'] = "%" . $searchTerm . "%";
            $params[':searchPartialBarcode'] = "%" . $searchTerm . "%";
        }
    }

    if (!empty($categoryId)) {
        $conditions[] = "p.category_id = :categoryId";
        $params[':categoryId'] = $categoryId;
    }

    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $main_sql = $sql . " ORDER BY p.name ASC LIMIT :limit OFFSET :offset";
    $countSql = "SELECT COUNT(*) FROM products p";
    if (count($conditions) > 0) {
        $countSql .= " WHERE " . implode(' AND ', $conditions);
    }

    try {
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalProducts = $countStmt->fetchColumn();

        $stmt = $pdo->prepare($main_sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "products" => $products, "total" => $totalProducts]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error fetching products: " . $e->getMessage()]);
    }
}

// === POST ===
function handlePostRequest($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['product'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input for POST.']);
        exit();
    }

    $product = $data['product'];
    $required_fields = ['barcode', 'name', 'price', 'stock_quantity', 'category_id'];

    foreach ($required_fields as $field) {
        if (!isset($product[$field]) || $product[$field] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
            exit();
        }
    }

    $cost_price = $product['cost_price'] ?? 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO products (barcode, name, category_id, price, cost_price, stock_quantity) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $product['barcode'],
            $product['name'],
            $product['category_id'],
            $product['price'],
            $product['cost_price'],
            $product['stock_quantity']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully.',
            'product_id' => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating product: ' . $e->getMessage()]);
    }
}

// === PUT ===
function handlePutRequest($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input for PUT.']);
        exit();
    }

    $required_fields = ['id', 'name', 'category_id', 'cost_price', 'price', 'stock_quantity', 'barcode'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing or empty required field: {$field}"]);
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE products 
                               SET name=?, category_id=?, price=?, cost_price=?, stock_quantity=?, barcode=? 
                               WHERE id=?");
        $stmt->execute([
            $data['name'],
            $data['category_id'],
            $data['price'],
            $data['cost_price'],
            $data['stock_quantity'],
            $data['barcode'],
            $data['id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error during update: ' . $e->getMessage()]);
    }
}

// === DELETE ===
function handleDeleteRequest($pdo) {
    $product_id = $_GET['id'] ?? null;

    if (empty($product_id) || !is_numeric($product_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product ID provided.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);

        if ($stmt->rowCount()) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error during deletion: ' . $e->getMessage()]);
    }
}
?>


