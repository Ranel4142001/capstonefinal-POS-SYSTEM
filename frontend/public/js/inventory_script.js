document.addEventListener('DOMContentLoaded', function () {
    const productTableBody = document.getElementById('productTableBody');
    const productSearch = document.getElementById('productSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const productPagination = document.getElementById('productPagination');

    const productsPerPage = 10;
    let currentPage = 1;
    let totalProducts = 0;
    let lowStockThreshold = 0; // Initialize, will be updated by API response

    // Edit Modal Elements
    const editProductModal = new bootstrap.Modal(document.getElementById('editProductModal'));
    const editProductForm = document.getElementById('editProductForm');
    const editProductId = document.getElementById('editProductId');
    const editProductName = document.getElementById('editProductName');
    const editProductCategory = document.getElementById('editProductCategory');
    const editProductCostPrice = document.getElementById('editProductCostPrice'); // NEW ELEMENT
    const editProductPrice = document.getElementById('editProductPrice');
    const editProductStock = document.getElementById('editProductStock');
    const editProductBarcode = document.getElementById('editProductBarcode');

    // Function to fetch products from the API and update the table
    async function fetchProducts() {
        productTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-info py-4">Fetching products...</td></tr>`;

        const searchTerm = productSearch.value.trim();
        const categoryId = categoryFilter.value;
        const offset = (currentPage - 1) * productsPerPage;

        // Ensure this path correctly points to your API endpoint for products
        let url = `../api/products.php?limit=${productsPerPage}&offset=${offset}`;
        if (searchTerm) {
            url += `&search=${encodeURIComponent(searchTerm)}`;
        }
        if (categoryId) {
            url += `&category_id=${encodeURIComponent(categoryId)}`;
        }

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                totalProducts = data.total;
                lowStockThreshold = data.low_stock_threshold; // Update threshold from API
                renderProducts(data.products);
                renderPagination();
            } else {
                productTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${data.message || 'Error fetching products.'}</td></tr>`;
                totalProducts = 0;
                renderPagination();
            }
        } catch (error) {
            console.error('Error fetching products:', error);
            productTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Failed to load products. Network error or server issue.</td></tr>`;
            totalProducts = 0;
            renderPagination();
        }
    }

    // Function to render products in the table
    // Function to render products in the table
    function renderProducts(products) {
        productTableBody.innerHTML = ''; // Clear existing rows

        if (products.length === 0) {
            productTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr>`;
            return;
        }

        products.forEach(product => {
            const row = document.createElement('tr');
            let stockClass = '';
            let stockDisplay = product.stock_quantity;

            // Apply low stock styling and text
            if (product.stock_quantity <= lowStockThreshold) {
                stockClass = 'text-danger fw-bold';
                stockDisplay += ' (Low Stock)';
            }

            row.innerHTML = `
            <td>${product.id}</td>
            <td>${htmlspecialchars(product.name)}</td>
            <td>${htmlspecialchars(product.category_name || 'N/A')}</td>
            <td>₱ ${parseFloat(product.price).toFixed(2)}</td>
            <td>₱ ${parseFloat(product.cost_price).toFixed(2)}</td> 
            <td class="${stockClass}">${stockDisplay}</td>
            <td>${htmlspecialchars(product.barcode || 'N/A')}</td>
            <td>
                <button class="btn btn-sm btn-info me-1 edit-product-btn"
                        data-id="${product.id}"
                        data-name="${htmlspecialchars(product.name)}"
                        data-category-id="${product.category_id}"
                        data-price="${product.price}"
                        data-cost-price="${product.cost_price}"
                        data-stock="${product.stock_quantity}"
                        data-barcode="${htmlspecialchars(product.barcode || '')}"
                        title="Edit Product">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-product-btn" data-id="${product.id}" title="Delete Product">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
            productTableBody.appendChild(row);
        });

        attachActionEventListeners();
    }
    // Function to render pagination controls
    function renderPagination() {
        productPagination.innerHTML = ''; // Clear existing pagination

        const totalPages = Math.ceil(totalProducts / productsPerPage);

        if (totalPages <= 1) {
            return; // No pagination needed
        }

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.classList.add('page-item');
        if (currentPage === 1) prevLi.classList.add('disabled');
        prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous" data-page="${currentPage - 1}"><span aria-hidden="true">&laquo;</span></a>`;
        productPagination.appendChild(prevLi);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const pageLi = document.createElement('li');
            pageLi.classList.add('page-item');
            if (i === currentPage) pageLi.classList.add('active');
            pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            productPagination.appendChild(pageLi);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.classList.add('page-item');
        if (currentPage === totalPages) nextLi.classList.add('disabled');
        nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next" data-page="${currentPage + 1}"><span aria-hidden="true">&raquo;</span></a>`;
        productPagination.appendChild(nextLi);

        // Add event listeners for page clicks
        productPagination.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page > 0 && page <= totalPages && page !== currentPage) {
                    currentPage = page;
                    fetchProducts();
                }
            });
        });
    }

    // Function to attach event listeners to dynamically added buttons
    function attachActionEventListeners() {
        // Attach event listeners for edit buttons
        document.querySelectorAll('.edit-product-btn').forEach(button => {
            button.addEventListener('click', openEditModal);
        });

        // Attach event listeners for delete buttons
        document.querySelectorAll('.delete-product-btn').forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.dataset.id;
                const productName = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                if (confirm(`Are you sure you want to delete "${productName}"?`)) {
                    deleteProduct(productId);
                }
            });
        });
    }

    // Function to open the edit modal and populate its fields
    function openEditModal(event) {
        const button = event.currentTarget; // The clicked edit button
        const productId = button.dataset.id;
        const productName = button.dataset.name;
        const productCategoryId = button.dataset.categoryId;
        const productCostPrice = button.dataset.costPrice;
        const productPrice = button.dataset.price;
        const productStock = button.dataset.stock;
        const productBarcode = button.dataset.barcode;

        // Populate the modal form fields with the product data
        editProductId.value = productId;
        editProductName.value = productName;
        editProductCategory.value = productCategoryId;
        editProductCostPrice.value = parseFloat(productCostPrice).toFixed(2); // NEW LINE
        editProductPrice.value = parseFloat(productPrice).toFixed(2);
        editProductStock.value = productStock;
        editProductBarcode.value = productBarcode;

        // Show the modal
        editProductModal.show();
    }

    // Event listener for the edit product form submission
    editProductForm.addEventListener('submit', async function (event) {
        event.preventDefault(); // Prevent default form submission

        const formData = new FormData(editProductForm);

        // Convert FormData to a plain JavaScript object for JSON submission
        const productData = {};
        formData.forEach((value, key) => {
            productData[key] = value;
        });

        try {
            // Send a PUT request to your API endpoint (assuming products.php handles PUT)
            const response = await fetch(`../api/products.php`, {
                method: 'PUT', // Use PUT for updating resources
                headers: {
                    'Content-Type': 'application/json' // Indicate that the body is JSON
                },
                body: JSON.stringify(productData) // Send data as JSON string
            });

            const data = await response.json();

            if (data.success) {
                alert('Product updated successfully!');
                editProductModal.hide(); // Hide the modal on success
                fetchProducts(); // Refresh the product list to show updated data
            } else {
                alert('Error updating product: ' + (data.message || 'Unknown error.'));
            }
        } catch (error) {
            console.error('Error updating product:', error);
            alert('Failed to update product due to a network or server error.');
        }
    });

    // Function to handle product deletion
    async function deleteProduct(productId) {
        try {
            // Send a DELETE request to your API endpoint (assuming products.php handles DELETE)
            const response = await fetch(`../api/products.php?id=${productId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            const data = await response.json();

            if (data.success) {
                alert('Product deleted successfully!');
                // Re-fetch products to update the table
                fetchProducts();
            } else {
                alert('Error deleting product: ' + (data.message || 'Unknown error.'));
            }
        } catch (error) {
            console.error('Error deleting product:', error);
            alert('Failed to delete product due to a network or server error.');
        }
    }

    // Helper for HTML escaping (basic)
    function htmlspecialchars(str) {
        if (typeof str !== 'string' && typeof str !== 'number') return str; // Return non-strings/numbers as is
        str = String(str); // Ensure it's a string
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    // Event Listeners for Filters
    productSearch.addEventListener('input', () => {
        currentPage = 1; // Reset to first page on search
        fetchProducts();
    });
    categoryFilter.addEventListener('change', () => {
        currentPage = 1; // Reset to first page on filter change
        fetchProducts();
    });
    resetFiltersBtn.addEventListener('click', () => {
        productSearch.value = '';
        categoryFilter.value = '';
        currentPage = 1;
        fetchProducts();
    });

    // Initial fetch of products when the page loads
    fetchProducts();
});