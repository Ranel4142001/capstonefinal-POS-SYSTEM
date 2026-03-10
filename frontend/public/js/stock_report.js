// ========================
// Print Report Function
// ========================
function printReportInNewWindow() {
    const printContents = document.getElementById('printableArea').innerHTML;

    // Open a new popup window for printing
    const printWindow = window.open('', '_blank', 'height=800,width=1000');

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Stock Report Print</title>

            <!-- CSS Files -->
            <link rel="stylesheet" href="../public/css/style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

            <style>
                @media print {
                    .no-print { display: none !important; }
                    @page { size: landscape; margin: 20mm; } /* Landscape with margins */
                    body { counter-reset: page; }
                    footer { 
                        position: fixed; 
                        bottom: 0; 
                        width: 100%; 
                        text-align: center; 
                        font-size: 9pt; 
                    }
                    footer::after { 
                        content: "Page " counter(page) " of " counter(pages); 
                    }
                }
                body { font-size: 10pt; }
                table { width: 100%; border-collapse: collapse; }
                table, th, td { border: 1px solid black; padding: 8px; }
                .print-header { text-align: center; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h3>Stock Report</h3>
                <p>Date Generated: ${new Date().toLocaleDateString()}</p>
            </div>
            ${printContents}
            <footer></footer>
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    // Ensure CSS loads before printing
    printWindow.onload = function () {
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    };
}

// ========================
// Auto-close Multiple Alerts
// ========================
document.addEventListener('DOMContentLoaded', function () {
    const alertElements = document.querySelectorAll('.alert');
    alertElements.forEach(alertElement => {
        setTimeout(() => {
            const bootstrapAlert = new bootstrap.Alert(alertElement);
            bootstrapAlert.close();
        }, 5000); // Close after 5 seconds
    });
});
