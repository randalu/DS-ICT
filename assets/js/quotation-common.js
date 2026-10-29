'use strict';
window.Quote = (() => {
    const config = JSON.parse(document.getElementById('quote-config').textContent);
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const message = (text, error = false) => { const el = document.getElementById('quote-message'); if (el) { el.className = text ? `alert alert-${error ? 'danger' : 'success'}` : ''; el.textContent = text; } };
    const api = async (action, payload = {}) => {
        const response = await fetch(config.api, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action, csrf_token:config.csrf, ...payload})});
        let result; try { result = await response.json(); } catch { throw new Error('The server did not return a valid response. Your entries are retained.'); }
        if (!response.ok) { const error = new Error(result.error || 'The request failed.'); error.status = response.status; throw error; } return result;
    };
    document.getElementById('quote-create')?.addEventListener('click', async event => { event.target.disabled = true; try { const result = await api('create',{inquiry_id:config.initialInquiry||0}); location.assign(`${config.edit}?id=${result.id}`); } catch (e) { message(e.message,true); event.target.disabled=false; } });
    return {config, escape, message, api};
})();
