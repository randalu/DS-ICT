(function () {
    'use strict';
    var config = window.ICTPrivacy;
    if (!config) return;
    var suspended = false;
    var dialog = document.getElementById('analyticsChoiceDialog');
    var notice = document.getElementById('analyticsNotice');
    var restoreFocus = null;
    var signalKey = 'ict_analytics_choice_changed:' + config.cookiePath;

    config.canTrack = function (transport) {
        if (transport === 'first_party') return !!config.pageGroup && config.firstPartyEnabled === true;
        if (transport !== 'gtm') return false;
        return !suspended && config.state.chosen === true && config.state.analytics === true && config.state.expires_at * 1000 > Date.now()
            && !!config.pageGroup && (transport === 'gtm' ? config.gtmEnabled : config.firstPartyEnabled);
    };
    config.canLoad = function (category) {
        return !suspended && config.state.chosen === true && config.state[category] === true
            && config.state.expires_at * 1000 > Date.now() && config.optionalContext === true
            && ['external_reviews', 'translation', 'chat'].indexOf(category) !== -1
            && config.availableCategories?.[category] === true;
    };
    function suspend() { suspended = true; window.dataLayer = []; }
    function signal() { try { localStorage.setItem(signalKey, String(Date.now()) + ':' + Math.random()); } catch (_) {} }
    function sizeNotice() {
        if (notice) document.documentElement.style.setProperty('--ict-analytics-notice-height', Math.ceil(notice.getBoundingClientRect().height + 16) + 'px');
    }
    function refresh() {
        var url = new URL(config.choiceUrl, window.location.href);
        if (url.origin !== window.location.origin) { suspend(); return; }
        return fetch(url.pathname + '?format=json', { credentials: 'same-origin', cache: 'no-store' })
            .then(function (response) { if (!response.ok) throw new Error('refresh'); return response.json(); })
            .then(function (next) {
                if (!next.state || next.state.choice_token !== config.state.choice_token || next.revision !== config.revision
                    || next.firstPartyEnabled !== config.firstPartyEnabled || next.gtmEnabled !== config.gtmEnabled
                    || JSON.stringify(next.availableCategories) !== JSON.stringify(config.availableCategories)) {
                    suspend(); window.location.reload();
                }
            }).catch(function () { suspend(); });
    }
    document.querySelectorAll('[data-analytics-open]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!dialog || typeof dialog.showModal !== 'function') return;
            event.preventDefault(); restoreFocus = link; dialog.showModal();
        });
    });
    document.querySelectorAll('[data-analytics-close]').forEach(function (button) {
        button.addEventListener('click', function () { if (dialog) dialog.close(); });
    });
    if (dialog) dialog.addEventListener('close', function () { if (restoreFocus) restoreFocus.focus(); });
    document.querySelectorAll('[data-analytics-choice-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (typeof fetch !== 'function' || !event.submitter) return;
            event.preventDefault(); suspend();
            var error = form.querySelector('[data-analytics-error]');
            if (error) { error.hidden = true; error.textContent = ''; }
            var body = new URLSearchParams(new FormData(form));
            body.set('choice_action', event.submitter.value);
            ['analytics', 'external_reviews', 'translation', 'chat'].forEach(function (category) {
                body.set(category, form.querySelector('input[type="checkbox"][name="' + category + '"]')?.checked ? '1' : '0');
            });
            var url = new URL(config.saveUrl, window.location.href);
            if (url.origin !== window.location.origin) { if (error) { error.textContent = 'Could not save your privacy choices.'; error.hidden = false; } return; }
            form.querySelectorAll('button').forEach(function (button) { button.disabled = true; });
            fetch(url.pathname, { method: 'POST', headers: { Accept: 'application/json' }, body: body, credentials: 'same-origin', cache: 'no-store' })
                .then(function (response) { return response.json(); })
                .then(function (result) { if (!result.ok) throw new Error(result.error || 'Could not save your privacy choices.'); signal(); window.location.reload(); })
                .catch(function (exception) {
                    if (error) { error.textContent = exception.message || 'Could not save your privacy choices. Please try again.'; error.hidden = false; }
                    form.querySelectorAll('button').forEach(function (button) { button.disabled = false; });
                });
        });
    });
    window.addEventListener('storage', function (event) { if (event.key === signalKey) { suspend(); refresh(); } });
    window.addEventListener('focus', refresh);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
    window.addEventListener('resize', sizeNotice);
    if (typeof ResizeObserver === 'function' && notice) new ResizeObserver(sizeNotice).observe(notice);
    sizeNotice();
    if (config.state.chosen) setInterval(function () { if (config.state.expires_at * 1000 <= Date.now()) { suspend(); window.location.reload(); } }, 60000);
})();
