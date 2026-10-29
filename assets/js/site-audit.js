(function () {
    'use strict';
    const root=document.getElementById('site-audit'); if (!root) return;
    const $=selector=>root.querySelector(selector), api=root.dataset.api, csrf=root.dataset.csrf, ready=root.dataset.ready==='1';
    const el=(tag,text)=>{const node=document.createElement(tag);if(text!==undefined)node.textContent=text;return node;};
    let runId=0, page=1, observationPage=1, report=null, timer=null, preview=null, pending=false;
    const selected=new Set();
    const message=text=>{const target=$('#audit-message');target.textContent=text;target.hidden=!text;};
    const post=(action,payload)=>SeoAI.request(api,csrf,action,payload);
    async function get(action,params={}) { const response=await fetch(api+'?'+new URLSearchParams({action,...params}),{credentials:'same-origin'});const data=await response.json();if(!response.ok||data.error)throw new Error(data.error||'Request failed');return data; }
    async function busy(button,task) { if(button.disabled)return;button.disabled=true;message('');try{await task();}catch(error){message(error.message);}finally{button.disabled=false;} }
    function tab(name){root.querySelectorAll('[data-audit-panel]').forEach(node=>node.hidden=node.dataset.auditPanel!==name);root.querySelectorAll('[data-audit-tab]').forEach(node=>{if(node.dataset.auditTab===name)node.setAttribute('aria-current','page');else node.removeAttribute('aria-current');});}
    root.querySelectorAll('[data-audit-tab]').forEach(button=>button.addEventListener('click',()=>tab(button.dataset.auditTab)));
    function invalidate(){preview=null;$('#audit-review').disabled=true;$('#audit-context-preview').replaceChildren();$('#audit-selected').textContent=selected.size+' selected (maximum 20)';}
    function link(label,url){const node=el('a',label);node.href=url;return node;}
    async function refreshHistory(preserve=true){
        const data=await get('history'), select=$('#audit-runs');select.replaceChildren();
        data.runs.forEach(run=>{const option=el('option','#'+run.id+' · '+run.status+' · '+run.started_at);option.value=run.id;select.append(option);});
        if(!preserve||!runId)runId=Number(data.runs[0]?.id||0);select.value=String(runId);
        const history=$('#audit-history');history.replaceChildren();data.runs.forEach(run=>{const item=el('p'),button=el('button','#'+run.id+' · '+run.status+' · '+run.pages_crawled+' pages · '+run.started_at);button.type='button';button.addEventListener('click',()=>chooseRun(Number(run.id)));item.append(button);history.append(item);});
    }
    function chooseRun(id){if(pending){message('Save or discard the pending preview first.');return;}clearTimeout(timer);runId=id;page=1;observationPage=1;selected.clear();invalidate();$('#audit-runs').value=String(id);load().catch(error=>message(error.message));}
    async function poll(){
        const body=new FormData();body.set('_token',csrf);body.set('run_id',String(runId));
        const response=await fetch(root.dataset.pollApi,{method:'POST',body,credentials:'same-origin'});const data=await response.json();if(!response.ok||data.error)throw new Error(data.error||'Polling failed');return data;
    }
    async function load(){
        if(pending){message('Save or discard the pending preview before refreshing the report.');return;}
        if(!runId){$('#audit-summary').textContent='No audit runs yet. Check host readiness, then start a public audit.';return;}
        clearTimeout(timer);const id=runId;
        const next=await get('report',{run_id:id,page,observation_page:observationPage,...Object.fromEntries(new FormData($('#audit-filter')))});
        if(id!==runId)return;report=next;
        $('#audit-progress').textContent='#'+runId+' · '+next.run.status+' · '+next.run.pages_crawled+' / '+(next.run.total_pages||'?')+' pages · '+next.run.issue_count+' findings';
        const selectedRun=$('#audit-runs').selectedOptions[0];if(selectedRun)selectedRun.textContent='#'+runId+' · '+next.run.status+' · '+next.run.started_at;
        $('#audit-start').disabled=next.run.status==='running'||!ready;
        const summary=$('#audit-summary');summary.replaceChildren();const badges=el('div');badges.className='audit-badges';
        ['critical','high','medium','low'].forEach(severity=>badges.append(el('span',severity+': '+(next.counts[severity]||0))));badges.append(el('span',next.affected_pages+' affected pages'));summary.append(badges);
        const groups=$('#audit-groups');groups.replaceChildren(el('p','Showing '+(next.groups||[]).length+' of '+(next.group_count||0)+' groups.'));
        (next.groups||[]).forEach(group=>{
            const item=el('details');item.className='audit-observation';
            item.append(el('summary',group.occurrences+' occurrences · '+group.affected_pages+' pages · '+group.certainty+' · '+group.cause.replaceAll('_',' ')),el('p',group.target||group.page_url),el('p','Rules: '+group.rules.join(', ')));
            const show=el('button','Review matching findings');show.type='button';show.addEventListener('click',()=>{
                if(pending){message('Save or discard the pending preview first.');return;}
                $('#audit-filter').reset();$('#audit-filter [name="q"]').value=(group.target||group.page_url).slice(0,100);$('#audit-filter [name="certainty"]').value=group.certainty;page=1;tab('issues');load().catch(error=>message(error.message));
            });item.append(show);groups.append(item);
        });
        if(next.coverage.resources_not_checked!==undefined)summary.append(el('p',next.coverage.resources_checked+' resource probe observations attempted · '+next.coverage.resources_not_checked+' excluded (not checked).'));
        const coverage=next.coverage||{};summary.append(el('h3',next.run.status==='running'?'Audit in progress':coverage.complete?'Configured checks completed':'Partial coverage — review notices'));
        summary.append(el('p','Limits: '+(coverage.max_pages||5000)+' pages · '+(coverage.max_internal||5000)+' internal links · '+(coverage.max_external??25)+' external links · 2 concurrent pages'));
        [...new Set([...(coverage.notices||[]),...(next.inventory_notices||[])])].forEach(text=>summary.append(el('p',text)));
        ['json','csv'].forEach(format=>$('#audit-'+format).href=api+'?'+new URLSearchParams({action:'export',format,run_id:runId}));
        renderIssues(next.issues);$('#audit-prev').disabled=page===1;$('#audit-next').disabled=page*100>=next.filtered_count;$('#audit-page-label').textContent='Page '+page+' · '+next.filtered_count+' findings';
        const observations=$('#audit-observations');observations.replaceChildren();next.observations.forEach(row=>{const details=el('details');details.className='audit-observation';details.append(el('summary',row.kind+' · '+row.page_url),el('pre',JSON.stringify(JSON.parse(row.data_json),null,2)));observations.append(details);});
        $('#audit-observation-prev').disabled=observationPage===1;$('#audit-observation-next').disabled=!next.more_observations;$('#audit-observation-page').textContent='Page '+observationPage;
        const compare=$('#audit-comparison'),c=next.comparison;compare.replaceChildren(el('p',c.previous_run?'Compared with #'+c.previous_run+': '+c.new.length+' new · '+c.persistent.length+' persistent · '+c.resolved.length+' resolved · '+c.unverified.length+' unverified':'No earlier compatible completed run.'));
        ['resolved','unverified'].forEach(kind=>{if(!c[kind].length)return;const detail=el('details');detail.append(el('summary',kind+' findings'));c[kind].forEach(row=>detail.append(el('p',row.type+' · '+row.page_url)));compare.append(detail);});
        const reviews=$('#audit-reviews');reviews.replaceChildren();next.reviews.forEach(row=>renderReview(JSON.parse(row.result_json),reviews,row.created_at+' · '+row.model));
        if(next.run.status==='running')timer=setTimeout(async()=>{try{await poll();await load();}catch(error){message(error.message+' Refresh to resume checking progress.');}},2500);
    }
    function renderReview(data,container,title='AI recommendations'){
        const section=el('section');section.append(el('h3',title));data.recommendations.forEach(row=>{const details=el('details');details.append(el('summary','#'+row.issue_id+' · '+row.priority+' · '+row.action),el('p',row.explanation),el('p','Dependencies: '+row.dependencies.join(' · ')),el('p','Verify: '+row.verification.join(' · ')));section.append(details);});container.append(section);
    }
    function usageText(usage){const rows=usage.today||[],generation=rows.find(row=>row.kind==='generation');return (generation?.requests||0)+' / '+(usage.generation_limit||50)+' shared generation requests today · '+rows.reduce((sum,row)=>sum+Number(row.input_tokens||0)+Number(row.output_tokens||0),0)+' reported tokens today';}
    function renderIssues(rows){
        const list=$('#audit-issues');list.replaceChildren();if(!rows.length)list.append(el('p','No findings match these filters.'));
        rows.forEach(row=>{
            const card=el('article');card.className='audit-issue';card.dataset.severity=row.severity;
            const label=el('label'),check=el('input');check.type='checkbox';check.checked=selected.has(Number(row.id));label.append(check,document.createTextNode('#'+row.id+' · '+row.severity+' · '+row.certainty));
            check.addEventListener('change',()=>{if(check.checked&&selected.size>=20){check.checked=false;message('Select up to 20 findings per AI request.');return;}if(check.checked)selected.add(Number(row.id));else selected.delete(Number(row.id));invalidate();});
            card.append(label,el('h3',row.type.replaceAll('_',' ')),link(row.page_url,row.page_url),el('p',row.detail||'Inspect this page.'),el('small','Observed: '+(row.observed_at||row.created_at)+' · '+(row.source_location||'page')));
            if(row.target_url)card.append(el('p','Target: '+row.target_url));
            const evidence=el('details');evidence.append(el('summary','Evidence'),el('pre',row.evidence_json||'Legacy run: no structured evidence'));card.append(evidence);
            const actions=el('div');actions.className='seo-actions';Object.entries(row.editors).forEach(([key,url])=>actions.append(link(key==='images'?'Choose replacement image':key==='seo'?'Open SEO editor':key==='fields'?'Review all package fields':'Open full editor',url)));
            if(row.editors.seo && /^(missing_title|duplicate_title|missing_meta_description|short_meta_description|duplicate_description)$/.test(row.type)){const previewButton=el('button','Preview SEO fix');previewButton.type='button';previewButton.addEventListener('click',()=>busy(previewButton,()=>previewFix(row,card)));actions.append(previewButton);}
            if(!Object.keys(row.editors).length)card.append(el('p','No supported editor for this route. Review the evidence as a scoped implementation task, then run another audit.'));
            card.append(actions);
            const stateLabel=el('label','Review state'),state=el('select');['open','acknowledged','ignored'].forEach(value=>{const option=el('option',value);option.value=value;state.append(option);});state.value=row.triage_state;stateLabel.append(state);
            const reasonLabel=el('label','Review reason'),reason=el('textarea');reason.maxLength=1000;reason.value=row.reason;reasonLabel.append(reason);
            const save=el('button','Save review state');save.type='button';save.disabled=!row.fingerprint;save.addEventListener('click',()=>busy(save,async()=>{await post('triage',{fingerprint:row.fingerprint,state:state.value,reason:reason.value});message('Review state saved. A recheck determines resolution.');}));card.append(stateLabel,reasonLabel,save);list.append(card);
        });
    }
    async function previewFix(row,card){
        if(pending)throw new Error('Save or discard the pending preview first.');
        const seoApi=root.dataset.seoApi;
        const response=await fetch(seoApi+'?'+new URLSearchParams({action:'load',type:row.record.type,id:row.record.id}),{credentials:'same-origin'});const data=await response.json();if(!response.ok||data.error)throw new Error(data.error||'Cannot load SEO record');
        const record=data.record;const field=/description/.test(row.type)?'meta_description':/title/.test(row.type)?'meta_title':'excerpt';
        if(!record.fields[field])throw new Error('This field is unavailable. Open the SEO editor to choose another field.');
        if(!confirm('Generate a paid suggestion for '+field.replaceAll('_',' ')+'? Current content is preserved until you save.'))return;
        const generate=async()=> (await SeoAI.request(seoApi,csrf,'generate',{type:record.type,id:record.id,revision:record.revision,field,existing:record.fields[field].value,request_token:SeoAI.token()})).result;
        const decision=await SeoAI.choose(record.fields[field].value,await generate(),generate);if(!decision)return;
        pending=true;const panel=el('section');panel.className='audit-pending';panel.append(el('h3','Pending '+field.replaceAll('_',' ')+' fix'),el('p',record.published?'Published page: saving updates this public page immediately.':'Draft: saving does not publish.'),el('pre',decision.value));
        const save=el('button','Save this selected field'),discard=el('button','Discard preview');save.type=discard.type='button';
        const finish=()=>{pending=false;panel.remove();};discard.addEventListener('click',finish);
        save.addEventListener('click',()=>busy(save,async()=>{await SeoAI.request(seoApi,csrf,'save',{type:record.type,id:record.id,revision:record.revision,values:{[field]:decision.value},suggestions:{[field]:decision.suggestion_id}});finish();message('Selected field saved with revision history. Run another audit to verify the finding.');}));panel.append(save,discard);card.append(panel);
    }
    $('#audit-context').addEventListener('click',event=>busy(event.currentTarget,async()=>{preview=await post('context',{run_id:runId,ids:[...selected]});preview.ids=[...selected];preview.runId=runId;const container=$('#audit-context-preview');container.replaceChildren(el('h3','Public evidence to send'),el('pre',JSON.stringify(preview.context,null,2)));$('#audit-review').disabled=false;tab('ai');}));
    $('#audit-review').addEventListener('click',event=>busy(event.currentTarget,async()=>{if(!preview)return;const submitted=preview;preview=null;$('#audit-review').disabled=true;try{const data=await post('review',{run_id:submitted.runId,ids:submitted.ids,context_hash:submitted.hash,request_token:SeoAI.token()});renderReview(data.result,$('#audit-reviews'));$('#audit-usage').textContent=usageText(data.usage);message('AI review saved. Recommendations require your review.');}finally{setTimeout(()=>{$('#audit-review').disabled=true;},0);}}));
    $('#audit-start').addEventListener('click',event=>busy(event.currentTarget,async()=>{if(pending)throw new Error('Save or discard the pending preview first.');const body=new FormData();body.set('_token',csrf);const response=await fetch(root.dataset.runApi,{method:'POST',body,credentials:'same-origin'});const data=await response.json();if(!response.ok||data.error)throw new Error(data.error||'Could not start audit');runId=Number(data.run_id);selected.clear();invalidate();await refreshHistory();await load();}));
    $('#audit-preflight').addEventListener('click',event=>busy(event.currentTarget,async()=>{const result=await post('preflight',{});message(result.ok?'Host preflight passed: Chromium launches and private storage is writable. Production limits still require release review.':result.error);}));
    $('#audit-refresh').addEventListener('click',event=>busy(event.currentTarget,async()=>{await refreshHistory();if(runId)await poll();await load();}));
    $('#audit-runs').addEventListener('change',()=>{if(pending){message('Save or discard the pending preview before switching runs.');$('#audit-runs').value=String(runId);return;}chooseRun(Number($('#audit-runs').value));});
    $('#audit-filter').addEventListener('submit',event=>{event.preventDefault();if(pending){message('Save or discard the pending preview first.');return;}page=1;load().catch(error=>message(error.message));});
    [['#audit-prev',-1],['#audit-next',1]].forEach(([selector,delta])=>$(selector).addEventListener('click',()=>{if(pending){message('Save or discard the pending preview first.');return;}page+=delta;load().catch(error=>message(error.message));}));
    [['#audit-observation-prev',-1],['#audit-observation-next',1]].forEach(([selector,delta])=>$(selector).addEventListener('click',()=>{observationPage+=delta;load().catch(error=>message(error.message));}));
    window.addEventListener('beforeunload',event=>{if(pending){event.preventDefault();event.returnValue='';}});
    if(ready)refreshHistory(false).then(load).catch(error=>message(error.message));
})();
