<?php
$seoFormType = ['destination_form.php' => 'destination', 'itinerary_form.php' => 'itinerary', 'tour_form.php' => 'tour', 'blog_post_form.php' => 'blog'][basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''))] ?? null;
if ($seoFormType !== null):
    require_once __DIR__ . '/../includes/seo_manager.php';
    $seoFormId = (int) ($record['id'] ?? 0);
    $seoFormRevision = '';
    if ($seoFormId && seo_ready()) {
        $seoFormRevision = seo_revision($record, seo_plan($seoFormType, $seoFormId));
    }
?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/seo-manager.css')) ?>">
<script id="seo-form-config" data-type="<?= e($seoFormType) ?>" data-id="<?= $seoFormId ?>" data-revision="<?= e($seoFormRevision) ?>" data-api="<?= e(base_url('admin/seo_manager_api.php')) ?>" data-csrf="<?= e(csrf_token()) ?>" src="<?= e(base_url('assets/js/seo-ai.js')) ?>"></script>
<?php endif; ?>
<script src="<?= e(ict_privacy_url('assets/vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(ict_privacy_url('assets/vendor/quill-2.0.2/dist/quill.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/admin.js')) ?>"></script>
<?php if (in_array($adminPage ?? '', ['webstats', 'dashboard'], true)): ?>
<script src="<?= e(base_url('assets/js/admin-stats-refresh.js')) ?>" defer></script>
<?php endif; ?>
<script>
var ictAiUrl = <?= json_encode(base_url('admin/ai_generate.php')) ?>;
</script>
<script>
(function () {
    var aiUrl = ictAiUrl; // alias for the Quill block
    var editors = document.querySelectorAll('textarea.quill-editor, textarea.js-richtext');
    if (!editors.length || typeof Quill === 'undefined') return;

    function openInternalLinkDialog(quill) {
        var range = quill.getSelection(true);
        if (!range || range.length === 0) {
            alert('Select the text you want to link first.');
            return;
        }

        var query = prompt('Search destination, itinerary, experience, or blog post:');
        if (!query) return;

        fetch(<?= json_encode(base_url('admin/content_link_search.php')) ?> + '?q=' + encodeURIComponent(query), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (payload) {
                var items = payload.items || [];
                if (!items.length) {
                    alert('No matching content found.');
                    return;
                }
                var choiceText = items.map(function (item, index) {
                    return (index + 1) + '. [' + item.label + '] ' + item.title;
                }).join('\n');
                var selected = parseInt(prompt('Choose content number:\n' + choiceText), 10);
                if (!selected || !items[selected - 1]) return;
                var item = items[selected - 1];
                quill.formatText(range.index, range.length, 'link', item.url);
                setTimeout(function () {
                    var link = quill.root.querySelector('a[href="' + item.url.replace(/"/g, '\\"') + '"]');
                    if (link) {
                        link.classList.add('content-hover-link');
                        link.setAttribute('data-preview-type', item.type);
                        link.setAttribute('data-preview-id', item.id);
                    }
                }, 0);
            })
            .catch(function () { alert('Content search failed.'); });
    }

    editors.forEach(function (textarea) {
        var form = textarea.closest('form');
        if (!form) return;

        var wrapper = document.createElement('div');
        wrapper.style.cssText = 'border:1px solid #dfe5ef;border-radius:1rem;background:#fff;';
        textarea.parentNode.insertBefore(wrapper, textarea);
        textarea.style.display = 'none';

        var quill = new Quill(wrapper, {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: [
                        [{ header: [2, 3, 4, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: [] }, { background: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        [{ align: [] }],
                        ['link', 'blockquote', 'image', 'video'],
                        ['ictInternalLink'],
                        ['clean']
                    ],
                    handlers: {
                        ictInternalLink: function () {
                            openInternalLinkDialog(quill);
                        }
                    }
                }
            }
        });

        textarea._seoQuill = quill;
        var internalButton = wrapper.parentNode.querySelector('.ql-ictInternalLink');
        if (internalButton) {
            internalButton.innerHTML = 'Link page';
            internalButton.setAttribute('type', 'button');
            internalButton.setAttribute('title', 'Link selected text to site content');
            internalButton.style.width = 'auto';
            internalButton.style.fontSize = '12px';
            internalButton.style.padding = '0 8px';
        }

        // Inject AI button into Quill toolbar if this textarea has data-ai-field
        var aiField = textarea.getAttribute('data-ai-field');
        var aiLabel = textarea.getAttribute('data-ai-label') || aiField;
        var aiContext = textarea.getAttribute('data-ai-context') || '';
        if (aiField) {
            var toolbar = wrapper.querySelector('.ql-toolbar');
            if (toolbar) {
                var aiBtn = document.createElement('button');
                aiBtn.type = 'button';
                aiBtn.className = 'ql-ict-ai-btn';
                aiBtn.title = 'Generate or improve this content with AI';
                aiBtn.textContent = '✦ AI';
                toolbar.appendChild(aiBtn);

                aiBtn.addEventListener('click', function () {
                    if (aiBtn.dataset.loading === '1') return;
                    aiBtn.dataset.loading = '1';
                    aiBtn.textContent = '…';
                    aiBtn.disabled = true;

                    var existing = quill.getText().trim();
                    var form = textarea.closest('form');
                    var csrfInput = form ? form.querySelector('input[name="_token"], input[name="_csrf_token"]') : null;
                    var pageType  = (form && form.getAttribute('data-ai-context')) || '';
                    var pageTitle = (form && form.getAttribute('data-ai-title')) || '';

                    var fd = new FormData();
                    fd.set('field',           aiField);
                    fd.set('page_type',       pageType);
                    fd.set('page_title',      pageTitle);
                    fd.set('field_label',     aiLabel);
                    fd.set('field_context',   aiContext);
                    fd.set('existing_content', existing);
                    if (csrfInput) fd.set('_token', csrfInput.value);

                    fetch(ictAiUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data.text) {
                                quill.clipboard.dangerouslyPasteHTML(data.text);
                                textarea.value = data.text;
                            } else if (data.error) {
                                console.warn('[AI] Error:', data.error);
                            }
                        })
                        .catch(function (err) {
                            console.warn('[AI] Request failed:', err);
                        })
                        .finally(function () {
                            aiBtn.dataset.loading = '0';
                            aiBtn.textContent = '✦ AI';
                            aiBtn.disabled = false;
                        });
                });
            }
        }

        var raw = textarea.value.trim();
        if (raw) {
            try {
                if (raw.charAt(0) === '<') {
                    quill.clipboard.dangerouslyPasteHTML(raw);
                } else {
                    quill.setText(raw);
                }
            } catch (err) {
                quill.setText(raw);
            }
        }

        form.addEventListener('submit', function () {
            textarea.value = quill.getSemanticHTML();
        });
    });
})();
</script>
<script>
(function () {
    var pickers = document.querySelectorAll('[data-media-picker]');
    if (!pickers.length) return;

    var modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'adminMediaPickerModal';
    modal.tabIndex = -1;
    modal.innerHTML = '<div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">'
        + '<div class="modal-header"><h5 class="modal-title">Choose media</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
        + '<div class="modal-body"><input type="search" class="form-control mb-3" placeholder="Search media..." data-media-search><div class="admin-media-grid" data-media-grid></div></div>'
        + '</div></div>';
    document.body.appendChild(modal);

    var activePicker = null;
    var bsModal = window.bootstrap ? new bootstrap.Modal(modal) : null;
    var grid = modal.querySelector('[data-media-grid]');
    var search = modal.querySelector('[data-media-search]');

    function renderItems(items) {
        grid.innerHTML = '';
        if (!items.length) {
            grid.innerHTML = '<div class="text-secondary">No media found.</div>';
            return;
        }
        items.forEach(function (item) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'admin-media-choice';
            button.innerHTML = (item.media_type === 'video'
                ? '<video src="' + item.url + '" muted preload="metadata"></video>'
                : '<img src="' + item.url + '" alt="">')
                + '<span>' + (item.name || item.path) + '</span>';
            button.addEventListener('click', function () {
                if (!activePicker) return;
                activePicker.querySelector('[data-media-asset-id]').value = item.id;
                if (['cover_image', 'gallery_image'].includes(activePicker.dataset.field)) {
                    var file = activePicker.querySelector('input[type=file]'); if (file) file.value = '';
                    var clear = activePicker.closest('form').querySelector('[name=cover_clear]'); if (clear) clear.checked = false;
                }
                var selected = activePicker.querySelector('[data-media-selected]');
                if (selected) selected.textContent = 'Selected: ' + item.name + ' (#' + item.id + ')';
                if (bsModal) bsModal.hide();
            });
            grid.appendChild(button);
        });
    }

    function loadItems() {
        if (!activePicker) return;
        var allowVideo = activePicker.getAttribute('data-allow-video') === '1' ? '1' : '0';
        var q = search.value || '';
        fetch(<?= json_encode(base_url('admin/media_picker_api.php')) ?> + '?allow_video=' + allowVideo + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (payload) { renderItems(payload.items || []); })
            .catch(function () { grid.innerHTML = '<div class="text-danger">Could not load media.</div>'; });
    }

    pickers.forEach(function (picker) {
        if (['cover_image', 'gallery_image'].includes(picker.dataset.field)) {
            var file = picker.querySelector('input[type=file]');
            if (file) file.addEventListener('change', function () {
                picker.querySelector('[data-media-asset-id]').value = '';
                picker.querySelector('[data-media-selected]').textContent = file.files[0] ? 'Selected: ' + file.files[0].name : 'No replacement selected';
                var clear = picker.closest('form').querySelector('[name=cover_clear]'); if (clear) clear.checked = false;
            });
            if (picker.dataset.field === 'cover_image') {
                var clearCover = picker.closest('form').querySelector('[name=cover_clear]');
                if (clearCover) clearCover.addEventListener('change', function () {
                    if (!clearCover.checked) return;
                    picker.querySelector('[data-media-asset-id]').value = ''; if (file) file.value = '';
                    picker.querySelector('[data-media-selected]').textContent = 'Cover will be cleared when saved';
                });
            }
        }
        var open = picker.querySelector('[data-media-open]');
        if (!open) return;
        open.addEventListener('click', function () {
            activePicker = picker;
            search.value = '';
            loadItems();
            if (bsModal) bsModal.show();
        });
    });

    var timer = null;
    search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(loadItems, 250);
    });
})();
</script>
<script>
(function () {
    var appUrl = <?= json_encode(rtrim(APP_URL, '/')) ?>;

    function slugify(value) {
        if (!value) return '';
        return String(value)
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function canonicalUrl(prefix, slug) {
        var cleanPrefix = (prefix || '').replace(/^\/+|\/+$/g, '');
        var cleanSlug = (slug || '').replace(/^\/+|\/+$/g, '');
        var path = '';

        if (cleanPrefix && cleanSlug) {
            path = cleanPrefix + '/' + cleanSlug;
        } else if (cleanPrefix) {
            path = cleanPrefix;
        } else if (cleanSlug) {
            path = cleanSlug;
        }

        return path ? (appUrl + '/' + path) : appUrl;
    }

    document.querySelectorAll('input[data-live-slug="1"]').forEach(function (slugInput) {
        var form = slugInput.closest('form');
        if (!form) return;

        var sourceName = slugInput.getAttribute('data-slug-source') || '';
        var sourceInput = sourceName ? form.querySelector('[name="' + sourceName + '"]') : null;
        if (!sourceInput) return;

        var canonicalFieldName = slugInput.getAttribute('data-canonical-input') || '';
        var canonicalInput = canonicalFieldName ? form.querySelector('[name="' + canonicalFieldName + '"]') : null;
        var canonicalPrefix = slugInput.getAttribute('data-canonical-prefix') || '';

        var initialSlug = slugInput.value.trim();
        var initialAutoSlug = slugify(sourceInput.value);
        var slugManual = initialSlug !== '' && initialSlug !== initialAutoSlug;

        var initialCanonical = canonicalInput ? canonicalInput.value.trim() : '';
        var initialAutoCanonical = canonicalUrl(canonicalPrefix, initialSlug || initialAutoSlug);
        var canonicalManual = canonicalInput ? (initialCanonical !== '' && initialCanonical !== initialAutoCanonical) : false;

        function syncSlugFromSource() {
            if (slugManual) return;
            var autoSlug = slugify(sourceInput.value);
            slugInput.value = autoSlug;
            syncCanonical(autoSlug);
        }

        function syncCanonical(currentSlug) {
            if (!canonicalInput || canonicalManual) return;
            canonicalInput.value = canonicalUrl(canonicalPrefix, currentSlug || slugInput.value);
        }

        if (initialSlug === '') {
            slugInput.value = initialAutoSlug;
        }
        syncCanonical(slugInput.value);

        sourceInput.addEventListener('input', function () {
            syncSlugFromSource();
        });

        slugInput.addEventListener('input', function () {
            var normalized = slugify(slugInput.value);
            if (slugInput.value !== normalized) {
                slugInput.value = normalized;
            }
            if (normalized === '') {
                slugManual = false;
                syncSlugFromSource();
                return;
            }
            slugManual = true;
            syncCanonical(normalized);
        });

        slugInput.addEventListener('blur', function () {
            if (slugInput.value.trim() === '') {
                slugManual = false;
                syncSlugFromSource();
            }
        });

        if (canonicalInput) {
            canonicalInput.addEventListener('input', function () {
                canonicalManual = canonicalInput.value.trim() !== '';
            });

            canonicalInput.addEventListener('blur', function () {
                if (canonicalInput.value.trim() === '') {
                    canonicalManual = false;
                    syncCanonical(slugInput.value);
                }
            });
        }
    });
})();
</script>
<script>
(function () {
    var selector = 'textarea[data-ai-field]:not(.quill-editor):not(.js-richtext), input[data-ai-field][type="text"]';
    var fields = document.querySelectorAll(selector);
    if (!fields.length) return;

    var contextForm = null;
    fields.forEach(function (el) {
        // Wrap input + button in a flex column group
        var group = document.createElement('div');
        group.className = 'ai-field-group';
        el.parentNode.insertBefore(group, el);
        group.appendChild(el);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ai-generate-btn';
        btn.innerHTML = '<span class="ai-spinner"></span><span class="ai-btn-label">'
            + (el.value.trim() ? '✦ AI Improve' : '✦ AI Write') + '</span>';
        btn.title = 'Generate or improve content with AI';
        group.appendChild(btn);

        // Update button label when field content changes
        el.addEventListener('input', function () {
            var labelEl = btn.querySelector('.ai-btn-label');
            if (labelEl) {
                labelEl.textContent = el.value.trim() ? '✦ AI Improve' : '✦ AI Write';
            }
        });

        if (!contextForm) {
            contextForm = el.closest('form[data-ai-context]');
        }

        btn.addEventListener('click', function () {
            if (btn.classList.contains('ai-loading')) return;
            btn.classList.add('ai-loading');

            var form = el.closest('form');
            var pageType = (form && form.getAttribute('data-ai-context')) || (contextForm && contextForm.getAttribute('data-ai-context')) || '';
            var pageTitle = (form && form.getAttribute('data-ai-title')) || '';
            var fieldName = el.getAttribute('data-ai-field') || el.name || '';
            var fieldLabel = el.getAttribute('data-ai-label') || '';
            var existing = el.value.trim();

            var fd = new FormData();
            fd.set('field', fieldName);
            fd.set('page_type', pageType);
            fd.set('page_title', pageTitle);
            fd.set('field_label', fieldLabel);
            fd.set('existing_content', existing);
            var fieldContext = el.getAttribute('data-ai-context') || '';
            fd.set('field_context', fieldContext);

            var csrfInput = form ? form.querySelector('input[name="_token"], input[name="_csrf_token"]') : null;
            if (csrfInput) fd.set('_token', csrfInput.value);

            fetch(ictAiUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.text) {
                        el.value = data.text;
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (data.error) {
                        console.warn('AI generate error:', data.error);
                    }
                })
                .catch(function (err) { console.warn('AI generate request failed:', err); })
                .finally(function () { btn.classList.remove('ai-loading'); });
        });
    });
})();
</script>
</body>
</html>
