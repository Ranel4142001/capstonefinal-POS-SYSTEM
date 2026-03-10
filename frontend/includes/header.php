<?php
require_once __DIR__ . '/bootstrap.php';
// This file contains the common HTML <head> section for the application.
// It includes meta tags, title, and links to CSS and external libraries.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= LEGACY_BASE_URL ?>/public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   
   <style>
        /* Optimized Print-Specific CSS for Thermal Receipt Printer with Centered Details */
        /* These styles are specifically for printing receipts and do not affect screen responsiveness. */
        @media print {
            /* Critical for single page: Ensure HTML and body have no extra space and manage overflow */
            html, body {
                height: 1px !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            /* Hide everything first */
            body * {
                visibility: hidden;
            }

            /* Make only the receipt template visible and position it */
            #receipt-template {
                visibility: visible !important;
                display: block !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 80mm !important;
                min-height: 10mm !important;
                margin: 0 !important;
                padding: 5mm !important;
                font-family: 'Courier New', monospace !important;
                font-size: 10px !important;
                line-height: 1.4 !important;
                color: #000 !important;
                box-sizing: border-box !important;

                /* AVOID PAGE BREAKS */
                page-break-after: avoid !important;
                page-break-before: avoid !important;
                page-break-inside: avoid !important;
                clear: both !important;
            }

            /* Ensure all content inside receipt-template is also visible */
            #receipt-template * {
                visibility: visible !important;
                orphans: 3;
                widows: 3;
                float: none !important;
            }

            /* SPECIFICALLY CENTER THESE ELEMENTS for the header and footer sections */
            #receipt-template h3,
            #receipt-template p,
            #receipt-template div:first-of-type, /* Targets the first div within receipt-template (store info) */
            #receipt-template div:last-of-type /* Targets the last div within receipt-template (thank you message) */
            {
                text-align: center !important;
                margin-left: auto !important; /* Help with centering block elements */
                margin-right: auto !important; /* Help with centering block elements */
            }

            /* For the Date, Sale ID, Cashier lines - these are direct children p tags */
            #receipt-template > p {
                text-align: center !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            /* Table styles for receipt */
            #receipt-template table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-bottom: 5px !important;
                /* Table itself should NOT be text-aligned center, its cells will be aligned */
                text-align: left !important;
            }
            #receipt-template th,
            #receipt-template td {
                padding: 1px 0 !important;
                vertical-align: top !important;
                border: none !important;
            }
            #receipt-template th {
                border-bottom: 1px dashed #000 !important;
                padding-bottom: 3px !important;
            }
            #receipt-template tr {
                page-break-inside: avoid !important;
            }
            /* Specific table column alignment */
            #receipt-template td:nth-child(1) { text-align: left !important; } /* Item name */
            #receipt-template td:nth-child(2) { text-align: center !important; } /* Quantity */
            #receipt-template td:nth-child(3) { text-align: right !important; } /* Price */
            #receipt-template td:nth-child(4) { text-align: right !important; } /* Total */


            /* RIGHT ALIGNMENT for Subtotal, Discount, Tax, Grand Total, Payment, Cash Received, Change */
            #receipt-template div:nth-of-type(2), /* This targets the div containing Subtotal to Grand Total */
            #receipt-template div:nth-of-type(3) /* This targets the div containing Payment Method to Change */
            {
                text-align: right !important;
            }
            #receipt-template h4 { /* Grand Total heading */
                text-align: right !important;
            }


            /* Horizontal rules for separation */
            #receipt-template hr {
                border: none !important;
                border-top: 1px dashed #000 !important;
                margin: 8px auto !important; /* Center the HR */
                width: 90% !important; /* Give it a width to center it within the 80mm */
            }

            /* Ensuring no extra space from generic divs (if any remain outside of the targeted ones) */
            #receipt-template div {
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Force no print margins/headers/footers from the browser */
            @page {
                margin: 0 !important;
                size: 80mm auto !important;
                @top-left { content: ""; }
                @top-center { content: ""; }
                @top-right { content: ""; }
                @bottom-left { content: ""; }
                @bottom-center { content: ""; }
                @bottom-right { content: ""; }
            }
        }
    </style>
</head>
<body>

