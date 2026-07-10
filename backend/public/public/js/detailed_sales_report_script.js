// public/js/detailed_sales_report_script.js
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const recordsPerPageSelect = document.getElementById('records_per_page');
    const salesTableBody = document.querySelector('#salesTable tbody');
    const paginationNav = document.getElementById('paginationNav');
    const totalSalesAmountElem = document.getElementById('totalSalesAmount');
    const totalItemsSoldElem = document.getElementById('totalItemsSold');
    const reportDateRangeElem = document.getElementById('reportDateRange');
    const currencyFormatter = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const numberFormatter = new Intl.NumberFormat('en-PH');

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    function formatCurrency(value) {
        return currencyFormatter.format(Number(value || 0));
    }

    function formatNumber(value) {
        return numberFormatter.format(Number(value || 0));
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function toIsoDate(date) {
        const offset = date.getTimezoneOffset();
        const adjusted = new Date(date.getTime() - (offset * 60000));
        return adjusted.toISOString().slice(0, 10);
    }

    function showAlert(message, type) {
        const alertPlaceholder = document.querySelector('.dashboard-page-content');
        if (alertPlaceholder) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show no-print" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            alertPlaceholder.insertAdjacentHTML('afterbegin', alertHtml);
            const newAlert = alertPlaceholder.querySelector('.alert');
            setTimeout(() => {
                const bootstrapAlert = new bootstrap.Alert(newAlert);
                bootstrapAlert.close();
            }, 5000);
        }
    }

    async function loadSalesData(page = 1) {
        salesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Loading sales data...</td></tr>';
        paginationNav.innerHTML = '';

        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const recordsPerPage = recordsPerPageSelect.value;

        try {
            const response = await fetch(`../api/sales_reports.php?action=list_sales&start_date=${startDate}&end_date=${endDate}&records_per_page=${recordsPerPage}&page=${page}`, {
                cache: 'no-store',
            });
            const data = await response.json();

            if (data.status === 'success') {
                salesTableBody.innerHTML = '';

                if (data.data.length > 0) {
                    data.data.forEach(sale => {
                        const row = `
                            <tr>
                                <td>${sale.sale_id}</td>
                                <td>${formatDate(sale.sale_date)}</td>
                                <td>${escapeHtml(sale.cashier_name)}</td>
                                <td>${sale.items_sold}</td>
                                <td>${formatCurrency(sale.total_amount)}</td>
                            </tr>
                        `;
                        salesTableBody.insertAdjacentHTML('beforeend', row);
                    });
                    buildPagination(data.pagination.total_pages, data.pagination.current_page);
                } else {
                    salesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No sales transactions found for this period.</td></tr>';
                }

                updateReportHeader(startDate, endDate);
            } else {
                showAlert(data.message, 'danger');
                salesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Error loading sales data.</td></tr>';
            }
        } catch (error) {
            console.error('Error loading sales data:', error);
            showAlert('An unexpected error occurred while fetching sales data.', 'danger');
            salesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Network error loading sales data.</td></tr>';
        }
    }

    async function loadSummaryData() {
        totalSalesAmountElem.textContent = 'Loading...';
        totalItemsSoldElem.textContent = 'Loading...';

        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        try {
            const response = await fetch(`../api/sales_reports.php?action=get_summary&start_date=${startDate}&end_date=${endDate}`, {
                cache: 'no-store',
            });
            const data = await response.json();

            if (data.status === 'success' && data.summary) {
                totalSalesAmountElem.textContent = formatCurrency(data.summary.total_sales_amount);
                totalItemsSoldElem.textContent = formatNumber(data.summary.total_items_sold);
            } else {
                showAlert(data.message, 'danger');
                totalSalesAmountElem.textContent = 'Error';
                totalItemsSoldElem.textContent = 'Error';
            }
        } catch (error) {
            console.error('Error loading summary data:', error);
            showAlert('An unexpected error occurred while fetching summary data.', 'danger');
            totalSalesAmountElem.textContent = 'Network Error';
            totalItemsSoldElem.textContent = 'Network Error';
        }
    }

    function buildPagination(totalPages, currentPage) {
        paginationNav.innerHTML = '';
        if (totalPages <= 1) {
            return;
        }

        let paginationHtml = '<ul class="pagination justify-content-center">';

        paginationHtml += `<li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span> Previous
            </a>
        </li>`;

        for (let page = 1; page <= totalPages; page += 1) {
            paginationHtml += `<li class="page-item ${page === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${page}">${page}</a>
            </li>`;
        }

        paginationHtml += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                Next <span aria-hidden="true">&raquo;</span>
            </a>
        </li>`;

        paginationHtml += '</ul>';
        paginationNav.innerHTML = paginationHtml;

        paginationNav.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                const newPage = parseInt(this.dataset.page, 10);
                if (newPage > 0 && newPage <= totalPages) {
                    loadSalesData(newPage);
                }
            });
        });
    }

    function updateReportHeader(startDate, endDate) {
        reportDateRangeElem.textContent = `Date Range: ${formatDate(startDate)} to ${formatDate(endDate)}`;
    }

    if (filterForm) {
        filterForm.addEventListener('submit', function (event) {
            event.preventDefault();
            loadSalesData();
            loadSummaryData();
        });
    }

    if (!startDateInput.value) {
        const today = new Date();
        startDateInput.value = toIsoDate(new Date(today.getFullYear(), today.getMonth(), 1));
    }
    if (!endDateInput.value) {
        endDateInput.value = toIsoDate(new Date());
    }

    loadSalesData();
    loadSummaryData();

    window.printReportInNewWindow = function () {
        const printContents = document.getElementById('printableArea').innerHTML;
        const printWindow = window.open('', '_blank', 'height=800,width=800');
        const fontAwesomeUrl = new URL('../public/css/fontawesome.min.css', window.location.href).href;
        const bootstrapUrl = new URL('../public/css/bootstrap.min.css', window.location.href).href;

        printWindow.document.write('<!DOCTYPE html>');
        printWindow.document.write('<html lang="en">');
        printWindow.document.write('<head>');
        printWindow.document.write('<meta charset="UTF-8">');
        printWindow.document.write('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
        printWindow.document.write('<title>Sales Report Print</title>');
        printWindow.document.write('<link rel="stylesheet" href="' + fontAwesomeUrl + '">');
        printWindow.document.write('<link rel="stylesheet" href="' + bootstrapUrl + '">');
        printWindow.document.write('<style>');
        printWindow.document.write('@media print { .no-print { display: none !important; } }');
        printWindow.document.write('body { font-size: 10pt; } table { width: 100%; border-collapse: collapse; } table, th, td { border: 1px solid black; padding: 8px; }');
        printWindow.document.write('.print-header { text-align: center; margin-bottom: 20px; }');
        printWindow.document.write('.summary-box { border: 1px solid #dee2e6; }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head>');
        printWindow.document.write('<body>');
        printWindow.document.write(printContents);
        printWindow.document.write('</body>');
        printWindow.document.write('</html>');

        printWindow.document.close();
        printWindow.focus();
        printWindow.onload = function () {
            setTimeout(function () {
                printWindow.print();
                printWindow.close();
            }, 500);
        };
    };
});
