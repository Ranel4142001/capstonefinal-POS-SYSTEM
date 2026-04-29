document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('analyticsDashboard');
    if (!root) {
        return;
    }

    const apiUrl = root.dataset.apiUrl;
    const username = root.dataset.username || 'Unknown User';
    const startDateInput = document.getElementById('analyticsStartDate');
    const endDateInput = document.getElementById('analyticsEndDate');
    const applyButton = document.getElementById('analyticsApplyBtn');
    const statusElement = document.getElementById('analyticsStatus');
    const reportRangeElement = document.getElementById('analyticsReportRange');
    const generatedAtElement = document.getElementById('analyticsGeneratedAt');
    const printRangeElement = document.getElementById('analyticsPrintRange');
    const printGeneratedAtElement = document.getElementById('analyticsPrintGeneratedAt');
    const printButton = document.getElementById('analyticsPrintBtn');
    const salesTrendSubtitle = document.getElementById('salesTrendSubtitle');
    const salesTrendGroupingNote = document.getElementById('salesTrendGroupingNote');
    const salesTrendYearlyBreakdown = document.getElementById('salesTrendYearlyBreakdown');
    const presetButtons = Array.from(document.querySelectorAll('.analytics-preset-btn'));
    const loadingOverlays = Array.from(document.querySelectorAll('.analytics-loading-overlay'));
    const slowMovingItemsTableBody = document.getElementById('slowMovingItemsTableBody');
    const printSlowMovingItemsTableBody = document.getElementById('printSlowMovingItemsTableBody');

    const currencyFormatter = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const numberFormatter = new Intl.NumberFormat('en-PH');
    const dateFormatter = new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
    const dateTimeFormatter = new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });

    const chartInstances = {};
    const printChartBindings = [
        { key: 'salesTrendChart', imageId: 'printSalesTrendImage', label: 'Sales Trends' },
        { key: 'revenueByCategoryChart', imageId: 'printRevenueByCategoryImage', label: 'Revenue by Category' },
        { key: 'topProductsChart', imageId: 'printTopProductsImage', label: 'Top 5 Selling Products' },
    ];

    let currentPreset = 'last_30_days';
    let activeRequestController = null;
    let activeRequestToken = 0;
    let latestPayload = null;
    let dashboardLoading = false;
    let printPreparing = false;
    let printChartsDirty = true;
    let printRefreshPromise = null;

    function setStatus(message, type) {
        if (!statusElement) {
            return;
        }

        statusElement.textContent = message || '';
        statusElement.classList.remove('text-danger', 'text-success');

        if (type === 'danger') {
            statusElement.classList.add('text-danger');
        } else if (type === 'success') {
            statusElement.classList.add('text-success');
        }
    }

    function notify(message, type) {
        if (typeof window.showMessage === 'function') {
            window.showMessage(message, type);
            return;
        }

        if (type === 'danger') {
            console.error(message);
        } else {
            console.log(message);
        }
    }

    function syncPrintButtonState() {
        if (printButton) {
            printButton.disabled = dashboardLoading || printPreparing;
        }
    }

    function setLoading(isLoading) {
        dashboardLoading = isLoading;

        presetButtons.forEach(function (button) {
            button.disabled = isLoading;
        });

        if (applyButton) {
            applyButton.disabled = isLoading;
        }

        loadingOverlays.forEach(function (overlay) {
            overlay.classList.toggle('active', isLoading);
        });

        syncPrintButtonState();

        if (isLoading) {
            setStatus('Loading analytics dashboard...', '');
        }
    }

    function setPrintPreparing(isPreparing) {
        printPreparing = isPreparing;
        syncPrintButtonState();
    }

    function formatCurrency(value) {
        return currencyFormatter.format(Number(value || 0));
    }

    function formatNumber(value) {
        return numberFormatter.format(Number(value || 0));
    }

    function formatDateRange(startDate, endDate) {
        return dateFormatter.format(new Date(startDate + 'T00:00:00')) + ' - ' +
            dateFormatter.format(new Date(endDate + 'T00:00:00'));
    }

    function formatDateTimeLabel(value) {
        return dateTimeFormatter.format(new Date(value));
    }

    function waitForFrame() {
        return new Promise(function (resolve) {
            window.requestAnimationFrame(function () {
                resolve();
            });
        });
    }

    async function waitForFrames(count) {
        const frameCount = Number.isFinite(count) && count > 0 ? count : 1;

        for (let index = 0; index < frameCount; index += 1) {
            await waitForFrame();
        }
    }

    function setPresetState(preset) {
        currentPreset = preset;

        presetButtons.forEach(function (button) {
            const isActive = button.dataset.preset === preset;
            button.classList.toggle('active', isActive);
            button.classList.toggle('btn-primary', isActive);
            button.classList.toggle('btn-outline-primary', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        const isCustom = preset === 'custom';
        if (startDateInput) {
            startDateInput.disabled = !isCustom;
        }
        if (endDateInput) {
            endDateInput.disabled = !isCustom;
        }
    }

    function getRequestParams() {
        const params = new URLSearchParams();
        params.set('preset', currentPreset);

        if (currentPreset === 'custom') {
            const startDate = startDateInput ? startDateInput.value : '';
            const endDate = endDateInput ? endDateInput.value : '';

            if (!startDate || !endDate) {
                notify('Choose both a start date and end date for the custom range.', 'warning');
                setStatus('Choose both custom dates before applying the filter.', 'danger');
                return null;
            }

            params.set('start_date', startDate);
            params.set('end_date', endDate);
        }

        return params;
    }

    function upsertChart(key, canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        if (chartInstances[key]) {
            chartInstances[key].data = config.data;
            chartInstances[key].options = config.options;
            chartInstances[key].update();
            return;
        }

        chartInstances[key] = new Chart(canvas, config);
    }

    function buildSalesTrendChart(chartData) {
        const values = chartData && chartData.datasets && chartData.datasets[0] ? chartData.datasets[0].data : [];
        const labels = chartData ? chartData.labels : [];
        const fullLabels = chartData ? chartData.full_labels || [] : [];
        const chartType = chartData && chartData.chart_type ? chartData.chart_type : 'line';
        const isMonthly = chartData && chartData.granularity === 'month';
        const pointRadius = isMonthly ? 0 : (values.length > 35 ? 2 : 3);

        upsertChart('salesTrendChart', 'salesTrendChart', {
            type: chartType,
            data: {
                labels: labels && labels.length ? labels : ['No data'],
                datasets: [{
                    label: chartData && chartData.datasets && chartData.datasets[0] ? chartData.datasets[0].label : 'Net Revenue',
                    data: values && values.length ? values : [0],
                    borderColor: '#1f78ff',
                    backgroundColor: isMonthly ? 'rgba(31, 120, 255, 0.82)' : 'rgba(31, 120, 255, 0.16)',
                    fill: !isMonthly,
                    tension: isMonthly ? 0 : 0.28,
                    borderWidth: isMonthly ? 1.5 : 3,
                    pointRadius: pointRadius,
                    pointHoverRadius: isMonthly ? 4 : 5,
                    borderRadius: isMonthly ? 8 : 0,
                    borderSkipped: false,
                    maxBarThickness: isMonthly ? 42 : undefined,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                const item = items && items[0];
                                if (!item) {
                                    return '';
                                }

                                return fullLabels[item.dataIndex] || item.label || '';
                            },
                            label: function (context) {
                                return ' ' + formatCurrency(context.parsed.y);
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return formatCurrency(value);
                            },
                        },
                    },
                    x: {
                        ticks: {
                            maxRotation: isMonthly ? 35 : 0,
                            minRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: isMonthly ? 12 : 10,
                        },
                        grid: {
                            display: !isMonthly,
                        },
                    },
                },
            },
        });
    }

    function renderSalesTrendBreakdown(chartData) {
        if (salesTrendSubtitle) {
            salesTrendSubtitle.textContent = chartData && chartData.description
                ? chartData.description
                : 'Daily net revenue across the active range';
        }

        if (salesTrendGroupingNote) {
            salesTrendGroupingNote.textContent = chartData && chartData.grouping_note
                ? chartData.grouping_note
                : '';
        }

        if (!salesTrendYearlyBreakdown) {
            return;
        }

        const yearlyBreakdown = chartData && Array.isArray(chartData.yearly_breakdown)
            ? chartData.yearly_breakdown
            : [];

        if (!yearlyBreakdown.length) {
            salesTrendYearlyBreakdown.innerHTML = '';
            return;
        }

        salesTrendYearlyBreakdown.innerHTML = yearlyBreakdown.map(function (item) {
            return '' +
                '<div class="analytics-year-card">' +
                '<div class="analytics-year-card-title">Year ' + escapeHtml(item.year) + '</div>' +
                '<div class="analytics-year-card-value">' + formatCurrency(item.revenue) + '</div>' +
                '<div class="analytics-year-card-meta">Transactions: ' + formatNumber(item.transaction_count) + '</div>' +
                '<div class="analytics-year-card-meta">Avg Basket: ' + formatCurrency(item.average_basket_value) + '</div>' +
                '</div>';
        }).join('');
    }

    function buildTopProductsChart(chartData) {
        const labels = chartData ? chartData.labels : [];
        const quantities = chartData && chartData.datasets && chartData.datasets[0] ? chartData.datasets[0].data : [];
        const revenues = chartData ? chartData.revenue || [] : [];

        upsertChart('topProductsChart', 'topProductsChart', {
            type: 'bar',
            data: {
                labels: labels && labels.length ? labels : ['No qualifying sales'],
                datasets: [{
                    label: 'Quantity Sold',
                    data: quantities && quantities.length ? quantities : [0],
                    backgroundColor: ['#204a87', '#2d5eaa', '#3a72bf', '#5688cf', '#7aa3df'],
                    borderRadius: 10,
                    borderSkipped: false,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const index = context.dataIndex;
                                return ' Qty: ' + formatNumber(context.parsed.x) + ' | Revenue: ' + formatCurrency(revenues[index] || 0);
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                    },
                },
            },
        });
    }

    function buildRevenueByCategoryChart(chartData) {
        const labels = chartData ? chartData.labels : [];
        const values = chartData && chartData.datasets && chartData.datasets[0] ? chartData.datasets[0].data : [];
        const categoryColors = ['#0d6efd', '#20c997', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#198754', '#6610f2'];

        upsertChart('revenueByCategoryChart', 'revenueByCategoryChart', {
            type: 'doughnut',
            data: {
                labels: labels && labels.length ? labels : ['No category revenue'],
                datasets: [{
                    label: 'Revenue by Category',
                    data: values && values.length ? values : [0],
                    backgroundColor: categoryColors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' ' + context.label + ': ' + formatCurrency(context.parsed);
                            },
                        },
                    },
                },
                cutout: '62%',
            },
        });
    }

    function buildPeakHoursChart(chartData) {
        const labels = chartData ? chartData.labels : [];
        const values = chartData && chartData.datasets && chartData.datasets[0] ? chartData.datasets[0].data : [];

        upsertChart('peakSalesHoursChart', 'peakSalesHoursChart', {
            type: 'bar',
            data: {
                labels: labels && labels.length ? labels : ['00:00'],
                datasets: [{
                    label: 'Transactions',
                    data: values && values.length ? values : [0],
                    backgroundColor: 'rgba(32, 201, 151, 0.78)',
                    borderColor: '#20c997',
                    borderWidth: 1.5,
                    borderRadius: 10,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                    },
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 12,
                        },
                    },
                },
            },
        });
    }

    function renderSlowMovingItemsTable(tableBody, items, emptyClassName) {
        if (!tableBody) {
            return;
        }

        if (!items || !items.length) {
            tableBody.innerHTML = '<tr><td colspan="5" class="' + emptyClassName + '">No slow-moving items found in the selected inventory window.</td></tr>';
            return;
        }

        tableBody.innerHTML = items.map(function (item) {
            return '' +
                '<tr>' +
                '<td><strong>' + escapeHtml(item.product_name) + '</strong></td>' +
                '<td>' + escapeHtml(item.category_name) + '</td>' +
                '<td class="text-end">' + formatNumber(item.stock_quantity) + '</td>' +
                '<td class="text-end">' + formatCurrency(item.cost_price) + '</td>' +
                '<td class="text-end">' + formatCurrency(item.stock_value) + '</td>' +
                '</tr>';
        }).join('');
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setText(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value;
        }
    }

    function updateRangeAndTimestamp(filter, generatedAt) {
        if (reportRangeElement && filter.start_date && filter.end_date) {
            reportRangeElement.textContent = 'Date Range: ' + formatDateRange(filter.start_date, filter.end_date) + ' (' + (filter.label || 'Selected Range') + ')';
        }

        if (printRangeElement && filter.start_date && filter.end_date) {
            printRangeElement.textContent = 'Date Range: ' + formatDateRange(filter.start_date, filter.end_date);
        }

        if (generatedAtElement && generatedAt) {
            generatedAtElement.textContent = 'Generated by: ' + username + ' on ' + formatDateTimeLabel(generatedAt);
        }

        if (printGeneratedAtElement && generatedAt) {
            printGeneratedAtElement.textContent = 'Generated by: ' + username + ' on ' + formatDateTimeLabel(generatedAt);
        }
    }

    function updateKpis(kpis, inventory) {
        setText('kpiTotalRevenue', formatCurrency(kpis.total_revenue));
        setText('kpiNetProfit', formatCurrency(kpis.net_profit));
        setText('kpiTransactionCount', formatNumber(kpis.transaction_count));
        setText('kpiAverageBasket', formatCurrency(kpis.average_basket_value));
        setText('kpiStockValue', formatCurrency(inventory.total_stock_value));
        setText('kpiSlowMovingCount', formatNumber(inventory.slow_moving_count));
        setText('kpiRevenueMeta', 'Gross sales less refunds.');
        setText('kpiProfitMeta', 'Net COGS applied to the active range.');
        setText('kpiSlowMovingMeta', 'Zero sales during ' + (inventory.slow_moving_window_label || 'the last 30 days') + '.');

        setText('printKpiTotalRevenue', formatCurrency(kpis.total_revenue));
        setText('printKpiNetProfit', formatCurrency(kpis.net_profit));
        setText('printKpiTransactionCount', formatNumber(kpis.transaction_count));
        setText('printKpiAverageBasket', formatCurrency(kpis.average_basket_value));
        setText('printKpiStockValue', formatCurrency(inventory.total_stock_value));
    }

    function updateInventorySummary(kpis, inventory) {
        setText('inventoryStockValue', formatCurrency(inventory.total_stock_value));
        setText('inventorySlowMovingCount', formatNumber(inventory.slow_moving_count) + ' items');
        setText('inventorySlowMovingWindow', 'Zero sales during ' + (inventory.slow_moving_window_label || 'the last 30 days') + '.');
        setText('inventoryRefundMeta', formatCurrency(kpis.refunds_total));
        setText('inventoryCostMeta', formatCurrency(kpis.cost_of_goods_sold));

        setText('printInventorySlowMovingCount', formatNumber(inventory.slow_moving_count) + ' items');
        setText('printInventoryRefundMeta', formatCurrency(kpis.refunds_total));
        setText('printInventoryCostMeta', formatCurrency(kpis.cost_of_goods_sold));
        setText('printInventoryWindow', 'Slow-moving window: ' + (inventory.slow_moving_window_label || 'the last 30 days'));
    }

    function setPrintImage(imageId, dataUrl, altText) {
        const image = document.getElementById(imageId);
        if (!image) {
            return null;
        }

        if (dataUrl) {
            image.src = dataUrl;
        } else {
            image.removeAttribute('src');
        }
        image.alt = altText;
        return image;
    }

    function decodePrintImage(image) {
        if (!image || !image.getAttribute('src') || typeof image.decode !== 'function') {
            return Promise.resolve();
        }

        return image.decode().catch(function () {
            return undefined;
        });
    }

    async function refreshPrintChartSnapshots(force) {
        if (!force && !printChartsDirty) {
            return;
        }

        if (printRefreshPromise) {
            return printRefreshPromise;
        }

        printRefreshPromise = (async function () {
            const originalAnimations = new Map();
            const pendingImageDecodes = [];
            setPrintPreparing(true);

            try {
                Object.keys(chartInstances).forEach(function (key) {
                    const chart = chartInstances[key];
                    if (!chart) {
                        return;
                    }

                    originalAnimations.set(key, chart.options.animation);
                    chart.stop();
                    chart.options.animation = false;
                    chart.resize();
                    chart.update('none');
                });

                await waitForFrames(2);

                printChartBindings.forEach(function (binding) {
                    const chart = chartInstances[binding.key];
                    if (!chart || typeof chart.toBase64Image !== 'function') {
                        setPrintImage(binding.imageId, '', binding.label + ' chart snapshot unavailable');
                        return;
                    }

                    const printImage = setPrintImage(binding.imageId, chart.toBase64Image(), binding.label + ' chart snapshot');
                    pendingImageDecodes.push(decodePrintImage(printImage));
                });

                await Promise.all(pendingImageDecodes);
                await waitForFrames(2);
                printChartsDirty = false;
            } finally {
                originalAnimations.forEach(function (animation, key) {
                    const chart = chartInstances[key];
                    if (chart) {
                        chart.options.animation = animation;
                    }
                });

                setPrintPreparing(false);
                printRefreshPromise = null;
            }
        })();

        return printRefreshPromise;
    }

    function renderDashboard(payload) {
        const filter = payload.filter || {};
        const kpis = payload.kpis || {};
        const charts = payload.charts || {};
        const inventory = payload.inventory || {};

        latestPayload = payload;
        setPresetState(filter.preset || currentPreset);

        if (startDateInput) {
            startDateInput.value = filter.start_date || '';
        }
        if (endDateInput) {
            endDateInput.value = filter.end_date || '';
        }

        updateRangeAndTimestamp(filter, payload.generated_at);
        updateKpis(kpis, inventory);
        updateInventorySummary(kpis, inventory);

        buildSalesTrendChart(charts.sales_trends || {});
        renderSalesTrendBreakdown(charts.sales_trends || {});
        buildTopProductsChart(charts.top_products || {});
        buildRevenueByCategoryChart(charts.revenue_by_category || {});
        buildPeakHoursChart(charts.peak_sales_hours || {});
        renderSlowMovingItemsTable(slowMovingItemsTableBody, inventory.slow_moving_items || [], 'analytics-empty');
        renderSlowMovingItemsTable(printSlowMovingItemsTableBody, inventory.slow_moving_items || [], 'analytics-print-empty');

        printChartsDirty = true;
        void refreshPrintChartSnapshots(false);

        if (filter.start_date && filter.end_date) {
            setStatus('Showing analytics from ' + formatDateRange(filter.start_date, filter.end_date) + '.', 'success');
        } else {
            setStatus('Analytics dashboard ready.', 'success');
        }
    }

    async function loadDashboard() {
        const params = getRequestParams();
        if (!params) {
            return;
        }

        const requestToken = activeRequestToken + 1;
        activeRequestToken = requestToken;

        if (activeRequestController) {
            activeRequestController.abort();
        }

        const requestController = new AbortController();
        activeRequestController = requestController;
        setLoading(true);

        try {
            const response = await fetch(apiUrl + '?' + params.toString(), {
                method: 'GET',
                cache: 'no-store',
                signal: requestController.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json();

            if (requestToken !== activeRequestToken) {
                return;
            }

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to load the analytics dashboard.');
            }

            renderDashboard(payload);
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }

            if (requestToken !== activeRequestToken) {
                return;
            }

            const message = error instanceof Error ? error.message : 'Unable to load the analytics dashboard.';
            setStatus(message, 'danger');
            notify(message, 'danger');
        } finally {
            if (activeRequestController === requestController) {
                activeRequestController = null;
            }

            if (requestToken === activeRequestToken) {
                setLoading(false);
            }
        }
    }

    async function handlePrintReport() {
        if (activeRequestController) {
            setStatus('Wait for the analytics dashboard to finish loading before printing.', 'danger');
            return;
        }

        if (!latestPayload) {
            await loadDashboard();
            if (!latestPayload) {
                setStatus('Unable to prepare the report because analytics data is not available.', 'danger');
                return;
            }
        }

        setStatus('Preparing print-optimized analytics report...', '');

        try {
            await refreshPrintChartSnapshots(true);
            await waitForFrames(2);
            window.print();
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Unable to prepare the print report.';
            setStatus(message, 'danger');
            notify(message, 'danger');
        }
    }

    presetButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const preset = button.dataset.preset || 'last_30_days';
            setPresetState(preset);

            if (preset === 'custom') {
                setStatus('Select a custom date range, then click Apply Filter.', '');
                return;
            }

            void loadDashboard();
        });
    });

    if (applyButton) {
        applyButton.addEventListener('click', function () {
            void loadDashboard();
        });
    }

    if (printButton) {
        printButton.addEventListener('click', function () {
            void handlePrintReport();
        });
    }

    window.addEventListener('afterprint', function () {
        Object.keys(chartInstances).forEach(function (key) {
            const chart = chartInstances[key];
            if (chart) {
                chart.resize();
                chart.update('none');
            }
        });
    });

    setPresetState(currentPreset);
    void loadDashboard();
});
