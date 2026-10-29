(function () {
    'use strict';

    var bootstrap = window.WidgetBuilderBootstrap || {};
    var listEl = document.getElementById('widgetList');
    var hiddenInput = document.getElementById('widgets_v2_json');
    var emptyHint = document.getElementById('widgetEmptyHint');
    var picker = document.getElementById('widgetTypePicker');
    var addBtn = document.getElementById('widgetAddBtn');

    if (!listEl || !hiddenInput) return;

    var state = (bootstrap.config && bootstrap.config.widgets) ? bootstrap.config.widgets.slice() : [];

    function newId() {
        return 'w' + Math.random().toString(36).slice(2, 10);
    }

    function defaultsFor(type) {
        var s = {};
        switch (type) {
            case 'related_posts':
            case 'related_itineraries':
            case 'related_destinations':
                s = { count: 4, manual_ids: [] };
                break;
            case 'cta':
                s = { title: 'Plan Your Trip', text: '', button_label: 'Get a Quote', button_url: '', whatsapp: 0, after_paragraph: 2 };
                break;
            case 'newsletter':
                s = { title: 'Stay in the loop', intro: 'Monthly Sri Lanka travel tips. No spam.', button_label: 'Subscribe', after_paragraph: 2 };
                break;
            case 'ad':
                s = { title: '', html: '', devices: ['desktop', 'mobile'], starts_at: '', ends_at: '', after_paragraph: 2 };
                break;
        }
        return s;
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderRow(w, idx) {
        var positions = bootstrap.positions || {};
        var visibility = bootstrap.visibility || {};
        var typeLabel = (bootstrap.catalogue || {})[w.type] || w.type;

        var posOptions = '';
        Object.keys(positions).forEach(function (k) {
            posOptions += '<option value="' + escapeHtml(k) + '"' + (w.position === k ? ' selected' : '') + '>' + escapeHtml(positions[k]) + '</option>';
        });

        var visMode = (w.visibility && w.visibility.mode) || 'all';
        var visIds = (w.visibility && w.visibility.ids) || [];
        var visOptions = '';
        Object.keys(visibility).forEach(function (k) {
            visOptions += '<option value="' + escapeHtml(k) + '"' + (visMode === k ? ' selected' : '') + '>' + escapeHtml(visibility[k]) + '</option>';
        });

        var html = ''
            + '<div class="widget-row card card-soft" data-idx="' + idx + '" style="border:1px solid var(--admin-line);">'
            +   '<div class="card-body p-3">'
            +     '<div class="d-flex justify-content-between align-items-center mb-2">'
            +       '<div class="d-flex align-items-center gap-2">'
            +         '<span class="widget-handle" style="cursor:grab;font-size:1.1rem;line-height:1;">⋮⋮</span>'
            +         '<strong>' + escapeHtml(typeLabel) + '</strong>'
            +         '<span class="admin-chip" style="font-size:.65rem;">' + escapeHtml(w.id) + '</span>'
            +       '</div>'
            +       '<button type="button" class="btn btn-outline-danger btn-sm js-remove">Remove</button>'
            +     '</div>'
            +     '<div class="row g-2">'
            +       '<div class="col-md-5">'
            +         '<label class="form-label small mb-1">Position</label>'
            +         '<select class="form-select form-select-sm js-position">' + posOptions + '</select>'
            +       '</div>'
            +       '<div class="col-md-5">'
            +         '<label class="form-label small mb-1">Visibility</label>'
            +         '<select class="form-select form-select-sm js-visibility-mode">' + visOptions + '</select>'
            +       '</div>'
            +       '<div class="col-md-2">'
            +         '<label class="form-label small mb-1">After ¶</label>'
            +         '<input type="number" min="1" class="form-control form-control-sm js-after-paragraph" value="' + (w.settings && w.settings.after_paragraph ? w.settings.after_paragraph : 2) + '">'
            +       '</div>'
            +       '<div class="col-12 js-visibility-ids" style="' + (visMode === 'all' ? 'display:none;' : '') + '">'
            +         '<label class="form-label small mb-1">Visibility IDs</label>'
            +         renderVisibilityIdsSelect(visMode, visIds)
            +       '</div>'
            +       '<div class="col-12 js-settings-panel">'
            +         renderSettingsPanel(w)
            +       '</div>'
            +     '</div>'
            +   '</div>'
            + '</div>';
        return html;
    }

    function renderVisibilityIdsSelect(mode, selectedIds) {
        if (mode === 'all') return '';
        var collection = mode === 'category_in' ? (bootstrap.categories || []) : (bootstrap.posts || []);
        var titleKey = mode === 'category_in' ? 'name' : 'title';
        var idSet = {};
        (selectedIds || []).forEach(function (id) { idSet[String(id)] = true; });

        var options = '';
        collection.forEach(function (item) {
            var idStr = String(item.id);
            options += '<option value="' + escapeHtml(idStr) + '"' + (idSet[idStr] ? ' selected' : '') + '>' + escapeHtml(item[titleKey] || '') + '</option>';
        });
        return '<select multiple size="4" class="form-select form-select-sm js-visibility-id-list">' + options + '</select>';
    }

    function renderSettingsPanel(w) {
        var s = w.settings || {};
        var html = '<div class="mt-2 p-2" style="background:#f7f9fc;border-radius:.6rem;">';
        switch (w.type) {
            case 'related_posts':
            case 'related_itineraries':
            case 'related_destinations':
                var collection = w.type === 'related_posts' ? (bootstrap.posts || [])
                    : w.type === 'related_itineraries' ? (bootstrap.itineraries || [])
                    : (bootstrap.destinations || []);
                var labelKey = w.type === 'related_posts' ? 'title' : 'name';
                var manualIds = s.manual_ids || [];
                var idSet = {};
                manualIds.forEach(function (id) { idSet[String(id)] = true; });
                var opts = '';
                collection.forEach(function (item) {
                    var idStr = String(item.id);
                    var label = item[labelKey] || '';
                    if (item.days) label += ' (' + item.days + ' days)';
                    opts += '<option value="' + escapeHtml(idStr) + '"' + (idSet[idStr] ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
                });
                html += '<div class="row g-2">'
                    +   '<div class="col-md-3">'
                    +     '<label class="form-label small mb-1">Count</label>'
                    +     '<input type="number" min="1" max="10" class="form-control form-control-sm js-setting" data-key="count" value="' + (s.count || 4) + '">'
                    +   '</div>'
                    +   '<div class="col-md-9">'
                    +     '<label class="form-label small mb-1">Manual picks (optional)</label>'
                    +     '<select multiple size="4" class="form-select form-select-sm js-manual-ids">' + opts + '</select>'
                    +   '</div>'
                    + '</div>';
                break;
            case 'cta':
                html += '<div class="row g-2">'
                    +   '<div class="col-md-6"><label class="form-label small mb-1">Title</label><input class="form-control form-control-sm js-setting" data-key="title" value="' + escapeHtml(s.title || '') + '"></div>'
                    +   '<div class="col-md-6"><label class="form-label small mb-1">Button Label</label><input class="form-control form-control-sm js-setting" data-key="button_label" value="' + escapeHtml(s.button_label || '') + '"></div>'
                    +   '<div class="col-12"><label class="form-label small mb-1">Text</label><textarea rows="2" class="form-control form-control-sm js-setting" data-key="text">' + escapeHtml(s.text || '') + '</textarea></div>'
                    +   '<div class="col-md-9"><label class="form-label small mb-1">Button URL</label><input class="form-control form-control-sm js-setting" data-key="button_url" value="' + escapeHtml(s.button_url || '') + '"></div>'
                    +   '<div class="col-md-3 d-flex align-items-end"><label class="form-check"><input type="checkbox" class="form-check-input js-setting" data-key="whatsapp" data-bool="1" ' + (s.whatsapp ? 'checked' : '') + '> <span class="small">WhatsApp btn</span></label></div>'
                    + '</div>';
                break;
            case 'newsletter':
                html += '<div class="row g-2">'
                    +   '<div class="col-md-6"><label class="form-label small mb-1">Title</label><input class="form-control form-control-sm js-setting" data-key="title" value="' + escapeHtml(s.title || '') + '"></div>'
                    +   '<div class="col-md-6"><label class="form-label small mb-1">Button Label</label><input class="form-control form-control-sm js-setting" data-key="button_label" value="' + escapeHtml(s.button_label || '') + '"></div>'
                    +   '<div class="col-12"><label class="form-label small mb-1">Intro</label><textarea rows="2" class="form-control form-control-sm js-setting" data-key="intro">' + escapeHtml(s.intro || '') + '</textarea></div>'
                    + '</div>';
                break;
            case 'ad':
                var devices = s.devices || ['desktop', 'mobile'];
                html += '<div class="row g-2">'
                    +   '<div class="col-md-6"><label class="form-label small mb-1">Title</label><input class="form-control form-control-sm js-setting" data-key="title" value="' + escapeHtml(s.title || '') + '"></div>'
                    +   '<div class="col-md-6"><label class="form-label small mb-1">Devices</label>'
                    +     '<div class="d-flex gap-3 small">'
                    +       '<label><input type="checkbox" class="js-ad-device" value="desktop" ' + (devices.indexOf('desktop') >= 0 ? 'checked' : '') + '> Desktop</label>'
                    +       '<label><input type="checkbox" class="js-ad-device" value="mobile" ' + (devices.indexOf('mobile') >= 0 ? 'checked' : '') + '> Mobile</label>'
                    +     '</div>'
                    +   '</div>'
                    +   '<div class="col-md-6"><label class="form-label small mb-1">Starts at</label><input type="datetime-local" class="form-control form-control-sm js-setting" data-key="starts_at" value="' + escapeHtml((s.starts_at || '').replace(' ', 'T').slice(0, 16)) + '"></div>'
                    +   '<div class="col-md-6"><label class="form-label small mb-1">Ends at</label><input type="datetime-local" class="form-control form-control-sm js-setting" data-key="ends_at" value="' + escapeHtml((s.ends_at || '').replace(' ', 'T').slice(0, 16)) + '"></div>'
                    +   '<div class="col-12"><label class="form-label small mb-1">HTML (sanitised on save)</label><textarea rows="4" class="form-control form-control-sm js-setting" data-key="html">' + escapeHtml(s.html || '') + '</textarea></div>'
                    + '</div>';
                break;
        }
        html += '</div>';
        return html;
    }

    function render() {
        listEl.innerHTML = state.map(renderRow).join('');
        if (emptyHint) emptyHint.style.display = state.length === 0 ? '' : 'none';
        sync();
    }

    function readRow(rowEl, w) {
        w.position = rowEl.querySelector('.js-position').value;
        var visMode = rowEl.querySelector('.js-visibility-mode').value;
        w.visibility = w.visibility || { mode: 'all', ids: [] };
        w.visibility.mode = visMode;

        var idsBox = rowEl.querySelector('.js-visibility-ids');
        if (visMode !== 'all' && idsBox) {
            idsBox.style.display = '';
            var sel = rowEl.querySelector('.js-visibility-id-list');
            if (sel) {
                w.visibility.ids = Array.prototype.map.call(sel.selectedOptions, function (o) { return parseInt(o.value, 10); }).filter(function (n) { return n > 0; });
            }
        } else {
            if (idsBox) idsBox.style.display = 'none';
            w.visibility.ids = [];
        }

        var afterP = rowEl.querySelector('.js-after-paragraph');
        if (afterP) {
            w.settings = w.settings || {};
            w.settings.after_paragraph = parseInt(afterP.value, 10) || 2;
        }

        rowEl.querySelectorAll('.js-setting').forEach(function (el) {
            var k = el.getAttribute('data-key');
            if (!k) return;
            w.settings = w.settings || {};
            if (el.getAttribute('data-bool') === '1') {
                w.settings[k] = el.checked ? 1 : 0;
            } else if (el.type === 'number') {
                w.settings[k] = parseInt(el.value, 10) || 0;
            } else {
                w.settings[k] = el.value;
            }
        });

        var manualIds = rowEl.querySelector('.js-manual-ids');
        if (manualIds) {
            w.settings.manual_ids = Array.prototype.map.call(manualIds.selectedOptions, function (o) { return parseInt(o.value, 10); }).filter(function (n) { return n > 0; });
        }

        var devs = rowEl.querySelectorAll('.js-ad-device');
        if (devs.length) {
            var picked = [];
            devs.forEach(function (cb) { if (cb.checked) picked.push(cb.value); });
            w.settings.devices = picked.length ? picked : ['desktop', 'mobile'];
        }
    }

    function sync() {
        var rows = listEl.querySelectorAll('.widget-row');
        var newOrder = [];
        rows.forEach(function (rowEl, i) {
            var idx = parseInt(rowEl.getAttribute('data-idx'), 10);
            var w = state[idx];
            if (!w) return;
            w.order = i;
            readRow(rowEl, w);
            newOrder.push(w);
        });
        state = newOrder;
        listEl.querySelectorAll('.widget-row').forEach(function (rowEl, i) {
            rowEl.setAttribute('data-idx', i);
        });
        hiddenInput.value = JSON.stringify({ version: 2, widgets: state });
    }

    function add(type) {
        if (!type) return;
        state.push({
            id: newId(),
            type: type,
            position: 'sidebar',
            order: state.length,
            visibility: { mode: 'all', ids: [] },
            settings: defaultsFor(type)
        });
        render();
    }

    listEl.addEventListener('click', function (ev) {
        if (ev.target && ev.target.classList.contains('js-remove')) {
            var rowEl = ev.target.closest('.widget-row');
            if (!rowEl) return;
            var idx = parseInt(rowEl.getAttribute('data-idx'), 10);
            state.splice(idx, 1);
            render();
        }
    });

    listEl.addEventListener('change', function (ev) {
        if (ev.target.classList.contains('js-visibility-mode')) {
            var rowEl = ev.target.closest('.widget-row');
            var idx = parseInt(rowEl.getAttribute('data-idx'), 10);
            var w = state[idx];
            w.visibility = w.visibility || { mode: 'all', ids: [] };
            w.visibility.mode = ev.target.value;
            w.visibility.ids = [];
            render();
            return;
        }
        sync();
    });

    listEl.addEventListener('input', function () { sync(); });

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            var type = picker ? picker.value : '';
            if (!type) return;
            add(type);
            picker.value = '';
        });
    }

    if (typeof Sortable !== 'undefined') {
        Sortable.create(listEl, {
            handle: '.widget-handle',
            animation: 150,
            onEnd: sync
        });
    }

    render();
})();
