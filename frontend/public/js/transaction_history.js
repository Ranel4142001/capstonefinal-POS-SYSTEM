// public/js/transaction_history.js
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('transactionFilterForm');
    const canRefundInput = document.getElementById('transactionCanRefund');
    const startDateInput = document.getElementById('transaction_start_date');
    const endDateInput = document.getElementById('transaction_end_date');
    const recordsPerPageSelect = document.getElementById('transaction_records_per_page');
    const tableBody = document.getElementById('transactionHistoryTableBody');
    const paginationNav = document.getElementById('transactionPaginationNav');
    const detailsModalElement = document.getElementById('transactionDetailsModal');
    const printTransactionReceiptBtn = document.getElementById('printTransactionReceiptBtn');
    const openRefundTransactionBtn = document.getElementById('openRefundTransactionBtn');
    const refundTransactionModalElement = document.getElementById('refundTransactionModal');
    const refundTransactionForm = document.getElementById('refundTransactionForm');
    const refundTransactionSaleIdInput = document.getElementById('refund_transaction_sale_id');
    const refundTransactionReceiptNoInput = document.getElementById('refund_transaction_receipt_no');
    const refundReasonInput = document.getElementById('refund_reason');
    const detailsModal = detailsModalElement ? new bootstrap.Modal(detailsModalElement) : null;
    const refundModal = refundTransactionModalElement ? new bootstrap.Modal(refundTransactionModalElement) : null;
    const canRefund = canRefundInput ? canRefundInput.value === '1' : false;

    const receiptNoElem = document.getElementById('transactionDetailReceiptNo');
    const dateTimeElem = document.getElementById('transactionDetailDateTime');
    const cashierElem = document.getElementById('transactionDetailCashier');
    const statusElem = document.getElementById('transactionDetailStatus');
    const paymentMethodElem = document.getElementById('transactionDetailPaymentMethod');
    const cashReceivedElem = document.getElementById('transactionDetailCashReceived');
    const refundDateWrapper = document.getElementById('transactionRefundDateWrapper');
    const refundDateElem = document.getElementById('transactionDetailRefundDate');
    const refundReasonWrapper = document.getElementById('transactionRefundReasonWrapper');
    const refundReasonElem = document.getElementById('transactionDetailRefundReason');
    const itemsBody = document.getElementById('transactionDetailItemsBody');
    const discountElem = document.getElementById('transactionDetailDiscount');
    const taxElem = document.getElementById('transactionDetailTax');
    const changeDueElem = document.getElementById('transactionDetailChangeDue');
    const totalElem = document.getElementById('transactionDetailTotal');

    let currentTransactionDetails = null;
    let currentPage = 1;

    function toIsoDate(date) {
        const offset = date.getTimezoneOffset();
        const adjusted = new Date(date.getTime() - (offset * 60000));
        return adjusted.toISOString().slice(0, 10);
    }

    function formatCurrency(amount) {
        return `\u20B1${parseFloat(amount || 0).toFixed(2)}`;
    }

    function formatDateTime(dateString) {
        return new Date(dateString).toLocaleString();
    }

    function getReceiptNumber(saleId) {
        return `TRX-${String(saleId).padStart(6, '0')}`;
    }

    function getStatusBadge(status) {
        const normalized = String(status || 'completed').toLowerCase();
        let badgeClass = 'bg-success';

        if (normalized === 'refunded') {
            badgeClass = 'bg-warning text-dark';
        } else if (normalized === 'voided') {
            badgeClass = 'bg-danger';
        } else if (normalized !== 'completed') {
            badgeClass = 'bg-secondary';
        }

        const label = normalized.charAt(0).toUpperCase() + normalized.slice(1);
        return `<span class="badge ${badgeClass}">${label}</span>`;
    }

    function setDefaultDates() {
        if (!startDateInput.value) {
            const today = new Date();
            startDateInput.value = toIsoDate(new Date(today.getFullYear(), today.getMonth(), 1));
        }

        if (!endDateInput.value) {
            endDateInput.value = toIsoDate(new Date());
        }
    }

    function buildPagination(totalPages, currentPage) {
        paginationNav.innerHTML = '';

        if (totalPages <= 1) {
            return;
        }

        let html = '<ul class="pagination justify-content-center">';

        html += `
            <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;

        for (let page = 1; page <= totalPages; page += 1) {
            html += `
                <li class="page-item ${page === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${page}">${page}</a>
                </li>
            `;
        }

        html += `
            <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;

        html += '</ul>';
        paginationNav.innerHTML = html;
    }

    async function loadTransactions(page = 1) {
        currentPage = page;
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Loading transactions...</td></tr>';
        paginationNav.innerHTML = '';

        const params = new URLSearchParams({
            action: 'list_transactions',
            start_date: startDateInput.value,
            end_date: endDateInput.value,
            records_per_page: recordsPerPageSelect.value,
            page: String(page),
            _: String(Date.now())
        });

        try {
            const response = await fetch(`../api/sales_reports.php?${params.toString()}`, {
                cache: 'no-store'
            });
            const data = await response.json();

            if (data.status !== 'success') {
                showMessage(data.message || 'Failed to load transaction history.', 'danger');
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Unable to load transaction history.</td></tr>';
                return;
            }

            tableBody.innerHTML = '';

            if (!data.data.length) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No transactions found for this period.</td></tr>';
                return;
            }

            data.data.forEach(transaction => {
                const isCompleted = String(transaction.status || '').toLowerCase() === 'completed';
                const refundButton = canRefund && isCompleted
                    ? `<button type="button" class="btn btn-sm btn-outline-danger refund-transaction-btn" data-sale-id="${transaction.sale_id}">Refund</button>`
                    : '';
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${formatDateTime(transaction.sale_date)}</td>
                    <td>${getReceiptNumber(transaction.sale_id)}</td>
                    <td>${transaction.cashier_name}</td>
                    <td>${formatCurrency(transaction.total_amount)}</td>
                    <td>${getStatusBadge(transaction.status)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary me-2 view-transaction-btn" data-sale-id="${transaction.sale_id}">
                            View Details
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2 print-transaction-btn" data-sale-id="${transaction.sale_id}">
                            Print
                        </button>
                        ${refundButton}
                    </td>
                `;
                tableBody.appendChild(row);
            });

            buildPagination(data.pagination.total_pages, data.pagination.current_page);
        } catch (error) {
            console.error('Error loading transaction history:', error);
            showMessage('An unexpected error occurred while loading transaction history.', 'danger');
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Network error loading transaction history.</td></tr>';
        }
    }

    async function fetchTransactionDetails(saleId) {
        const params = new URLSearchParams({
            action: 'get_transaction_details',
            sale_id: String(saleId),
            _: String(Date.now())
        });

        const response = await fetch(`../api/sales_reports.php?${params.toString()}`, {
            cache: 'no-store'
        });
        const data = await response.json();

        if (data.status !== 'success') {
            throw new Error(data.message || 'Failed to load transaction details.');
        }

        return data;
    }

    function populateTransactionModal(details) {
        currentTransactionDetails = details;
        const isCompleted = String(details.transaction.status || '').toLowerCase() === 'completed';

        receiptNoElem.textContent = getReceiptNumber(details.transaction.sale_id);
        dateTimeElem.textContent = formatDateTime(details.transaction.sale_date);
        cashierElem.textContent = details.transaction.cashier_name;
        statusElem.innerHTML = getStatusBadge(details.transaction.status);
        paymentMethodElem.textContent = details.transaction.payment_method || 'N/A';
        cashReceivedElem.textContent = formatCurrency(details.transaction.cash_received);
        discountElem.textContent = formatCurrency(details.transaction.discount_amount);
        taxElem.textContent = formatCurrency(details.transaction.tax_amount);
        changeDueElem.textContent = formatCurrency(details.transaction.change_due);
        totalElem.textContent = formatCurrency(details.transaction.total_amount);

        if (details.refund) {
            refundDateElem.textContent = formatDateTime(details.refund.refund_date);
            refundReasonElem.textContent = details.refund.reason;
            refundDateWrapper.classList.remove('d-none');
            refundReasonWrapper.classList.remove('d-none');
        } else {
            refundDateElem.textContent = '-';
            refundReasonElem.textContent = '-';
            refundDateWrapper.classList.add('d-none');
            refundReasonWrapper.classList.add('d-none');
        }

        if (openRefundTransactionBtn) {
            if (canRefund && isCompleted) {
                openRefundTransactionBtn.classList.remove('d-none');
                openRefundTransactionBtn.dataset.saleId = details.transaction.sale_id;
            } else {
                openRefundTransactionBtn.classList.add('d-none');
                delete openRefundTransactionBtn.dataset.saleId;
            }
        }

        itemsBody.innerHTML = '';

        if (!details.items.length) {
            itemsBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No item details found.</td></tr>';
            return;
        }

        details.items.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.product_name}</td>
                <td>${item.quantity}</td>
                <td>${formatCurrency(item.price_at_sale)}</td>
                <td>${formatCurrency(item.subtotal)}</td>
            `;
            itemsBody.appendChild(row);
        });
    }

    function openRefundModal(saleId) {
        const receiptNo = getReceiptNumber(saleId);
        refundTransactionSaleIdInput.value = saleId;
        refundTransactionReceiptNoInput.value = receiptNo;
        refundReasonInput.value = '';
        refundModal.show();
    }

    async function refundTransaction(saleId, reason) {
        const formData = new FormData();
        formData.append('action', 'refund_transaction');
        formData.append('sale_id', String(saleId));
        formData.append('reason', reason);

        const response = await fetch('../api/sales_reports.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.status !== 'success') {
            throw new Error(data.message || 'Failed to process refund.');
        }

        return data;
    }

    async function showTransactionDetails(saleId) {
        itemsBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Loading transaction items...</td></tr>';
        detailsModal.show();

        try {
            const details = await fetchTransactionDetails(saleId);
            populateTransactionModal(details);
        } catch (error) {
            console.error('Error loading transaction details:', error);
            showMessage(error.message || 'Unable to load transaction details.', 'danger');
            itemsBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Unable to load transaction items.</td></tr>';
        }
    }

    function buildReceiptHtml(details) {
        const itemsHtml = details.items.map(item => `
            <tr>
                <td>${item.product_name}</td>
                <td style="text-align:center;">${item.quantity}</td>
                <td style="text-align:right;">${formatCurrency(item.price_at_sale)}</td>
                <td style="text-align:right;">${formatCurrency(item.subtotal)}</td>
            </tr>
        `).join('');

        return `
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>${getReceiptNumber(details.transaction.sale_id)}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
                    h2, p { margin: 0 0 8px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
                    th, td { border-bottom: 1px solid #ddd; padding: 8px; text-align: left; }
                    .summary { margin-top: 20px; max-width: 320px; margin-left: auto; }
                    .summary-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
                    .summary-row.total { border-top: 1px solid #111; padding-top: 8px; font-weight: bold; }
                </style>
            </head>
            <body>
                <h2>Transaction Receipt</h2>
                <p><strong>Receipt No.:</strong> ${getReceiptNumber(details.transaction.sale_id)}</p>
                <p><strong>Date & Time:</strong> ${formatDateTime(details.transaction.sale_date)}</p>
                <p><strong>Cashier:</strong> ${details.transaction.cashier_name}</p>
                <p><strong>Status:</strong> ${details.transaction.status}</p>
                <p><strong>Payment Method:</strong> ${details.transaction.payment_method || 'N/A'}</p>
                ${details.refund ? `<p><strong>Refund Reason:</strong> ${details.refund.reason}</p>` : ''}
                ${details.refund ? `<p><strong>Refund Date:</strong> ${formatDateTime(details.refund.refund_date)}</p>` : ''}

                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml || '<tr><td colspan="4">No items found.</td></tr>'}
                    </tbody>
                </table>

                <div class="summary">
                    <div class="summary-row"><span>Discount</span><span>${formatCurrency(details.transaction.discount_amount)}</span></div>
                    <div class="summary-row"><span>Tax</span><span>${formatCurrency(details.transaction.tax_amount)}</span></div>
                    <div class="summary-row"><span>Cash Received</span><span>${formatCurrency(details.transaction.cash_received)}</span></div>
                    <div class="summary-row"><span>Change Due</span><span>${formatCurrency(details.transaction.change_due)}</span></div>
                    <div class="summary-row total"><span>Total Amount</span><span>${formatCurrency(details.transaction.total_amount)}</span></div>
                </div>
            </body>
            </html>
        `;
    }

    async function printTransactionReceipt(saleId) {
        try {
            const details = currentTransactionDetails && String(currentTransactionDetails.transaction.sale_id) === String(saleId)
                ? currentTransactionDetails
                : await fetchTransactionDetails(saleId);

            const printWindow = window.open('', '_blank', 'width=900,height=800');
            if (!printWindow) {
                showMessage('Unable to open print window. Please allow pop-ups for this site.', 'warning');
                return;
            }

            printWindow.document.write(buildReceiptHtml(details));
            printWindow.document.close();
            printWindow.focus();
            printWindow.onload = function () {
                setTimeout(function () {
                    printWindow.print();
                    printWindow.close();
                }, 300);
            };
        } catch (error) {
            console.error('Error printing receipt:', error);
            showMessage(error.message || 'Unable to print this transaction.', 'danger');
        }
    }

    filterForm.addEventListener('submit', function (event) {
        event.preventDefault();
        loadTransactions(1);
    });

    paginationNav.addEventListener('click', function (event) {
        const link = event.target.closest('.page-link');
        if (!link) {
            return;
        }

        event.preventDefault();

        const targetPage = parseInt(link.dataset.page, 10);
        if (!Number.isNaN(targetPage) && targetPage > 0) {
            loadTransactions(targetPage);
        }
    });

    tableBody.addEventListener('click', function (event) {
        const viewButton = event.target.closest('.view-transaction-btn');
        if (viewButton) {
            showTransactionDetails(viewButton.dataset.saleId);
            return;
        }

        const printButton = event.target.closest('.print-transaction-btn');
        if (printButton) {
            printTransactionReceipt(printButton.dataset.saleId);
            return;
        }

        const refundButton = event.target.closest('.refund-transaction-btn');
        if (refundButton) {
            openRefundModal(refundButton.dataset.saleId);
        }
    });

    printTransactionReceiptBtn.addEventListener('click', function () {
        if (currentTransactionDetails) {
            printTransactionReceipt(currentTransactionDetails.transaction.sale_id);
        }
    });

    if (openRefundTransactionBtn) {
        openRefundTransactionBtn.addEventListener('click', function () {
            if (this.dataset.saleId) {
                openRefundModal(this.dataset.saleId);
            }
        });
    }

    if (refundTransactionForm) {
        refundTransactionForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const saleId = refundTransactionSaleIdInput.value;
            const reason = refundReasonInput.value.trim();

            if (!reason) {
                showMessage('Refund reason is required.', 'warning');
                return;
            }

            try {
                const data = await refundTransaction(saleId, reason);
                refundModal.hide();
                showMessage(data.message, 'success');
                await loadTransactions(currentPage);

                if (currentTransactionDetails && String(currentTransactionDetails.transaction.sale_id) === String(saleId)) {
                    const refreshedDetails = await fetchTransactionDetails(saleId);
                    populateTransactionModal(refreshedDetails);
                }
            } catch (error) {
                console.error('Error refunding transaction:', error);
                showMessage(error.message || 'Unable to process refund.', 'danger');
            }
        });
    }

    setDefaultDates();
    loadTransactions(1);
});
