<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// Enable CORS for AJAX requests if your frontend is on a different origin (e.g., different port)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


require_once LEGACY_BASE_PATH . '/config/db.php'; // Include database connection

$method = $_SERVER['REQUEST_METHOD'];

// Define low stock threshold (you can make this configurable, e.g., from app_settings.php)
$lowStockThreshold = 20; // Products with stock <= this will be considered low stock

switch ($method) {
    case 'GET':
        handleGetRequest($pdo, $lowStockThreshold);
        break;
    case 'POST':
        // handlePostRequest($pdo); // For adding products
        break;
    case 'PUT':
        // handlePutRequest($pdo); // For updating products
        break;
    case 'DELETE':
        // handleDeleteRequest($pdo); // For deleting products
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(array("message" => "Method not allowed."));
        break;
}

function handleGetRequest($pdo, $lowStockThreshold) {
    $searchTerm = $_GET['search'] ?? ''; // Search by product name or barcode
    $categoryId = $_GET['category_id'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $sql = "SELECT p.id, p.name, p.barcode, p.price, p.cost_price, p.stock_quantity, c.name as category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id";
    
    $countSql = "SELECT COUNT(*)
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id";

    $conditions = [];
    $params = []; // Use named parameters for clarity and robustness

    if (!empty($searchTerm)) {
        // Check if searchTerm is numeric and could be a barcode
        if (is_numeric($searchTerm)) {
            $conditions[] = "(p.name LIKE :searchTermName OR p.barcode = :searchTermBarcode)";
            $params[':searchTermName'] = "%" . $searchTerm . "%";
            $params[':searchTermBarcode'] = $searchTerm;
        } else {
            $conditions[] = "p.name LIKE :searchTermName";
            $params[':searchTermName'] = "%" . $searchTerm . "%";
        }
    }
    if (!empty($categoryId)) {
        $conditions[] = "p.category_id = :categoryId";
        $params[':categoryId'] = $categoryId;
    }

    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
        $countSql .= " WHERE " . implode(' AND ', $conditions);
    }

    // ORDER BY stock_quantity ASC for lowest stock first, then by name
    $sql .= " ORDER BY p.stock_quantity ASC, p.name ASC LIMIT :limit OFFSET :offset";
    
    // Add limit and offset to the parameters for the main query
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    try {
        // --- Get total count for pagination ---
        $countStmt = $pdo->prepare($countSql);
        // Create a separate array for count query parameters (without limit/offset)
        $countParams = [];
        foreach ($params as $key => $value) {
            // Only include search and category parameters for the count query
            if ($key !== ':limit' && $key !== ':offset') {
                $countParams[$key] = $value;
            }
        }
        $countStmt->execute($countParams);
        $totalProducts = $countStmt->fetchColumn();

        // --- Get paginated products ---
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params); // Execute with all named parameters
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(array(
            "success" => true,
            "products" => $products,
            "total" => $totalProducts,
            "low_stock_threshold" => $lowStockThreshold, // Include the threshold
            "message" => "Products fetched successfully."
        ));
    } catch (PDOException $e) {
        error_log("Error fetching products: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode(array("success" => false, "message" => "Error fetching products: " . $e->getMessage()));
    }
}

// You'd add handlePostRequest, handlePutRequest, handleDeleteRequest functions here later
// For now, we'll focus on GET for inventory display
?>

