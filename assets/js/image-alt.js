(() => {
    'use strict';
    if(window.ictImageAltReady)return;
    const manager=document.querySelector('[data-manager],[data-page-media]');if(!manager)return;
    window.ictImageAltReady=true;
    const api=new URL('image_alt_api.php',manager.dataset.api);
    const pending=new WeakMap();
    const referenceFields=form=>{
        const entry=form.closest('[data-ag-entry]'),gallery=form.closest('[data-ag-manager]');
        if(entry&&gallery)return {type:'activity',id:gallery.dataset.category,slot:'activity_entry',example_id:entry.dataset.agEntry,photo_id:form.dataset.photo||'0'};
        const values={};for(const name of ['page_media','type','id','slot','child_id'])if(form.elements[name])values[name]=form.elements[name].value;
        return values;
    };
    const source=(form,index=null)=>{
        const files=form.elements.media?.files;
        const file=files?.length?files[index===null?0:index]:null;
        const asset=file?0:Number(form.elements.asset_id?.value||form.querySelector('[data-image-alt]')?.dataset.savedAsset||0);
        const assignment=!file&&!asset&&form.elements.slot&&form.querySelector('[data-current] img,img')?String(form.elements.revision.value):'';
        return {file,asset,assignment,references:JSON.stringify(referenceFields(form))};
    };
    const same=(a,b)=>a.file===b.file&&a.asset===b.asset&&a.assignment===b.assignment&&a.references===b.references;
    const message=(form,text)=>{form.querySelector('[data-alt-status]').textContent=text;};
    async function defaults(form){
        const controls=form.querySelector('[data-image-alt]');if(!controls)return;
        const current=source(form);pending.delete(form);controls.querySelector('[data-alt-preview]').hidden=true;
        const generators=[...form.querySelectorAll('[data-alt-generate],[data-alt-file-index]')];generators.forEach(button=>button.disabled=true);
        form.elements.alt_context_revision.value='';
        if(form.dataset.inactive==='1')return;
        form.elements.asset_alt_revision.value='';form.elements.update_asset_alt.checked=false;
        try{
            const url=new URL(api);url.searchParams.set('asset_id',current.asset);
            Object.entries(referenceFields(form)).forEach(([key,value])=>url.searchParams.set(key,value));
            const response=await fetch(url,{credentials:'same-origin'}),data=await response.json();
            if(!same(current,source(form)))return;
            if(!response.ok)throw new Error(data.error||'Could not load saved description.');
            form.elements.asset_alt_revision.value=data.asset?.revision||'';
            form.elements.alt_context_revision.value=data.context_revision||'';
            const context=data.context||{},places=[...(context.destinations||[]),...(context.locations||[])].filter(Boolean);
            const labels=[context.country||'Sri Lanka',context.page,context.entry,...new Set(places)].filter(Boolean);
            if(context.asset_destination&&!places.includes(context.asset_destination))labels.push('Media Library destination: '+context.asset_destination);
            controls.querySelector('[data-alt-context]').textContent='Reference: '+labels.join(' · ');
            const input=form.elements.caption||form.elements.alt_text||form.elements.description;
            if(input&&!input.value.trim()&&data.asset?.alt_text)input.value=data.asset.alt_text;
            generators.forEach(button=>button.disabled=!data.configured||!data.ready);
            message(form,!data.configured?'Server OpenAI key not configured. You can edit descriptions manually.':!data.ready?'Apply the shared OpenAI migration to enable generation.':'Suggestions use the server OpenAI key. Generating sends this image to OpenAI.');
        }catch(error){message(form,error.message);}
    }
    const initialized=new WeakSet();
    function initialize(root){
        root.querySelectorAll('[data-image-alt]').forEach(controls=>{
            if(initialized.has(controls))return;initialized.add(controls);defaults(controls.closest('form'));
        });
    }
    initialize(document);
    new MutationObserver(()=>initialize(document)).observe(document.body,{childList:true,subtree:true});
    document.addEventListener('image-alt-selection',event=>defaults(event.target));
    document.addEventListener('change',event=>{
        const form=event.target.closest('form');if(form?.querySelector('[data-image-alt]')&&event.target.name==='media')defaults(form);
        if(form&&event.target.name==='update_asset_alt'){
            const input=form.elements.caption||form.elements.alt_text||form.elements.description;
            if(input)input.dispatchEvent(new Event('input',{bubbles:true}));
        }
    });
    document.addEventListener('click',async event=>{
        const button=event.target.closest('[data-alt-generate],[data-alt-file-index],[data-alt-apply],[data-alt-dismiss]');if(!button)return;
        const form=button.closest('form'),controls=form.querySelector('[data-image-alt]');if(!controls)return;
        const preview=controls.querySelector('[data-alt-preview]');
        if(button.hasAttribute('data-alt-dismiss')){preview.hidden=true;pending.delete(form);return;}
        if(button.hasAttribute('data-alt-apply')){
            const saved=pending.get(form);if(!saved||!same(saved.source,source(form,saved.index))){preview.hidden=true;message(form,'The selected image changed. Generate a new suggestion.');return;}
            const input=saved.index===null?(form.elements.caption||form.elements.alt_text||form.elements.description):form.querySelector('[data-ag-file-alt="'+saved.index+'"]');
            if(!input){preview.hidden=true;return;}
            input.value=controls.querySelector('[data-alt-suggestion]').value;
            input.dispatchEvent(new Event('input',{bubbles:true}));preview.hidden=true;message(form,'Suggestion applied. Review the wording, then save the image.');return;
        }
        const index=button.hasAttribute('data-alt-file-index')?Number(button.dataset.altFileIndex):null;
        if(index===null&&form.elements.media?.files.length>1){message(form,'Use the Generate button beside each selected photograph.');return;}
        const selected=source(form,index);if(!selected.file&&!selected.asset&&!selected.assignment){message(form,'Choose an image first.');return;}
        if(!form.elements.alt_context_revision.value){message(form,'Wait for the saved image references to load, then generate.');return;}
        const body=new FormData();body.set('_token',document.querySelector('[name="_token"]')?.value||document.querySelector('[data-ag-manager]')?.dataset.token||'');
        body.set('request_token',crypto.randomUUID());body.set('asset_id',String(selected.asset));if(selected.file)body.set('media',selected.file);
        Object.entries(referenceFields(form)).forEach(([key,value])=>body.set(key,value));
        body.set('context_revision',form.elements.alt_context_revision.value);
        if(selected.assignment){for(const name of ['page_media','type','id','slot','child_id','revision'])body.set(name,form.elements[name]?.value||'');}
        button.disabled=true;preview.hidden=true;message(form,'Generating a description…');
        try{
            const response=await fetch(api,{method:'POST',body,credentials:'same-origin'}),data=await response.json();
            if(!response.ok)throw new Error(data.error||'Generation did not finish. Review before retrying.');
            if(!same(selected,source(form,index))){message(form,'The image changed. The old suggestion was discarded.');return;}
            pending.set(form,{source:selected,index});controls.querySelector('[data-alt-suggestion]').value=data.alt_text;preview.hidden=false;message(form,'Review this suggestion before applying it.');
        }catch(error){message(form,error.message);}
        finally{button.disabled=false;}
    });
})();
