<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

global $pdo;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetSuppliersRequest();
        break;
    case 'POST':
        handleAddSupplierRequest();
        break;
    case 'PUT':
        handleUpdateSupplierRequest();
        break;
    case 'DELETE':
        handleDeleteSupplierRequest();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        http_response_code(405);
        break;
}

function handleGetSuppliersRequest() {
    global $pdo;

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $sql = "SELECT id, name, contact_person, phone, email, address FROM suppliers";
    $countSql = "SELECT COUNT(*) FROM suppliers";

    $queryParamsForMain = []; // Parameters for the main paginated query
    $queryParamsForCount = []; // Parameters for the count query

    if (!empty($search)) {
        $searchParamValue = '%' . $search . '%';
        $whereConditions = [];
        $tempSearchParams = []; // Use temporary array for building to avoid confusion

        // Create unique named parameters for each LIKE clause
        $whereConditions[] = "name LIKE :search_name";
        $tempSearchParams[':search_name'] = $searchParamValue;

        $whereConditions[] = "contact_person LIKE :search_contact";
        $tempSearchParams[':search_contact'] = $searchParamValue;

        $whereConditions[] = "phone LIKE :search_phone";
        $tempSearchParams[':search_phone'] = $searchParamValue;

        $whereConditions[] = "email LIKE :search_email";
        $tempSearchParams[':search_email'] = $searchParamValue;

        $whereConditions[] = "address LIKE :search_address";
        $tempSearchParams[':search_address'] = $searchParamValue;

        $whereClause = " WHERE " . implode(" OR ", $whereConditions);

        $sql .= $whereClause;
        $countSql .= $whereClause;

        // Merge tempSearchParams into both query parameter arrays
        $queryParamsForMain = array_merge($queryParamsForMain, $tempSearchParams);
        $queryParamsForCount = array_merge($queryParamsForCount, $tempSearchParams);
    }

    $sql .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";
    $queryParamsForMain[':limit'] = $limit;
    $queryParamsForMain[':offset'] = $offset;

    try {
        // --- Debugging: Log SQL queries and parameters ---
        error_log("SQL for count: " . $countSql);
        error_log("Params for count: " . print_r($queryParamsForCount, true));
        error_log("SQL for suppliers: " . $sql);
        error_log("Params for suppliers: " . print_r($queryParamsForMain, true));
        // --- End Debugging ---

        // --- Get total count ---
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($queryParamsForCount);
        $total = $stmtCount->fetchColumn();

        // --- Get paginated suppliers ---
        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryParamsForMain);
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'suppliers' => $suppliers, 'total' => $total]);
    } catch (PDOException $e) {
        error_log("Error fetching suppliers: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500);
    }
}

function handleAddSupplierRequest() {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
        http_response_code(400);
        return;
    }

    $name = trim($data['name'] ?? '');
    $contact_person = trim($data['contact_person'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $address = trim($data['address'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Supplier Name is required.']);
        http_response_code(400);
        return;
    }

    $sql = "INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (:name, :contact_person, :phone, :email, :address)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':contact_person', $contact_person);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':address', $address);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Supplier added successfully!', 'id' => $pdo->lastInsertId()]);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Error adding supplier: " . implode(" - ", $errorInfo));
            echo json_encode(['success' => false, 'message' => 'Failed to add supplier: ' . ($errorInfo[2] ?? 'Unknown error.')]);
            http_response_code(500);
        }
    } catch (PDOException $e) {
        error_log("PDO Error adding supplier: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500);
    }
}

function handleUpdateSupplierRequest() {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
        http_response_code(400);
        return;
    }

    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $contact_person = trim($data['contact_person'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $address = trim($data['address'] ?? '');

    if ($id <= 0 || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Invalid supplier ID or Name is required.']);
        http_response_code(400);
        return;
    }

    $sql = "UPDATE suppliers SET name = :name, contact_person = :contact_person, phone = :phone, email = :email, address = :address WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':contact_person', $contact_person);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':address', $address);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Supplier updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No supplier found with that ID or no changes made.']);
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Error updating supplier: " . implode(" - ", $errorInfo));
            echo json_encode(['success' => false, 'message' => 'Failed to update supplier: ' . ($errorInfo[2] ?? 'Unknown error.')]);
            http_response_code(500);
        }
    } catch (PDOException $e) {
        error_log("PDO Error updating supplier: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500);
    }
}

function handleDeleteSupplierRequest() {
    global $pdo;

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id === 0) {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
    }

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid supplier ID.']);
        http_response_code(400);
        return;
    }

    $sql = "DELETE FROM suppliers WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No supplier found with that ID.']);
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Error deleting supplier: " . implode(" - ", $errorInfo));
            echo json_encode(['success' => false, 'message' => 'Failed to delete supplier: ' . ($errorInfo[2] ?? 'Unknown error.')]);
            http_response_code(500);
        }
    } catch (PDOException $e) {
        error_log("PDO Error deleting supplier: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500);
    }
}
?>


