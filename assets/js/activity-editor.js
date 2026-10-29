(() => {
    'use strict';
    const form=document.querySelector('[data-activity-editor]');
    if(!form)return;
    form.addEventListener('click',event=>{
        const button=event.target.closest('[data-entry-move]');if(!button)return;
        const row=button.closest('[data-activity-entry]');
        const siblings=[...row.parentElement.querySelectorAll('[data-activity-entry]')];
        const index=siblings.indexOf(row),other=siblings[index+(button.dataset.entryMove==='up'?-1:1)];
        if(!other)return;
        if(button.dataset.entryMove==='up')other.before(row);else other.after(row);
        [...row.parentElement.querySelectorAll('[data-activity-entry]')].forEach((entry,i)=>{entry.querySelector('legend').textContent='Example '+(i+1);});
        button.focus();
    });
    form.addEventListener('submit',event=>{
        const removed=[...form.querySelectorAll('[data-activity-entry]')].filter(row=>{
            const marked=row.querySelector('input[name$="[remove]"]')?.checked;
            const empty=!row.querySelector('input[name$="[title]"]').value.trim()&&!row.querySelector('input[name$="[destinations]"]').value.trim();
            return Number(row.dataset.photoCount)>0&&(marked||empty);
        }).map(row=>Number(row.dataset.entryId));
        if(removed.length&&!confirm('Remove '+removed.length+' entry/entries and their image assignments? The photographs remain in the Media Library.')){event.preventDefault();return;}
        form.elements.namedItem('confirmed_removals').value=JSON.stringify(removed);
    });
})();
