// capstonefinal/public/js/suppliers_script.js

document.addEventListener('DOMContentLoaded', function () {
    const supplierTableBody = document.getElementById('supplierTableBody');
    const supplierSearch = document.getElementById('supplierSearch');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const supplierPagination = document.getElementById('supplierPagination');

    const suppliersPerPage = 10;
    let currentPage = 1;
    let totalSuppliers = 0;

    // Add Supplier Modal Elements
    const addSupplierModal = new bootstrap.Modal(document.getElementById('addSupplierModal'));
    const addSupplierForm = document.getElementById('addSupplierForm');

    // Edit Supplier Modal Elements
    const editSupplierModal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
    const editSupplierForm = document.getElementById('editSupplierForm');
    const editSupplierId = document.getElementById('editSupplierId');
    const editSupplierName = document.getElementById('editSupplierName');
    const editContactPerson = document.getElementById('editContactPerson');
    const editSupplierPhone = document.getElementById('editSupplierPhone');
    const editSupplierEmail = document.getElementById('editSupplierEmail');
    const editSupplierAddress = document.getElementById('editSupplierAddress');
    const deleteSupplierModalElement = document.getElementById('deleteSupplierModal');
    const deleteSupplierModal = deleteSupplierModalElement ? new bootstrap.Modal(deleteSupplierModalElement) : null;
    const deleteSupplierName = document.getElementById('deleteSupplierName');
    const confirmDeleteSupplierBtn = document.getElementById('confirmDeleteSupplierBtn');
    let supplierToDeleteId = null;

    // Function to fetch suppliers from the API and update the table
    async function fetchSuppliers() {
        supplierTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-info py-4">Fetching suppliers...</td></tr>`;

        const searchTerm = supplierSearch.value.trim();
        const offset = (currentPage - 1) * suppliersPerPage;

        // Ensure this path correctly points to your API endpoint
        // It's relative to views/suppliers.php, so ../api/suppliers.php
        let url = `../api/suppliers.php?limit=${suppliersPerPage}&offset=${offset}`;
        if (searchTerm) {
            url += `&search=${encodeURIComponent(searchTerm)}`;
        }

        try {
            const response = await fetch(url);
            const data = await response.json(); // This is where the JSON parsing error occurs if PHP returns HTML

            if (data.success) {
                totalSuppliers = data.total;
                renderSuppliers(data.suppliers);
                renderPagination();
            } else {
                supplierTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${data.message || 'Error fetching suppliers.'}</td></tr>`;
                totalSuppliers = 0;
                renderPagination();
            }
        } catch (error) {
            console.error('Error fetching suppliers:', error);
            // This alert shows "Failed to load suppliers. Network error or server issue."
            showMessage('Failed to load suppliers. Network error or server issue.', 'danger');
            supplierTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Failed to load suppliers. Network error or server issue.</td></tr>`;
            totalSuppliers = 0;
            renderPagination();
        }
    }

    // Function to render suppliers in the table
    function renderSuppliers(suppliers) {
        supplierTableBody.innerHTML = ''; // Clear existing rows

        if (suppliers.length === 0) {
            supplierTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No suppliers found.</td></tr>`;
            return;
        }

        suppliers.forEach(supplier => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${htmlspecialchars(supplier.id)}</td>
                <td>${htmlspecialchars(supplier.name)}</td>
                <td>${htmlspecialchars(supplier.contact_person || 'N/A')}</td>
                <td>${htmlspecialchars(supplier.phone || 'N/A')}</td>
                <td>${htmlspecialchars(supplier.email || 'N/A')}</td>
                <td>${htmlspecialchars(supplier.address || 'N/A')}</td>
                <td class="table-actions">
                    <button class="btn btn-sm btn-info me-1 edit-supplier-btn"
                            data-id="${supplier.id}"
                            data-name="${htmlspecialchars(supplier.name)}"
                            data-contact-person="${htmlspecialchars(supplier.contact_person || '')}"
                            data-phone="${htmlspecialchars(supplier.phone || '')}"
                            data-email="${htmlspecialchars(supplier.email || '')}"
                            data-address="${htmlspecialchars(supplier.address || '')}"
                            title="Edit Supplier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-supplier-btn" data-id="${supplier.id}" title="Delete Supplier">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            supplierTableBody.appendChild(row);
        });

        // Attach event listeners for edit and delete buttons
        document.querySelectorAll('.edit-supplier-btn').forEach(button => {
            button.addEventListener('click', openEditModal);
        });

        document.querySelectorAll('.delete-supplier-btn').forEach(button => {
            button.addEventListener('click', function () {
                if (!deleteSupplierModal) {
                    return;
                }

                supplierToDeleteId = this.dataset.id;
                if (deleteSupplierName) {
                    deleteSupplierName.textContent = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                }
                deleteSupplierModal.show();
            });
        });
    }

    // Function to render pagination controls
    function renderPagination() {
        supplierPagination.innerHTML = '';

        const totalPages = Math.ceil(totalSuppliers / suppliersPerPage);

        if (totalPages <= 1) {
            return;
        }

        const prevLi = document.createElement('li');
        prevLi.classList.add('page-item');
        if (currentPage === 1) prevLi.classList.add('disabled');
        prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous" data-page="${currentPage - 1}"><span aria-hidden="true">&laquo;</span></a>`;
        supplierPagination.appendChild(prevLi);

        for (let i = 1; i <= totalPages; i++) {
            const pageLi = document.createElement('li');
            pageLi.classList.add('page-item');
            if (i === currentPage) pageLi.classList.add('active');
            pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            supplierPagination.appendChild(pageLi);
        }

        const nextLi = document.createElement('li');
        nextLi.classList.add('page-item');
        if (currentPage === totalPages) nextLi.classList.add('disabled');
        nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next" data-page="${currentPage + 1}"><span aria-hidden="true">&raquo;</span></a>`;
        supplierPagination.appendChild(nextLi);

        supplierPagination.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page > 0 && page <= totalPages && page !== currentPage) {
                    currentPage = page;
                    fetchSuppliers();
                }
            });
        });
    }

    // Function to handle adding a new supplier
    addSupplierForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const formData = new FormData(addSupplierForm);
        const supplierData = {};
        formData.forEach((value, key) => {
            supplierData[key] = value;
        });

        try {
            const response = await fetch('../api/suppliers.php', { // Ensure this path is correct
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(supplierData)
            });

            const data = await response.json();

            if (data.success) {
                showMessage('Supplier added successfully!', 'success');
                addSupplierModal.hide();
                addSupplierForm.reset(); // Clear the form
                currentPage = 1; // Go to first page to see the new supplier
                fetchSuppliers();
            } else {
                showMessage('Error adding supplier: ' + (data.message || 'Unknown error.'), 'danger');
            }
        } catch (error) {
            console.error('Error adding supplier:', error);
            // This alert shows "Failed to add supplier due to a network or server error."
            showMessage('Failed to add supplier due to a network or server error.', 'danger');
        }
    });

    // Function to open the edit modal and populate its fields
    function openEditModal(event) {
        const button = event.currentTarget;
        const supplierId = button.dataset.id;
        const supplierName = button.dataset.name;
        const contactPerson = button.dataset.contactPerson;
        const supplierPhone = button.dataset.phone;
        const supplierEmail = button.dataset.email;
        const supplierAddress = button.dataset.address;

        editSupplierId.value = supplierId;
        editSupplierName.value = supplierName;
        editContactPerson.value = contactPerson;
        editSupplierPhone.value = supplierPhone;
        editSupplierEmail.value = supplierEmail;
        editSupplierAddress.value = supplierAddress;

        editSupplierModal.show();
    }

    // Event listener for the edit supplier form submission
    editSupplierForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const formData = new FormData(editSupplierForm);
        const supplierData = {};
        formData.forEach((value, key) => {
            supplierData[key] = value;
        });

        try {
            const response = await fetch(`../api/suppliers.php`, { // Ensure this path is correct
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(supplierData)
            });

            const data = await response.json();

            if (data.success) {
                showMessage('Supplier updated successfully!', 'success');
                editSupplierModal.hide();
                fetchSuppliers();
            } else {
                showMessage('Error updating supplier: ' + (data.message || 'Unknown error.'), 'danger');
            }
        } catch (error) {
            console.error('Error updating supplier:', error);
            showMessage('Failed to update supplier due to a network or server error.', 'danger');
        }
    });


    // Function to handle supplier deletion
    async function deleteSupplier(supplierId) {
        try {
            const response = await fetch(`../api/suppliers.php?id=${supplierId}`, { // Ensure this path is correct
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            const data = await response.json();

            if (data.success) {
                showMessage('Supplier deleted successfully!', 'success');
                fetchSuppliers();
            } else {
                showMessage('Error deleting supplier: ' + (data.message || 'Unknown error.'), 'danger');
            }
        } catch (error) {
            console.error('Error deleting supplier:', error);
            showMessage('Failed to delete supplier due to a network or server error.', 'danger');
        }
    }

    confirmDeleteSupplierBtn?.addEventListener('click', async function () {
        if (!supplierToDeleteId) {
            return;
        }

        confirmDeleteSupplierBtn.disabled = true;

        try {
            await deleteSupplier(supplierToDeleteId);
        } finally {
            supplierToDeleteId = null;
            confirmDeleteSupplierBtn.disabled = false;
            deleteSupplierModal?.hide();
        }
    });

    deleteSupplierModalElement?.addEventListener('hidden.bs.modal', function () {
        supplierToDeleteId = null;
        if (deleteSupplierName) {
            deleteSupplierName.textContent = 'this supplier';
        }
        if (confirmDeleteSupplierBtn) {
            confirmDeleteSupplierBtn.disabled = false;
        }
    });

    // Helper for HTML escaping (basic) - important for displaying fetched data safely
    function htmlspecialchars(str) {
        if (typeof str !== 'string' && typeof str !== 'number') return str;
        str = String(str); // Ensure it's a string
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    // Event Listeners for Filters
    supplierSearch.addEventListener('input', () => {
        currentPage = 1;
        fetchSuppliers();
    });
    resetFiltersBtn.addEventListener('click', () => {
        supplierSearch.value = '';
        currentPage = 1;
        fetchSuppliers();
    });

    // Initial fetch of suppliers when the page loads
    fetchSuppliers();
});
