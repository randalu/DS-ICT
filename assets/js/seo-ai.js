/* Shared preview component: provider responses are always rendered as text. */
(function () {
    'use strict';
    async function request(api, token, action, payload, extras) {
        const body = new FormData(); body.set('_token', token); body.set('action', action);
        body.set('payload', JSON.stringify(payload || {}));
        Object.entries(extras || {}).forEach(([key, value]) => body.set(key, value));
        const response = await fetch(api, { method: 'POST', body, credentials: 'same-origin' });
        let data;
        try { data = await response.json(); } catch (_) { throw new Error('The server returned an unexpected response. Review before retrying.'); }
        if (!response.ok || data.error) { throw new Error(data.error || 'Request failed.'); }
        return data;
    }
    function token() {
        if (crypto.randomUUID) return crypto.randomUUID();
        return Array.from(crypto.getRandomValues(new Uint8Array(20)), x => x.toString(16).padStart(2, '0')).join('');
    }
    function preview(current, suggestion, regenerate) {
        return new Promise(resolve => {
            const dialog = document.createElement('dialog'); dialog.className = 'seo-dialog';
            const heading = document.createElement('h2'); heading.textContent = 'Review AI suggestion'; dialog.append(heading);
            const oldLabel = document.createElement('h3'); oldLabel.textContent = 'Current text'; dialog.append(oldLabel);
            const before = document.createElement('pre'); before.textContent = current; dialog.append(before);
            const label = document.createElement('label'); label.textContent = 'Suggested replacement — edit before accepting';
            const edit = document.createElement('textarea'); edit.value = suggestion.replacement; label.append(edit); dialog.append(label);
            const details = document.createElement('pre');
            details.textContent = [suggestion.explanation, 'Intent: ' + suggestion.intent, 'Keywords: ' + (suggestion.keywords || []).join(', '),
                ...(suggestion.warnings || []), 'Model: ' + suggestion.model + ' · Policy: ' + suggestion.policy_version].join('\n\n'); dialog.append(details);
            (suggestion.sources || []).forEach(source => {
                try {
                    const url = new URL(source.url); if (url.protocol !== 'https:') return;
                    const p = document.createElement('p'), a = document.createElement('a'); a.href = url.href; a.textContent = source.title || url.href; a.target = '_blank'; a.rel = 'noopener noreferrer'; p.append(a); dialog.append(p);
                } catch (_) { /* Invalid citations are not links. */ }
            });
            function finish(value) { dialog.close(); dialog.remove(); resolve(value); }
            [['Accept suggestion', () => finish({ value: edit.value, suggestion_id: suggestion.suggestion_id })], ['Regenerate', async () => {
                const buttons = dialog.querySelectorAll('button'); buttons.forEach(button => { button.disabled = true; });
                try { const next = await regenerate(); finish({ regenerate: next }); }
                catch (error) { details.textContent = error.message; buttons.forEach(button => { button.disabled = false; }); }
            }], ['Discard', () => finish(null)]].forEach(([text, callback]) => {
                const button = document.createElement('button'); button.type = 'button'; button.textContent = text; button.addEventListener('click', callback); dialog.append(button);
            });
            dialog.addEventListener('cancel', event => { event.preventDefault(); finish(null); });
            document.body.append(dialog); dialog.showModal();
        });
    }
    async function choose(current, suggestion, regenerate) {
        let next = suggestion;
        for (;;) {
            const decision = await preview(current, next, regenerate);
            if (!decision || !decision.regenerate) return decision;
            next = decision.regenerate;
        }
    }
    window.SeoAI = { request, token, choose };

    // This script runs before legacy admin AI initialisation, preventing duplicate buttons.
    const config = document.getElementById('seo-form-config');
    if (!config) return;
    const supported = config.dataset.type === 'blog' ? ['meta_title', 'meta_description', 'excerpt', 'title', 'content'] : ['meta_title', 'meta_description', 'excerpt', 'intro', 'faq_json'];
    document.querySelectorAll('[data-ai-field]').forEach(field => {
        if (supported.includes(field.name) || field.name === 'name') field.removeAttribute('data-ai-field');
    });
    document.addEventListener('DOMContentLoaded', () => {
        let changed = false;
        const form = document.querySelector('form[data-ai-context]');
        if (form) {
            form.addEventListener('input', () => { changed = true; });
            form.addEventListener('submit', () => { changed = false; });
            window.addEventListener('beforeunload', event => { if (changed) { event.preventDefault(); event.returnValue = ''; } });
        }
        supported.forEach(name => {
            const field = document.querySelector('[name="' + name + '"]'); if (!field) return;
            const button = document.createElement('button'); button.type = 'button'; button.className = 'seo-ai-button btn btn-outline-secondary'; button.textContent = '✦ AI';
            button.setAttribute('aria-label', 'Preview SEO suggestion for ' + name.replaceAll('_', ' '));
            button.disabled = !config.dataset.revision;
            if (button.disabled) button.title = 'Save the record and apply the SEO migration first.';
            const anchor = name === 'faq_json' ? document.getElementById('faq-builder') : (field._seoQuill ? field._seoQuill.container : field);
            anchor.after(button);
            const undo = document.createElement('button'); undo.type = 'button'; undo.className = 'seo-ai-button btn btn-outline-secondary'; undo.textContent = 'Undo AI change'; undo.disabled = true; button.after(undo);
            let previous = null;
            undo.addEventListener('click', () => {
                if (previous === null) return;
                if (field._seoQuill) field._seoQuill.clipboard.dangerouslyPasteHTML(previous);
                field.value = previous; field.dispatchEvent(new Event('input', { bubbles: true }));
                if (name === 'faq_json') field.dispatchEvent(new Event('seo:replace'));
                undo.disabled = true;
            });
            button.addEventListener('click', async () => {
                const quill = field._seoQuill;
                if (name === 'faq_json') {
                    field.value = JSON.stringify(Array.from(document.querySelectorAll('#faq-builder .faq-row'), row => ({ question: row.querySelector('.faq-question').value, answer: row.querySelector('.faq-answer').value })));
                }
                const existing = quill ? quill.root.innerHTML : field.value;
                let selection = '', range = null;
                if (name === 'content') {
                    if (quill) { range = quill.getSelection(true); selection = range && range.length ? quill.getText(range.index, range.length) : ''; }
                    else selection = field.value.slice(field.selectionStart, field.selectionEnd);
                    if (!selection || (quill ? quill.getText() : existing).split(selection).length !== 2) { alert('Highlight one unique section of blog body text first.'); return; }
                }
                button.disabled = true;
                const generate = async () => (await request(config.dataset.api, config.dataset.csrf, 'generate', {
                    type: config.dataset.type, id: Number(config.dataset.id), revision: config.dataset.revision,
                    field: name, existing: name === 'content' && quill ? quill.getText() : existing, selection, request_token: token()
                })).result;
                try {
                    const decision = await choose(selection || existing, await generate(), generate);
                    if (!decision) return;
                    if ((quill ? quill.root.innerHTML : field.value) !== existing) { alert('The field changed while generating. Review and try again.'); return; }
                    previous = existing; undo.disabled = false;
                    if (quill) {
                        if (name === 'content') { const node = document.createElement('div'); node.innerHTML = decision.value; quill.deleteText(range.index, range.length); quill.insertText(range.index, node.textContent); }
                        else { quill.clipboard.dangerouslyPasteHTML(decision.value); }
                        field.value = quill.root.innerHTML;
                    } else { field.value = name === 'content' ? existing.replace(selection, decision.value) : decision.value; }
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    if (name === 'faq_json') field.dispatchEvent(new Event('seo:replace'));
                } catch (error) { alert(error.message + '\nConnect through SEO Manager → Connection & Usage if needed.'); }
                finally { button.disabled = false; }
            });
        });
    });
})();
