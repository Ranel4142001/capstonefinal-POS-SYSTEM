document.addEventListener('DOMContentLoaded', function () {
    // --- DOM Elements ---
    const barcodeInput = document.getElementById('barcodeInput');
    const lookupProductBtn = document.getElementById('lookupProductBtn');
    const cartItemsTableBody = document.getElementById('cartItems');
    const cartTotalSpan = document.getElementById('cartTotal');
    const clearCartBtn = document.getElementById('clearCartBtn');
    const completeSaleBtn = document.getElementById('completeSaleBtn');
    const holdSaleBtn = document.getElementById('holdSaleBtn');

    const discountInput = document.getElementById('discountInput');
    const taxRateInput = document.getElementById('taxRateInput');
    const paymentMethodSelect = document.getElementById('paymentMethod');
    const cashReceivedInput = document.getElementById('cashReceived');
    const changeDueSpan = document.getElementById('changeDue');

    // Receipt elements
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
    const receiptCashReceivedSpan = document.getElementById('receipt-cash-received');
    const receiptChangeDueRow = document.getElementById('receipt-change-due-row');
    const receiptChangeDueSpan = document.getElementById('receipt-change-due');

    // --- Cart Data ---
    let cart = [];

    // --- Utility Functions ---
    function formatCurrency(amount) {
        return `₱ ${parseFloat(amount).toFixed(2)}`;
    }

    function updateCartDisplay() {
        cartItemsTableBody.innerHTML = '';

        if (cart.length === 0) {
            cartItemsTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No items in cart yet. Scan a product!</td>
                </tr>`;
        } else {
            cart.forEach(item => {
                const row = cartItemsTableBody.insertRow();
                row.dataset.productId = item.id;
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td>${formatCurrency(item.price)}</td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary quantity-minus" type="button" data-product-id="${item.id}">-</button>
                            <input type="text" class="form-control text-center item-quantity" value="${item.quantity}" data-product-id="${item.id}" readonly>
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
        const existingItemIndex = cart.findIndex(item => item.id === product.id);

        if (existingItemIndex > -1) {
            if (cart[existingItemIndex].quantity < product.stock_quantity) {
                cart[existingItemIndex].quantity += 1;
            } else {
                alert(`Stock limit reached (${product.stock_quantity}) for ${product.name}.`);
            }
        } else {
            if (product.stock_quantity > 0) {
                cart.push({
                    id: product.id,
                    name: product.name,
                    barcode: product.barcode,
                    price: parseFloat(product.price),
                    quantity: 1,
                    stock_quantity: parseInt(product.stock_quantity)
                });
            } else {
                alert(`Product "${product.name}" is out of stock.`);
            }
        }
        updateCartDisplay();
        barcodeInput.value = '';
        barcodeInput.focus();
    }

    function handleQuantityChange(productId, change) {
        const itemIndex = cart.findIndex(item => item.id == productId);
        if (itemIndex > -1) {
            const item = cart[itemIndex];
            let newQuantity = item.quantity + change;

            if (newQuantity < 1) {
                removeItemFromCart(productId);
                return;
            }
            if (newQuantity > item.stock_quantity) {
                alert(`Max stock: ${item.stock_quantity} for ${item.name}.`);
                newQuantity = item.stock_quantity;
            }

            item.quantity = newQuantity;
            updateCartDisplay();
        }
    }

    function removeItemFromCart(productId) {
        if (confirm('Remove this item?')) {
            cart = cart.filter(item => item.id != productId);
            updateCartDisplay();
        }
    }

    function calculateTotals() {
        let subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discountPercentage = parseFloat(discountInput.value) || 0;
        const taxRatePercentage = parseFloat(taxRateInput.value) || 0;

        let discountAmount = subtotal * (discountPercentage / 100);
        let totalAfterDiscount = subtotal - discountAmount;
        let taxAmount = totalAfterDiscount * (taxRatePercentage / 100);
        let grandTotal = totalAfterDiscount + taxAmount;

        cartTotalSpan.textContent = formatCurrency(grandTotal);
        updateChangeDue();
    }

    function updateChangeDue() {
        const grandTotal = parseFloat(cartTotalSpan.textContent.replace('₱ ', '')) || 0;
        const cashReceived = parseFloat(cashReceivedInput.value) || 0;

        if (paymentMethodSelect.value === 'Cash') {
            changeDueSpan.textContent = formatCurrency(cashReceived - grandTotal);
        } else {
            changeDueSpan.textContent = formatCurrency(0);
        }
    }

    function clearCart() {
        if (confirm('Clear entire cart?')) {
            cart = [];
            updateCartDisplay();
            discountInput.value = '0';
            taxRateInput.value = '12';
            paymentMethodSelect.value = 'Cash';
            cashReceivedInput.value = '';
            updateChangeDue();
        }
    }

    // --- Product Lookup ---
    async function lookupProduct() {
        const barcode = barcodeInput.value.trim();
        if (!barcode) {
            alert('Please scan or type a barcode.');
            return;
        }
        try {
            const response = await fetch(`../api/products.php?search=${barcode}`);
            if (!response.ok) throw new Error('Network error');
            const data = await response.json();

            if (data.success && data.products?.length) {
                let foundProduct = data.products.find(p => p.barcode === barcode) || data.products[0];
                if (foundProduct.stock_quantity > 0) {
                    addProductToCart(foundProduct);
                } else {
                    alert(`Product "${foundProduct.name}" is out of stock.`);
                }
            } else {
                $('#newProductBarcode').val(barcode);
                $('#newProductModal').modal('show');
            }
        } catch (error) {
            alert('Failed to lookup product.');
        }
    }

    // --- Sale Management ---
    async function completeSale() {
        if (cart.length === 0) {
            alert('Cart is empty.');
            return;
        }
        const grandTotal = parseFloat(cartTotalSpan.textContent.replace('₱ ', ''));
        const discountPercentage = parseFloat(discountInput.value) || 0;
        const taxRate = parseFloat(taxRateInput.value) || 0;
        const paymentMethod = paymentMethodSelect.value;
        const cashReceived = parseFloat(cashReceivedInput.value) || 0;
        const changeDue = cashReceived - grandTotal;

        if (paymentMethod === 'Cash' && cashReceived < grandTotal) {
            alert('Insufficient cash.');
            return;
        }

        let subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        let discountAmount = subtotal * (discountPercentage / 100);
        let taxAmount = (subtotal - discountAmount) * (taxRate / 100);

        if (!confirm(`Confirm sale total: ${formatCurrency(grandTotal)}?`)) return;

        try {
            const response = await fetch('../api/complete_sale.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart, total_amount: grandTotal,
                    discount_amount: discountAmount,
                    tax_amount: taxAmount,
                    payment_method: paymentMethod,
                    cash_received: cashReceived,
                    change_due: changeDue
                }),
            });
            const result = await response.json();

            if (result.success) {
                alert('Sale completed. Sale ID: ' + result.sale_id);
                populateReceipt(result.sale_id, cart, grandTotal, subtotal, discountAmount, discountPercentage, taxAmount, taxRate, paymentMethod, cashReceived, changeDue);
                cart = [];
                updateCartDisplay();
                discountInput.value = '0';
                taxRateInput.value = '12';
                paymentMethodSelect.value = 'Cash';
                cashReceivedInput.value = '';
                updateChangeDue();
            } else {
                alert('Failed: ' + (result.message || 'Unknown error.'));
            }
        } catch {
            alert('Error completing sale.');
        }
    }

    function populateReceipt(saleId, cartItems, grandTotal, subtotal, discountAmount, discountPercent, taxAmount, taxPercent, paymentMethod, cashReceived, changeDue) {
        receiptDateSpan.textContent = new Date().toLocaleString();
        receiptSaleIdSpan.textContent = saleId;

        receiptItemsTableBody.innerHTML = '';
        cartItems.forEach(item => {
            const row = receiptItemsTableBody.insertRow();
            row.innerHTML = `
                <td style="text-align: left;">${item.name}</td>
                <td style="text-align: center;">${item.quantity}</td>
                <td style="text-align: right;">${formatCurrency(item.price)}</td>
                <td style="text-align: right;">${formatCurrency(item.price * item.quantity)}</td>`;
        });

        receiptSubtotalSpan.textContent = formatCurrency(subtotal);
        receiptDiscountPercentSpan.textContent = discountPercent.toFixed(2);
        receiptDiscountAmountSpan.textContent = formatCurrency(discountAmount);
        receiptTaxPercentSpan.textContent = taxPercent.toFixed(2);
        receiptTaxAmountSpan.textContent = formatCurrency(taxAmount);
        receiptGrandTotalSpan.textContent = formatCurrency(grandTotal);
        receiptPaymentMethodSpan.textContent = paymentMethod;

        if (paymentMethod === 'Cash') {
            receiptCashReceivedRow.style.display = 'block';
            receiptChangeDueRow.style.display = 'block';
            receiptCashReceivedSpan.textContent = formatCurrency(cashReceived);
            receiptChangeDueSpan.textContent = formatCurrency(changeDue);
        } else {
            receiptCashReceivedRow.style.display = 'none';
            receiptChangeDueRow.style.display = 'none';
        }
        window.print();
    }

                // --- Load Categories into Modal ---
            function loadCategories() {
                fetch('../api/categories.php?action=list')
                    .then(res => {
                        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                        return res.json();
                    })
                    .then(data => {
                        const select = document.getElementById('newProductCategory');
                        select.innerHTML = '<option value="" disabled selected>Select a category</option>';

                        if (Array.isArray(data) && data.length) {
                            data.forEach(cat => {
                                select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                            });
                        } else {
                            console.warn("Categories response is empty or invalid:", data);
                            alert("No categories available. Please add categories first.");
                        }
                    })
                    .catch(err => {
                        console.error("Error loading categories:", err);
                        alert('Failed to load categories. Check console for details.');
                    });
            }
    $('#newProductModal').on('show.bs.modal', loadCategories);

    // --- New Product Form ---
    document.getElementById('newProductForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const newProduct = {
            barcode: document.getElementById('newProductBarcode').value,
            name: document.getElementById('newProductName').value.trim(),
            category_id: document.getElementById('newProductCategory').value.trim(),
            price: parseFloat(document.getElementById('newProductPrice').value),
            cost_price: parseFloat(document.getElementById('newProductCostPrice').value),
            stock_quantity: parseInt(document.getElementById('newProductStock').value)
        };
        try {
            const response = await fetch('../api/products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create', product: newProduct })
            });
            const result = await response.json();
            if (result.success) {
                $('#newProductModal').modal('hide');
                alert('New product added.');
                addProductToCart({ id: result.product_id, ...newProduct });
            } else {
                alert('Failed: ' + (result.message || 'Unknown error.'));
            }
        } catch {
            alert('Error saving product.');
        }
    });

    // --- Event Listeners ---
    lookupProductBtn?.addEventListener('click', lookupProduct);
    barcodeInput?.addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); lookupProduct(); } });
    clearCartBtn?.addEventListener('click', clearCart);
    completeSaleBtn?.addEventListener('click', completeSale);
    holdSaleBtn?.addEventListener('click', () => alert('Hold Sale not implemented.'));
    discountInput?.addEventListener('input', calculateTotals);
    taxRateInput?.addEventListener('input', calculateTotals);
    paymentMethodSelect?.addEventListener('change', function () {
        const cashSection = document.getElementById('cashPaymentSection');
        cashSection.style.display = this.value === 'Cash' ? 'block' : 'none';
        updateChangeDue();
    });
    cashReceivedInput?.addEventListener('input', updateChangeDue);

    cartItemsTableBody.addEventListener('click', function (event) {
        const btn = event.target.closest('.quantity-plus, .quantity-minus, .remove-item');
        if (btn) {
            const productId = btn.dataset.productId;
            if (btn.classList.contains('quantity-plus')) handleQuantityChange(productId, 1);
            if (btn.classList.contains('quantity-minus')) handleQuantityChange(productId, -1);
            if (btn.classList.contains('remove-item')) removeItemFromCart(productId);
        }
    });

    // --- Init ---
    updateCartDisplay();
    calculateTotals();
    updateChangeDue();
});
