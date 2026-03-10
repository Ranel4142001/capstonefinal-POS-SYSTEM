// public/js/add_product.js
// Handles form submission for adding a new product via AJAX.

document.addEventListener('DOMContentLoaded', () => {
    const addProductForm = document.getElementById('addProductForm');
    const alertContainer = document.getElementById('alert-container');
    const loadingSpinner = document.getElementById('loadingSpinner');

    // Function to display alerts
    const showAlert = (message, type) => {
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    };

    // Function to clear form validation errors
    const clearFormErrors = () => {
        const formControls = addProductForm.querySelectorAll('.form-control, .form-select');
        formControls.forEach(control => {
            control.classList.remove('is-invalid');
        });
        const invalidFeedbacks = addProductForm.querySelectorAll('.invalid-feedback');
        invalidFeedbacks.forEach(feedback => {
            feedback.textContent = '';
        });
    };

    // Function to display form validation errors
    const displayFormErrors = (errors) => {
        clearFormErrors();
        for (const field in errors) {
            const input = document.getElementById(field);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = document.getElementById(`${field}_err`);
                if (feedback) {
                    feedback.textContent = errors[field];
                }
            }
        }
    };

    // Handle form submission
    addProductForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearFormErrors();
        alertContainer.innerHTML = '';
        loadingSpinner.style.display = 'block';

        const formData = new FormData(addProductForm);

        try {
            // Step 1: Add the new product
            const response = await fetch('../api/products.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Step 2: If product creation is successful, log the initial stock
                const stockFormData = new FormData();
                stockFormData.append('action', 'add_stock');
                stockFormData.append('product_id', result.product_id); // Use the new product ID
                stockFormData.append('quantity_to_add', formData.get('stock_quantity')); // Get stock quantity from form

                const stockResponse = await fetch('../api/stocks.php', {
                    method: 'POST',
                    body: stockFormData
                });

                const stockResult = await stockResponse.json();

                if (stockResult.success) {
                    showAlert('Product and initial stock added successfully!', 'success');
                } else {
                    showAlert('Product added, but failed to log initial stock: ' + stockResult.message, 'danger');
                }

                addProductForm.reset();
            } else {
                showAlert(result.message, 'danger');
                if (result.errors) {
                    displayFormErrors(result.errors);
                }
            }
        } catch (error) {
            console.error('Submission Error:', error);
            showAlert('An unexpected error occurred. Please try again.', 'danger');
        } finally {
            loadingSpinner.style.display = 'none';
        }
    });
});
