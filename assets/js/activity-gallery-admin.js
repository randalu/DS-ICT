(() => {
    'use strict';
    const manager=document.querySelector('[data-ag-manager]');if(!manager)return;
    const api=document.querySelector('[data-manager]').dataset.api;
    const dialog=manager.querySelector('[data-ag-library-dialog]');
    let libraryForm=null,libraryPage=1,libraryRequest=0;
    const state=entry=>entry.querySelector('[data-ag-state]');
    const status=(entry,text)=>{entry.querySelector('[data-ag-status]').textContent=text;};
    const dirty=form=>form.dataset.dirty==='1';
    const otherDirty=(entry,own)=>[...entry.querySelectorAll('[data-ag-form]')].some(f=>f!==own&&dirty(f));
    const field=(form,name)=>form.elements.namedItem(name);
    const busyEntries=new Set();
    const previews=new WeakMap();
    const revoke=form=>{(previews.get(form)||[]).forEach(url=>URL.revokeObjectURL(url));previews.delete(form);};
    const refresh=(entry,html)=>{entry.querySelectorAll('[data-ag-form]').forEach(revoke);entry.querySelector('[data-ag-current]').innerHTML=html;};
    const allow=(entry,form)=>{
        if(busyEntries.has(entry))return false;
        if(otherDirty(entry,form)){status(entry,'Save or cancel the other pending image edits in this entry first.');return false;}
        return true;
    };
    async function request(entry,action,values={},file=null,onProgress=null){
        const body=new FormData();
        Object.entries({action,type:'activity',id:manager.dataset.category,example_id:entry.dataset.agEntry,_token:manager.dataset.token,revision:state(entry).dataset.revision,...values}).forEach(([k,v])=>body.set(k,String(v)));
        if(file)body.set('media',file);
        let result;
        if(file){
            result=await new Promise((resolve,reject)=>{
                const xhr=new XMLHttpRequest();xhr.open('POST',api);xhr.responseType='json';xhr.timeout=180000;
                xhr.upload.onprogress=event=>{if(event.lengthComputable&&onProgress)onProgress(Math.round(event.loaded/event.total*100));};
                xhr.onload=()=>{if(xhr.status>=200&&xhr.status<300&&xhr.response?.ok)resolve(xhr.response);else reject(new Error(xhr.response?.error||'Upload outcome unknown. Refresh before retrying.'));};
                xhr.onerror=xhr.ontimeout=()=>reject(new Error('Connection interrupted. Upload outcome unknown.'));
                xhr.send(body);
            });
        }else{
            const response=await fetch(api,{method:'POST',body,credentials:'same-origin'});result=await response.json();
            if(!response.ok)throw new Error(result.error||'Image save failed. Refresh the gallery before retrying.');
        }
        refresh(entry,result.html);
    }
    async function run(entry,callback){
        const focused=document.activeElement,photo=focused.closest('[data-ag-form]')?.dataset.photo;
        const focusSelector=focused.hasAttribute('data-ag-move')?'[data-ag-move="'+focused.dataset.agMove+'"]':focused.type==='submit'?'[type="submit"]':focused.name==='alt_text'?'[name="alt_text"]':null;
        busyEntries.add(entry);entry.setAttribute('aria-busy','true');
        entry.querySelector('[data-ag-current]').inert=true;
        const controls=[...entry.querySelectorAll('button,input')].map(el=>[el,el.disabled]);controls.forEach(([el])=>el.disabled=true);
        try{await callback();status(entry,'Saved.');}
        catch(error){status(entry,error.message+' If the connection failed, refresh to confirm whether it saved before retrying.');}
        finally{
            controls.forEach(([el,disabled])=>{if(el.isConnected)el.disabled=disabled;});entry.querySelector('[data-ag-current]').inert=false;busyEntries.delete(entry);entry.removeAttribute('aria-busy');
            if(photo!==undefined){const row=entry.querySelector('[data-photo="'+photo+'"]');const target=(focusSelector&&row?.querySelector(focusSelector+':not(:disabled)'))||row?.querySelector('button:not(:disabled)')||entry.querySelector('[data-ag-refresh]');target?.focus();}
        }
    }
    manager.addEventListener('input',event=>{const form=event.target.closest('[data-ag-form]');if(form)form.dataset.dirty='1';});
    manager.addEventListener('change',event=>{
        const input=event.target,form=input.closest('[data-ag-form]');if(!form||input.name!=='media')return;
        revoke(form);
        field(form,'asset_id').value='';field(form,'source').value='upload';form.dataset.dirty='1';
        form.querySelector('[data-ag-selection]').textContent='';
        const queue=form.querySelector('[data-ag-queue]');
        const previewUrls=[];
        if(!queue){if(input.files[0]){const image=document.createElement('img');const url=URL.createObjectURL(input.files[0]);image.src=url;image.alt='Selected replacement preview';previewUrls.push(url);form.querySelector('[data-ag-selection]').append(image);}previews.set(form,previewUrls);return;}
        queue.replaceChildren();
        const files=[...input.files];form.querySelector('[data-ag-alt-label]').hidden=files.length>0;
        field(form,'alt_text').required=!files.length;
        files.forEach((file,index)=>{
            const label=document.createElement('label');label.textContent=(index+1)+'. '+file.name+' — alt text';
            const image=document.createElement('img'),url=URL.createObjectURL(file);image.src=url;image.alt='Selected upload preview';previewUrls.push(url);label.append(image);
            const alt=document.createElement('input');alt.required=true;alt.maxLength=255;alt.dataset.agFileAlt=String(index);label.append(alt);
            const generate=document.createElement('button');generate.type='button';generate.dataset.altFileIndex=String(index);generate.textContent='Generate alt text';queue.append(label,generate);
        });
        previews.set(form,previewUrls);
    });
    manager.addEventListener('submit',async event=>{
        const form=event.target.closest('[data-ag-form]');if(!form)return;
        event.preventDefault();const entry=form.closest('[data-ag-entry]');if(!allow(entry,form))return;
        const photo=Number(form.dataset.photo),files=[...field(form,'media').files];
        const limit=Number(document.querySelector('[data-manager]').dataset.imageLimit);
        if(files.some(file=>file.size>limit)){status(entry,'One of the selected files exceeds the displayed upload limit. Choose a smaller file.');return;}
        if(!photo&&files.length){
            const remaining=5-Number(state(entry).dataset.count);
            if(files.length>remaining){status(entry,'Select at most '+remaining+' more image(s).');return;}
            const alts=[...form.querySelectorAll('[data-ag-file-alt]')].map(input=>input.value.trim());
            const report=entry.querySelector('[data-ag-upload-report]');report.replaceChildren();
            const reports=files.map(file=>{const item=document.createElement('li');item.textContent=file.name+' — waiting';report.append(item);return item;});
            await run(entry,async()=>{for(let index=0;index<files.length;index++){
                status(entry,'Uploading '+(index+1)+' of '+files.length+'…');
                try{await request(entry,'activity_gallery_add',{source:'upload',alt_text:alts[index]},files[index],percent=>{reports[index].textContent=files[index].name+' — '+percent+'%';});reports[index].textContent=files[index].name+' — saved';}
                catch(error){reports[index].textContent=files[index].name+' — '+error.message;throw error;}
            }});return;
        }
        const asset=field(form,'asset_id').value,alt=field(form,'alt_text').value;
        const action=!photo?'activity_gallery_add':files.length||asset?'activity_gallery_replace':'activity_gallery_alt';
        const values={photo_id:photo,alt_text:alt,asset_id:asset,source:files.length?'upload':asset?'library':'',update_asset_alt:form.elements.update_asset_alt?.checked?'1':'',asset_alt_revision:form.elements.asset_alt_revision?.value||''};
        await run(entry,()=>request(entry,action,values,files[0]||null,percent=>status(entry,'Uploading replacement… '+percent+'%')));
    });
    manager.addEventListener('click',async event=>{
        const button=event.target.closest('button');if(!button)return;
        if(button.hasAttribute('data-ag-library-close')){dialog.close();return;}
        if(button.hasAttribute('data-ag-library-prev')){libraryPage--;loadLibrary();return;}
        if(button.hasAttribute('data-ag-library-next')){libraryPage++;loadLibrary();return;}
        const entry=button.closest('[data-ag-entry]'),form=button.closest('[data-ag-form]');
        if(!entry)return;
        if(button.hasAttribute('data-ag-cancel')){revoke(form);form.reset();form.dataset.dirty='0';field(form,'source').value='';field(form,'asset_id').value='';form.querySelector('[data-ag-selection]').textContent='';form.querySelector('[data-ag-queue]')?.replaceChildren();const label=form.querySelector('[data-ag-alt-label]');if(label)label.hidden=false;field(form,'alt_text').required=true;form.dispatchEvent(new Event('image-alt-selection',{bubbles:true}));return;}
        if(!allow(entry,form))return;
        if(button.hasAttribute('data-ag-library')){libraryForm=form;libraryPage=1;dialog.showModal();loadLibrary();return;}
        if(button.hasAttribute('data-ag-refresh')){
            await run(entry,async()=>{
                const url=new URL(api,location.href);url.search=new URLSearchParams({action:'activity_gallery',id:manager.dataset.category,example_id:entry.dataset.agEntry});
                const response=await fetch(url,{credentials:'same-origin'}),result=await response.json();if(!response.ok)throw new Error(result.error||'Could not refresh.');refresh(entry,result.html);
            });return;
        }
        if(!form)return;
        if(dirty(form)){status(entry,'Save or cancel this image’s pending edits first.');return;}
        if(button.hasAttribute('data-ag-remove')){
            if(!confirm('Remove this image assignment? Its file remains in the Media Library.'))return;
            await run(entry,()=>request(entry,'activity_gallery_remove',{photo_id:form.dataset.photo}));
        }
        if(button.hasAttribute('data-ag-move')){
            const order=[...entry.querySelectorAll('[data-ag-form]')].map(f=>Number(f.dataset.photo)).filter(Boolean),index=order.indexOf(Number(form.dataset.photo)),target=index+(button.dataset.agMove==='up'?-1:1);
            if(target<0||target>=order.length)return;
            [order[index],order[target]]=[order[target],order[index]];
            await run(entry,()=>request(entry,'activity_gallery_order',{order:JSON.stringify(order)}));
        }
    });
    dialog.querySelector('[data-ag-library-search]').addEventListener('submit',event=>{event.preventDefault();libraryPage=1;loadLibrary();});
    async function loadLibrary(){
        const token=++libraryRequest,url=new URL(api,location.href);
        url.search=new URLSearchParams({action:'library',page:libraryPage,q:dialog.querySelector('[name=q]').value,video:'0'});
        const message=dialog.querySelector('[data-ag-library-status]'),items=dialog.querySelector('[data-ag-library-items]');message.textContent='Loading…';items.replaceChildren();
        try{
            const response=await fetch(url,{credentials:'same-origin'}),result=await response.json();if(token!==libraryRequest)return;
            if(!response.ok)throw new Error(result.error||'Could not load Media Library.');
            result.items.forEach(item=>{
                const button=document.createElement('button');button.type='button';const img=document.createElement('img');img.src=item.url;img.alt='';img.loading='lazy';const label=document.createElement('span');label.textContent=item.name;button.append(img,label);
                button.addEventListener('click',()=>{
                    if(!libraryForm?.isConnected)return;
                    revoke(libraryForm);
                    field(libraryForm,'media').value='';field(libraryForm,'asset_id').value=item.id;field(libraryForm,'source').value='library';
                    const queue=libraryForm.querySelector('[data-ag-queue]');queue?.replaceChildren();const altLabel=libraryForm.querySelector('[data-ag-alt-label]');if(altLabel)altLabel.hidden=false;
                    field(libraryForm,'alt_text').required=true;if(!field(libraryForm,'alt_text').value.trim())field(libraryForm,'alt_text').value=item.caption||'';
                    const selection=libraryForm.querySelector('[data-ag-selection]');selection.textContent='Selected: '+item.name;const preview=document.createElement('img');preview.src=item.url;preview.alt='Selected library image preview';selection.append(preview);libraryForm.dataset.dirty='1';dialog.close();libraryForm.dispatchEvent(new Event('image-alt-selection',{bubbles:true}));field(libraryForm,'alt_text').focus();
                });items.append(button);
            });
            message.textContent=result.items.length?'':'No matching images.';dialog.querySelector('[data-ag-library-page]').textContent='Page '+libraryPage;dialog.querySelector('[data-ag-library-prev]').disabled=libraryPage===1;dialog.querySelector('[data-ag-library-next]').disabled=!result.more;
        }catch(error){message.textContent=error.message;}
    }
    window.addEventListener('beforeunload',event=>{if(busyEntries.size||[...manager.querySelectorAll('[data-ag-form]')].some(dirty)){event.preventDefault();event.returnValue='';}});
})();
