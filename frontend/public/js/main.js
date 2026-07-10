// your_pos_project_root/public/js/main.js

(function () {
    const typeAliases = {
        error: 'danger',
        fail: 'danger',
    };

    const typeStyles = {
        success: { title: 'Success', headerClass: 'bg-primary text-white', buttonClass: 'btn-primary' },
        danger: { title: 'Error', headerClass: 'bg-danger text-white', buttonClass: 'btn-danger' },
        warning: { title: 'Warning', headerClass: 'bg-warning text-dark', buttonClass: 'btn-warning' },
        info: { title: 'Info', headerClass: 'bg-primary text-white', buttonClass: 'btn-primary' },
        secondary: { title: 'Notice', headerClass: 'bg-primary text-white', buttonClass: 'btn-primary' },
    };

    let manualBackdrop = null;

    function normalizeType(type) {
        const normalized = (type || 'success').toLowerCase();
        return typeAliases[normalized] || normalized;
    }

    function ensureMessageModalElements() {
        if (!document.body) {
            return {};
        }

        let modalElement = document.getElementById('globalMessageModal');

        if (!modalElement) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
                <div class="modal fade" id="globalMessageModal" tabindex="-1" aria-labelledby="globalMessageModalTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="globalMessageModalTitle">Success</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="globalMessageModalBody"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" id="globalMessageModalBtn" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(wrapper.firstElementChild);
            modalElement = document.getElementById('globalMessageModal');
        }

        return {
            modalElement,
            modalTitle: document.getElementById('globalMessageModalTitle'),
            modalBody: document.getElementById('globalMessageModalBody'),
            modalButton: document.getElementById('globalMessageModalBtn'),
            modalHeader: modalElement ? modalElement.querySelector('.modal-header') : null,
            closeButton: modalElement ? modalElement.querySelector('.btn-close') : null,
        };
    }

    function hideManualModal(modalElement) {
        if (!modalElement) {
            return;
        }

        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.removeAttribute('aria-modal');
        document.body.classList.remove('modal-open');

        if (manualBackdrop) {
            manualBackdrop.remove();
            manualBackdrop = null;
        }

        modalElement.dispatchEvent(new Event('hidden.bs.modal'));
    }

    function showManualModal(modalElement) {
        if (!modalElement) {
            return;
        }

        if (manualBackdrop) {
            manualBackdrop.remove();
        }

        manualBackdrop = document.createElement('div');
        manualBackdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(manualBackdrop);

        modalElement.style.display = 'block';
        modalElement.removeAttribute('aria-hidden');
        modalElement.setAttribute('aria-modal', 'true');
        modalElement.classList.add('show');
        document.body.classList.add('modal-open');
    }

    function bindManualDismiss(elements) {
        const dismissButtons = [elements.closeButton, elements.modalButton];

        dismissButtons.forEach(function (button) {
            if (!button || button.dataset.manualModalBound === 'true') {
                return;
            }

            button.dataset.manualModalBound = 'true';
            button.addEventListener('click', function () {
                if (typeof bootstrap === 'undefined') {
                    hideManualModal(elements.modalElement);
                }
            });
        });
    }

    function showMessage(message, type = 'success') {
        const normalizedType = normalizeType(type);
        const styles = typeStyles[normalizedType] || typeStyles.secondary;
        const elements = ensureMessageModalElements();

        if (!elements.modalElement || !elements.modalTitle || !elements.modalBody || !elements.modalButton || !elements.modalHeader) {
            console.warn('Unable to show message modal:', message);
            return;
        }

        elements.modalTitle.textContent = styles.title;
        elements.modalBody.textContent = message;

        elements.modalHeader.classList.remove(
            'bg-primary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-secondary',
            'text-white', 'text-dark'
        );
        styles.headerClass.split(' ').forEach(function (className) {
            elements.modalHeader.classList.add(className);
        });

        elements.modalButton.classList.remove('btn-primary', 'btn-success', 'btn-danger', 'btn-warning', 'btn-info', 'btn-secondary');
        elements.modalButton.classList.add(styles.buttonClass);

        bindManualDismiss(elements);

        if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
            const modalInstance = bootstrap.Modal.getOrCreateInstance(elements.modalElement);
            modalInstance.show();
            return;
        }

        showManualModal(elements.modalElement);
    }

    window.showMessage = showMessage;
})();

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const sidebarToggle = document.getElementById('sidebarToggle'); // Mobile toggle
    const sidebarToggleDesktop = document.getElementById('sidebarToggleDesktop'); // Desktop toggle
    const closeSidebarBtn = document.getElementById('closeSidebar'); // Close button for mobile
    const overlay = document.getElementById('overlay'); // Overlay

    const sidebarStateKey = 'sidebarState'; // Key for localStorage

    // --- Core Toggle Function ---
    function toggleSidebar() {
        if (window.innerWidth <= 768) {
            // Mobile behavior: slide in/out
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            // Save mobile state
            if (sidebar.classList.contains('active')) {
                localStorage.setItem(sidebarStateKey, 'active_mobile');
            } else {
                localStorage.removeItem(sidebarStateKey); // Remove if closed
            }
        } else {
            // Desktop behavior: collapse/expand
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('sidebar-collapsed');

            // Save desktop state to localStorage
            if (sidebar.classList.contains('hidden')) {
                localStorage.setItem(sidebarStateKey, 'hidden');
            } else {
                localStorage.setItem(sidebarStateKey, 'visible'); // or remove item
            }
        }
    }

    // --- Event Listeners ---
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    if (sidebarToggleDesktop) {
        sidebarToggleDesktop.addEventListener('click', toggleSidebar);
    }
    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', toggleSidebar); // Close button also calls toggle
    }
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar); // Close sidebar when clicking overlay
    }

    // --- Handle Initial Load and Resize ---
    function applySidebarState() {
        if (window.innerWidth <= 768) {
            // On mobile, hide sidebar by default unless it was explicitly open
            sidebar.classList.remove('hidden'); // Ensure desktop hidden state is removed
            mainContent.classList.remove('sidebar-collapsed'); // Ensure desktop collapsed state is removed

            if (localStorage.getItem(sidebarStateKey) === 'active_mobile') {
                sidebar.classList.add('active');
                overlay.classList.add('active');
            } else {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        } else {
            // On desktop, apply stored state or default to visible
            sidebar.classList.remove('active'); // Ensure mobile active state is removed
            overlay.classList.remove('active'); // Ensure mobile overlay is removed

            if (localStorage.getItem(sidebarStateKey) === 'hidden') {
                sidebar.classList.add('hidden');
                mainContent.classList.add('sidebar-collapsed');
            } else {
                sidebar.classList.remove('hidden'); // Default desktop is visible
                mainContent.classList.remove('sidebar-collapsed');
            }
        }
    }

    // Apply state on initial page load
    applySidebarState();

    // Reapply state on window resize
    window.addEventListener('resize', applySidebarState);

    // Minor adjustment for desktop toggle button state on resize, if needed
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // If desktop and sidebar was hidden from localStorage, ensure it's still hidden
            if (localStorage.getItem(sidebarStateKey) === 'hidden') {
                sidebar.classList.add('hidden');
                mainContent.classList.add('sidebar-collapsed');
            }
        }
    });

});
