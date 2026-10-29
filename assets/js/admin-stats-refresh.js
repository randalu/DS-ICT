(function () {
    'use strict';
    // Reload read-only reports, preserving the selected date-range URL.
    window.setInterval(function () {
        if (document.hidden) return;
        var active = document.activeElement;
        if (active && /^(INPUT|TEXTAREA|SELECT)$/.test(active.tagName)) return;
        window.location.reload();
    }, 60000);
})();
