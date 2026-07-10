<?php
require_once __DIR__ . '/../includes/bootstrap.php';

        include LEGACY_BASE_PATH . '/includes/auth_check.php';
        include LEGACY_BASE_PATH . '/includes/layout_start.php';
        include LEGACY_BASE_PATH . '/includes/functions.php';
        include LEGACY_BASE_PATH . '/config/db.php';

        
        $userRole = $_SESSION['role'] ?? '';
        $isAdmin = ($userRole === 'admin');

// Fetch categories for the filter dropdown
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error, but don't stop page load, just show empty categories
    error_log("Error fetching categories: " . $e->getMessage());
}


?>


            <div class="container-fluid dashboard-page-content mt-5 pt-3">
                <h2 class="mb-4">Products</h2>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Product List</h5>
                        <a href="add_product.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" id="productSearch" class="form-control" placeholder="Search by name or barcode...">
                            </div>
                            <div class="col-md-4">
                                <select id="categoryFilter" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" id="resetFiltersBtn">Reset</button>
                            </div>
                        </div>

                       <div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Cost Price</th>
                <th>Stock</th>
                <th>Barcode</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="productTableBody">
            <tr>
                <td colspan="8" class="text-center text-muted py-4">Loading products...</td> </tr>
        </tbody>
    </table>
</div>

                        <nav aria-label="Product Pagination">
                            <ul class="pagination justify-content-center" id="productPagination">
                                </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editProductModalLabel">Product Details</h5>

         <?php if ($_SESSION['role'] !== 'admin'): ?>
            <small class="text-muted position-absolute start-0 mt-5 ms-3">
             You cannot edit products — only the admin can make changes.
            </small>
             <?php endif; ?>

        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="editProductForm">
          <input type="hidden" id="editProductId" name="id">

          <div class="mb-3">
            <label for="editProductName" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="editProductName" name="name" <?php echo !$isAdmin ? 'readonly' : ''; ?> required>
          </div>

          <div class="mb-3">
            <label for="editProductCategory" class="form-label">Category</label>
            <select class="form-select" id="editProductCategory" name="category_id" <?php echo !$isAdmin ? 'disabled' : ''; ?> required>
              <option value="">Select Category</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                  <?php echo htmlspecialchars($category['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="editProductPrice" class="form-label">Price</label>
            <input type="number" step="0.01" class="form-control" id="editProductPrice" name="price" <?php echo !$isAdmin ? 'readonly' : ''; ?> required>
          </div>

          <div class="mb-3">
            <label for="editProductCostPrice" class="form-label">Cost Price</label>
            <input type="number" step="0.01" class="form-control" id="editProductCostPrice" name="cost_price" <?php echo !$isAdmin ? 'readonly' : ''; ?> required>
          </div>

          <div class="mb-3">
            <label for="editProductStock" class="form-label">Stock</label>
            <input type="number" class="form-control" id="editProductStock" name="stock_quantity" <?php echo !$isAdmin ? 'readonly' : ''; ?> required>
          </div>

          <div class="mb-3">
            <label for="editProductBarcode" class="form-label">Barcode</label>
            <input type="text" class="form-control" id="editProductBarcode" name="barcode" <?php echo !$isAdmin ? 'readonly' : ''; ?>>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <?php if ($isAdmin): ?>
              <button type="submit" class="btn btn-primary">Save changes</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
</div>
</div>

<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteProductModalLabel">Delete Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="deleteProductName">this product</strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteProductBtn">Delete Product</button>
            </div>
        </div>
    </div>
</div>

<script>
// Frontend: Disable edit/delete buttons for non-admins
document.addEventListener('DOMContentLoaded', function () {
  const role = "<?php echo $userRole; ?>";
  if (role !== 'admin') {
    document.querySelectorAll('.edit-btn, .delete-btn').forEach(btn => {
      btn.classList.add('disabled');
      btn.style.pointerEvents = 'none';
      btn.title = 'You do not have permission to edit or delete products.';
    });

    // Prevent form submission if someone inspects element and tries to enable it
    const form = document.getElementById('editProductForm');
    form.addEventListener('submit', function (e) {
      if (role !== 'admin') {
        e.preventDefault();
        showMessage('You do not have permission to edit this product.', 'warning');
      }
    });
  }
});
</script>

   <?php // Close layout (footer, scripts, closing tags)
    include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
    <script src="<?= LEGACY_BASE_URL ?>/public/js/inventory_script.js?v=<?= filemtime(LEGACY_BASE_PATH . '/public/js/inventory_script.js') ?>"></script>




