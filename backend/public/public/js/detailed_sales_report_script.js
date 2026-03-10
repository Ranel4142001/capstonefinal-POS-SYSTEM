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

    // --- Helper function to format date for display ---
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    // --- Function to display alerts ---
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
            }, 5000); // 5 seconds
        }
    }

    // --- Function to load Sales Data ---
    async function loadSalesData(page = 1) {
        salesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Loading sales data...</td></tr>';
        paginationNav.innerHTML = ''; // Clear pagination

        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const recordsPerPage = recordsPerPageSelect.value;

        try {
            const response = await fetch(`../api/sales_reports.php?action=list_sales&start_date=${startDate}&end_date=${endDate}&records_per_page=${recordsPerPage}&page=${page}`);
            const data = await response.json();

            if (data.status === 'success') {
                salesTableBody.innerHTML = ''; // Clear loading message

                if (data.data.length > 0) {
                    data.data.forEach(sale => {
                        const row = `
                            <tr>
                                <td>${sale.sale_id}</td>
                                <td>${formatDate(sale.sale_date)}</td>
                                <td>${sale.cashier_name}</td>
                                <td>${sale.items_sold}</td>
                                <td>₱${parseFloat(sale.total_amount).toFixed(2)}</td>
                            </tr>
                        `;
                        salesTableBody.insertAdjacentHTML('beforeend', row);
                    });
                    buildPagination(data.pagination.total_pages, data.pagination.current_page, startDate, endDate, recordsPerPage);
                } else {
                    salesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No sales transactions found for this period.</td></tr>';
                }
                updateReportHeader(startDate, endDate);
            } else {
                showAlert(data.message, 'danger');
                salesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Error loading sales data.</td></tr>';
            }
        } catch (error) {
            console.error("Error loading sales data:", error);
            showAlert("An unexpected error occurred while fetching sales data.", 'danger');
            salesTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Network error loading sales data.</td></tr>';
        }
    }

    // --- Function to load Summary Data ---
    async function loadSummaryData() {
        totalSalesAmountElem.textContent = 'Loading...';
        totalItemsSoldElem.textContent = 'Loading...';

        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        try {
            const response = await fetch(`../api/sales_reports.php?action=get_summary&start_date=${startDate}&end_date=${endDate}`);
            const data = await response.json();

            if (data.status === 'success' && data.summary) {
                totalSalesAmountElem.textContent = `₱${parseFloat(data.summary.total_sales_amount).toFixed(2)}`;
                totalItemsSoldElem.textContent = parseFloat(data.summary.total_items_sold).toLocaleString();
            } else {
                showAlert(data.message, 'danger');
                totalSalesAmountElem.textContent = 'Error';
                totalItemsSoldElem.textContent = 'Error';
            }
        } catch (error) {
            console.error("Error loading summary data:", error);
            showAlert("An unexpected error occurred while fetching summary data.", 'danger');
            totalSalesAmountElem.textContent = 'Network Error';
            totalItemsSoldElem.textContent = 'Network Error';
        }
    }

    // --- Function to build Pagination ---
    function buildPagination(totalPages, currentPage, startDate, endDate, recordsPerPage) {
        paginationNav.innerHTML = ''; // Clear existing pagination
        if (totalPages <= 1) return; // No pagination needed for 1 or less pages

        let paginationHtml = '<ul class="pagination justify-content-center">';

        // Previous button
        paginationHtml += `<li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span> Previous
            </a>
        </li>`;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }

        // Next button
        paginationHtml += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                Next <span aria-hidden="true">&raquo;</span>
            </a>
        </li>`;

        paginationHtml += '</ul>';
        paginationNav.innerHTML = paginationHtml;

        // Add event listeners to new pagination links
        paginationNav.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const newPage = parseInt(this.dataset.page);
                if (newPage > 0 && newPage <= totalPages) {
                    loadSalesData(newPage);
                }
            });
        });
    }

    // --- Function to update report header date range ---
    function updateReportHeader(startDate, endDate) {
        reportDateRangeElem.textContent = `Date Range: ${formatDate(startDate)} to ${formatDate(endDate)}`;
    }

    // --- Event Listener for Filter Form Submission ---
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent default form submission
            loadSalesData();    // Load data for the first page with new filters
            loadSummaryData();  // Load summary data with new filters
        });
    }

    // --- Initial Load on DOM Ready ---
    // Set default date values if not already set (e.g., from URL parameters)
    if (!startDateInput.value) {
        startDateInput.value = new Date().toISOString().slice(0, 10).replace(/(\d{4}-\d{2}-\d{2})/, (m, y, M, d) => `${y}-${M}-01`); // First day of current month
    }
    if (!endDateInput.value) {
        endDateInput.value = new Date().toISOString().slice(0, 10); // Current date
    }

    // Load initial data
    loadSalesData();
    loadSummaryData();

    // --- Custom print function: Opens content in a new window for printing ---
    // This function remains inline due to dynamic PHP variable for CSS path in new window.
    window.printReportInNewWindow = function () {
        var printContents = document.getElementById('printableArea').innerHTML;
        var originalTitle = document.title; // Store original title

        var printWindow = window.open('', '_blank', 'height=800,width=800');

        printWindow.document.write('<!DOCTYPE html>');
        printWindow.document.write('<html lang="en">');
        printWindow.document.write('<head>');
        printWindow.document.write('<meta charset="UTF-8">');
        printWindow.document.write('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
        printWindow.document.write('<title>Sales Report Print</title>');

        // IMPORTANT: Adjust the paths if your CSS files are not relative to the root like this
        // PHP variable base_url_path is echoed here.
        printWindow.document.write('<link rel="stylesheet" href="<?php echo $base_url_path; ?>/public/css/style.css">');
        printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');

        // Add print-specific styles to hide elements not meant for print
        printWindow.document.write('<style>');
        printWindow.document.write('@media print { .no-print { display: none !important; } }');
        printWindow.document.write('body { font-size: 10pt; } table { width: 100%; border-collapse: collapse; } table, th, td { border: 1px solid black; padding: 8px; }');
        printWindow.document.write('.print-header { text-align: center; margin-bottom: 20px; }');
        printWindow.document.write('</style>');

        printWindow.document.write('</head>');
        printWindow.document.write('<body>');
        printWindow.document.write(printContents); // Insert the content to print
        printWindow.document.write('</body>');
        printWindow.document.write('</html>');

        printWindow.document.close(); // Close the document to ensure content is parsed
        printWindow.focus(); // Focus the new window

        // Give the browser a moment to render the new window's content and load CSS
        // Then trigger the print.
        printWindow.onload = function () {
            setTimeout(function () {
                printWindow.print();
                printWindow.close(); // Close the print window after printing
            }, 500); // Small delay to ensure CSS loads
        };
    };
});
