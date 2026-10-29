(() => {
    'use strict';
    const manager = document.querySelector('[data-manager]');
    if (!manager) return;
    const dialog = document.querySelector('[data-library-dialog]');
    const dirty = new Set();
    const objectUrls = new WeakMap();
    let libraryCard = null, libraryPage = 1, libraryRequest = 0, libraryController, libraryDestination = '';
    const field = (card, name) => card.elements.namedItem(name);
    const status = (card, message, kind = '') => {
        const node = card.querySelector('[data-status]');
        node.textContent = message;
        delete node.dataset.error; delete node.dataset.success;
        if (kind) node.dataset[kind] = '';
    };
    const revoke = card => {
        if (objectUrls.has(card)) { URL.revokeObjectURL(objectUrls.get(card)); objectUrls.delete(card); }
    };
    function mediaNode(url, video, poster = '', alt = '') {
        const node = document.createElement(video ? 'video' : 'img');
        node.src = url;
        if (video) { node.controls = true; node.preload = 'none'; if (poster) node.poster = poster; }
        else { node.alt = alt; node.loading = 'lazy'; }
        return node;
    }
    function reset(card) {
        revoke(card); dirty.delete(card);
        if (field(card, 'caption') && card.dataset.originalCaption !== undefined) field(card, 'caption').value = card.dataset.originalCaption;
        field(card, 'media').value = ''; field(card, 'asset_id').value = ''; field(card, 'source').value = '';
        card.querySelector('[data-pending]').replaceChildren(); card.querySelector('[data-pending]').hidden = true;
        card.querySelector('[data-save]').disabled = true; card.querySelector('[data-cancel]').disabled = true;
        card.dispatchEvent(new Event('image-alt-selection',{bubbles:true}));
    }
    function selected(card, url, video, name) {
        const box = card.querySelector('[data-pending]');
        const label = document.createElement('p'); label.textContent = 'Selected: ' + name;
        box.replaceChildren(label, mediaNode(url, video)); box.hidden = false;
        dirty.add(card); card.querySelector('[data-save]').disabled = false; card.querySelector('[data-cancel]').disabled = false;
        status(card, 'Replacement ready. Save this image to apply it.');
    }
    function showCurrent(card, saved) {
        const altControls=card.querySelector('[data-image-alt]');
        if(altControls){altControls.dataset.savedAsset=String(saved.asset_id||0);}
        field(card, 'revision').value = saved.revision;
        const preview = card.querySelector('[data-current]');
        if (saved.url) preview.replaceChildren(mediaNode(saved.url, saved.media_type === 'video', saved.poster_url || '', saved.caption || card.querySelector('h2').textContent));
        else { const text = document.createElement('span'); text.textContent = 'No image assigned'; preview.replaceChildren(text); }
        if (field(card, 'caption')) { field(card, 'caption').value = saved.caption; card.dataset.originalCaption = saved.caption; }
        card.dispatchEvent(new Event('image-alt-selection',{bubbles:true}));
    }
    function syncPoster(hero, saved) {
        const poster = document.querySelector('[data-card][data-slot="hero_video_poster"]');
        if (!poster || field(poster, 'id').value !== field(hero, 'id').value) return;
        reset(poster); poster.querySelector('fieldset').disabled = !saved.poster;
        poster.dataset.inactive = saved.poster ? '0' : '1';
        if (saved.poster) showCurrent(poster, saved.poster);
        else {
            field(poster, 'revision').value = '';
            const text = document.createElement('span'); text.textContent = 'Choose and save a video hero to enable its poster.';
            poster.querySelector('[data-current]').replaceChildren(text);
        }
        status(poster, saved.poster ? 'Hero changed. Choose a poster for this video.' : '');
    }
    function bind(card) {
        if (field(card, 'caption')) card.dataset.originalCaption = field(card, 'caption').value;
        field(card, 'media').addEventListener('change', () => {
            const file = field(card, 'media').files[0];
            revoke(card); field(card, 'asset_id').value = ''; field(card, 'source').value = '';
            if (!file) { reset(card); return; }
            const video = file.type.startsWith('video/');
            if ((video && card.dataset.video !== '1') || file.size > Number(video ? manager.dataset.videoLimit : manager.dataset.imageLimit)) {
                reset(card); status(card, 'This file exceeds the displayed limit or video is not allowed here.', 'error'); return;
            }
            const url = URL.createObjectURL(file); objectUrls.set(card, url);
            field(card, 'source').value = 'upload'; selected(card, url, video, file.name);
        });
        card.querySelector('[data-cancel]').addEventListener('click', () => { reset(card); status(card, 'Selection cancelled. Current image unchanged.'); });
        card.querySelector('[data-library]').addEventListener('click', () => {
            const gallery = card.closest('[data-gallery]');
            dialog.querySelector('[data-destination-filter-label]').hidden = !!gallery;
            dialog.querySelector('[data-destination-filter]').value = gallery ? field(card, 'id').value : libraryDestination;
            libraryCard = card; libraryPage = 1; dialog.querySelector('input').value = ''; dialog.showModal(); loadLibrary();
        });
        if (field(card, 'caption')) field(card, 'caption').addEventListener('input', () => {
            if (!field(card, 'source').value) field(card, 'source').value = 'caption';
            dirty.add(card); card.querySelector('[data-save]').disabled = false; card.querySelector('[data-cancel]').disabled = false;
        });
        card.addEventListener('submit', event => {
            event.preventDefault();
            if (card.dataset.busy === '1' || !field(card, 'source').value) return;
            const data = new FormData(card);
            card.dataset.busy = '1'; card.querySelector('fieldset').disabled = true;
            const progress = card.querySelector('progress'); progress.value = 0; progress.hidden = false;
            status(card, 'Uploading…');
            const xhr = new XMLHttpRequest(); xhr.open('POST', card.action); xhr.timeout = 180000;
            xhr.upload.onprogress = e => { if (e.lengthComputable) progress.value = Math.round(e.loaded * 100 / e.total); if (progress.value === 100) status(card, 'Processing and saving…'); };
            const finish = () => { card.dataset.busy = '0'; card.querySelector('fieldset').disabled = false; progress.hidden = true; };
            xhr.onload = () => {
                finish();
                let result; try { result = JSON.parse(xhr.responseText); } catch { status(card, 'Unexpected response. Please retry, or reload if the session expired.', 'error'); return; }
                if (xhr.status !== 200 || !result.saved) {
                    if (xhr.status === 409) {
                        reset(card);
                        if (result.latest) showCurrent(card, result.latest);
                        if (card.dataset.slot === 'hero_image') {
                            const poster = document.querySelector('[data-card][data-slot="hero_video_poster"]');
                            if (poster) { reset(poster); poster.querySelector('fieldset').disabled = true; status(poster, 'Reload to review the updated hero and poster.'); }
                        }
                        if (card.dataset.slot === 'hero_video_poster' && !result.latest) {
                            card.querySelector('fieldset').disabled = true;
                        }
                    }
                    status(card, result.error || 'Save failed. Please retry.', 'error');
                    if (result.login) {
                        const link = document.createElement('a'); link.href = result.login; link.textContent = ' Sign in again';
                        card.querySelector('[data-status]').append(link);
                    }
                    return;
                }
                const saved = result.saved;
                if (card.closest('[data-gallery]')) {
                    reset(card); status(card, 'Saved successfully.', 'success');
                    document.dispatchEvent(new CustomEvent('gallery-saved', {detail: {card}}));
                    return;
                }
                if (card.dataset.slot === 'gallery_add') {
                    const added = card.cloneNode(true);
                    added.dataset.slot = 'gallery'; field(added, 'slot').value = 'gallery'; field(added, 'child_id').value = saved.child_id;
                    added.querySelector('h2').textContent = 'Gallery photo';
                    added.dataset.busy = '0'; added.querySelector('progress').hidden = true;
                    showCurrent(added, saved); reset(added); bind(added); card.before(added); status(added, 'Gallery photo added.', 'success');
                    field(card, 'revision').value = saved.add_revision; field(card, 'caption').value = '';
                } else {
                    showCurrent(card, saved);
                    if (card.dataset.slot === 'hero_image') syncPoster(card, saved);
                }
                const addCard = document.querySelector('[data-card][data-slot="gallery_add"]');
                if (addCard && saved.add_revision) field(addCard, 'revision').value = saved.add_revision;
                if (card.dataset.slot === 'hero_video_poster') {
                    const hero = document.querySelector('[data-card][data-slot="hero_image"]');
                    if (hero && saved.hero) showCurrent(hero, saved.hero);
                }
                reset(card); status(card, 'Saved successfully.', 'success');
            };
            xhr.onerror = xhr.ontimeout = () => {
                finish();
                if (card.closest('[data-gallery]')) {
                    reset(card);
                    status(card, 'Save outcome unknown. Refresh the gallery and review it before choosing this photo again.', 'error');
                } else status(card, 'Connection interrupted. Retry this image. If it already saved, the latest image will be shown.', 'error');
            };
            xhr.send(data);
        });
    }
    async function loadLibrary() {
        const request = ++libraryRequest;
        if (libraryController) libraryController.abort(); libraryController = new AbortController();
        const state = dialog.querySelector('[data-library-status]'); state.textContent = 'Loading media…';
        dialog.querySelector('[data-library-items]').replaceChildren();
        dialog.querySelector('[data-prev]').disabled = true; dialog.querySelector('[data-next]').disabled = true;
        const url = new URL(manager.dataset.api, location.href);
        url.search = new URLSearchParams({ action: 'library', q: dialog.querySelector('input').value, page: libraryPage, video: libraryCard.dataset.video });
        if (libraryCard.closest('[data-gallery]')) {
            url.searchParams.set('destination_id', field(libraryCard, 'id').value);
            url.searchParams.set('include_unassigned', '1');
        } else if (dialog.querySelector('[data-destination-filter]').value) {
            url.searchParams.set('destination_id', dialog.querySelector('[data-destination-filter]').value);
        }
        try {
            const response = await fetch(url, { credentials: 'same-origin', signal: libraryController.signal });
            const data = await response.json(); if (request !== libraryRequest) return;
            if (!response.ok) throw new Error(data.error || 'Media could not be loaded.');
            state.textContent = data.items.length ? 'Select a file to preview it before saving.' : 'No matching media.';
            dialog.querySelector('[data-library-page]').textContent = 'Page ' + libraryPage;
            dialog.querySelector('[data-prev]').disabled = libraryPage === 1; dialog.querySelector('[data-next]').disabled = !data.more;
            for (const item of data.items) {
                const button = document.createElement('button'); button.type = 'button';
                const label = document.createElement('span'); label.textContent = item.name;
                button.append(mediaNode(item.url, item.media_type === 'video'), label);
                button.addEventListener('click', () => {
                    const card = libraryCard, caption = field(card, 'caption')?.value;
                    reset(card);
                    if(caption!==undefined)field(card,'caption').value=caption;
                    field(card, 'asset_id').value = item.id; field(card, 'source').value = 'library';
                    if (field(card, 'caption') && !field(card, 'caption').value) field(card, 'caption').value = item.caption || '';
                    selected(card, item.url, item.media_type === 'video', item.name); card.dispatchEvent(new Event('image-alt-selection',{bubbles:true})); dialog.close();
                });
                dialog.querySelector('[data-library-items]').append(button);
            }
        } catch (error) { if (error.name !== 'AbortError' && request === libraryRequest) state.textContent = error.message; }
    }
    document.querySelectorAll('[data-card]').forEach(bind);
    window.ImageManager = {
        bind,
        hasDirty: root => [...dirty].some(card => root.contains(card)),
        discard: root => root.querySelectorAll('[data-card]').forEach(reset)
    };
    dialog.querySelector('[data-close]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-destination-filter]').addEventListener('change', () => {
        libraryDestination = dialog.querySelector('[data-destination-filter]').value;
        libraryPage = 1; loadLibrary();
    });
    dialog.querySelector('[data-library-search]').addEventListener('submit', event => { event.preventDefault(); libraryPage = 1; loadLibrary(); });
    dialog.querySelector('[data-prev]').addEventListener('click', () => { libraryPage--; loadLibrary(); });
    dialog.querySelector('[data-next]').addEventListener('click', () => { libraryPage++; loadLibrary(); });
    window.addEventListener('beforeunload', event => { if (dirty.size) { event.preventDefault(); event.returnValue = ''; } });
})();
