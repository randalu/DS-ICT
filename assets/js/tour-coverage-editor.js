(() => {
    'use strict';
    const editor = document.getElementById('tour-coverage-editor');
    if (!editor) return;
    const list = editor.querySelector('[data-coverage-items]');
    if (!list) return;
    const template = editor.querySelector('[data-coverage-template]');
    const add = editor.querySelector('[data-coverage-add]');
    const status = editor.querySelector('[data-coverage-status]');
    const dialog = document.querySelector('[data-coverage-library]');
    const grid = dialog.querySelector('[data-library-grid]');
    const search = dialog.querySelector('[data-library-search]');
    const libraryStatus = dialog.querySelector('[data-library-status]');
    const prev = dialog.querySelector('[data-library-prev]');
    const next = dialog.querySelector('[data-library-next]');
    let activeCard = null, page = 1, more = false, request = 0;

    function renumber() {
        const cards = [...list.children];
        cards.forEach((card, i) => {
            card.querySelector('[data-coverage-number]').textContent = String(i + 1);
            card.querySelector('[data-coverage-up]').disabled = i === 0;
            card.querySelector('[data-coverage-down]').disabled = i === cards.length - 1;
            card.querySelectorAll('[data-field]').forEach(input => {
                input.name = `coverage_items[${i}][${input.dataset.field}]`;
            });
        });
        add.disabled = cards.length >= 40;
    }
    function imageState(card) {
        const id = card.querySelector('[data-field="image_asset_id"]').value;
        card.querySelector('[data-coverage-image-label]').textContent = id && id !== '0' ? `Library image #${id}` : 'No image selected';
        card.querySelector('[data-field="image_alt"]').required = !!id && id !== '0';
    }
    function createCard(values = {}) {
        const card = template.content.firstElementChild.cloneNode(true);
        card.querySelectorAll('[data-field]').forEach(input => {
            if (values[input.dataset.field] !== undefined) input.value = String(values[input.dataset.field]);
        });
        card.querySelector('[data-coverage-up]').addEventListener('click', () => {
            if (card.previousElementSibling) list.insertBefore(card, card.previousElementSibling);
            renumber(); status.textContent = 'Coverage order updated.'; card.querySelector('[data-field="title"]').focus();
        });
        card.querySelector('[data-coverage-down]').addEventListener('click', () => {
            if (card.nextElementSibling) list.insertBefore(card.nextElementSibling, card);
            renumber(); status.textContent = 'Coverage order updated.'; card.querySelector('[data-field="title"]').focus();
        });
        card.querySelector('[data-coverage-remove]').addEventListener('click', () => {
            const focus = card.nextElementSibling || card.previousElementSibling;
            card.remove(); renumber(); status.textContent = 'Card removed. Save the tour to apply this change.';
            (focus?.querySelector('[data-field="title"]') || add).focus();
        });
        card.querySelector('[data-coverage-image]').addEventListener('click', () => {
            activeCard = card; page = 1; search.value = ''; dialog.showModal(); loadLibrary();
        });
        card.querySelector('[data-coverage-clear]').addEventListener('click', () => {
            card.querySelector('[data-field="image_asset_id"]').value = ''; imageState(card);
        });
        list.append(card); imageState(card); renumber(); return card;
    }
    async function loadLibrary() {
        const ticket = ++request;
        grid.replaceChildren(); libraryStatus.textContent = 'Loading images…'; prev.disabled = next.disabled = true;
        try {
            const response = await fetch(`${editor.dataset.libraryUrl}&q=${encodeURIComponent(search.value)}&page=${page}`, {credentials:'same-origin'});
            const data = await response.json();
            if (ticket !== request) return;
            if (!response.ok) throw new Error(data.error || 'Could not load images.');
            more = !!data.more;
            for (const item of data.items || []) {
                const button = document.createElement('button'); button.type = 'button';
                const img = document.createElement('img'); img.src = item.url; img.alt = ''; img.loading = 'lazy';
                const name = document.createElement('span'); name.textContent = item.name || `Image ${item.id}`;
                button.append(img, name);
                button.addEventListener('click', () => {
                    if (!activeCard?.isConnected) return;
                    activeCard.querySelector('[data-field="image_asset_id"]').value = item.id;
                    const alt = activeCard.querySelector('[data-field="image_alt"]');
                    if (!alt.value) alt.value = item.caption || '';
                    imageState(activeCard); dialog.close(); activeCard.querySelector('[data-coverage-image]').focus();
                });
                grid.append(button);
            }
            libraryStatus.textContent = grid.children.length ? `Page ${page}` : 'No images found. Try a different search.';
            prev.disabled = page <= 1; next.disabled = !more;
        } catch (error) {
            if (ticket === request) libraryStatus.textContent = error.message || 'Could not load images. Try again.';
        }
    }
    dialog.querySelector('[data-library-close]').addEventListener('click', () => dialog.close());
    prev.addEventListener('click', () => { if (page > 1) { page--; loadLibrary(); } });
    next.addEventListener('click', () => { if (more) { page++; loadLibrary(); } });
    let timer;
    search.addEventListener('input', () => { clearTimeout(timer); request++; timer = setTimeout(() => {page = 1; loadLibrary();}, 250); });
    add.addEventListener('click', () => { if (list.children.length < 40) createCard().querySelector('[data-field="title"]').focus(); });
    editor.querySelector('[data-coverage-reload]')?.addEventListener('click', () => {
        const saved = JSON.parse(editor.querySelector('[data-coverage-saved]').textContent);
        list.replaceChildren(); saved.forEach(createCard);
        editor.querySelector('[name="coverage_revision"]').value = editor.dataset.currentRevision;
        editor.querySelector('[data-coverage-stale-warning]').hidden = true;
        renumber(); status.textContent = 'Saved coverage loaded. Review these cards before saving.';
        (list.querySelector('[data-field="title"]') || add).focus();
    });
    try {
        const rows = JSON.parse(editor.querySelector('[data-coverage-initial]').textContent);
        rows.forEach(createCard);
        // Set only after initialization, so a failed/no-JS editor cannot erase saved cards.
        const ready = document.createElement('input'); ready.type = 'hidden'; ready.name = 'coverage_ready'; ready.value = '1'; list.append(ready);
        // Keep the readiness marker outside the reorderable list.
        editor.append(ready); add.hidden = false; renumber();
    } catch (_) {
        list.replaceChildren(); status.textContent = 'Coverage could not be loaded. Reload before editing coverage; other fields can still be saved.';
    }
})();
