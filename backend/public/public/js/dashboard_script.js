document.addEventListener('DOMContentLoaded', function () {
    const todaySalesAmount = document.getElementById('todaySalesAmount');
    const lowStockCount = document.getElementById('lowStockCount');
    const totalProductsCount = document.getElementById('totalProductsCount');

    if (!todaySalesAmount || !lowStockCount || !totalProductsCount) {
        return;
    }

    function setFallbackValues(value) {
        todaySalesAmount.textContent = value;
        lowStockCount.textContent = value;
        totalProductsCount.textContent = value;
    }

    async function fetchDashboardData() {
        try {
            const response = await fetch('../api/dashboard.php', {
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Unable to load dashboard data.');
            }

            todaySalesAmount.textContent = data.data.today_sales;
            lowStockCount.textContent = data.data.low_stock_items;
            totalProductsCount.textContent = data.data.total_products;
        } catch (error) {
            console.error('Dashboard data load failed:', error);
            setFallbackValues('N/A');
        }
    }

    fetchDashboardData();
});
