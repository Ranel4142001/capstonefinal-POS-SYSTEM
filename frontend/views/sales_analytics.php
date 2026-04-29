<?php
require_once __DIR__ . '/../includes/bootstrap.php';

include LEGACY_BASE_PATH . '/includes/auth_check.php';
include LEGACY_BASE_PATH . '/includes/layout_start.php';
?>

<style id="analyticsDashboardStyles">
    .analytics-dashboard {
        color: #233142;
    }

    .analytics-screen-shell {
        display: block;
    }

    .analytics-toolbar {
        border: 1px solid #dbe2ea;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
        box-shadow: 0 18px 40px rgba(35, 49, 66, 0.08);
    }

    .analytics-toolbar .card-body {
        text-align: left;
    }

    .analytics-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .analytics-subtitle {
        color: #5c6b7a;
        margin-bottom: 0;
    }

    .analytics-preset-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .analytics-preset-btn {
        border-radius: 999px;
        padding: 0.55rem 1rem;
        font-weight: 600;
    }

    .analytics-preset-btn.active {
        box-shadow: 0 8px 18px rgba(13, 110, 253, 0.22);
    }

    .analytics-status {
        min-height: 1.5rem;
        font-size: 0.95rem;
        color: #5c6b7a;
    }

    .analytics-kpi-card {
        border: 1px solid #dbe2ea;
        border-radius: 18px;
        padding: 1.25rem;
        background: #ffffff;
        box-shadow: 0 16px 34px rgba(35, 49, 66, 0.07);
        height: 100%;
    }

    .analytics-kpi-label {
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #60758a;
        margin-bottom: 0.5rem;
    }

    .analytics-kpi-value {
        font-size: 2rem;
        font-weight: 700;
        color: #1f3c88;
        margin-bottom: 0.35rem;
        line-height: 1.1;
    }

    .analytics-kpi-meta {
        color: #6f7f90;
        font-size: 0.92rem;
        margin-bottom: 0;
    }

    .analytics-section-card {
        border: 1px solid #dbe2ea;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 16px 34px rgba(35, 49, 66, 0.07);
        height: 100%;
    }

    .analytics-section-card .card-header {
        border-bottom: 1px solid #ecf1f6;
        background: #f8fbff;
        border-radius: 18px 18px 0 0;
        font-weight: 700;
        text-align: left;
    }

    .analytics-chart-shell {
        position: relative;
        min-height: 320px;
    }

    .analytics-chart-shell.analytics-chart-tall {
        min-height: 360px;
    }

    .analytics-chart-shell canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .analytics-insight-list {
        display: grid;
        gap: 0.85rem;
    }

    .analytics-insight-item {
        border: 1px solid #edf2f7;
        border-radius: 14px;
        padding: 0.9rem 1rem;
        background: #fbfcfe;
    }

    .analytics-insight-item strong {
        display: block;
        color: #1f3c88;
        font-size: 1rem;
    }

    .analytics-insight-item span {
        color: #617285;
        font-size: 0.92rem;
    }

    .analytics-table thead th {
        background: #eff5ff;
        border-bottom-width: 1px;
    }

    .analytics-empty {
        color: #6f7f90;
        text-align: center;
        padding: 2rem 1rem;
    }

    .analytics-loading-overlay {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: rgba(248, 251, 255, 0.72);
        z-index: 2;
    }

    .analytics-loading-overlay.active {
        display: flex;
    }

    .analytics-report-meta {
        color: #60758a;
        font-size: 0.95rem;
    }

    .analytics-chart-note {
        color: #60758a;
        font-size: 0.92rem;
    }

    .analytics-year-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.85rem;
    }

    .analytics-year-card {
        border: 1px solid #e6edf4;
        border-radius: 14px;
        background: #fbfdff;
        padding: 0.95rem 1rem;
    }

    .analytics-year-card-title {
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #60758a;
        margin-bottom: 0.35rem;
    }

    .analytics-year-card-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1f3c88;
        margin-bottom: 0.35rem;
    }

    .analytics-year-card-meta {
        color: #5c6b7a;
        font-size: 0.9rem;
    }

    .analytics-print-report {
        display: none;
        max-width: 794px;
        margin: 0 auto;
        color: #111111;
    }

    .analytics-print-report * {
        box-sizing: border-box;
    }

    .analytics-print-report-header {
        text-align: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #111111;
    }

    .analytics-print-report-header h1 {
        font-size: 1.55rem;
        font-weight: 700;
        margin: 0 0 0.45rem;
        color: #111111;
    }

    .analytics-print-report-meta {
        margin: 0.15rem 0;
        font-size: 0.92rem;
        color: #333333;
    }

    .analytics-print-section {
        margin-bottom: 1.35rem;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .analytics-print-section-title {
        margin: 0 0 0.8rem;
        padding-bottom: 0.45rem;
        border-bottom: 1px solid #b9b9b9;
        font-size: 0.98rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #111111;
    }

    .analytics-print-kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.8rem;
    }

    .analytics-print-kpi {
        border: 1px solid #111111;
        padding: 0.8rem 0.9rem;
        background: #ffffff;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .analytics-print-kpi-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
        color: #111111;
    }

    .analytics-print-kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 0.3rem;
        color: #111111;
        line-height: 1.15;
    }

    .analytics-print-kpi-meta {
        font-size: 0.82rem;
        color: #333333;
        margin: 0;
    }

    .analytics-print-chart-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.8rem;
    }

    .analytics-print-chart-card {
        border: 1px solid #111111;
        padding: 0.8rem;
        background: #ffffff;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .analytics-print-chart-card.analytics-print-chart-card-wide {
        grid-column: 1 / -1;
    }

    .analytics-print-chart-title {
        margin: 0 0 0.55rem;
        font-size: 0.86rem;
        font-weight: 700;
        color: #111111;
    }

    .analytics-print-chart-frame {
        min-height: 210px;
        border: 1px solid #cfcfcf;
        padding: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
    }

    .analytics-print-chart-frame img {
        display: block;
        width: 100%;
        height: auto;
        max-width: 100%;
    }

    .analytics-print-chart-placeholder {
        text-align: center;
        font-size: 0.82rem;
        color: #555555;
    }

    .analytics-print-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }

    .analytics-print-summary-item {
        border: 1px solid #111111;
        padding: 0.7rem 0.8rem;
        background: #ffffff;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .analytics-print-summary-label {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #111111;
        margin-bottom: 0.25rem;
    }

    .analytics-print-summary-value {
        font-size: 1rem;
        font-weight: 700;
        color: #111111;
    }

    .analytics-print-summary-note {
        margin: 0 0 0.85rem;
        font-size: 0.84rem;
        color: #333333;
    }

    .analytics-print-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .analytics-print-table th,
    .analytics-print-table td {
        border: 1px solid #111111;
        padding: 0.55rem;
        font-size: 0.82rem;
        vertical-align: top;
        color: #111111;
        word-wrap: break-word;
    }

    .analytics-print-table thead th {
        background: #ededed;
        font-weight: 700;
    }

    .analytics-print-table tbody tr:nth-child(even) {
        background: #f7f7f7;
    }

    .analytics-print-table .text-end {
        text-align: right;
    }

    .analytics-print-empty {
        text-align: center;
        font-style: italic;
        color: #555555;
    }

    @media (max-width: 991px) {
        .analytics-title {
            font-size: 1.7rem;
        }

        .analytics-kpi-value {
            font-size: 1.6rem;
        }

        .analytics-print-kpi-grid,
        .analytics-print-chart-grid,
        .analytics-print-summary-grid {
            grid-template-columns: 1fr;
        }

        .analytics-print-chart-card.analytics-print-chart-card-wide {
            grid-column: auto;
        }
    }

    @page {
        size: A4 portrait;
        margin: 12mm;
    }

    @media print {
        html,
        body {
            height: auto !important;
            min-height: auto !important;
            overflow: visible !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            color: #000000 !important;
        }

        body {
            font-family: "Times New Roman", Georgia, serif;
            font-size: 11pt;
        }

        body,
        body * {
            visibility: visible !important;
        }

        #receipt-template {
            display: none !important;
            visibility: hidden !important;
        }

        .dashboard-wrapper .sidebar,
        .navbar.custom-navbar-top,
        .overlay,
        .modal,
        .no-print {
            display: none !important;
            visibility: hidden !important;
        }

        .dashboard-wrapper,
        .main-content,
        .container-fluid.dashboard-page-content,
        #analyticsDashboard {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            float: none !important;
            position: static !important;
            box-shadow: none !important;
        }

        .analytics-screen-shell {
            display: none !important;
        }

        .analytics-print-report {
            display: block !important;
            position: static !important;
            max-width: none !important;
            margin: 0 !important;
            visibility: visible !important;
        }

        .analytics-print-report,
        .analytics-print-report * {
            color: #000000 !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        .analytics-print-report a {
            text-decoration: none;
        }

        .analytics-print-chart-card,
        .analytics-print-kpi,
        .analytics-print-summary-item,
        .analytics-print-table,
        .analytics-print-table tr,
        .analytics-print-section {
            break-inside: avoid;
            page-break-inside: avoid;
            box-shadow: none !important;
        }

        .analytics-print-chart-frame,
        .analytics-print-kpi,
        .analytics-print-summary-item,
        .analytics-print-table,
        .analytics-print-table thead th,
        .analytics-print-table tbody tr,
        .analytics-print-report-header {
            background: #ffffff !important;
        }

        .analytics-print-table thead {
            display: table-header-group;
        }

        .analytics-print-table tbody {
            display: table-row-group;
        }

        .analytics-print-table tbody tr:nth-child(even) {
            background: #f7f7f7 !important;
        }

        .analytics-print-table,
        .analytics-print-table thead th,
        .analytics-print-table tbody tr:nth-child(even) {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<div
    id="analyticsDashboard"
    class="analytics-dashboard"
    data-api-url="<?= LEGACY_BASE_URL ?>/api/sales_analytics.php"
    data-username="<?= htmlspecialchars($_SESSION['username'] ?? 'Unknown User', ENT_QUOTES, 'UTF-8') ?>"
>
    <div class="analytics-screen-shell">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
            <div>
                <h1 class="analytics-title">Analytics Dashboard</h1>
                <p class="analytics-subtitle">
                    Real-time revenue, profit, transaction, and inventory intelligence for Mitzikikay General Merchandise.
                </p>
            </div>
            <div class="text-lg-end">
                <div id="analyticsReportRange" class="analytics-report-meta">Preparing filter range...</div>
                <div id="analyticsGeneratedAt" class="analytics-report-meta">Generated just now</div>
                <button type="button" class="btn btn-outline-secondary mt-3 no-print" id="analyticsPrintBtn">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>

        <div class="card analytics-toolbar mb-4 no-print">
            <div class="card-body p-4">
                <div class="row g-4 align-items-end">
                    <div class="col-12">
                        <div class="analytics-preset-group" role="group" aria-label="Analytics filter presets">
                            <button type="button" class="btn btn-outline-primary analytics-preset-btn" data-preset="today">Today</button>
                            <button type="button" class="btn btn-outline-primary analytics-preset-btn" data-preset="last_7_days">Last 7 Days</button>
                            <button type="button" class="btn btn-outline-primary analytics-preset-btn" data-preset="last_30_days">Last 30 Days</button>
                            <button type="button" class="btn btn-outline-primary analytics-preset-btn" data-preset="this_month">This Month</button>
                            <button type="button" class="btn btn-outline-primary analytics-preset-btn" data-preset="custom">Custom Range</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="analyticsStartDate" class="form-label fw-semibold">Start Date</label>
                        <input type="date" class="form-control" id="analyticsStartDate">
                    </div>
                    <div class="col-md-4">
                        <label for="analyticsEndDate" class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control" id="analyticsEndDate">
                    </div>
                    <div class="col-md-4">
                        <div class="d-grid">
                            <button type="button" class="btn btn-primary btn-lg" id="analyticsApplyBtn">Apply Filter</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div id="analyticsStatus" class="analytics-status" aria-live="polite"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-4">
                <div class="analytics-kpi-card">
                    <div class="analytics-kpi-label">Total Revenue</div>
                    <div id="kpiTotalRevenue" class="analytics-kpi-value">₱0.00</div>
                    <p id="kpiRevenueMeta" class="analytics-kpi-meta">Gross sales less refunds.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="analytics-kpi-card">
                    <div class="analytics-kpi-label">Net Profit</div>
                    <div id="kpiNetProfit" class="analytics-kpi-value">₱0.00</div>
                    <p id="kpiProfitMeta" class="analytics-kpi-meta">Net COGS applied to the active range.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="analytics-kpi-card">
                    <div class="analytics-kpi-label">Transaction Count</div>
                    <div id="kpiTransactionCount" class="analytics-kpi-value">0</div>
                    <p class="analytics-kpi-meta">Unique orders.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="analytics-kpi-card">
                    <div class="analytics-kpi-label">Average Basket Value</div>
                    <div id="kpiAverageBasket" class="analytics-kpi-value">₱0.00</div>
                    <p class="analytics-kpi-meta">Revenue per transaction.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="analytics-kpi-card">
                    <div class="analytics-kpi-label">Total Stock Value</div>
                    <div id="kpiStockValue" class="analytics-kpi-value">₱0.00</div>
                    <p class="analytics-kpi-meta">Current inventory value at cost price.</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="analytics-kpi-card">
                    <div class="analytics-kpi-label">Slow-Moving Items</div>
                    <div id="kpiSlowMovingCount" class="analytics-kpi-value">0</div>
                    <p id="kpiSlowMovingMeta" class="analytics-kpi-meta">Zero sales in the last 30 days.</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="card analytics-section-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Sales Trends</span>
                        <span class="text-muted small" id="salesTrendSubtitle">Daily net revenue across the active range</span>
                    </div>
                    <div class="card-body position-relative">
                        <div class="analytics-loading-overlay" data-loading-target="sales-trends">
                            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        </div>
                        <div class="analytics-chart-shell analytics-chart-tall">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                        <div class="border-top mt-3 pt-3">
                            <div id="salesTrendGroupingNote" class="analytics-chart-note mb-3"></div>
                            <div id="salesTrendYearlyBreakdown" class="analytics-year-grid"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card analytics-section-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Revenue by Category</span>
                        <span class="text-muted small">Net revenue distribution</span>
                    </div>
                    <div class="card-body position-relative">
                        <div class="analytics-loading-overlay" data-loading-target="revenue-by-category">
                            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        </div>
                        <div class="analytics-chart-shell">
                            <canvas id="revenueByCategoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-6">
                <div class="card analytics-section-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Top 5 Selling Products</span>
                        <span class="text-muted small">Ranked by quantity sold</span>
                    </div>
                    <div class="card-body position-relative">
                        <div class="analytics-loading-overlay" data-loading-target="top-products">
                            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        </div>
                        <div class="analytics-chart-shell">
                            <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card analytics-section-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Peak Sales Hours</span>
                        <span class="text-muted small">Transaction volume by hour</span>
                    </div>
                    <div class="card-body position-relative">
                        <div class="analytics-loading-overlay" data-loading-target="peak-hours">
                            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        </div>
                        <div class="analytics-chart-shell">
                            <canvas id="peakSalesHoursChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card analytics-section-card h-100">
                    <div class="card-header">Inventory Intelligence</div>
                    <div class="card-body">
                        <div class="analytics-insight-list">
                            <div class="analytics-insight-item">
                                <strong id="inventoryStockValue">PHP 0.00</strong>
                                <span>Total stock value at cost price.</span>
                            </div>
                            <div class="analytics-insight-item">
                                <strong id="inventorySlowMovingCount">0 items</strong>
                                <span id="inventorySlowMovingWindow">Zero sales in the last 30 days.</span>
                            </div>
                            <div class="analytics-insight-item">
                                <strong id="inventoryRefundMeta">PHP 0.00</strong>
                                <span>Refund reversals inside the active range.</span>
                            </div>
                            <div class="analytics-insight-item">
                                <strong id="inventoryCostMeta">PHP 0.00</strong>
                                <span>Net cost of goods sold in the active range.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card analytics-section-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Slow-Moving Items</span>
                        <span class="text-muted small">Products with zero sales in the last 30 days</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover analytics-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th class="text-end">Stock Qty</th>
                                        <th class="text-end">Cost Price</th>
                                        <th class="text-end">Stock Value</th>
                                    </tr>
                                </thead>
                                <tbody id="slowMovingItemsTableBody">
                                    <tr>
                                        <td colspan="5" class="analytics-empty">Loading inventory intelligence...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="analyticsPrintReport" class="analytics-print-report" aria-hidden="true">
        <header class="analytics-print-report-header">
            <h1>Mitzikikay General Merchandise - Analytics Report</h1>
            <p id="analyticsPrintRange" class="analytics-print-report-meta">Date Range: Preparing...</p>
            <p id="analyticsPrintGeneratedAt" class="analytics-print-report-meta">Generated by: <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown User', ENT_QUOTES, 'UTF-8') ?></p>
        </header>

        <section class="analytics-print-section">
            <h2 class="analytics-print-section-title">KPI Summary</h2>
            <div class="analytics-print-kpi-grid">
                <article class="analytics-print-kpi">
                    <div class="analytics-print-kpi-label">Total Revenue</div>
                    <div id="printKpiTotalRevenue" class="analytics-print-kpi-value">₱0.00</div>
                    <p class="analytics-print-kpi-meta">Gross sales less refunds.</p>
                </article>
                <article class="analytics-print-kpi">
                    <div class="analytics-print-kpi-label">Net Profit</div>
                    <div id="printKpiNetProfit" class="analytics-print-kpi-value">₱0.00</div>
                    <p class="analytics-print-kpi-meta">Net COGS applied to the active range.</p>
                </article>
                <article class="analytics-print-kpi">
                    <div class="analytics-print-kpi-label">Transaction Count</div>
                    <div id="printKpiTransactionCount" class="analytics-print-kpi-value">0</div>
                    <p class="analytics-print-kpi-meta">Unique orders.</p>
                </article>
                <article class="analytics-print-kpi">
                    <div class="analytics-print-kpi-label">Average Basket Value</div>
                    <div id="printKpiAverageBasket" class="analytics-print-kpi-value">₱0.00</div>
                    <p class="analytics-print-kpi-meta">Revenue per transaction.</p>
                </article>
                <article class="analytics-print-kpi">
                    <div class="analytics-print-kpi-label">Total Stock Value</div>
                    <div id="printKpiStockValue" class="analytics-print-kpi-value">₱0.00</div>
                    <p class="analytics-print-kpi-meta">Current inventory value at cost price.</p>
                </article>
            </div>
        </section>

        <section class="analytics-print-section">
            <h2 class="analytics-print-section-title">Sales Visualizations</h2>
            <div class="analytics-print-chart-grid">
                <figure class="analytics-print-chart-card analytics-print-chart-card-wide">
                    <figcaption class="analytics-print-chart-title">Sales Trends</figcaption>
                    <div class="analytics-print-chart-frame">
                        <img id="printSalesTrendImage" alt="Sales Trends chart snapshot">
                    </div>
                </figure>
                <figure class="analytics-print-chart-card">
                    <figcaption class="analytics-print-chart-title">Revenue by Category</figcaption>
                    <div class="analytics-print-chart-frame">
                        <img id="printRevenueByCategoryImage" alt="Revenue by Category chart snapshot">
                    </div>
                </figure>
                <figure class="analytics-print-chart-card analytics-print-chart-card-wide">
                    <figcaption class="analytics-print-chart-title">Top 5 Selling Products</figcaption>
                    <div class="analytics-print-chart-frame">
                        <img id="printTopProductsImage" alt="Top 5 Selling Products chart snapshot">
                    </div>
                </figure>
            </div>
        </section>

        <section class="analytics-print-section">
            <h2 class="analytics-print-section-title">Inventory Intelligence</h2>
            <div class="analytics-print-summary-grid">
                <div class="analytics-print-summary-item">
                    <span class="analytics-print-summary-label">Slow-Moving Items</span>
                    <div id="printInventorySlowMovingCount" class="analytics-print-summary-value">0 items</div>
                </div>
                <div class="analytics-print-summary-item">
                    <span class="analytics-print-summary-label">Refund Reversals</span>
                    <div id="printInventoryRefundMeta" class="analytics-print-summary-value">₱0.00</div>
                </div>
                <div class="analytics-print-summary-item">
                    <span class="analytics-print-summary-label">Net Cost of Goods Sold</span>
                    <div id="printInventoryCostMeta" class="analytics-print-summary-value">₱0.00</div>
                </div>
            </div>
            <p id="printInventoryWindow" class="analytics-print-summary-note">Slow-moving window: Preparing...</p>
            <table class="analytics-print-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-end">Stock Qty</th>
                        <th class="text-end">Cost Price</th>
                        <th class="text-end">Stock Value</th>
                    </tr>
                </thead>
                <tbody id="printSlowMovingItemsTableBody">
                    <tr>
                        <td colspan="5" class="analytics-print-empty">Loading inventory intelligence...</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</div>
</div>
</div>

<?php include LEGACY_BASE_PATH . '/includes/layout_end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= LEGACY_BASE_URL ?>/public/js/sales_analytics.js?v=<?= filemtime(LEGACY_BASE_PATH . '/public/js/sales_analytics.js') ?>"></script>
