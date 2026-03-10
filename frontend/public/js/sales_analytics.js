// public/js/sales_analytics.js

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    const alertElement = document.querySelector('.alert');
    if (alertElement) {
        setTimeout(() => {
            // Check if bootstrap.Alert is available before trying to use it
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bootstrapAlert = new bootstrap.Alert(alertElement);
                bootstrapAlert.close();
            } else {
                // Fallback for non-Bootstrap alert dismissal (e.g., just hide it)
                alertElement.style.display = 'none';
            }
        }, 5000); 
    }
});

/**
 * Opens the report in a new window for printing, including necessary CSS.
 */
function printReportInNewWindow() {
    var content = document.getElementById('printableArea').innerHTML;
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Sales Analytics Report</title>');

    // Include Bootstrap CSS
    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');

    // Include custom report CSS
    printWindow.document.write('<link rel="stylesheet" href="../../public/css/report.css" media="all">');

    // Print-specific styles
    printWindow.document.write('<style>@media print { .no-print { display: none !important; } }</style>');

    printWindow.document.write('</head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();

    // Delay print command slightly to ensure assets load
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}