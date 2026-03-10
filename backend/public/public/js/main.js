// your_pos_project_root/public/js/main.js

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