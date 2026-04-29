<?php
require_once __DIR__ . '/../includes/bootstrap.php';

        include LEGACY_BASE_PATH . '/includes/auth_check.php';
require_role(['admin']);
        include LEGACY_BASE_PATH . '/includes/layout_start.php';
        include LEGACY_BASE_PATH . '/includes/functions.php';
        
?>
    

            <div class="container-fluid dashboard-page-content mt-5 pt-3">
                <h2 class="mb-4">Category Management</h2>
                <?php
                // Display feedback message (success or danger) if set in session.
                // This is still useful for any non-AJAX PHP messages or general page alerts.
                if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Card for adding new categories -->
                <div class="card mb-4">
                    <div class="card-header">
                        Add New Category
                    </div>
                    <div class="card-body">
                        <!-- Form for adding a new category. Action is handled by JavaScript via AJAX. -->
                        <form id="addCategoryForm">
                            <input type="hidden" name="action" value="add_category"> <!-- Action for API -->
                            <div class="mb-3">
                                <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="category_name" id="category_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="category_description" class="form-label">Description (Optional)</label>
                                <textarea name="category_description" id="category_description" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </form>
                    </div>
                </div>

                <!-- Card for displaying existing categories -->
                <div class="card">
                    <div class="card-header">
                        Existing Categories
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="categoryTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Category data will be loaded dynamically by public/js/categories_script.js -->
                                    <tr>
                                        <td colspan="4">Loading categories...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ----------------------------------------------------------------------- -->
    <!-- Modals for Edit and Delete Operations (placed outside main content for structure) -->
    <!-- ----------------------------------------------------------------------- -->

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Form for editing a category. Action is handled by JavaScript via AJAX. -->
                <form id="editCategoryForm">
                    <input type="hidden" name="action" value="edit_category"> <!-- Action for API -->
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Category Name</label>
                            <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category_description" class="form-label">Description</label>
                            <textarea name="category_description" id="edit_category_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Form for deleting a category. Action is handled by JavaScript via AJAX. -->
                <form id="deleteCategoryForm">
                    <input type="hidden" name="action" value="delete_category"> <!-- Action for API -->
                    <input type="hidden" name="category_id" id="delete_category_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete the category "<strong id="delete_category_name"></strong>"? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

   <?php // Close layout (footer, scripts, closing tags)
    include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
    <script src="<?= LEGACY_BASE_URL ?>/public/js/categories_script.js?v=<?= filemtime(LEGACY_BASE_PATH . '/public/js/categories_script.js') ?>"></script>
    <script>
        // Auto-hide alerts after a few seconds for better user experience.
        // This is still useful for any non-AJAX PHP messages or general page alerts.
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    const bootstrapAlert = new bootstrap.Alert(alert);
                    bootstrapAlert.close();
                }, 5000); // Alert will close after 5 seconds.
            }
        });
    </script>
</body>
</html>




