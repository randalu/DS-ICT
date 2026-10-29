(() => {
    'use strict';
    document.querySelectorAll('[data-map-collection]').forEach(select => {
        select.addEventListener('change', () => { select.form.querySelector('[name=id]').value = '0'; select.form.requestSubmit(); });
    });
    document.querySelectorAll('[data-route-map-form]').forEach(form => {
        const shell = form.closest('.route-map-editor');
        const preview = shell.querySelector('[data-map-preview]');
        const status = form.querySelector('[data-map-status]');
        let timer, request, sequence = 0;
        function updateButtons() {
            form.querySelectorAll('[data-map-day]').forEach(day => {
                const rows = [...day.querySelectorAll('.route-map-stop-row')];
                rows.forEach((row, index) => {
                    row.querySelector('select').setAttribute('aria-label', `Day ${day.dataset.mapDay} stop ${index + 1}`);
                    row.querySelector('.route-map-stop-tools').hidden = false;
                    row.querySelector('[data-map-up]').disabled = index === 0;
                    row.querySelector('[data-map-down]').disabled = index === rows.length - 1;
                });
                const add = day.querySelector('[data-map-add]'); add.hidden = false; add.disabled = rows.length >= 8;
            });
        }
        async function draw() {
            const current = ++sequence;
            request?.abort(); request = new AbortController();
            status.textContent = 'Updating unsaved map preview…';
            try {
                const response = await fetch(form.dataset.previewUrl, { method: 'POST', credentials: 'same-origin', body: new FormData(form), signal: request.signal, headers: { Accept: 'application/json' } });
                if (response.redirected || !response.headers.get('Content-Type')?.includes('application/json')) throw new Error('Your admin session may have expired. Save your entries and sign in again.');
                const result = await response.json();
                if (current !== sequence) return;
                if (!response.ok || typeof result.html !== 'string') throw new Error(result.error || 'Map preview is unavailable.');
                preview.innerHTML = result.html;
                preview.querySelectorAll('[data-map-enlarge]').forEach(button => { button.hidden = false; });
                status.textContent = 'Unsaved preview updated. Use Save journey map to store your changes.';
            } catch (error) {
                if (error.name !== 'AbortError' && current === sequence) status.textContent = `${error.message} Your selected stops are retained.`;
            }
        }
        function changed() { clearTimeout(timer); timer = setTimeout(draw, 160); updateButtons(); }
        form.addEventListener('change', changed);
        form.addEventListener('click', event => {
            const day = event.target.closest('[data-map-day]'); if (!day) return;
            const holder = day.querySelector('[data-map-stops]');
            const row = event.target.closest('.route-map-stop-row');
            if (event.target.closest('[data-map-add]')) {
                if (holder.children.length >= 8) return;
                const copy = holder.firstElementChild.cloneNode(true); copy.querySelector('select').value = '';
                holder.append(copy); copy.querySelector('select').focus(); changed();
            } else if (event.target.closest('[data-map-remove]')) {
                if (holder.children.length === 1) row.querySelector('select').value = ''; else row.remove();
                holder.querySelector('select').focus(); changed();
            } else if (event.target.closest('[data-map-up]') && row.previousElementSibling) {
                holder.insertBefore(row, row.previousElementSibling); changed(); row.querySelector('[data-map-up]').focus();
            } else if (event.target.closest('[data-map-down]') && row.nextElementSibling) {
                holder.insertBefore(row.nextElementSibling, row); changed(); row.querySelector('[data-map-down]').focus();
            }
        });
        updateButtons();
        if (shell.querySelector('.alert-info')?.textContent.includes('entries were retained')) changed();
    });
    document.querySelectorAll('[data-map-location-form]').forEach(form => {
        const select = form.querySelector('[data-map-suggestion]');
        if (!select) return;
        const places = JSON.parse(form.querySelector('[data-map-suggestions]').textContent);
        select.addEventListener('change', () => {
            if (select.value === '') return;
            const place = places[Number(select.value)]; if (!place) return;
            for (const [name, value] of Object.entries({ name: place.name, latitude: place.latitude, longitude: place.longitude, coordinate_source: place.source })) form.elements.namedItem(name).value = value;
            form.elements.namedItem('is_verified').checked = false;
        });
    });
    document.querySelectorAll('[data-map-design-form]').forEach(form => {
        form.querySelector('[name=route_color]').addEventListener('input', event => {
            form.parentElement.querySelectorAll('.route-map').forEach(map => map.style.setProperty('--route-map-color', event.target.value));
        });
    });
})();
