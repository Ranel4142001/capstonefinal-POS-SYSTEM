// public/js/categories_script.js
document.addEventListener('DOMContentLoaded', function() {
    // Function to load and display categories in the table
    function loadCategories() {
        fetch('../api/categories.php?action=list') // Make a GET request to the API to list categories
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                const tbody = document.querySelector('#categoryTable tbody');
                tbody.innerHTML = ''; // Clear existing table rows

                if (data.length > 0) {
                    // Iterate over the fetched categories and create table rows
                    data.forEach(category => {
                        const row = `
                            <tr>
                                <td>${category.id}</td>
                                <td>${category.name}</td>
                                <td>${category.description || ''}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning edit-category-btn"
                                        data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                        data-id="${category.id}"
                                        data-name="${category.name}"
                                        data-description="${category.description || ''}">
                                        Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-category-btn"
                                        data-bs-toggle="modal" data-bs-target="#deleteCategoryModal"
                                        data-id="${category.id}"
                                        data-name="${category.name}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.insertAdjacentHTML('beforeend', row); // Add the new row to the table
                    });
                } else {
                    // Display a message if no categories are found
                    tbody.innerHTML = '<tr><td colspan="4">No categories found.</td></tr>';
                }
            })
            .catch(error => {
                console.error("Error loading categories:", error);
                alert("Error loading categories. Please try again.");
            });
    }

    // Initial load of categories when the page is ready
    loadCategories();

    // Event listener for Add Category Form submission
    const addCategoryForm = document.getElementById('addCategoryForm');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Collect form data
            formData.append('action', 'add'); // Add the action for the API endpoint

            fetch('../api/categories.php', {
                method: 'POST', // Use POST method for adding data
                body: formData   // Send the form data
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                if (data.status === 'success') {
                    // Hide the modal and reset the form on success
                    const addModal = bootstrap.Modal.getInstance(document.getElementById('addCategoryModal'));
                    if (addModal) addModal.hide();
                    addCategoryForm.reset();
                    loadCategories(); // Reload categories to update the table
                    alert(data.message); // Show success message
                } else {
                    alert(data.message); // Show error message
                }
            })
            .catch(error => {
                console.error("Error adding category:", error);
                alert("Error adding category. Please check your input.");
            });
        });
    }

    // Event delegation for Edit Category buttons (since they are added dynamically)
    document.querySelector('#categoryTable tbody').addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-category-btn')) {
            const button = e.target;
            // Populate the edit modal fields with data from the clicked button's data-attributes
            document.getElementById('edit_category_id').value = button.dataset.id;
            document.getElementById('edit_category_name').value = button.dataset.name;
            document.getElementById('edit_category_description').value = button.dataset.description;

            // Optional: Manually show the modal if data-bs-toggle is not sufficient
            // const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            // editModal.show();
        }
    });

    // Event listener for Edit Category Form submission
    const editCategoryForm = document.getElementById('editCategoryForm');
    if (editCategoryForm) {
        editCategoryForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Collect form data
            formData.append('action', 'edit'); // Add the action for the API endpoint

            fetch('../api/categories.php', {
                method: 'POST', // Use POST method for editing data
                body: formData   // Send the form data
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                if (data.status === 'success') {
                    // Hide the modal on success
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editCategoryModal'));
                    if (editModal) editModal.hide();
                    loadCategories(); // Reload categories to update the table
                    alert(data.message); // Show success message
                } else {
                    alert(data.message); // Show error message
                }
            })
            .catch(error => {
                console.error("Error updating category:", error);
                alert("Error updating category. Please try again.");
            });
        });
    }

    // Event delegation for Delete Category buttons
    document.querySelector('#categoryTable tbody').addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-category-btn')) {
            const button = e.target;
            // Populate the delete confirmation modal
            document.getElementById('delete_category_id').value = button.dataset.id;
            document.getElementById('delete_category_name').textContent = button.dataset.name;
        }
    });

    // Event listener for Delete Category Confirmation Form submission
    const deleteCategoryForm = document.getElementById('deleteCategoryForm');
    if (deleteCategoryForm) {
        deleteCategoryForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Collect form data
            formData.append('action', 'delete'); // Add the action for the API endpoint

            fetch('../api/categories.php', {
                method: 'POST', // Use POST method for deleting data
                body: formData   // Send the form data
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                if (data.status === 'success') {
                    // Hide the modal on success
                    const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteCategoryModal'));
                    if (deleteModal) deleteModal.hide();
                    loadCategories(); // Reload categories to update the table
                    alert(data.message); // Show success message
                } else {
                    alert(data.message); // Show error message
                }
            })
            .catch(error => {
                console.error("Error deleting category:", error);
                alert("Error deleting category. Please try again.");
            });
        });
    }
});