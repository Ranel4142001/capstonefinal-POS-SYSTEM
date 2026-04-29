document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('auditTrailFilterForm');
    const resetButton = document.getElementById('auditTrailResetBtn');
    const apiUrlInput = document.getElementById('auditTrailApiUrl');
    const startDateInput = document.getElementById('audit_start_date');
    const endDateInput = document.getElementById('audit_end_date');
    const eventTypeSelect = document.getElementById('audit_event_type');
    const auditableTypeSelect = document.getElementById('audit_auditable_type');
    const userIdInput = document.getElementById('audit_user_id');
    const perPageSelect = document.getElementById('audit_per_page');
    const tableBody = document.getElementById('auditTrailTableBody');
    const paginationNav = document.getElementById('auditTrailPaginationNav');
    const summaryText = document.getElementById('auditTrailSummaryText');

    const detailId = document.getElementById('auditDetailId');
    const detailUser = document.getElementById('auditDetailUser');
    const detailEvent = document.getElementById('auditDetailEvent');
    const detailEntity = document.getElementById('auditDetailEntity');
    const detailRecordId = document.getElementById('auditDetailRecordId');
    const detailCreatedAt = document.getElementById('auditDetailCreatedAt');
    const detailMessage = document.getElementById('auditDetailMessage');
    const detailOldValues = document.getElementById('auditDetailOldValues');
    const detailNewValues = document.getElementById('auditDetailNewValues');

    const detailsModalElement = document.getElementById('auditTrailDetailsModal');
    const detailsModal = detailsModalElement ? new bootstrap.Modal(detailsModalElement) : null;
    const apiUrl = apiUrlInput ? apiUrlInput.value : '../api/audit_logs.php';

    let currentPage = 1;
    let currentLogs = [];

    function toIsoDate(date) {
        const offset = date.getTimezoneOffset();
        const adjusted = new Date(date.getTime() - (offset * 60000));
        return adjusted.toISOString().slice(0, 10);
    }

    function setDefaultDates() {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        startDateInput.value = toIsoDate(thirtyDaysAgo);
        endDateInput.value = toIsoDate(today);
        eventTypeSelect.value = '';
        auditableTypeSelect.value = '';
        userIdInput.value = '';
        perPageSelect.value = '20';
    }

    function formatDateTime(value) {
        if (!value) {
            return 'N/A';
        }

        const date = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getBadgeClass(eventType) {
        switch (String(eventType || '').toLowerCase()) {
            case 'create':
                return 'bg-success';
            case 'update':
                return 'bg-primary';
            case 'delete':
                return 'bg-danger';
            case 'refund':
                return 'bg-warning text-dark';
            default:
                return 'bg-secondary';
        }
    }

    function buildPagination(totalPages, activePage) {
        paginationNav.innerHTML = '';

        if (totalPages <= 1) {
            return;
        }

        let html = '<ul class="pagination justify-content-center">';
        html += `
            <li class="page-item ${activePage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${activePage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;

        for (let page = 1; page <= totalPages; page += 1) {
            html += `
                <li class="page-item ${page === activePage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${page}">${page}</a>
                </li>
            `;
        }

        html += `
            <li class="page-item ${activePage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${activePage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;
        html += '</ul>';

        paginationNav.innerHTML = html;
    }

    function buildQueryParams(page) {
        const params = new URLSearchParams({
            page: String(page),
            per_page: perPageSelect.value,
            _: String(Date.now()),
        });

        if (startDateInput.value) {
            params.set('start_date', startDateInput.value);
        }

        if (endDateInput.value) {
            params.set('end_date', endDateInput.value);
        }

        if (eventTypeSelect.value) {
            params.set('event_type', eventTypeSelect.value);
        }

        if (auditableTypeSelect.value) {
            params.set('auditable_type', auditableTypeSelect.value);
        }

        if (userIdInput.value.trim()) {
            params.set('user_id', userIdInput.value.trim());
        }

        return params;
    }

    function prettyJson(value) {
        if (!value || (typeof value === 'object' && Object.keys(value).length === 0)) {
            return 'No values recorded.';
        }

        try {
            return JSON.stringify(value, null, 2);
        } catch (error) {
            return String(value);
        }
    }

    function normalizeResponseMessage(rawBody, fallbackMessage) {
        const trimmed = String(rawBody || '').trim();

        if (!trimmed) {
            return fallbackMessage;
        }

        if (trimmed.startsWith('<')) {
            return 'Audit trail endpoint returned an invalid response. Please refresh the page and try again.';
        }

        return trimmed.length > 240 ? `${trimmed.slice(0, 240)}...` : trimmed;
    }

    function openDetails(logId) {
        const log = currentLogs.find(item => String(item.id) === String(logId));
        if (!log || !detailsModal) {
            return;
        }

        detailId.textContent = log.id;
        detailUser.textContent = log.user ? `${log.user.username} (ID ${log.user.id}, ${log.user.role})` : (log.user_id ? `User ID ${log.user_id}` : 'System');
        detailEvent.innerHTML = `<span class="badge ${getBadgeClass(log.event_type)}">${escapeHtml(log.event_type)}</span>`;
        detailEntity.textContent = log.auditable_type || 'N/A';
        detailRecordId.textContent = log.auditable_id || 'N/A';
        detailCreatedAt.textContent = formatDateTime(log.created_at);
        detailMessage.textContent = log.message || 'No message recorded.';
        detailOldValues.textContent = prettyJson(log.old_values);
        detailNewValues.textContent = prettyJson(log.new_values);

        detailsModal.show();
    }

    async function loadAuditLogs(page = 1) {
        currentPage = page;
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Loading audit logs...</td></tr>';
        paginationNav.innerHTML = '';
        summaryText.textContent = 'Loading audit logs...';

        try {
            const response = await fetch(`${apiUrl}?${buildQueryParams(page).toString()}`, {
                cache: 'no-store'
            });
            const rawBody = await response.text();
            let data = null;

            try {
                data = rawBody ? JSON.parse(rawBody) : null;
            } catch (parseError) {
                throw new Error(normalizeResponseMessage(rawBody, 'Received an invalid response while loading audit logs.'));
            }

            if (!response.ok || !data || !data.success) {
                const message = data?.message || normalizeResponseMessage(rawBody, 'Failed to load audit logs.');
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(message)}</td></tr>`;
                summaryText.textContent = 'Audit trail unavailable';
                showMessage(message, response.status === 503 ? 'warning' : 'danger');
                return;
            }

            currentLogs = Array.isArray(data.data) ? data.data : [];
            tableBody.innerHTML = '';

            if (!currentLogs.length) {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No audit logs found for the selected filters.</td></tr>';
                summaryText.textContent = '0 records found';
                return;
            }

            currentLogs.forEach(log => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHtml(formatDateTime(log.created_at))}</td>
                    <td>${escapeHtml(log.user ? `${log.user.username} (ID ${log.user.id})` : (log.user_id ? `User ID ${log.user_id}` : 'System'))}</td>
                    <td><span class="badge ${getBadgeClass(log.event_type)}">${escapeHtml(log.event_type)}</span></td>
                    <td>${escapeHtml(log.auditable_type || 'N/A')}</td>
                    <td>${escapeHtml(log.auditable_id || 'N/A')}</td>
                    <td>${escapeHtml(log.message || '')}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary audit-detail-btn" data-log-id="${log.id}">
                            View
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            const total = data.meta?.total ?? currentLogs.length;
            const current = data.meta?.current_page ?? page;
            const lastPage = data.meta?.last_page ?? 1;
            summaryText.textContent = `${total} record${total === 1 ? '' : 's'} found`;
            buildPagination(lastPage, current);
        } catch (error) {
            console.error('Error loading audit logs:', error);
            const message = error instanceof Error && error.message
                ? error.message
                : 'Network error while loading audit logs.';
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(message)}</td></tr>`;
            summaryText.textContent = 'Audit trail unavailable';
            showMessage(message, 'danger');
        }
    }

    filterForm.addEventListener('submit', function (event) {
        event.preventDefault();
        loadAuditLogs(1);
    });

    resetButton.addEventListener('click', function () {
        setDefaultDates();
        loadAuditLogs(1);
    });

    paginationNav.addEventListener('click', function (event) {
        const link = event.target.closest('.page-link');
        if (!link) {
            return;
        }

        event.preventDefault();
        const page = parseInt(link.dataset.page, 10);
        if (!Number.isNaN(page) && page > 0) {
            loadAuditLogs(page);
        }
    });

    tableBody.addEventListener('click', function (event) {
        const button = event.target.closest('.audit-detail-btn');
        if (!button) {
            return;
        }

        openDetails(button.dataset.logId);
    });

    setDefaultDates();
    loadAuditLogs(1);
});
