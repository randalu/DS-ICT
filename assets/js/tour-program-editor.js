(() => {
  'use strict';
  const root = document.querySelector('#tour-program-editor');
  if (!root) return;
  let initial;
  try { initial = JSON.parse(root.querySelector('[data-program-data]').textContent); } catch { return; }
  const status = root.querySelector('[data-program-status]');
  const fields = {
    days: [['day_start','First day','number'],['day_end','Last day','number'],['title','Day title'],['location','Locations'],['summary','Day details','textarea'],['highlights','Highlights — one per line','textarea'],['overnight','Overnight location'],['travel_time','Travel time / transfer note']],
    hotels: [['name','Hotel / stay name'],['location','Location'],['category','Category / style'],['description','Accommodation details','textarea'],['nights','Nights','number'],['room_type','Room type'],['board_basis','Meal basis'],['status','Plan status','select']],
    sections: [['title','Section title'],['body','Detailed information (HTML supported)','textarea']]
  };
  const rename = section => {
    const group = section.dataset.programGroup;
    Array.from(section.querySelectorAll('fieldset')).forEach((row, i) => {
      row.querySelector('legend').textContent = `${group === 'days' ? 'Day plan' : group === 'hotels' ? 'Stay' : 'Section'} ${i + 1}`;
      row.querySelectorAll('[data-field]').forEach(input => { input.name = `program[${group}][${i}][${input.dataset.field}]`; });
    });
  };
  const renumberDays = section => {
    if (section.dataset.programGroup !== 'days') return;
    let next = 1;
    for (const row of section.querySelectorAll('fieldset')) {
      const start = row.querySelector('[data-field="day_start"]');
      const end = row.querySelector('[data-field="day_end"]');
      const span = Math.max(1, Number(end.value) - Number(start.value) + 1);
      start.value = next; end.value = next + span - 1; next += span;
    }
  };
  const chooseImage = async (row) => {
    const dialog = document.createElement('dialog'); dialog.className = 'program-library-dialog';
    const close = document.createElement('button'); close.type = 'button'; close.textContent = 'Close'; close.className = 'btn btn-outline-dark'; close.onclick = () => dialog.close(); dialog.append(close);
    const search = document.createElement('input'); search.placeholder = 'Search image library'; search.setAttribute('aria-label', 'Search image library'); search.className = 'form-control my-3'; dialog.append(search);
    const list = document.createElement('div'); list.className = 'program-library-grid'; dialog.append(list);
    let page = 1;
    const more = document.createElement('button'); more.type = 'button'; more.textContent = 'Load more photographs'; more.className = 'btn btn-outline-dark mt-3'; more.hidden = true; dialog.append(more);
    const load = async (append = false) => {
      if (!append) { page = 1; list.textContent = 'Loading photographs…'; }
      more.disabled = true;
      try {
        const url = new URL(root.dataset.libraryUrl, location.href); url.searchParams.set('q', search.value); url.searchParams.set('video', '0'); url.searchParams.set('page', String(page));
        const response = await fetch(url, {credentials:'same-origin'}); const data = await response.json();
        if (!response.ok || data.ok === false) throw new Error(data.error || 'Cannot load the image library.');
        if (!append) list.textContent = '';
        for (const asset of (data.assets || data.items || [])) {
          if (asset.media_type !== 'image') continue;
          const button = document.createElement('button'); button.type = 'button'; button.className = 'program-library-choice';
          const image = document.createElement('img'); image.src = asset.url; image.alt = asset.caption || asset.name || 'Library photograph'; image.loading = 'lazy';
          const label = document.createElement('span'); label.textContent = asset.caption || asset.name || `Image ${asset.id}`; button.append(image,label);
          button.onclick = () => { row.querySelector('[data-field="image_asset_id"]').value = asset.id; if (!row.querySelector('[data-field="image_alt"]').value) row.querySelector('[data-field="image_alt"]').value = asset.caption || ''; row.querySelector('[data-image-label]').textContent = `Selected image ${asset.id}`; dialog.close(); };
          list.append(button);
        }
        if (!list.children.length) list.textContent = 'No matching photographs.';
        more.hidden = !data.more;
      } catch(error) { list.textContent = error.message; }
      finally { more.disabled = false; }
    };
    more.onclick = () => { page += 1; load(true); };
    search.addEventListener('change',()=>load(false)); dialog.addEventListener('close',()=>dialog.remove()); document.body.append(dialog); dialog.showModal(); await load();
  };
  for (const section of root.querySelectorAll('[data-program-group]')) {
    const group = section.dataset.programGroup; const list = section.querySelector('[data-program-rows]');
    const add = (data={}) => {
      const row = document.createElement('fieldset'); row.className = 'program-edit-row'; row.append(document.createElement('legend'));
      const actions = document.createElement('div'); actions.className = 'd-flex gap-2 mb-3 flex-wrap';
      for (const [text, action] of [['Move up',()=>row.previousElementSibling?.before(row)],['Move down',()=>row.nextElementSibling?.after(row)],['Remove row',()=>row.remove()]]) {
        const b = document.createElement('button'); b.type = 'button'; b.textContent = text; b.className = 'btn btn-outline-dark btn-sm'; b.onclick = () => { if ((text === 'Move up' && !row.previousElementSibling) || (text === 'Move down' && !row.nextElementSibling)) return; action(); renumberDays(section); rename(section); }; actions.append(b);
      } row.append(actions);
      for (const [key,label,type='text'] of fields[group]) {
        const wrapper = document.createElement('label'); wrapper.className = 'form-label d-block'; wrapper.textContent = label;
        const input = document.createElement(type === 'textarea' ? 'textarea' : type === 'select' ? 'select' : 'input'); input.className = 'form-control'; input.dataset.field = key;
        if (input.tagName === 'INPUT') input.type = type;
        if (type === 'select') { for (const value of ['proposed','optional']) { const o = document.createElement('option'); o.value = value; o.textContent = value; input.append(o); } }
        if (type === 'number') { input.min = key === 'nights' ? '0' : '1'; input.max = '366'; }
        if (type === 'textarea') input.rows = key === 'body' ? 6 : 3;
        if (['title','name','location'].includes(key)) input.required = true;
        const nextDay = Number(list.lastElementChild?.querySelector('[data-field="day_end"]')?.value || 0) + 1;
        input.value = Array.isArray(data[key]) ? data[key].join('\n') : data[key] ?? (key === 'day_start' || key === 'day_end' ? nextDay : key === 'nights' ? 0 : key === 'status' ? 'proposed' : '');
        wrapper.append(input); row.append(wrapper);
      }
      if (group !== 'sections') {
        const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.dataset.field = 'image_asset_id'; hidden.value = data.image_asset_id || 0; row.append(hidden);
        const pick = document.createElement('button'); pick.type = 'button'; pick.textContent = 'Choose library photograph'; pick.className = 'btn btn-outline-dark'; pick.onclick = () => chooseImage(row); row.append(pick);
        const clear = document.createElement('button'); clear.type = 'button'; clear.textContent = 'Clear photograph'; clear.className = 'btn btn-outline-secondary ms-2'; clear.onclick = () => {hidden.value = 0; row.querySelector('[data-image-label]').textContent = 'No photograph selected';}; row.append(clear);
        const label = document.createElement('span'); label.dataset.imageLabel = ''; label.textContent = data.image_asset_id ? `Selected image ${data.image_asset_id}` : 'No photograph selected'; label.className = 'small ms-2'; row.append(label);
        const altLabel = document.createElement('label'); altLabel.textContent = 'Photograph description / alt text'; altLabel.className = 'form-label d-block mt-2';
        const alt = document.createElement('input'); alt.className = 'form-control'; alt.dataset.field = 'image_alt'; alt.maxLength = 255; alt.value = data.image_alt || ''; altLabel.append(alt); row.append(altLabel);
      }
      list.append(row); rename(section);
    };
    for (const row of (initial[group] || [])) add(row);
    const button = section.querySelector('[data-program-add]'); button.hidden = false; button.onclick = () => add();
  }
  const ready = document.createElement('input'); ready.type='hidden'; ready.name='program_ready'; ready.value='1'; root.append(ready);
  status.textContent = 'Route, days, stays and detailed sections are editable below. Save to update this Tour only.';
})();
