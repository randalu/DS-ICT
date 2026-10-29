(function () {
    'use strict';
    const root = document.getElementById('seo-manager'); if (!root) return;
    const api = root.dataset.api, csrf = root.dataset.csrf, ready = root.dataset.ready === '1';
    const $ = selector => document.querySelector(selector);
    let record = null, page = 1, dirty = false, guidanceDirty = false, guidance = null, history = {}, provenance = {};
    function node(tag, text, className) { const el = document.createElement(tag); if (text !== undefined) el.textContent = text; if (className) el.className = className; return el; }
    function message(text) { $('#seo-message').hidden = !text; $('#seo-message').textContent = text; }
    async function get(action, params) {
        const response = await fetch(api + '?' + new URLSearchParams({ action, ...params }), { credentials: 'same-origin' });
        const data = await response.json(); if (!response.ok || data.error) throw new Error(data.error || 'Request failed'); return data;
    }
    const post = (action, payload, extras) => window.SeoAI.request(api, csrf, action, payload, extras);
    async function run(button, task) {
        if (button.disabled) return; button.disabled = true; message('');
        try { await task(); } catch (error) { message(error.message); }
        finally { button.disabled = false; }
    }
    function showTab(name) {
        document.querySelectorAll('[data-panel]').forEach(panel => { panel.hidden = panel.dataset.panel !== name; });
        document.querySelectorAll('[data-tab]').forEach(button => { if (button.dataset.tab === name) button.setAttribute('aria-current', 'page'); else button.removeAttribute('aria-current'); });
    }
    document.querySelectorAll('[data-tab]').forEach(button => button.addEventListener('click', () => showTab(button.dataset.tab)));
    async function list() {
        const params = Object.fromEntries(new FormData($('#seo-filter'))); params.page = page;
        const result = await get('list', params), container = $('#seo-list'); container.replaceChildren();
        result.items.forEach(item => {
            const button = node('button', item.title, 'seo-record'); button.type = 'button'; button.append(node('small', (Number(item.is_published) ? 'Published' : 'Draft') + ' · ' + (item.reviewed_at || 'Never reviewed')));
            button.addEventListener('click', () => run(button, async () => {
                if (dirty && !confirm('Discard your unsaved page edits and open this page?')) return;
                if (!ready) { message('Apply the reviewed SEO migration before editing.'); return; }
                render((await get('load', { type: params.type, id: item.id })).record);
            })); container.append(button);
        });
        if (!result.items.length) container.append(node('p', result.notice || 'No matching content.', 'seo-list-empty'));
        $('#seo-prev').disabled = page <= 1; $('#seo-next').disabled = !result.more; $('#seo-page-number').textContent = 'Page ' + page;
    }
    $('#seo-filter').addEventListener('submit', event => { event.preventDefault(); page = 1; list().catch(error => message(error.message)); });
    $('#seo-prev').addEventListener('click', () => { page--; list().catch(error => message(error.message)); });
    $('#seo-next').addEventListener('click', () => { page++; list().catch(error => message(error.message)); });
    function render(next) {
        record = next; dirty = false; history = {}; provenance = {};
        const editor = $('#seo-editor'); editor.replaceChildren(node('h2', record.title));
        const link = node('a', record.url); link.href = record.url; link.target = '_blank'; link.rel = 'noopener'; editor.append(link);
        editor.append(node('p', record.published ? 'Published: saving selected fields updates this public page.' : 'Draft: saving does not publish this page.', 'seo-notice'));
        const editors = node('nav'); editors.setAttribute('aria-label', 'Package editors');
        Object.entries(record.editor_links || {}).forEach(([label, url]) => { const a = node('a', label); a.href = url; a.style.marginRight = '1rem'; editors.append(a); }); editor.append(editors);
        if (Object.keys(record.context || {}).length) { const context = node('details'); context.append(node('summary', 'All stored package fields, locations and relationships (reference only)')); const pre = node('pre', JSON.stringify(record.context, null, 2)); pre.style.whiteSpace = 'pre-wrap'; pre.style.overflowWrap = 'anywhere'; context.append(pre); editor.append(context); }
        const snippet = node('details'); snippet.append(node('summary', 'Current metadata including fallbacks'), node('p', record.rendered.title), node('p', record.rendered.description)); editor.append(snippet);
        Object.entries(record.fields).forEach(([key, definition]) => {
            const section = node('section', undefined, 'seo-field'), header = node('div', undefined, 'seo-field-head');
            const label = node('label', undefined, 'seo-check'), select = node('input'); select.type = 'checkbox'; select.dataset.saveField = key;
            label.append(select, document.createTextNode(definition.label));
            const ai = node('button', '✦ AI'); ai.type = 'button'; ai.setAttribute('aria-label', 'Preview AI suggestion for ' + definition.label); header.append(label, ai);
            ai.hidden = definition.ai === false;
            const field = node('textarea'); field.id = 'seo-value-' + key; field.setAttribute('aria-label', definition.label); field.dataset.field = key; field.rows = ['intro','content','faq_json'].includes(key) ? 8 : 3; field.maxLength = definition.limit; field.value = definition.value;
            const count = node('small', '', 'seo-counter');
            const update = () => { count.textContent = field.value.length + ' / ' + definition.limit + (key === 'meta_title' ? ' · soft target 50–60 before brand' : key === 'meta_description' ? ' · soft target 140–160' : ''); };
            field.addEventListener('input', () => { dirty = true; select.checked = true; update(); }); update();
            const undo = node('button', 'Undo'); undo.type = 'button'; undo.addEventListener('click', () => {
                field.value = history[key] === undefined ? definition.value : history[key]; delete provenance[key]; field.dispatchEvent(new Event('input'));
            });
            ai.addEventListener('click', () => run(ai, async () => {
                const existing = field.value;
                const selection = key === 'content' ? existing.slice(field.selectionStart, field.selectionEnd) : '';
                if (key === 'content' && !selection) throw new Error('Highlight a unique passage in the body field before clicking AI.');
                const generate = async () => (await post('generate', { type: record.type, id: record.id, revision: record.revision, field: key, existing, selection, plan: getPlan(), request_token: SeoAI.token() })).result;
                const decision = await SeoAI.choose(selection || existing, await generate(), generate);
                if (!decision) return;
                if (field.value !== existing) throw new Error('The field changed while generating. Review and try again.');
                history[key] = existing; field.value = key === 'content' ? existing.replace(selection, decision.value) : decision.value;
                provenance[key] = decision.suggestion_id; field.dispatchEvent(new Event('input')); await status(false);
            }));
            section.append(header, field, count, undo);
            if (key === 'content') section.append(node('p', 'Body HTML: highlight only the passage you want rewritten. The rest of the body is preserved.'));
            if (definition.format === 'faq') section.append(node('p', 'Use a JSON list of {"question":"…","answer":"…"} pairs.'));
            editor.append(section);
        });
        const keywordSection = node('section', undefined, 'seo-field'), planCheck = node('input'); planCheck.type = 'checkbox'; planCheck.id = 'seo-save-plan';
        const planLabel = node('label', undefined, 'seo-check'); planLabel.append(planCheck, document.createTextNode('Keyword plan — suggested targets')); keywordSection.append(planLabel);
        const keywordLabels = { primary_phrase: 'Primary phrase', supporting_phrases: 'Supporting phrases (one per line)', intent: 'Search intent', questions: 'Audience questions (one per line)', market: 'Target market', notes: 'Notes' };
        Object.entries(keywordLabels).forEach(([key, title]) => {
            const label = node('label', title), input = node(key === 'market' || key === 'primary_phrase' ? 'input' : 'textarea'); input.dataset.plan = key;
            input.value = record.plan[key] || (key === 'market' ? 'International' : ''); input.addEventListener('input', () => { dirty = true; planCheck.checked = true; }); label.append(input); keywordSection.append(label);
        });
        if (record.overlap.length) keywordSection.append(node('p', 'Possible topic overlap: ' + record.overlap.map(x => x.type + ' #' + x.id).join(', '), 'seo-notice'));
        const research = node('button', 'Open keyword research'); research.type = 'button'; research.addEventListener('click', () => { showTab('research'); showBrief(record.brief); }); keywordSection.append(research);
        if (record.brief) {
            const review = node('button', 'Review & apply keyword suggestions'); review.type = 'button';
            review.addEventListener('click', () => reviewKeywords(record.brief)); keywordSection.append(review);
        }
        editor.append(keywordSection);
        const saveBar = node('div', undefined, 'seo-save'), save = node('button', 'Save selected fields'); save.type = 'button';
        save.addEventListener('click', () => run(save, async () => {
            const values = {}, suggestions = {};
            editor.querySelectorAll('[data-save-field]:checked').forEach(input => {
                const key = input.dataset.saveField, value = $('#seo-value-' + key).value;
                if (key === 'content') {
                    const original = record.fields.content.value;
                    let start = 0, end = 0;
                    while (start < original.length && start < value.length && original[start] === value[start]) start++;
                    while (end < original.length - start && end < value.length - start && original[original.length - end - 1] === value[value.length - end - 1]) end++;
                    const nodeStart = original.lastIndexOf('>', Math.max(0, start - 1)) + 1;
                    let nodeEnd = original.indexOf('<', original.length - end);
                    if (nodeEnd < 0) nodeEnd = original.length;
                    values[key] = { selection: original.slice(nodeStart, nodeEnd), replacement: value.slice(nodeStart, value.length - (original.length - nodeEnd)) };
                } else values[key] = value;
                if (provenance[key]) suggestions[key] = provenance[key];
            });
            const payload = { type: record.type, id: record.id, revision: record.revision, values, suggestions };
            if (planCheck.checked) payload.plan = getPlan();
            const result = await post('save', payload); render(result.record); message('Selected fields saved. A reversible revision was recorded.'); await list();
        })); saveBar.append(save); editor.append(saveBar);
        const revisions = node('details'); revisions.append(node('summary', 'Revision history (latest 20)'));
        record.revisions.forEach(revision => {
            const row = node('p', revision.created_at + ' · Admin ' + revision.admin_id + ' · Policy ' + revision.policy_version + ' '), restore = node('button', 'Restore before this revision'); restore.type = 'button';
            restore.addEventListener('click', () => run(restore, async () => {
                if (!confirm('Restore the fields changed by this revision? This saves to the record and records another revision. Unsaved editor changes will be discarded.')) return;
                render((await post('restore', { type: record.type, id: record.id, revision: record.revision, revision_id: revision.id })).record); message('Revision restored.');
            })); row.append(restore); revisions.append(row);
        }); editor.append(revisions); showBrief(record.brief);
    }
    function getPlan() { return Object.fromEntries(Array.from(document.querySelectorAll('[data-plan]'), input => [input.dataset.plan, input.value])); }
    function briefMatchesPage(brief) {
        return record && brief?.kind === 'keywords' && brief.content_type === record.type && Number(brief.content_id) === Number(record.id);
    }
    function reviewKeywords(brief) {
        if (!briefMatchesPage(brief)) { message('Select the page that this keyword research belongs to before applying it.'); return; }
        message('');
        const selected = { type: record.type, id: record.id, revision: record.revision }, before = getPlan(), data = brief.data;
        const join = value => Array.isArray(value) ? value.join('\n') : (typeof value === 'string' ? value : '');
        const notes = [['Recommended actions', data.actions], ['Uncertainties', data.uncertainties]].filter(([, values]) => Array.isArray(values) && values.length).map(([label, values]) => label + ':\n' + values.join('\n')).join('\n\n');
        const proposals = { primary_phrase: data.primary_phrase || '', supporting_phrases: join(data.supporting_phrases), intent: data.intent || '', questions: join(data.questions), market: data.market || brief.market || '', notes };
        const labels = { primary_phrase: 'Primary phrase', supporting_phrases: 'Supporting phrases', intent: 'Search intent', questions: 'Audience questions', market: 'Target market', notes: 'Notes' };
        const limits = { primary_phrase: 180, supporting_phrases: 2000, intent: 500, questions: 3000, market: 100, notes: 3000 };
        const dialog = node('dialog', undefined, 'seo-dialog'); dialog.append(node('h2', 'Review keyword suggestions for ' + record.title));
        dialog.append(node('p', 'Research from ' + brief.created_at + '. Apply only the fields you approve. This fills the editor; Save selected fields is still required.'));
        if (!data.primary_phrase?.trim()) dialog.append(node('p', 'No primary phrase was returned. You can still apply the available intent, questions and notes. Empty suggestions preserve your existing fields.', 'seo-notice'));
        if (brief.stale) dialog.append(node('p', 'This research is over 30 days old. Review its sources before using it.', 'seo-notice'));
        const rows = [];
        Object.entries(labels).forEach(([key, label]) => {
            const section = node('section', undefined, 'seo-field'), checkLabel = node('label', undefined, 'seo-check'), checkbox = node('input'); checkbox.type = 'checkbox'; checkbox.checked = Boolean(proposals[key].trim()); checkbox.dataset.approvePlan = key;
            checkLabel.append(checkbox, document.createTextNode('Apply ' + label));
            const current = node('p', 'Current: ' + (before[key] || '(empty)'), 'seo-wrap'), editLabel = node('label', label + ' suggestion (up to ' + limits[key] + ' characters)'), edit = node('textarea');
            edit.value = proposals[key]; edit.maxLength = limits[key]; edit.rows = 3; edit.dataset.reviewPlan = key;
            if (!proposals[key]) edit.placeholder = 'No suggestion returned. Existing value will be kept.';
            edit.addEventListener('input', () => { checkbox.checked = Boolean(edit.value.trim()); });
            editLabel.append(edit); section.append(checkLabel, current, editLabel); dialog.append(section); rows.push({ key, checkbox, edit });
        });
        const notice = node('p', '', 'seo-notice'); notice.hidden = true; notice.setAttribute('role', 'status'); dialog.append(notice);
        const apply = node('button', 'Apply approved suggestions to editor'); apply.type = 'button';
        apply.addEventListener('click', () => {
            if (!record || record.type !== selected.type || record.id !== selected.id || record.revision !== selected.revision || JSON.stringify(getPlan()) !== JSON.stringify(before)) {
                notice.textContent = 'The selected page or its editor changed. Close this review and reopen it before applying.'; notice.hidden = false; return;
            }
            const approved = rows.filter(({ checkbox, edit }) => checkbox.checked && edit.value.trim());
            const oversized = approved.find(({ key, edit }) => Array.from(edit.value.trim()).length > limits[key]);
            if (oversized) { notice.textContent = labels[oversized.key] + ' exceeds its limit. Shorten it or uncheck it before applying.'; notice.hidden = false; return; }
            let applied = 0;
            approved.forEach(({ key, edit }) => {
                const input = $('[data-plan="' + key + '"]'); input.value = edit.value.trim(); input.dispatchEvent(new Event('input')); applied++;
            });
            if (!applied) { notice.textContent = 'Select or enter at least one non-empty suggestion to apply.'; notice.hidden = false; return; }
            dialog.close(); dialog.remove(); showTab('content'); message('Approved keyword suggestions filled the editor. Review them, then click Save selected fields.');
            $('#seo-save-plan').scrollIntoView({ block: 'center' });
        });
        const cancel = node('button', 'Cancel review'); cancel.type = 'button'; cancel.addEventListener('click', () => { dialog.close(); dialog.remove(); });
        dialog.addEventListener('cancel', event => { event.preventDefault(); dialog.close(); dialog.remove(); });
        dialog.append(apply, cancel); document.body.append(dialog); dialog.showModal();
    }
    function showBrief(brief) {
        const output = $('#seo-research-output'); output.replaceChildren();
        if (!brief) { output.append(node('p', 'No research brief. Generation can use verified page context.')); return; }
        output.append(node('p', 'Retrieved ' + brief.created_at + (brief.stale ? ' · Stale (over 30 days)' : ' · Within 30-day cache'), 'seo-notice'));
        const data = brief.data;
        if (briefMatchesPage(brief)) {
            const review = node('button', 'Review & apply keyword suggestions'); review.type = 'button'; review.addEventListener('click', () => reviewKeywords(brief)); output.append(review);
        } else if (brief.kind === 'guidance') output.append(node('p', 'General SEO guidance is for the Principles review; it does not fill a page’s keyword plan.'));
        ['query','market','language','notice','intent','primary_phrase','supporting_phrases','questions','findings','uncertainties','actions'].forEach(key => {
            if (Array.isArray(data[key]) ? data[key].length : data[key]) output.append(node('h3', key.replaceAll('_', ' ')), node('p', Array.isArray(data[key]) ? data[key].join('\n') : data[key], 'seo-wrap'));
        });
        (data.sources || []).forEach(source => {
            try { const url = new URL(source.url); if (url.protocol !== 'https:') return; const p = node('p'), a = node('a', source.title || url.href); a.href = url.href; a.target = '_blank'; a.rel = 'noopener noreferrer'; p.append(a); output.append(p); } catch (_) { /* Skip invalid URL. */ }
        });
    }
    async function research(kind, button) {
        await run(button, async () => {
            if (kind === 'keywords' && !record) throw new Error('Select a page in Content first.');
            const market = kind === 'keywords' ? getPlan().market : 'International', selectedType = record?.type, selectedId = record?.id;
            const result = await post('research', { kind, type: selectedType, id: selectedId, market, refresh: true, request_token: SeoAI.token() });
            if (kind === 'keywords' && record?.type === selectedType && record?.id === selectedId) record.brief = result.result;
            showBrief(result.result); await status(false); message('Research completed. Review sources before using its recommendations.');
        });
    }
    $('#seo-platform-research').addEventListener('click', () => research('guidance', $('#seo-platform-research')));
    $('#seo-page-research').addEventListener('click', () => research('keywords', $('#seo-page-research')));
    function usage(data) {
        $('#seo-connection-status').textContent = data.connected ? 'Server configured · ' + data.model : 'Server OpenAI key not configured';
        const generation = data.today.find(row => row.kind === 'generation') || {}, research = data.today.find(row => row.kind === 'research') || {};
        $('#seo-usage').textContent = 'Shared usage: ' + JSON.stringify(data.shared_usage || []) + '\n' + 'Today’s requests (including failed or uncertain requests):\nGeneration and connection tests: ' + (generation.requests || 0) + ' / ' + data.generation_limit + '\nResearch briefs: ' + (research.requests || 0) + ' / ' + data.research_limit + '\nInput tokens reported: ' + data.today.reduce((sum, row) => sum + Number(row.input_tokens), 0) + '\nOutput tokens reported: ' + data.today.reduce((sum, row) => sum + Number(row.output_tokens), 0);
    }
    async function status(fillGuidance = true) {
        if (!ready) return;
        const result = await get('status'); usage(result.usage);
        if (fillGuidance) { guidance = result.guidance; Object.entries(guidance.values).forEach(([key, value]) => { $('#seo-guidance [name="' + key + '"]').value = value; }); if (!record) showBrief(result.brief); }
    }
    $('#seo-test').addEventListener('click', () => run($('#seo-test'), async () => {
        if (!confirm('Run a paid test using the server-configured OpenAI model?')) return;
        const result = await post('test', { request_token: SeoAI.token() }); usage(result.usage); message(result.result.notice);
    }));
    $('#seo-refresh-status').addEventListener('click', () => run($('#seo-refresh-status'), () => status(false)));
    const guidanceValues = () => Object.fromEntries(new FormData($('#seo-guidance')));
    $('#seo-guidance-preview').addEventListener('click', () => { $('#seo-guidance-preview-text').textContent = 'Core policy always applies. Editorial guidance for the next request:\n\n' + Object.entries(guidanceValues()).map(([key, value]) => key.replaceAll('_', ' ') + ': ' + value).join('\n\n'); });
    $('#seo-guidance').addEventListener('input', () => { guidanceDirty = true; });
    $('#seo-guidance').addEventListener('submit', event => {
        event.preventDefault(); run($('#seo-guidance button:last-child'), async () => {
            const result = await post('guidance', { revision: guidance.id, values: guidanceValues() }); guidance = result.guidance; guidanceDirty = false; message('New guidance version saved. Core rules remain mandatory.');
        });
    });
    window.addEventListener('beforeunload', event => { if (dirty || guidanceDirty) { event.preventDefault(); event.returnValue = ''; } });
    const auditTarget = new URLSearchParams(location.search);
    if (['destination','itinerary','tour','day_tour','blog'].includes(auditTarget.get('type'))) $('#seo-filter [name="type"]').value = auditTarget.get('type');
    list().catch(error => message(error.message)); status().catch(error => message(error.message));
    if (ready && ['destination','itinerary','tour','day_tour','blog'].includes(auditTarget.get('type')) && /^[1-9][0-9]*$/.test(auditTarget.get('id') || '')) {
        get('load', { type: auditTarget.get('type'), id: auditTarget.get('id') }).then(data => render(data.record)).catch(error => message(error.message));
    }
    if (!ready) document.querySelectorAll('[data-panel]:not([data-panel="content"]) button').forEach(button => { button.disabled = true; });
})();
