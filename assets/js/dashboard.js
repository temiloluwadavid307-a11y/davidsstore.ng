(function () {
    'use strict';

    const toggle = document.querySelector('[data-dashboard-toggle]');
    const sidebar = document.querySelector('[data-dashboard-sidebar]');
    const backdrop = document.querySelector('[data-dashboard-backdrop]');
    const closeBtn = document.querySelector('[data-dashboard-close]');

    const closeSidebar = function () {
        document.body.classList.remove('sidebar-open');
        if (sidebar) sidebar.classList.remove('active');
        if (backdrop) backdrop.classList.remove('active');
    };

    const openSidebar = function () {
        document.body.classList.add('sidebar-open');
        if (sidebar) sidebar.classList.add('active');
        if (backdrop) backdrop.classList.add('active');
    };

    if (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            if (document.body.classList.contains('sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function (event) {
            event.preventDefault();
            closeSidebar();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    document.querySelectorAll('[data-dashboard-sidebar] a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
})();
