<?php
require_once __DIR__ . '/../includes/bootstrap.php';

        include LEGACY_BASE_PATH . '/includes/auth_check.php';
        include LEGACY_BASE_PATH . '/includes/layout_start.php';
        include LEGACY_BASE_PATH . '/includes/functions.php';
        include LEGACY_BASE_PATH . '/config/db.php';

        // Set a flag to check if the user is an admin
        $is_admin = ($_SESSION['role'] === 'admin');



// Initialize variables
$name = $barcode = $description = $price = $cost_price = $stock_quantity = $category_id = $supplier_id = $brand = "";
$name_err = $barcode_err = $description_err = $price_err = $cost_price_err = $stock_quantity_err = $category_id_err = $brand_err = "";
$success_message = $error_message = "";

// Fetch categories for dropdown
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories for add product form: " . $e->getMessage());
    $error_message = "Could not load categories.";
}

// Only process POST requests if the user is an admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_admin) {
    // Validate inputs
    // Name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter a product name.";
    } else {
        $name = trim($_POST["name"]);
    }

    // Barcode (optional, but check for uniqueness if provided)
    if (!empty(trim($_POST["barcode"]))) {
        $barcode = trim($_POST["barcode"]);
        // Check if barcode already exists
        $sql = "SELECT id FROM products WHERE barcode = :barcode";
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":barcode", $param_barcode, PDO::PARAM_STR);
            $param_barcode = $barcode;
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $barcode_err = "This barcode is already taken.";
                }
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
        }
        unset($stmt);
    }

    // Price
    if (empty(trim($_POST["price"])) || !is_numeric($_POST["price"]) || $_POST["price"] < 0) {
        $price_err = "Please enter a valid price (non-negative number).";
    } else {
        $price = floatval($_POST["price"]);
    }

    // Cost Price (optional, but validate if provided)
    if (empty(trim($_POST["cost_price"])) || !is_numeric($_POST["cost_price"]) || $_POST["cost_price"] < 0) {
        $cost_price_err = "Please enter a valid cost price (non-negative number).";
    } else  {
        $cost_price = floatval($_POST["cost_price"]);
    }

    // Stock Quantity
    if (empty(trim($_POST["stock_quantity"])) || !is_numeric($_POST["stock_quantity"]) || $_POST["stock_quantity"] < 0) {
        $stock_quantity_err = "Please enter a valid stock quantity (non-negative integer).";
    } else {
        $stock_quantity = intval($_POST["stock_quantity"]);
    }

    // Category ID
    if (empty(trim($_POST["category_id"]))) {
        $category_id_err = "Please select a category.";
    } else {
        $category_id = intval($_POST["category_id"]);
    }

    // Brand (New field)
    $brand = trim($_POST["brand"]);
    // Optional: Add validation for brand if needed, e.g., max length
    // if (strlen($brand) > 100) {
    //     $brand_err = "Brand name cannot exceed 100 characters.";
    // }

    // Description (optional)
    $description = trim($_POST["description"]);

   $supplier_id = isset($_POST["supplier_id"]) && trim($_POST["supplier_id"]) !== "" ? intval(trim($_POST["supplier_id"])) : NULL;


    // Check input errors before inserting into database
    if (empty($name_err) && empty($barcode_err) && empty($price_err) && empty($cost_price_err) && empty($stock_quantity_err) && empty($category_id_err) && empty($brand_err)) {
        $sql = "INSERT INTO products (name, barcode, description, price, cost_price, stock_quantity, category_id, supplier_id, brand, is_active) VALUES (:name, :barcode, :description, :price, :cost_price, :stock_quantity, :category_id, :supplier_id, :brand, 1)";

        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":name", $param_name, PDO::PARAM_STR);
            $stmt->bindParam(":barcode", $param_barcode, PDO::PARAM_STR);
            $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
            $stmt->bindParam(":price", $param_price);
            $stmt->bindParam(":cost_price", $param_cost_price);
            $stmt->bindParam(":stock_quantity", $param_stock_quantity, PDO::PARAM_INT);
            $stmt->bindParam(":category_id", $param_category_id, PDO::PARAM_INT);
            $stmt->bindParam(":supplier_id", $param_supplier_id, PDO::PARAM_INT);
            $stmt->bindParam(":brand", $param_brand, PDO::PARAM_STR); // Bind new brand parameter

            $param_name = $name;
            $param_barcode = !empty($barcode) ? $barcode : NULL;
            $param_description = !empty($description) ? $description : NULL;
            $param_price = $price;
            $param_cost_price = !empty($cost_price) ? $cost_price : NULL;
            $param_stock_quantity = $stock_quantity;
            $param_category_id = $category_id;
            $param_supplier_id = $supplier_id; // Will be NULL if not set
            $param_brand = !empty($brand) ? $brand : NULL; // Set brand parameter

            if ($stmt->execute()) {
                $success_message = "Product added successfully!";
                // Clear form fields
                $name = $barcode = $description = $price = $cost_price = $stock_quantity = $category_id = $supplier_id = $brand = "";
            } else {
                $error_message = "Error adding product. Please try again.";
                error_log("Error executing product insert: " . $stmt->errorInfo()[2]);
            }
            unset($stmt);
        }
    }
    unset($pdo);
}


?>
    
            <div class="container-fluid dashboard-page-content mt-5 pt-3">
                <h2 class="mb-4">Add New Product</h2>

                <?php if (!$is_admin): ?>
                    <div class="alert alert-warning" role="alert">
                        You do not have permission to add new products. Only administrators can perform this action.
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <fieldset <?php echo !$is_admin ? 'disabled' : ''; ?>>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($name); ?>" required>
                                    <div class="invalid-feedback"><?php echo $name_err; ?></div>
                                </div>
                                <div class="mb-3">
                                    <label for="barcode" class="form-label">Barcode (Optional)</label>
                                    <input type="text" name="barcode" id="barcode" class="form-control <?php echo (!empty($barcode_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($barcode); ?>">
                                    <div class="invalid-feedback"><?php echo $barcode_err; ?></div>
                                </div>
                                <div class="mb-3">
                                    <label for="brand" class="form-label">Brand (Optional)</label>
                                    <input type="text" name="brand" id="brand" class="form-control <?php echo (!empty($brand_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($brand); ?>">
                                    <div class="invalid-feedback"><?php echo $brand_err; ?></div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description (Optional)</label>
                                    <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="price" class="form-label">Selling Price <span class="text-danger">*</span></label>
                                        <input type="number" name="price" id="price" step="0.01" min="0" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($price); ?>" required>
                                        <div class="invalid-feedback"><?php echo $price_err; ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cost_price" class="form-label">Cost Price <span class="text-danger">*</span> </label>
                                        <input type="number" name="cost_price" id="cost_price" step="0.01" min="0" class="form-control <?php echo (!empty($cost_price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($cost_price); ?>">
                                        <div class="invalid-feedback"><?php echo $cost_price_err; ?></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="stock_quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                        <input type="number" name="stock_quantity" id="stock_quantity" min="0" class="form-control <?php echo (!empty($stock_quantity_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($stock_quantity); ?>" required>
                                        <div class="invalid-feedback"><?php echo $stock_quantity_err; ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                        <select name="category_id" id="category_id" class="form-select <?php echo (!empty($category_id_err)) ? 'is-invalid' : ''; ?>" required>
                                            <option value="">Select a Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback"><?php echo $category_id_err; ?></div>
                                    </div>
                                </div>
                                <?php if ($is_admin): ?>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary me-2">Add Product</button>
                                        <a href="inventory.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                <?php endif; ?>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
        // Close layout (footer, scripts, closing tags)
        include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>


