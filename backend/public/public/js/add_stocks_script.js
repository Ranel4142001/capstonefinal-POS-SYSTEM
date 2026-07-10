// public/js/add_stocks_script.js
document.addEventListener('DOMContentLoaded', function () {
    const addStockForm = document.getElementById('addStockForm');
    const productSelect = document.getElementById('product_id');
    const productInventoryTableBody = document.querySelector('#productInventoryTable tbody');
    const editStockForm = document.getElementById('editStockForm');
    const deleteStockForm = document.getElementById('deleteStockForm');
    const editStockProductIdInput = document.getElementById('edit_stock_product_id');
    const editStockProductNameInput = document.getElementById('edit_stock_product_name');
    const editStockQuantityInput = document.getElementById('edit_stock_quantity');
    const deleteStockProductIdInput = document.getElementById('delete_stock_product_id');
    const deleteStockProductName = document.getElementById('delete_stock_product_name');
    const editStockModalElement = document.getElementById('editStockModal');
    const deleteStockModalElement = document.getElementById('deleteStockModal');
    const editStockModal = editStockModalElement ? new bootstrap.Modal(editStockModalElement) : null;
    const deleteStockModal = deleteStockModalElement ? new bootstrap.Modal(deleteStockModalElement) : null;
    const stocksApiUrl = '../api/stocks.php';

    const LOW_STOCK_THRESHOLD = 10;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            const entities = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };

            return entities[character];
        });
    }

    function getStockCellHtml(stockQuantity) {
        const quantity = parseInt(stockQuantity, 10) || 0;
        return `
            ${quantity}
            ${quantity <= LOW_STOCK_THRESHOLD ? '<span class="badge bg-warning text-dark ms-2">(Low Stock)</span>' : ''}
        `;
    }

    function updateSelectOption(productId, productName, stockQuantity) {
        const option = productSelect.querySelector(`option[value="${productId}"]`);
        if (option) {
            option.textContent = `${productName} (Current Stock: ${stockQuantity})`;
        }
    }

    function updateProductRow(productId, productName, stockQuantity) {
        const row = productInventoryTableBody.querySelector(`tr[data-product-id="${productId}"]`);
        if (!row) {
            return;
        }

        const stockCell = row.querySelector('.product-stock-cell');
        const editButton = row.querySelector('.edit-stock-btn');
        const nameCell = row.querySelector('.product-name-cell');

        if (stockCell) {
            stockCell.innerHTML = getStockCellHtml(stockQuantity);
        }

        if (nameCell) {
            nameCell.textContent = productName;
        }

        if (editButton) {
            editButton.dataset.stockQuantity = String(stockQuantity);
            editButton.dataset.productName = productName;
        }

        const deleteButton = row.querySelector('.delete-stock-btn');
        if (deleteButton) {
            deleteButton.dataset.productName = productName;
        }
    }

    function removeProductRow(productId) {
        const row = productInventoryTableBody.querySelector(`tr[data-product-id="${productId}"]`);
        if (row) {
            row.remove();
        }

        const option = productSelect.querySelector(`option[value="${productId}"]`);
        if (option) {
            option.remove();
        }

        if (productInventoryTableBody.children.length === 0) {
            productInventoryTableBody.innerHTML = '<tr><td colspan="7">No products found in the inventory.</td></tr>';
        }
    }

    async function sendStockRequest(formData) {
        const response = await fetch(stocksApiUrl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            const text = await response.text();
            throw new Error(`HTTP error! Status: ${response.status}. Response: ${text}`);
        }

        return response.json();
    }

    function renderProducts(products, selectedProductId = '') {
        productSelect.innerHTML = '<option value="">Select a Product</option>';
        productInventoryTableBody.innerHTML = '';

        if (products.length === 0) {
            productSelect.innerHTML += '<option value="" disabled>No products found</option>';
            productInventoryTableBody.innerHTML = '<tr><td colspan="7">No products found in the inventory.</td></tr>';
            return;
        }

        products.forEach(product => {
            const productId = String(product.id);
            const productName = String(product.name ?? '');
            const stockQuantity = parseInt(product.stock_quantity, 10) || 0;

            const option = document.createElement('option');
            option.value = productId;
            option.textContent = `${productName} (Current Stock: ${stockQuantity})`;
            if (productId === String(selectedProductId)) {
                option.selected = true;
            }
            productSelect.appendChild(option);

            const row = document.createElement('tr');
            row.dataset.productId = productId;
            row.innerHTML = `
                <td>${productId}</td>
                <td class="product-name-cell">${escapeHtml(productName)}</td>
                <td>${escapeHtml(product.category_name || 'N/A')}</td>
                <td>₱${parseFloat(product.price).toFixed(2)}</td>
                <td class="product-stock-cell">${getStockCellHtml(stockQuantity)}</td>
                <td>${escapeHtml(product.barcode || 'N/A')}</td>
                <td>
                    <button type="button" class="btn btn-info btn-sm me-1 edit-stock-btn" data-product-id="${productId}" data-product-name="${escapeHtml(productName)}" data-stock-quantity="${stockQuantity}" title="Edit Stock">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm delete-stock-btn" data-product-id="${productId}" data-product-name="${escapeHtml(productName)}" title="Delete Product">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            productInventoryTableBody.appendChild(row);
        });
    }

    function loadProducts(selectedProductId = productSelect.value) {
        return fetch(`${stocksApiUrl}?action=list_products&_=${Date.now()}`, {
            cache: 'no-store'
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
                if (data.status === 'success' && data.data) {
                    renderProducts(data.data, selectedProductId);
                    return;
                }

                console.error('API Error loading products:', data.message);
                showMessage(data.message || 'Failed to load products.', 'danger');
                productSelect.innerHTML = '<option value="" disabled>Error loading products</option>';
                productInventoryTableBody.innerHTML = '<tr><td colspan="7">Error loading products.</td></tr>';
            })
            .catch(error => {
                console.error('Network or parsing error:', error);
                showMessage('An unexpected error occurred while loading products. Check console for details.', 'danger');
                productSelect.innerHTML = '<option value="" disabled>Network Error</option>';
                productInventoryTableBody.innerHTML = '<tr><td colspan="7">Network error loading products.</td></tr>';
            });
    }

    function openEditStockModal(productId, productName, currentStock) {
        editStockProductIdInput.value = productId;
        editStockProductNameInput.value = productName;
        editStockQuantityInput.value = currentStock;
        editStockModal.show();
    }

    function openDeleteStockModal(productId, productName) {
        deleteStockProductIdInput.value = productId;
        deleteStockProductName.textContent = productName;
        deleteStockModal.show();
    }

    loadProducts();

    if (addStockForm) {
        addStockForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'add_stock');

            try {
                const data = await sendStockRequest(formData);
                if (data.status === 'success') {
                    showMessage(data.message, 'success');
                    addStockForm.reset();
                    await loadProducts(formData.get('product_id'));
                } else {
                    showMessage(data.message, 'danger');
                }
            } catch (error) {
                console.error('Error adding stock:', error);
                showMessage('An error occurred while adding stock. Check console for details.', 'danger');
            }
        });
    }

    if (editStockForm) {
        editStockForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const formData = new FormData(editStockForm);
            formData.append('action', 'edit_stock');

            const productId = formData.get('product_id');
            const productName = editStockProductNameInput.value;

            try {
                const data = await sendStockRequest(formData);
                if (data.status === 'success') {
                    const newStock = data.new_stock ?? editStockQuantityInput.value;
                    updateProductRow(productId, productName, newStock);
                    updateSelectOption(productId, productName, newStock);
                    editStockModal.hide();
                    showMessage(data.message, 'success');
                    await loadProducts(productId);
                } else {
                    showMessage(data.message || 'Failed to update stock.', 'danger');
                }
            } catch (error) {
                console.error('Error editing stock:', error);
                showMessage('An error occurred while updating stock. Check console for details.', 'danger');
            }
        });
    }

    if (deleteStockForm) {
        deleteStockForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const formData = new FormData(deleteStockForm);
            formData.append('action', 'delete_product');

            const productId = formData.get('product_id');

            try {
                const data = await sendStockRequest(formData);
                if (data.status === 'success') {
                    deleteStockModal.hide();
                    removeProductRow(productId);
                    if (productSelect.value === String(productId)) {
                        addStockForm.reset();
                    }
                    showMessage(data.message, 'success');
                    await loadProducts();
                } else {
                    showMessage(data.message || 'Failed to delete product.', 'danger');
                }
            } catch (error) {
                console.error('Error deleting product:', error);
                showMessage('An error occurred while deleting the product. Check console for details.', 'danger');
            }
        });
    }

    if (productInventoryTableBody) {
        productInventoryTableBody.addEventListener('click', function (event) {
            const editButton = event.target.closest('.edit-stock-btn');
            if (editButton) {
                openEditStockModal(
                    editButton.dataset.productId,
                    editButton.dataset.productName,
                    editButton.dataset.stockQuantity
                );
                return;
            }

            const deleteButton = event.target.closest('.delete-stock-btn');
            if (deleteButton) {
                openDeleteStockModal(
                    deleteButton.dataset.productId,
                    deleteButton.dataset.productName
                );
            }
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const msg = urlParams.get('msg');

    if (status && msg) {
        showMessage(decodeURIComponent(msg), status);
        history.replaceState({}, document.title, window.location.pathname);
    }
});
