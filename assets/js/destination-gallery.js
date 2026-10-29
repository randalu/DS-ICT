(() => {
    'use strict';
    const gallery = document.querySelector('[data-gallery]');
    if (!gallery || !gallery.querySelector('[data-gallery-current]')) return;
    const manager = document.querySelector('[data-manager]');
    const current = gallery.querySelector('[data-gallery-current]');
    const queue = gallery.querySelector('[data-gallery-queue]');
    const queueItems = queue.querySelector('[data-queue-items]');
    const queueStatus = queue.querySelector('[data-queue-status]');
    const items = [];
    let busy = false, orderChanged = false, blocked = false, refreshing = false;
    const captionDirty = new Set();
    const changed = () => orderChanged || captionDirty.size > 0;
    const state = () => current.querySelector('[data-gallery-state]');
    const message = text => { current.querySelector('[data-gallery-status]').textContent = text; };
    const revision = () => state()?.dataset.revision || '';
    function data(action) {
        const body = new FormData();
        Object.entries({action, id: gallery.dataset.id, type: 'destination', _token: gallery.dataset.token, revision: revision()}).forEach(([k, v]) => body.set(k, v));
        return body;
    }
    function upload(body, progress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest(); xhr.open('POST', manager.dataset.api); xhr.timeout = 180000;
            xhr.upload.onprogress = event => { if (event.lengthComputable && progress) progress.value = Math.round(event.loaded / event.total * 100); };
            xhr.onload = () => {
                let result;
                try { result = JSON.parse(xhr.responseText); }
                catch { reject(Object.assign(new Error('Unexpected response: save outcome unknown. Review the destination Media Library before retrying.'), {uncertain: true})); return; }
                if (xhr.status !== 200) reject(Object.assign(new Error(result.error || 'Save failed.'), {conflict: xhr.status === 409, uncertain: xhr.status >= 500}));
                else resolve(result);
            };
            xhr.onerror = xhr.ontimeout = () => reject(Object.assign(new Error('Connection interrupted: save outcome unknown. Review the destination Media Library before retrying.'), {uncertain: true}));
            xhr.send(body);
        });
    }
    async function refresh(force = false) {
        if (refreshing) throw new Error('Gallery refresh already in progress.');
        if (!force && (changed() || window.ImageManager.hasDirty(current)) && !confirm('Discard unsaved gallery selections, captions and order, then refresh?')) return false;
        refreshing = true;
        const scroll = window.scrollY;
        try {
            const url = new URL(manager.dataset.api, location.href); url.search = new URLSearchParams({action: 'gallery', id: gallery.dataset.id});
            const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store'}); const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Could not refresh gallery.');
            window.ImageManager.discard(current);
            current.innerHTML = result.html;
            current.querySelectorAll('[data-card]').forEach(window.ImageManager.bind);
            window.scrollTo({top: scroll, behavior: 'instant'});
            orderChanged = false; captionDirty.clear(); blocked = false;
            return true;
        } finally { refreshing = false; }
    }
    current.addEventListener('input', event => { if (event.target.name === 'caption') captionDirty.add(event.target.closest('[data-card]')); });
    current.addEventListener('click', async event => {
        const button = event.target.closest('button'); if (!button || busy || refreshing) return;
        const photo = button.closest('[data-gallery-photo]');
        if (button.hasAttribute('data-move')) {
            const neighbor = button.dataset.move === 'up' ? photo.previousElementSibling : photo.nextElementSibling;
            if (neighbor) { if (button.dataset.move === 'up') neighbor.before(photo); else neighbor.after(photo); orderChanged = true; current.querySelector('[data-save-order]').disabled = false; }
            return;
        }
        if (button.hasAttribute('data-refresh-gallery')) {
            try { await refresh(); } catch (error) { message(error.message); } return;
        }
        let action;
        if (button.hasAttribute('data-caption-save')) action = 'gallery_caption';
        if (button.hasAttribute('data-remove-photo')) action = 'gallery_remove';
        if (button.hasAttribute('data-save-order')) action = 'gallery_order';
        if (!action) return;
        if (action === 'gallery_remove' && !confirm('Remove this photo from the public gallery? Its file and library asset will be retained.')) return;
        // A refresh after a write must not silently discard another card's work.
        const ownCard = photo?.querySelector('[data-card]');
        const otherUnsaved = [...captionDirty].some(card => card !== ownCard) || (orderChanged && action !== 'gallery_order') || window.ImageManager.hasDirty(current) || (action === 'gallery_order' && captionDirty.size > 0);
        if (otherUnsaved && !confirm('Save this change and refresh the gallery? Other unsaved gallery selections, captions and ordering will be discarded.')) return;
        const body = data(action);
        if (photo) { body.set('child_id', photo.dataset.galleryPhoto); body.set('caption', photo.querySelector('[name=caption]').value); }
        if (action === 'gallery_order') body.set('order', JSON.stringify([...current.querySelectorAll('[data-gallery-photo]')].map(node => Number(node.dataset.galleryPhoto))));
        busy = true; current.inert = true; button.disabled = true;
        try { await upload(body); await refresh(true); message('Gallery saved.'); }
        catch (error) {
            if (error.conflict) { try { await refresh(true); } catch {} }
            if (error.uncertain) blocked = true;
            message(error.message);
        } finally { busy = false; current.inert = false; button.disabled = false; }
    });
    document.addEventListener('gallery-saved', async event => {
        captionDirty.delete(event.detail.card);
        // Keep other unsaved cards until the editor explicitly refreshes.
        if (changed() || window.ImageManager.hasDirty(current)) { blocked = true; message('Photo saved. Refresh to see it; review other unsaved gallery selections first.'); return; }
        try { await refresh(true); message('Photo saved.'); }
        catch (error) { blocked = true; message(error.message); }
    });
    queue.querySelector('[data-queue-files]').addEventListener('change', event => {
        for (const file of event.target.files) {
            const row = document.createElement('div'); row.className = 'im-card';
            const label = document.createElement('p'); label.textContent = file.name;
            const image = document.createElement('img'); const url = URL.createObjectURL(file); image.src = url; image.alt = 'Selected photo preview'; image.className = 'im-queue-preview';
            const captionLabel = document.createElement('label'); captionLabel.textContent = 'Caption / alt text';
            const caption = document.createElement('input'); caption.maxLength = 300; caption.required = true; captionLabel.append(caption);
            const progress = document.createElement('progress'); progress.max = 100; progress.value = 0;
            const status = document.createElement('p'); status.setAttribute('role', 'status');
            const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Remove from queue';
            const requestId = Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
            const item = {file, row, caption, progress, status, url, requestId, done: false, uncertain: false, removed: false}; items.push(item);
            remove.addEventListener('click', () => { if (busy) return; item.removed = true; URL.revokeObjectURL(url); row.remove(); });
            row.append(label, image, captionLabel, progress, status, remove); queueItems.append(row);
            if (file.type.startsWith('video/') || file.size > Number(manager.dataset.imageLimit)) status.textContent = 'Unsupported or oversized image. Remove it and choose a supported image within the displayed limit.';
        }
        event.target.value = '';
    });
    queue.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || refreshing) return;
        if (blocked) { queueStatus.textContent = 'Review the destination Media Library and refresh before continuing. Uncertain items will not be resent.'; return; }
        busy = true; current.inert = true; queue.querySelector('[data-queue-files]').disabled = true; queue.querySelector('button[type=submit]').disabled = true;
        items.forEach(item => { item.caption.disabled = true; });
        try {
            for (const item of items.filter(item => !item.removed && !item.done && !item.uncertain)) {
                if (item.file.type.startsWith('video/') || item.file.size > Number(manager.dataset.imageLimit)) { item.status.textContent = 'Unsupported or oversized image. Remove it from the queue.'; continue; }
                const body = data('library_upload'); body.set('request_id', item.requestId); body.set('media', item.file); body.set('caption', item.caption.value);
                item.status.textContent = 'Uploading…';
                try {
                    await upload(body, item.progress); item.done = true; item.caption.disabled = true; item.status.textContent = 'Uploaded to destination Media Library. Not added to the public gallery.'; URL.revokeObjectURL(item.url);
                } catch (error) {
                    item.status.textContent = error.message;
                    if (error.uncertain || item.done) { item.uncertain = !item.done; blocked = true; break; }
                    if (error.conflict) { blocked = true; break; }
                }
            }
            queueStatus.textContent = blocked ? 'Queue paused. Review the destination Media Library, then refresh before continuing. Uncertain photos are never automatically resent.'
                : items.some(item => !item.removed && !item.done) ? 'Queue finished. Correct failed photos and use Upload selected photos to retry them.'
                    : 'Queue finished. All selected photos are in the destination Media Library. Choose photos separately to add to the public gallery.';
        } finally {
            busy = false; current.inert = false;
            items.forEach(item => { item.caption.disabled = item.done; });
            queue.querySelector('[data-queue-files]').disabled = false; queue.querySelector('button[type=submit]').disabled = false;
        }
    });
    window.addEventListener('beforeunload', event => {
        if (changed() || busy || items.some(item => !item.removed && !item.done)) { event.preventDefault(); event.returnValue = ''; }
    });
})();
