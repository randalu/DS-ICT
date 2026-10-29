(function () {
    'use strict';

    var scroller = document.querySelector('.container-scroller');
    var sidebar  = document.getElementById('sidebar');

    // Create backdrop as a direct child of <body>.
    // This is critical: if .container-scroller or .page-body-wrapper ever gets
    // a CSS transform applied, position:fixed children no longer fix to the
    // viewport. Appending to body avoids that entirely.
    var backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);

    function isMobile() {
        return window.innerWidth < 992;
    }

    function openSidebar() {
        scroller.classList.add('sidebar-open');
        document.body.classList.add('admin-sidebar-open');
    }

    function closeSidebar() {
        scroller.classList.remove('sidebar-open');
        document.body.classList.remove('admin-sidebar-open');
    }

    // Desktop sidebar collapse toggle (the ☰ button inside the sidebar header on desktop)
    document.querySelectorAll('[data-admin-toggle="sidebar"]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (isMobile()) {
                scroller.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
            } else {
                scroller.classList.toggle('sidebar-collapsed');
            }
        });
    });

    // Mobile hamburger button (top-right on mobile)
    document.querySelectorAll('[data-admin-toggle="sidebar-mobile"]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            scroller.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
        });
    });

    // Backdrop click — always close, never toggle.
    // This replaces the old data-admin-toggle on the backdrop div,
    // which caused double-fire with the document click handler below.
    backdrop.addEventListener('click', function () {
        closeSidebar();
    });

    // Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

    // Resize: if going to desktop width, ensure sidebar state is clean
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (!isMobile()) {
                closeSidebar();
            }
        }, 150);
    });

    // Persist sidebar group open/closed state across page loads — ict_admin_nav_state
    (function () {
        var STORAGE_KEY = 'ict_admin_nav_state'; // ict_admin_nav_state

        function loadState() {
            try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch (_) { return {}; }
        }

        function saveState(state) {
            try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (_) {}
        }

        // Listen for Bootstrap collapse events on sidebar nav items
        document.querySelectorAll('.sidebar .collapse').forEach(function (el) {
            var id = el.id;
            if (!id) return;

            // On show/hide, save state
            el.addEventListener('show.bs.collapse', function () {
                var state = loadState();
                state[id] = '1';
                saveState(state);
            });
            el.addEventListener('hide.bs.collapse', function () {
                var state = loadState();
                state[id] = '0';
                saveState(state);
            });

            // On page load, restore state (but only if this group is not already
            // set as active/open by the PHP active-class logic)
            var isActiveGroup = el.classList.contains('show');
            if (!isActiveGroup) {
                var state = loadState();
                if (state[id] === '1') {
                    el.classList.add('show');
                    var trigger = document.querySelector('[href="#' + id + '"], [data-bs-target="#' + id + '"]');
                    if (trigger) trigger.setAttribute('aria-expanded', 'true');
                }
            }
        });
    }());
})();
