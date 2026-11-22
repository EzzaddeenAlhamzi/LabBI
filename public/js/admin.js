/* LabBI Admin shell — sidebar, dropdowns. No dependencies. */
(function () {
    'use strict';

    var shell = document.getElementById('appShell');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    var toggleBtn = document.getElementById('sidebarToggle');
    var COLLAPSE_KEY = 'labbi_sidebar_collapsed';
    var mobile = window.matchMedia('(max-width: 1024px)');

    function isMobile() { return mobile.matches; }

    // Desktop collapse preference
    if (!isMobile() && localStorage.getItem(COLLAPSE_KEY) === '1') {
        shell.classList.add('sidebar-collapsed');
    }

    function closeMobile() {
        shell.classList.remove('sidebar-open');
        overlay.classList.remove('show');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (isMobile()) {
                var open = shell.classList.toggle('sidebar-open');
                overlay.classList.toggle('show', open);
            } else {
                var collapsed = shell.classList.toggle('sidebar-collapsed');
                localStorage.setItem(COLLAPSE_KEY, collapsed ? '1' : '0');
            }
        });
    }
    if (overlay) overlay.addEventListener('click', closeMobile);

    // Dropdowns
    document.querySelectorAll('.dropdown').forEach(function (dd) {
        var btn = dd.querySelector('button[aria-haspopup], .dropdown > button');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = dd.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.querySelectorAll('.dropdown.open').forEach(function (other) {
                if (other !== dd) { other.classList.remove('open'); var b = other.querySelector('button'); if (b) b.setAttribute('aria-expanded', 'false'); }
            });
        });
    });
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.dropdown.open').forEach(function (dd) {
            if (!dd.contains(e.target)) {
                dd.classList.remove('open');
                var b = dd.querySelector('button'); if (b) b.setAttribute('aria-expanded', 'false');
            }
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeMobile(); }
    });

    // Close mobile sidebar when a nav link is tapped
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            if (e.target.closest('a') && isMobile()) closeMobile();
        });
    }

    mobile.addEventListener('change', function () {
        closeMobile();
        shell.classList.remove('sidebar-collapsed');
    });
})();
