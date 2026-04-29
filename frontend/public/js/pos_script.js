document.addEventListener('DOMContentLoaded', function () {
    const barcodeInput = document.getElementById('barcodeInput');
    const lookupProductBtn = document.getElementById('lookupProductBtn');
    const productLookupResults = document.getElementById('productLookupResults');
    const cartItemsTableBody = document.getElementById('cartItems');
    const cartTotalSpan = document.getElementById('cartTotal');
    const clearCartBtn = document.getElementById('clearCartBtn');
    const completeSaleBtn = document.getElementById('completeSaleBtn');
    const holdSaleBtn = document.getElementById('holdSaleBtn');
    const posConfirmationModalElement = document.getElementById('posConfirmationModal');
    const posConfirmationTitle = document.getElementById('posConfirmationModalTitle');
    const posConfirmationBody = document.getElementById('posConfirmationModalBody');
    const posConfirmationConfirmBtn = document.getElementById('posConfirmationModalConfirmBtn');
    const posConfirmationCancelBtn = document.getElementById('posConfirmationModalCancelBtn');

    const discountInput = document.getElementById('discountInput');
    const taxRateInput = document.getElementById('taxRateInput');
    const paymentMethodSelect = document.getElementById('paymentMethod');
    const paymentAmountLabel = document.getElementById('paymentAmountLabel');
    const paymentAmountHelpText = document.getElementById('paymentAmountHelpText');
    const cashReceivedInput = document.getElementById('cashReceived');
    const changeDueWrapper = document.getElementById('changeDueWrapper');
    const changeDueSpan = document.getElementById('changeDue');

    const receiptDateSpan = document.getElementById('receipt-date');
    const receiptSaleIdSpan = document.getElementById('receipt-sale-id');
    const receiptCashierSpan = document.getElementById('receipt-cashier');
    const receiptItemsTableBody = document.getElementById('receipt-items');
    const receiptSubtotalSpan = document.getElementById('receipt-subtotal');
    const receiptDiscountPercentSpan = document.getElementById('receipt-discount-percent');
    const receiptDiscountAmountSpan = document.getElementById('receipt-discount-amount');
    const receiptTaxPercentSpan = document.getElementById('receipt-tax-percent');
    const receiptTaxAmountSpan = document.getElementById('receipt-tax-amount');
    const receiptGrandTotalSpan = document.getElementById('receipt-grand-total');
    const receiptPaymentMethodSpan = document.getElementById('receipt-payment-method');
    const receiptCashReceivedRow = document.getElementById('receipt-cash-received-row');
    const receiptPaymentAmountLabelSpan = document.getElementById('receipt-payment-amount-label');
    const receiptCashReceivedSpan = document.getElementById('receipt-cash-received');
    const receiptChangeDueRow = document.getElementById('receipt-change-due-row');
    const receiptChangeDueSpan = document.getElementById('receipt-change-due');

    const newProductModalElement = document.getElementById('newProductModal');
    const newProductForm = document.getElementById('newProductForm');
    const newProductBarcodeInput = document.getElementById('newProductBarcode');
    const newProductNameInput = document.getElementById('newProductName');
    const newProductCategorySelect = document.getElementById('newProductCategory');
    const newProductPriceInput = document.getElementById('newProductPrice');
    const newProductCostPriceInput = document.getElementById('newProductCostPrice');
    const newProductStockInput = document.getElementById('newProductStock');
    const posConfirmationModalInstance = posConfirmationModalElement && typeof bootstrap !== 'undefined'
        ? bootstrap.Modal.getOrCreateInstance(posConfirmationModalElement)
        : null;

    let cart = [];
    let currentLookupResults = [];
    let lookupPreviewTimer = null;
    let currentConfirmationHandler = null;
    let isSaleSubmitting = false;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatCurrency(amount) {
        return `PHP ${parseFloat(amount || 0).toFixed(2)}`;
    }

    function parseCurrencyText(value) {
        const sanitizedValue = String(value == null ? '' : value).replace(/[^0-9.-]/g, '');
        const parsedValue = parseFloat(sanitizedValue);
        return Number.isFinite(parsedValue) ? parsedValue : 0;
    }

    function normalizeProduct(product) {
        return {
            ...product,
            price: parseFloat(product.price) || 0,
            stock_quantity: parseInt(product.stock_quantity, 10) || 0,
        };
    }

    function isLikelyBarcodeQuery(query) {
        return /^[0-9-]+$/.test(String(query || '').trim());
    }

    function clearProductLookupResults() {
        currentLookupResults = [];

        if (!productLookupResults) {
            return;
        }

        productLookupResults.innerHTML = '';
        productLookupResults.classList.add('d-none');
    }

    function renderProductLookupResults(products) {
        if (!productLookupResults) {
            return;
        }

        currentLookupResults = products.map(normalizeProduct);

        if (!currentLookupResults.length) {
            clearProductLookupResults();
            return;
        }

        productLookupResults.innerHTML = currentLookupResults.map(function (product, index) {
            const barcodeText = product.barcode ? escapeHtml(product.barcode) : 'N/A';
            const categoryText = product.category_name ? escapeHtml(product.category_name) : 'Uncategorized';
            const isOutOfStock = product.stock_quantity <= 0;

            return `
                <button
                    type="button"
                    class="list-group-item list-group-item-action product-search-result"
                    data-result-index="${index}"
                    ${isOutOfStock ? 'disabled' : ''}
                >
                    <div class="d-flex w-100 justify-content-between align-items-start gap-3">
                        <div class="text-start">
                            <div class="fw-semibold">${escapeHtml(product.name)}</div>
                            <div class="small text-muted">Barcode: ${barcodeText}</div>
                            <div class="small text-muted">Category: ${categoryText} | Stock: ${product.stock_quantity}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">${formatCurrency(product.price)}</div>
                            <div class="small ${isOutOfStock ? 'text-danger' : 'text-success'}">${isOutOfStock ? 'Out of stock' : 'Click to add'}</div>
                        </div>
                    </div>
                </button>`;
        }).join('');

        productLookupResults.classList.remove('d-none');
    }

    async function fetchProducts(searchTerm, limit) {
        const response = await fetch(`../api/products.php?search=${encodeURIComponent(searchTerm)}&limit=${limit || 8}`);
        if (!response.ok) {
            throw new Error('Network error');
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Unable to fetch products.');
        }

        return Array.isArray(data.products) ? data.products.map(normalizeProduct) : [];
    }

    async function previewProductMatches(searchTerm) {
        const trimmedSearch = String(searchTerm || '').trim();

        if (!trimmedSearch || isLikelyBarcodeQuery(trimmedSearch) || trimmedSearch.length < 2) {
            clearProductLookupResults();
            return;
        }

        try {
            const products = await fetchProducts(trimmedSearch, 6);
            if (!barcodeInput || barcodeInput.value.trim() !== trimmedSearch) {
                return;
            }

            if (products.length > 1) {
                renderProductLookupResults(products);
            } else {
                clearProductLookupResults();
            }
        } catch (error) {
            if (barcodeInput && barcodeInput.value.trim() === trimmedSearch) {
                clearProductLookupResults();
            }
        }
    }

    function showNewProductModal() {
        if (!newProductModalElement) {
            return;
        }

        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            window.bootstrap.Modal.getOrCreateInstance(newProductModalElement).show();
            return;
        }

        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(newProductModalElement).modal('show');
        }
    }

    function hideNewProductModal() {
        if (!newProductModalElement) {
            return;
        }

        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            window.bootstrap.Modal.getOrCreateInstance(newProductModalElement).hide();
            return;
        }

        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(newProductModalElement).modal('hide');
        }
    }

    function showMessageWithCallback(message, type, onClose) {
        const globalMessageModalElement = document.getElementById('globalMessageModal');
        const canUseModalCallback = globalMessageModalElement && typeof bootstrap !== 'undefined';

        if (canUseModalCallback && typeof onClose === 'function') {
            globalMessageModalElement.addEventListener('hidden.bs.modal', function handleHidden() {
                onClose();
            }, { once: true });
        }

        if (typeof window.showMessage === 'function') {
            window.showMessage(message, type);
        } else {
            console.warn('Global message modal is unavailable:', message);
        }

        if (!canUseModalCallback && typeof onClose === 'function') {
            onClose();
        }
    }

    function setConfirmationButtonClass(className) {
        if (!posConfirmationConfirmBtn) {
            return;
        }

        posConfirmationConfirmBtn.classList.remove(
            'btn-primary',
            'btn-success',
            'btn-danger',
            'btn-warning',
            'btn-info',
            'btn-secondary'
        );
        posConfirmationConfirmBtn.classList.add(className || 'btn-primary');
    }

    function showFallbackConfirmationDialog(options) {
        const title = options && options.title ? options.title : 'Confirm Action';
        const message = options && options.message ? options.message : 'Are you sure you want to continue?';
        const confirmLabel = options && options.confirmLabel ? options.confirmLabel : 'Confirm';
        const confirmClass = options && options.confirmClass ? options.confirmClass : 'btn-primary';
        const onConfirm = options && typeof options.onConfirm === 'function' ? options.onConfirm : function () {};

        const existingDialog = document.getElementById('posFallbackConfirmationDialog');
        if (existingDialog) {
            existingDialog.remove();
        }

        const dialog = document.createElement('div');
        dialog.id = 'posFallbackConfirmationDialog';
        dialog.style.position = 'fixed';
        dialog.style.inset = '0';
        dialog.style.zIndex = '2000';
        dialog.style.display = 'flex';
        dialog.style.alignItems = 'center';
        dialog.style.justifyContent = 'center';
        dialog.style.backgroundColor = 'rgba(0, 0, 0, 0.45)';
        dialog.innerHTML = `
            <div class="bg-white rounded shadow p-4" style="width:min(100%, 420px);">
                <h5 class="mb-3">${escapeHtml(title)}</h5>
                <p class="mb-4">${escapeHtml(message)}</p>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-action="cancel">Cancel</button>
                    <button type="button" class="btn ${escapeHtml(confirmClass)}" data-action="confirm">${escapeHtml(confirmLabel)}</button>
                </div>
            </div>
        `;

        const closeDialog = function () {
            dialog.remove();
        };

        dialog.querySelector('[data-action="cancel"]')?.addEventListener('click', closeDialog);
        dialog.querySelector('[data-action="confirm"]')?.addEventListener('click', function () {
            closeDialog();

            Promise.resolve(onConfirm()).catch(function (error) {
                console.error('POS confirmation action failed:', error);
                showMessage('Unable to complete that action right now.', 'danger');
            });
        });

        document.body.appendChild(dialog);
    }

    function showConfirmationModal(options) {
        const title = options && options.title ? options.title : 'Confirm Action';
        const message = options && options.message ? options.message : 'Are you sure you want to continue?';
        const confirmLabel = options && options.confirmLabel ? options.confirmLabel : 'Confirm';
        const confirmClass = options && options.confirmClass ? options.confirmClass : 'btn-primary';
        const onConfirm = options && typeof options.onConfirm === 'function' ? options.onConfirm : function () {};

        if (!posConfirmationModalInstance || !posConfirmationTitle || !posConfirmationBody || !posConfirmationConfirmBtn) {
            showFallbackConfirmationDialog({ title, message, confirmLabel, confirmClass, onConfirm });
            return Promise.resolve();
        }

        currentConfirmationHandler = onConfirm;
        posConfirmationTitle.textContent = title;
        posConfirmationBody.textContent = message;
        posConfirmationConfirmBtn.textContent = confirmLabel;
        posConfirmationConfirmBtn.disabled = false;
        setConfirmationButtonClass(confirmClass);

        if (posConfirmationCancelBtn) {
            posConfirmationCancelBtn.classList.remove('d-none');
            posConfirmationCancelBtn.disabled = false;
        }

        posConfirmationModalInstance.show();
        return Promise.resolve();
    }

    function setSaleSubmitting(isSubmitting) {
        isSaleSubmitting = isSubmitting;

        if (completeSaleBtn) {
            completeSaleBtn.disabled = isSubmitting;
            completeSaleBtn.textContent = isSubmitting ? 'Processing...' : 'Complete Sale';
        }

        if (clearCartBtn) {
            clearCartBtn.disabled = isSubmitting;
        }

        if (lookupProductBtn) {
            lookupProductBtn.disabled = isSubmitting;
        }

        if (barcodeInput) {
            barcodeInput.disabled = isSubmitting;
        }

        if (discountInput) {
            discountInput.disabled = isSubmitting;
        }

        if (taxRateInput) {
            taxRateInput.disabled = isSubmitting;
        }

        if (paymentMethodSelect) {
            paymentMethodSelect.disabled = isSubmitting;
        }

        if (cashReceivedInput) {
            cashReceivedInput.disabled = isSubmitting;
        }

        if (cartItemsTableBody) {
            cartItemsTableBody.style.pointerEvents = isSubmitting ? 'none' : '';
            cartItemsTableBody.style.opacity = isSubmitting ? '0.72' : '';
        }

        if (productLookupResults) {
            productLookupResults.classList.toggle('pe-none', isSubmitting);
        }
    }

    function updatePaymentSection(resetAmount) {
        const paymentMethod = paymentMethodSelect ? paymentMethodSelect.value : 'Cash';
        const isCash = paymentMethod === 'Cash';

        if (paymentAmountLabel) {
            if (paymentMethod === 'GCash') {
                paymentAmountLabel.textContent = 'GCash Amount Received';
            } else if (paymentMethod === 'Credit Card') {
                paymentAmountLabel.textContent = 'Card Amount Received';
            } else {
                paymentAmountLabel.textContent = 'Cash Received';
            }
        }

        if (cashReceivedInput) {
            if (paymentMethod === 'GCash') {
                cashReceivedInput.placeholder = 'Enter confirmed GCash amount';
            } else if (paymentMethod === 'Credit Card') {
                cashReceivedInput.placeholder = 'Enter approved card amount';
            } else {
                cashReceivedInput.placeholder = 'Enter cash received';
            }

            if (resetAmount) {
                cashReceivedInput.value = '';
            }
        }

        if (paymentAmountHelpText) {
            if (paymentMethod === 'GCash') {
                paymentAmountHelpText.textContent = 'Enter the amount confirmed in GCash. This amount will be saved with the sale.';
            } else if (paymentMethod === 'Credit Card') {
                paymentAmountHelpText.textContent = 'Enter the approved card payment amount. This amount will be saved with the sale.';
            } else {
                paymentAmountHelpText.textContent = 'Enter the amount received from the customer before completing the sale.';
            }
        }

        if (changeDueWrapper) {
            changeDueWrapper.style.display = isCash ? 'block' : 'none';
        }

        updateChangeDue();
    }

    function updateCartDisplay() {
        if (!cartItemsTableBody) {
            return;
        }

        cartItemsTableBody.innerHTML = '';

        if (cart.length === 0) {
            cartItemsTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No items in cart yet. Scan a product!</td>
                </tr>`;
        } else {
            cart.forEach(function (item) {
                const row = cartItemsTableBody.insertRow();
                row.dataset.productId = item.id;
                row.innerHTML = `
                    <td>${escapeHtml(item.name)}</td>
                    <td>${formatCurrency(item.price)}</td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary quantity-minus" type="button" data-product-id="${item.id}">-</button>
                            <input type="number" class="form-control text-center item-quantity" value="${item.quantity}" min="1" max="${item.stock_quantity}" step="1" inputmode="numeric" data-product-id="${item.id}">
                            <button class="btn btn-outline-secondary quantity-plus" type="button" data-product-id="${item.id}">+</button>
                        </div>
                    </td>
                    <td>${formatCurrency(item.price * item.quantity)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-item" data-product-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>`;
            });
        }

        calculateTotals();
    }

    function addProductToCart(product) {
        const normalizedProduct = normalizeProduct(product);
        const existingItemIndex = cart.findIndex(function (item) {
            return item.id === normalizedProduct.id;
        });

        if (existingItemIndex > -1) {
            if (cart[existingItemIndex].quantity < normalizedProduct.stock_quantity) {
                cart[existingItemIndex].quantity += 1;
            } else {
                showMessage(`Stock limit reached (${normalizedProduct.stock_quantity}) for ${normalizedProduct.name}.`, 'warning');
            }
        } else if (normalizedProduct.stock_quantity > 0) {
            cart.push({
                id: normalizedProduct.id,
                name: normalizedProduct.name,
                barcode: normalizedProduct.barcode,
                price: normalizedProduct.price,
                quantity: 1,
                stock_quantity: normalizedProduct.stock_quantity,
            });
        } else {
            showMessage(`Product "${normalizedProduct.name}" is out of stock.`, 'warning');
        }

        updateCartDisplay();
        clearProductLookupResults();

        if (barcodeInput) {
            barcodeInput.value = '';
            barcodeInput.focus();
        }
    }

    function handleQuantityChange(productId, change) {
        const itemIndex = cart.findIndex(function (item) {
            return item.id == productId;
        });

        if (itemIndex === -1) {
            return;
        }

        const item = cart[itemIndex];
        let newQuantity = item.quantity + change;

        if (newQuantity < 1) {
            removeItemFromCart(productId);
            return;
        }

        if (newQuantity > item.stock_quantity) {
            showMessage(`Max stock: ${item.stock_quantity} for ${item.name}.`, 'warning');
            newQuantity = item.stock_quantity;
        }

        item.quantity = newQuantity;
        updateCartDisplay();
    }

    function setItemQuantity(productId, quantityValue) {
        const itemIndex = cart.findIndex(function (item) {
            return item.id == productId;
        });

        if (itemIndex === -1) {
            return;
        }

        const item = cart[itemIndex];
        const trimmedValue = String(quantityValue).trim();

        if (trimmedValue === '') {
            updateCartDisplay();
            return;
        }

        let newQuantity = parseInt(trimmedValue, 10);

        if (Number.isNaN(newQuantity)) {
            showMessage(`Please enter a valid quantity for ${item.name}.`, 'warning');
            updateCartDisplay();
            return;
        }

        if (newQuantity < 1) {
            removeItemFromCart(productId);
            return;
        }

        if (newQuantity > item.stock_quantity) {
            showMessage(`Max stock: ${item.stock_quantity} for ${item.name}.`, 'warning');
            newQuantity = item.stock_quantity;
        }

        item.quantity = newQuantity;
        updateCartDisplay();
    }

    function removeItemFromCart(productId) {
        const item = cart.find(function (cartItem) {
            return cartItem.id == productId;
        });

        void showConfirmationModal({
            title: 'Remove Item',
            message: item ? `Remove "${item.name}" from the cart?` : 'Remove this item from the cart?',
            confirmLabel: 'Remove',
            confirmClass: 'btn-danger',
            onConfirm: function () {
                cart = cart.filter(function (cartItem) {
                    return cartItem.id != productId;
                });
                updateCartDisplay();
            },
        });
    }

    function calculateTotals() {
        const subtotal = cart.reduce(function (sum, item) {
            return sum + (item.price * item.quantity);
        }, 0);
        const discountPercentage = parseFloat(discountInput ? discountInput.value : 0) || 0;
        const taxRatePercentage = parseFloat(taxRateInput ? taxRateInput.value : 0) || 0;
        const discountAmount = subtotal * (discountPercentage / 100);
        const totalAfterDiscount = subtotal - discountAmount;
        const taxAmount = totalAfterDiscount * (taxRatePercentage / 100);
        const grandTotal = totalAfterDiscount + taxAmount;

        if (cartTotalSpan) {
            cartTotalSpan.textContent = formatCurrency(grandTotal);
        }

        updateChangeDue();
    }

    function updateChangeDue() {
        const grandTotal = parseCurrencyText(cartTotalSpan ? cartTotalSpan.textContent : 0);
        const paymentAmount = parseFloat(cashReceivedInput ? cashReceivedInput.value : 0) || 0;

        if (!changeDueSpan) {
            return;
        }

        if (paymentMethodSelect && paymentMethodSelect.value === 'Cash') {
            changeDueSpan.textContent = formatCurrency(paymentAmount - grandTotal);
        } else {
            changeDueSpan.textContent = formatCurrency(0);
        }
    }

    function clearCart() {
        if (!cart.length) {
            showMessage('Cart is already empty.', 'info');
            return;
        }

        void showConfirmationModal({
            title: 'Clear Cart',
            message: 'Clear the entire cart and reset the current sale?',
            confirmLabel: 'Clear Cart',
            confirmClass: 'btn-danger',
            onConfirm: function () {
                cart = [];
                updateCartDisplay();
                clearProductLookupResults();

                if (discountInput) {
                    discountInput.value = '0';
                }
                if (taxRateInput) {
                    taxRateInput.value = '12';
                }
                if (paymentMethodSelect) {
                    paymentMethodSelect.value = 'Cash';
                }

                updatePaymentSection(true);
            },
        });
    }

    async function lookupProduct() {
        const searchTerm = barcodeInput ? barcodeInput.value.trim() : '';

        if (!searchTerm) {
            showMessage('Please scan a barcode or type a product name.', 'warning');
            return;
        }

        clearTimeout(lookupPreviewTimer);

        try {
            const products = await fetchProducts(searchTerm, 8);
            const normalizedQuery = searchTerm.toLowerCase();
            const exactMatch = products.find(function (product) {
                return String(product.barcode || '').trim().toLowerCase() === normalizedQuery ||
                    String(product.name || '').trim().toLowerCase() === normalizedQuery;
            });

            if (exactMatch || products.length === 1) {
                addProductToCart(exactMatch || products[0]);
                return;
            }

            if (products.length > 1) {
                renderProductLookupResults(products);
                return;
            }

            clearProductLookupResults();

            if (isLikelyBarcodeQuery(searchTerm)) {
                if (newProductBarcodeInput) {
                    newProductBarcodeInput.value = searchTerm;
                }
                if (newProductNameInput) {
                    newProductNameInput.value = '';
                }
                showNewProductModal();
            } else {
                showMessage(`No products found matching "${searchTerm}".`, 'warning');
            }
        } catch (error) {
            showMessage('Failed to lookup product.', 'danger');
        }
    }

    async function completeSale() {
        if (isSaleSubmitting) {
            return;
        }

        if (cart.length === 0) {
            showMessage('Cart is empty.', 'warning');
            return;
        }

        const grandTotal = parseCurrencyText(cartTotalSpan ? cartTotalSpan.textContent : 0);
        const discountPercentage = parseFloat(discountInput ? discountInput.value : 0) || 0;
        const taxRate = parseFloat(taxRateInput ? taxRateInput.value : 0) || 0;
        const paymentMethod = paymentMethodSelect ? paymentMethodSelect.value : 'Cash';
        const paymentAmount = parseFloat(cashReceivedInput ? cashReceivedInput.value : 0);
        const paymentLabelText = paymentAmountLabel ? paymentAmountLabel.textContent.toLowerCase() : 'payment amount';

        if (!Number.isFinite(paymentAmount) || paymentAmount <= 0) {
            showMessage(`Enter the ${paymentLabelText} before completing the sale.`, 'warning');
            if (cashReceivedInput) {
                cashReceivedInput.focus();
            }
            return;
        }

        if (paymentAmount < grandTotal) {
            if (paymentMethod === 'Cash') {
                showMessage('Insufficient cash.', 'warning');
            } else {
                showMessage(`The ${paymentMethod} amount must cover the total sale.`, 'warning');
            }
            return;
        }

        const changeDue = paymentMethod === 'Cash' ? paymentAmount - grandTotal : 0;
        const subtotal = cart.reduce(function (sum, item) {
            return sum + (item.price * item.quantity);
        }, 0);
        const discountAmount = subtotal * (discountPercentage / 100);
        const taxAmount = (subtotal - discountAmount) * (taxRate / 100);

        const cartSnapshot = cart.map(function (item) {
            return { ...item };
        });

        const salePayload = {
            cart: cartSnapshot,
            total_amount: grandTotal,
            discount_amount: discountAmount,
            tax_amount: taxAmount,
            payment_method: paymentMethod,
            cash_received: paymentAmount,
            change_due: changeDue,
        };

        await showConfirmationModal({
            title: 'Complete Sale',
            message: `Confirm sale total: ${formatCurrency(grandTotal)}?`,
            confirmLabel: 'Complete Sale',
            confirmClass: 'btn-success',
            onConfirm: async function () {
                if (isSaleSubmitting) {
                    return;
                }

                setSaleSubmitting(true);

                try {
                    const response = await fetch('../api/complete_sale.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(salePayload),
                    });
                    const result = await response.json();

                    if (result.success) {
                        cart = [];
                        updateCartDisplay();
                        clearProductLookupResults();

                        if (discountInput) {
                            discountInput.value = '0';
                        }
                        if (taxRateInput) {
                            taxRateInput.value = '12';
                        }
                        if (paymentMethodSelect) {
                            paymentMethodSelect.value = 'Cash';
                        }

                        updatePaymentSection(true);

                        showMessageWithCallback('Sale completed. Sale ID: ' + result.sale_id, 'success', function () {
                            populateReceipt(
                                result.sale_id,
                                cartSnapshot,
                                grandTotal,
                                subtotal,
                                discountAmount,
                                discountPercentage,
                                taxAmount,
                                taxRate,
                                paymentMethod,
                                paymentAmount,
                                changeDue
                            );
                        });
                    } else {
                        showMessage('Failed: ' + (result.message || 'Unknown error.'), 'danger');
                    }
                } catch (error) {
                    showMessage('Error completing sale.', 'danger');
                } finally {
                    setSaleSubmitting(false);
                }
            },
        });
    }

    function populateReceipt(saleId, cartItems, grandTotal, subtotal, discountAmount, discountPercent, taxAmount, taxPercent, paymentMethod, cashReceived, changeDue) {
        if (receiptDateSpan) {
            receiptDateSpan.textContent = new Date().toLocaleString();
        }
        if (receiptSaleIdSpan) {
            receiptSaleIdSpan.textContent = saleId;
        }

        if (receiptItemsTableBody) {
            receiptItemsTableBody.innerHTML = '';
            cartItems.forEach(function (item) {
                const row = receiptItemsTableBody.insertRow();
                row.innerHTML = `
                    <td style="text-align: left;">${escapeHtml(item.name)}</td>
                    <td style="text-align: center;">${item.quantity}</td>
                    <td style="text-align: right;">${formatCurrency(item.price)}</td>
                    <td style="text-align: right;">${formatCurrency(item.price * item.quantity)}</td>`;
            });
        }

        if (receiptSubtotalSpan) {
            receiptSubtotalSpan.textContent = formatCurrency(subtotal);
        }
        if (receiptDiscountPercentSpan) {
            receiptDiscountPercentSpan.textContent = discountPercent.toFixed(2);
        }
        if (receiptDiscountAmountSpan) {
            receiptDiscountAmountSpan.textContent = formatCurrency(discountAmount);
        }
        if (receiptTaxPercentSpan) {
            receiptTaxPercentSpan.textContent = taxPercent.toFixed(2);
        }
        if (receiptTaxAmountSpan) {
            receiptTaxAmountSpan.textContent = formatCurrency(taxAmount);
        }
        if (receiptGrandTotalSpan) {
            receiptGrandTotalSpan.textContent = formatCurrency(grandTotal);
        }
        if (receiptPaymentMethodSpan) {
            receiptPaymentMethodSpan.textContent = paymentMethod;
        }

        if (receiptCashReceivedRow) {
            receiptCashReceivedRow.style.display = 'block';
        }
        if (receiptCashReceivedSpan) {
            receiptCashReceivedSpan.textContent = formatCurrency(cashReceived);
        }

        if (paymentMethod === 'Cash') {
            if (receiptPaymentAmountLabelSpan) {
                receiptPaymentAmountLabelSpan.textContent = 'Cash Received';
            }
            if (receiptChangeDueRow) {
                receiptChangeDueRow.style.display = 'block';
            }
            if (receiptChangeDueSpan) {
                receiptChangeDueSpan.textContent = formatCurrency(changeDue);
            }
        } else {
            if (receiptPaymentAmountLabelSpan) {
                receiptPaymentAmountLabelSpan.textContent = `${paymentMethod} Amount`;
            }
            if (receiptChangeDueRow) {
                receiptChangeDueRow.style.display = 'none';
            }
        }

        window.print();
    }

    function loadCategories() {
        fetch('../api/categories.php?action=list')
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(function (data) {
                if (!newProductCategorySelect) {
                    return;
                }

                newProductCategorySelect.innerHTML = '<option value="" disabled selected>Select a category</option>';

                if (Array.isArray(data) && data.length) {
                    data.forEach(function (category) {
                        newProductCategorySelect.innerHTML += `<option value="${category.id}">${escapeHtml(category.name)}</option>`;
                    });
                } else {
                    showMessage('No categories available. Please add categories first.', 'warning');
                }
            })
            .catch(function (error) {
                console.error('Error loading categories:', error);
                showMessage('Failed to load categories. Check console for details.', 'danger');
            });
    }

    newProductModalElement?.addEventListener('show.bs.modal', loadCategories);

    newProductForm?.addEventListener('submit', async function (event) {
        event.preventDefault();

        const newProduct = {
            barcode: newProductBarcodeInput ? newProductBarcodeInput.value : '',
            name: newProductNameInput ? newProductNameInput.value.trim() : '',
            category_id: newProductCategorySelect ? newProductCategorySelect.value.trim() : '',
            price: parseFloat(newProductPriceInput ? newProductPriceInput.value : 0),
            cost_price: parseFloat(newProductCostPriceInput ? newProductCostPriceInput.value : 0),
            stock_quantity: parseInt(newProductStockInput ? newProductStockInput.value : 0, 10),
        };

        try {
            const response = await fetch('../api/products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create', product: newProduct }),
            });
            const result = await response.json();

            if (result.success) {
                hideNewProductModal();
                showMessage('New product added.', 'success');
                addProductToCart({ id: result.product_id, ...newProduct });
            } else {
                showMessage('Failed: ' + (result.message || 'Unknown error.'), 'danger');
            }
        } catch (error) {
            showMessage('Error saving product.', 'danger');
        }
    });

    posConfirmationConfirmBtn?.addEventListener('click', function () {
        if (!currentConfirmationHandler) {
            posConfirmationModalInstance?.hide();
            return;
        }

        const handler = currentConfirmationHandler;
        currentConfirmationHandler = null;
        posConfirmationConfirmBtn.disabled = true;

        if (posConfirmationCancelBtn) {
            posConfirmationCancelBtn.disabled = true;
        }

        posConfirmationModalInstance?.hide();

        Promise.resolve(handler()).catch(function (error) {
            console.error('POS confirmation action failed:', error);
            showMessage('Unable to complete that action right now.', 'danger');
        });
    });

    posConfirmationModalElement?.addEventListener('hidden.bs.modal', function () {
        currentConfirmationHandler = null;

        if (posConfirmationTitle) {
            posConfirmationTitle.textContent = 'Confirm Action';
        }

        if (posConfirmationBody) {
            posConfirmationBody.textContent = 'Are you sure you want to continue?';
        }

        if (posConfirmationConfirmBtn) {
            posConfirmationConfirmBtn.disabled = false;
            posConfirmationConfirmBtn.textContent = 'Confirm';
            setConfirmationButtonClass('btn-primary');
        }

        if (posConfirmationCancelBtn) {
            posConfirmationCancelBtn.classList.remove('d-none');
            posConfirmationCancelBtn.disabled = false;
        }
    });

    lookupProductBtn?.addEventListener('click', lookupProduct);
    barcodeInput?.addEventListener('keypress', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            void lookupProduct();
        }
    });
    barcodeInput?.addEventListener('input', function () {
        const searchTerm = this.value.trim();

        clearTimeout(lookupPreviewTimer);

        if (!searchTerm || isLikelyBarcodeQuery(searchTerm) || searchTerm.length < 2) {
            clearProductLookupResults();
            return;
        }

        lookupPreviewTimer = window.setTimeout(function () {
            void previewProductMatches(searchTerm);
        }, 220);
    });

    clearCartBtn?.addEventListener('click', clearCart);
    completeSaleBtn?.addEventListener('click', completeSale);
    holdSaleBtn?.addEventListener('click', function () {
        showMessage('Hold Sale not implemented.', 'info');
    });
    discountInput?.addEventListener('input', calculateTotals);
    taxRateInput?.addEventListener('input', calculateTotals);
    paymentMethodSelect?.addEventListener('change', function () {
        updatePaymentSection(true);
    });
    cashReceivedInput?.addEventListener('input', updateChangeDue);
    productLookupResults?.addEventListener('click', function (event) {
        const resultButton = event.target.closest('.product-search-result');
        if (!resultButton) {
            return;
        }

        const resultIndex = parseInt(resultButton.dataset.resultIndex, 10);
        if (!Number.isInteger(resultIndex) || !currentLookupResults[resultIndex]) {
            return;
        }

        addProductToCart(currentLookupResults[resultIndex]);
    });

    cartItemsTableBody?.addEventListener('click', function (event) {
        const button = event.target.closest('.quantity-plus, .quantity-minus, .remove-item');
        if (!button) {
            return;
        }

        const productId = button.dataset.productId;
        if (button.classList.contains('quantity-plus')) {
            handleQuantityChange(productId, 1);
        }
        if (button.classList.contains('quantity-minus')) {
            handleQuantityChange(productId, -1);
        }
        if (button.classList.contains('remove-item')) {
            removeItemFromCart(productId);
        }
    });

    cartItemsTableBody?.addEventListener('change', function (event) {
        const quantityInput = event.target.closest('.item-quantity');
        if (quantityInput) {
            setItemQuantity(quantityInput.dataset.productId, quantityInput.value);
        }
    });

    cartItemsTableBody?.addEventListener('keydown', function (event) {
        const quantityInput = event.target.closest('.item-quantity');
        if (!quantityInput) {
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            quantityInput.blur();
        }

        if (['e', 'E', '+', '-', '.'].includes(event.key)) {
            event.preventDefault();
        }
    });

    cartItemsTableBody?.addEventListener('focusin', function (event) {
        const quantityInput = event.target.closest('.item-quantity');
        if (quantityInput) {
            quantityInput.select();
        }
    });

    updateCartDisplay();
    calculateTotals();
    updatePaymentSection(false);
});
