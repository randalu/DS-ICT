/**
 * Theme toggle.
 *
 * Light is the server-rendered default, so this file only ever switches *away*
 * from it (or back). The choice is stored in localStorage and applied on the
 * document element, which is why the head partial renders data-theme="light"
 * up front: there is no flash of the wrong theme.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'ict-theme';
    var root = document.documentElement;

    var read = function () {
        try {
            return window.localStorage.getItem(STORAGE_KEY);
        } catch (error) {
            return null;
        }
    };

    var write = function (value) {
        try {
            window.localStorage.setItem(STORAGE_KEY, value);
        } catch (error) {
            /* storage may be blocked; the toggle still works for this page */
        }
    };

    // A stored preference wins over the server default.
    var stored = read();

    if (stored === 'dark' || stored === 'light') {
        root.setAttribute('data-theme', stored);
    }

    var toggle = document.querySelector('[data-ix-theme-toggle]');

    if (!toggle) {
        return;
    }

    var sync = function () {
        var isDark = root.getAttribute('data-theme') === 'dark';
        toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    };

    sync();

    toggle.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        write(next);
        sync();
    });
})();
