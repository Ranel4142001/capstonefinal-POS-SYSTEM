// public/js/add_stocks_script.js
document.addEventListener('DOMContentLoaded', function () {
    const addStockForm = document.getElementById('addStockForm');
    const productSelect = document.getElementById('product_id');
    const productInventoryTableBody = document.querySelector('#productInventoryTable tbody');

    // Define the low stock threshold (must match PHP or be passed from API)
    const LOW_STOCK_THRESHOLD = 10;

    // Function to load products into the dropdown and inventory table
    function loadProducts() {
        // CORRECTED PATH: '../../api/stocks.php' to go up two directories from views/reports/
        fetch(' ../api/stocks.php?action=list_products')
            .then(response => {
                if (!response.ok) {
                    // If response is not OK (e.g., 404, 500), throw an error
                    return response.text().then(text => { // Read response as text to see potential PHP errors
                        throw new Error(`HTTP error! Status: ${response.status}. Response: ${text}`);
                    });
                }
                return response.json(); // Parse the JSON response
            })
            .then(data => {
                if (data.status === 'success' && data.data) {
                    const products = data.data;

                    // Clear previous options and rows
                    productSelect.innerHTML = '<option value="">Select a Product</option>';
                    productInventoryTableBody.innerHTML = '';

                    if (products.length > 0) {
                        products.forEach(product => {
                            // Populate product dropdown
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = `${product.name} (Current Stock: ${product.stock_quantity})`;
                            productSelect.appendChild(option);

                            // Populate product inventory table
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${product.id}</td>
                                <td>${product.name}</td>
                                <td>${product.category_name || 'N/A'}</td>
                                <td>₱${parseFloat(product.price).toFixed(2)}</td>
                                <td>
                                    ${product.stock_quantity}
                                    ${product.stock_quantity <= LOW_STOCK_THRESHOLD ? '<span class="badge bg-warning text-dark ms-2">(Low Stock)</span>' : ''}
                                </td>
                                <td>${product.barcode || 'N/A'}</td>
                                <td>
                                    <a href="#" class="btn btn-info btn-sm me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="#" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            `;
                            productInventoryTableBody.appendChild(row);
                        });
                    } else {
                        // Display message if no products are found
                        productSelect.innerHTML += '<option value="" disabled>No products found</option>';
                        productInventoryTableBody.innerHTML = '<tr><td colspan="7">No products found in the inventory.</td></tr>';
                    }
                } else {
                    console.error("API Error loading products:", data.message);
                    alert(data.message || "Failed to load products.");
                    productSelect.innerHTML = '<option value="" disabled>Error loading products</option>';
                    productInventoryTableBody.innerHTML = '<tr><td colspan="7">Error loading products.</td></tr>';
                }
            })
            .catch(error => {
                console.error("Network or parsing error:", error);
                alert("An unexpected error occurred while loading products. Check console for details.");
                productSelect.innerHTML = '<option value="" disabled>Network Error</option>';
                productInventoryTableBody.innerHTML = '<tr><td colspan="7">Network error loading products.</td></tr>';
            });
    }

    // Call loadProducts on page load
    loadProducts();

    // Handle form submission for adding stock
    if (addStockForm) {
        addStockForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(this);
            formData.append('action', 'add_stock'); // Specify the action for the API

            // CORRECTED PATH for POST request
            fetch('../api/stocks.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error! Status: ${response.status}. Response: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        addStockForm.reset(); // Clear the form
                        loadProducts(); // Reload products to update stock quantities
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error adding stock:", error);
                    alert("An error occurred while adding stock. Check console for details.");
                });
        });
    }

    // This block handles alerts from GET parameters (for initial page load messages or if redirects are used elsewhere)
    // This was previously in the add_stocks.php HTML but is now here for consistency.
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const msg = urlParams.get('msg');

    if (status && msg) {
        const alertPlaceholder = document.querySelector('.dashboard-page-content');
        if (alertPlaceholder) {
            const alertHtml = `
                <div class="alert alert-${status} alert-dismissible fade show no-print" role="alert">
                    ${decodeURIComponent(msg)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            alertPlaceholder.insertAdjacentHTML('afterbegin', alertHtml);
            const newAlert = alertPlaceholder.querySelector('.alert');
            setTimeout(() => {
                const bootstrapAlert = new bootstrap.Alert(newAlert);
                bootstrapAlert.close();
            }, 5000); // 5 seconds
            history.replaceState({}, document.title, window.location.pathname); // Clean the URL
        }
    }
});
