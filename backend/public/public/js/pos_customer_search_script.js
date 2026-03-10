// public/js/pos_customer_search_script.js
// Handles customer search functionality in the POS system.

document.addEventListener('DOMContentLoaded', function () {
    const customerSearchInput = document.getElementById('customerSearchInput');
    const customerSearchResults = document.getElementById('customerSearchResults');
    const selectedCustomerDisplay = document.getElementById('selectedCustomerDisplay');
    const customerNameSpan = document.getElementById('customerName');
    const selectedCustomerIdInput = document.getElementById('selectedCustomerId');
    const clearCustomerBtn = document.getElementById('clearCustomerBtn');

    let searchTimeout; // For debouncing the search input

    // Function to perform customer search via API
    async function searchCustomers(query) {
        if (query.length < 2) { // Only search if query is at least 2 characters long
            customerSearchResults.innerHTML = '';
            customerSearchResults.style.display = 'none';
            return;
        }

        try {
            // Adjust the API path if your 'api' folder is not directly under 'capstonefinal'
            const response = await fetch(`../api/customers_api.php?action=search_customers&query=${encodeURIComponent(query)}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.status === 'success' && data.data.length > 0) {
                displaySearchResults(data.data);
            } else {
                customerSearchResults.innerHTML = '<div class="list-group-item text-muted">No customers found.</div>';
                customerSearchResults.style.display = 'block';
            }
        } catch (error) {
            console.error('Error fetching customers:', error);
            customerSearchResults.innerHTML = '<div class="list-group-item text-danger">Error searching customers.</div>';
            customerSearchResults.style.display = 'block';
        }
    }

    // Function to display search results in the dropdown
    function displaySearchResults(customers) {
        customerSearchResults.innerHTML = ''; // Clear previous results
        customerSearchResults.style.display = 'block'; // Show the results container

        customers.forEach(customer => {
            const customerItem = document.createElement('button');
            customerItem.type = 'button';
            customerItem.className = 'list-group-item list-group-item-action';
            customerItem.textContent = `${customer.first_name} ${customer.last_name} (${customer.contact_number})`;
            customerItem.dataset.customerId = customer.id;
            customerItem.dataset.customerName = `${customer.first_name} ${customer.last_name}`;

            customerItem.addEventListener('click', function () {
                selectCustomer(this.dataset.customerId, this.dataset.customerName);
            });
            customerSearchResults.appendChild(customerItem);
        });
    }

    // Function to select a customer from the search results
    function selectCustomer(id, name) {
        selectedCustomerIdInput.value = id;
        customerNameSpan.textContent = name;
        selectedCustomerDisplay.style.display = 'block'; // Show selected customer info
        customerSearchInput.value = ''; // Clear search input
        customerSearchResults.innerHTML = ''; // Clear results list
        customerSearchResults.style.display = 'none'; // Hide results container
    }

    // Function to clear the selected customer
    function clearSelectedCustomer() {
        selectedCustomerIdInput.value = '';
        customerNameSpan.textContent = 'None';
        selectedCustomerDisplay.style.display = 'none';
        customerSearchInput.value = ''; // Also clear the search input field
    }

    // Event Listener for customer search input with debouncing
    customerSearchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout); // Clear any previous timeout
        searchTimeout = setTimeout(() => {
            searchCustomers(this.value);
        }, 300); // Wait 300ms after user stops typing
    });

    // Event Listener for clearing the selected customer
    clearCustomerBtn.addEventListener('click', clearSelectedCustomer);

    // Optional: Hide search results when clicking outside
    document.addEventListener('click', function (event) {
        if (!customerSearchResults.contains(event.target) && event.target !== customerSearchInput) {
            customerSearchResults.style.display = 'none';
        }
    });

    // Initialize: Clear any pre-filled customer details on page load
    clearSelectedCustomer();
});
