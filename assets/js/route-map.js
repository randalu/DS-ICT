(() => {
    'use strict';
    document.querySelectorAll('[data-map-enlarge]').forEach(button => { button.hidden = false; });
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-map-enlarge]');
        if (!button) return;
        const source = button.closest('.route-map');
        const dialog = document.createElement('dialog');
        dialog.className = 'route-map-dialog';
        dialog.setAttribute('aria-label', 'Enlarged journey map');
        const form = document.createElement('form');
        form.method = 'dialog';
        const close = document.createElement('button');
        close.type = 'submit'; close.textContent = 'Close map'; close.className = 'route-map-enlarge';
        form.append(close);
        const copy = source.cloneNode(true);
        copy.querySelector('[data-map-enlarge]')?.remove();
        copy.querySelectorAll('details').forEach(item => { item.open = true; });
        dialog.append(form, copy); document.body.append(dialog);
        dialog.addEventListener('close', () => { dialog.remove(); button.focus(); }, { once: true });
        dialog.addEventListener('click', e => { if (e.target === dialog) { const box = dialog.getBoundingClientRect(); if (e.clientX < box.left || e.clientX > box.right || e.clientY < box.top || e.clientY > box.bottom) dialog.close(); } });
        dialog.showModal();
    });
})();
