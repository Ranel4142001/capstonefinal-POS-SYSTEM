document.addEventListener('DOMContentLoaded', function() {
    // Function to fetch and update dashboard data
    function fetchDashboardData() {
        fetch('../api/dashboard.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('todaySalesAmount').textContent = data.data.today_sales;
                    document.getElementById('lowStockCount').textContent = data.data.low_stock_items;
                    document.getElementById('totalProductsCount').textContent = data.data.total_products;
                } else {
                    console.error('API Error:', data.message);
                    // Optionally, update the UI to show an error message
                    document.getElementById('todaySalesAmount').textContent = 'Error';
                    document.getElementById('lowStockCount').textContent = 'Error';
                    document.getElementById('totalProductsCount').textContent = 'Error';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                // Optionally, update the UI to show an error message
                document.getElementById('todaySalesAmount').textContent = 'N/A';
                document.getElementById('lowStockCount').textContent = 'N/A';
                document.getElementById('totalProductsCount').textContent = 'N/A';
            });
    }

    // Call the function to fetch data when the page loads
    fetchDashboardData();

    // Optionally, refresh data periodically (e.g., every 5 minutes)
    // setInterval(fetchDashboardData, 300000); // 300000 ms = 5 minutes
});